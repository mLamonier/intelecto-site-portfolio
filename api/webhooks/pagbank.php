<?php

require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/PagBankService.php';
require_once __DIR__ . '/../services/PedidoAprovadoNotifier.php';

header('Content-Type: application/json');
const PIX_EXPIRATION_MINUTES = 15;

$rawPayload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAGBANK_SIGNATURE'] ?? '';
logWebhook('PAGBANK_INCOMING', [
    'has_signature' => $signature !== '',
    'payload' => $rawPayload
]);

$pagBank = new PagBankService();
if (!shouldSkipWebhookSignatureValidation() && !$pagBank->validateWebhookSignature($rawPayload, $signature)) {
    http_response_code(401);
    echo json_encode(['error' => 'Assinatura inválida']);
    exit;
}

$data = json_decode($rawPayload, true);

try {
    $database = new Database();
    $db = $database->getConnection();

    $eventType = $data['event'] ?? null;
    $reference = $data['reference'] ?? $data['reference_id'] ?? null;
    $gatewayId = $data['id'] ?? null;
    $chargeStatus = $data['charges'][0]['status'] ?? $data['charge_status'] ?? null;

    if (!$reference || !$gatewayId) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados incompletos']);
        logWebhook('PAGBANK_INCOMPLETE', [
            'event' => $eventType,
            'reference' => $reference,
            'id' => $gatewayId
        ]);
        exit;
    }

    
    preg_match('/PEDIDO-(\d+)-/', $reference, $matches);
    $id_pedido = $matches[1] ?? null;

    if (!$id_pedido) {
        http_response_code(400);
        echo json_encode(['error' => 'Referência inválida']);
        exit;
    }

    
    $stmt = $db->prepare("
        SELECT
            pg.id_pagamento,
            pg.id_pedido,
            pg.metodo,
            pg.criado_em,
            pg.status AS status_pagamento,
            pe.status AS status_pedido,
            TIMESTAMPDIFF(SECOND, pg.criado_em, NOW()) AS idade_segundos
        FROM pagamento pg
        JOIN pedidos pe ON pe.id_pedido = pg.id_pedido
        WHERE pg.codigo_gateway = :codigo
        LIMIT 1
    ");
    $stmt->execute([':codigo' => $gatewayId]);
    $pagamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pagamento) {
        http_response_code(404);
        echo json_encode(['error' => 'Pagamento não encontrado']);
        exit;
    }

    $id_pagamento = $pagamento['id_pagamento'];
    $statusExterno = mapPagBankWebhookStatus($eventType, $chargeStatus);

    if (
        shouldKeepSandboxPaymentPending((string)($pagamento['metodo'] ?? '')) &&
        in_array($statusExterno, ['APROVADO', 'RECUSADO'], true)
    ) {
        $statusOriginal = $statusExterno;
        $statusExterno = 'PENDENTE';
        logWebhook('PAGBANK_SANDBOX_MANUAL_PENDING', [
            'id_pagamento' => $id_pagamento,
            'id_pedido' => $id_pedido,
            'metodo' => $pagamento['metodo'] ?? null,
            'event' => $eventType,
            'charge_status' => $chargeStatus,
            'status_original' => $statusOriginal
        ]);
    }

    if ($statusExterno === 'APROVADO') {
        if (isPixExpiredForWebhook($pagamento)) {
            $statusExterno = 'RECUSADO';
            logWebhook('PAGBANK_PIX_EXPIRED', [
                'id_pagamento' => $id_pagamento,
                'id_pedido' => $id_pedido,
                'event' => $eventType,
                'charge_status' => $chargeStatus
            ]);
        }
    }

    if ($statusExterno === 'PENDENTE') {
        logWebhook('PAGBANK_IGNORED', [
            'id_pagamento' => $id_pagamento,
            'id_pedido' => $id_pedido,
            'event' => $eventType,
            'charge_status' => $chargeStatus
        ]);
        http_response_code(200);
        echo json_encode(['success' => true, 'status' => 'PENDENTE']);
        exit;
    }

    $stmtUpdate = $db->prepare("
        UPDATE pagamento 
        SET status = :status, atualizado_em = NOW()
        WHERE id_pagamento = :id
    ");
    $stmtUpdate->execute([
        ':status' => $statusExterno === 'APROVADO' ? 'APROVADO' : 'RECUSADO',
        ':id' => $id_pagamento
    ]);

    $pedidoJaPago = strtoupper((string)($pagamento['status_pedido'] ?? '')) === 'PAGO';

    if ($statusExterno === 'APROVADO') {
        $stmtUpdatePedido = $db->prepare("
            UPDATE pedidos 
            SET status = 'PAGO', atualizado_em = NOW()
            WHERE id_pedido = :id
        ");
        $stmtUpdatePedido->execute([':id' => $id_pedido]);

        if (!$pedidoJaPago) {
            PedidoAprovadoNotifier::notificar($db, (int)$id_pedido);
        }
    }

    logWebhook($statusExterno === 'APROVADO' ? 'PAGBANK_APPROVED' : 'PAGBANK_REFUSED', [
        'id_pagamento' => $id_pagamento,
        'id_pedido' => $id_pedido,
        'event' => $eventType,
        'charge_status' => $chargeStatus
    ]);

    http_response_code(200);
    echo json_encode(['success' => true, 'status' => $statusExterno]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    logWebhook('PAGBANK_ERROR', ['error' => $e->getMessage()]);
}

function logWebhook(string $tipo, array $dados): void
{
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/webhooks.log';
    $mensagem = date('Y-m-d H:i:s') . " [$tipo] " . json_encode($dados) . PHP_EOL;
    file_put_contents($logFile, $mensagem, FILE_APPEND);
}

function isPixExpiredForWebhook(array $pagamento): bool
{
    if (($pagamento['metodo'] ?? null) !== 'PIX' || empty($pagamento['criado_em'])) {
        return false;
    }

    if (isset($pagamento['idade_segundos']) && is_numeric($pagamento['idade_segundos'])) {
        return ((int)$pagamento['idade_segundos']) >= (PIX_EXPIRATION_MINUTES * 60);
    }

    $createdAt = strtotime((string)$pagamento['criado_em']);
    if ($createdAt === false) {
        return false;
    }

    return (time() - $createdAt) >= (PIX_EXPIRATION_MINUTES * 60);
}

function shouldSkipWebhookSignatureValidation(): bool
{
    $isSandbox = filter_var(
        $_ENV['PAGBANK_SANDBOX'] ?? getenv('PAGBANK_SANDBOX') ?? false,
        FILTER_VALIDATE_BOOLEAN
    );
    $skipValidation = filter_var(
        $_ENV['PAGBANK_SKIP_WEBHOOK_SIGNATURE'] ?? getenv('PAGBANK_SKIP_WEBHOOK_SIGNATURE') ?? false,
        FILTER_VALIDATE_BOOLEAN
    );

    return $isSandbox && $skipValidation;
}

function shouldKeepSandboxPaymentPending(string $metodo): bool
{
    $isSandbox = filter_var(
        $_ENV['PAGBANK_SANDBOX'] ?? getenv('PAGBANK_SANDBOX') ?? false,
        FILTER_VALIDATE_BOOLEAN
    );

    if (!$isSandbox) {
        return false;
    }

    $manualOnly = filter_var(
        $_ENV['PAGBANK_SANDBOX_MANUAL_APPROVAL_ONLY'] ?? getenv('PAGBANK_SANDBOX_MANUAL_APPROVAL_ONLY') ?? false,
        FILTER_VALIDATE_BOOLEAN
    );

    if (!$manualOnly) {
        return false;
    }

    return in_array(strtoupper($metodo), ['PIX', 'BOLETO'], true);
}

function mapPagBankWebhookStatus(?string $eventType, ?string $chargeStatus): string
{
    $event = strtoupper((string)$eventType);
    $charge = strtoupper((string)$chargeStatus);

    if (in_array($charge, ['PAID', 'APPROVED', 'CONFIRMED'], true)) {
        return 'APROVADO';
    }
    if (in_array($charge, ['DECLINED', 'REFUSED', 'DENIED', 'EXPIRED', 'CANCELED', 'CANCELLED'], true)) {
        return 'RECUSADO';
    }

    if (in_array($event, ['TRANSACTION.AUTHORIZED', 'TRANSACTION.PAID', 'CHARGE.CONFIRMED'], true)) {
        return 'APROVADO';
    }
    if (in_array($event, ['TRANSACTION.DENIED', 'TRANSACTION.EXPIRED', 'CHARGE.CANCELED', 'CHARGE.CANCELLED'], true)) {
        return 'RECUSADO';
    }

    return 'PENDENTE';
}
