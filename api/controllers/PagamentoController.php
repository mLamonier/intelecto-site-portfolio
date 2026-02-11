<?php

require_once __DIR__ . '/../services/PedidoAprovadoNotifier.php';

class PagamentoController
{
    private const PIX_EXPIRATION_MINUTES = 15;
    private const SANDBOX_APPROVAL_DEFAULT_CENTS = 9900;
    private PDO $db;
    private PagBankService $pagBank;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->pagBank = new PagBankService();
    }

    private function isAdmin(): bool
    {
        if (!empty($_SESSION['admin_logado'])) {
            return true;
        }
        $roles = $_SESSION['usuario_roles'] ?? [];
        return is_array($roles) && in_array('ADMIN', $roles, true);
    }

    private function getLoggedUserId(): ?int
    {
        $id = $_SESSION['usuario_id'] ?? null;
        return $id ? (int)$id : null;
    }

    private function requireLogin(): void
    {
        http_response_code(401);
        echo json_encode(["error" => "Login requerido"]);
        exit;
    }



    public function store()
    {
        $input = json_decode(file_get_contents("php://input"), true);

        $id_pedido     = $input['id_pedido'] ?? null;
        $metodo        = $input['metodo'] ?? null;
        $senderHash    = $input['senderHash'] ?? null;
        $cardToken     = $input['cardToken'] ?? null;
        $cardData      = $input['cardData'] ?? null;
        $installments  = $input['installments'] ?? 1;

        if (!$id_pedido || !$metodo) {
            http_response_code(400);
            echo json_encode(["error" => "id_pedido e metodo são obrigatórios"]);
            return;
        }


        if (!in_array($metodo, ['CARTAO', 'PIX', 'BOLETO', 'WHATSAPP'])) {
            http_response_code(400);
            echo json_encode(["error" => "Método de pagamento inválido"]);
            return;
        }

        try {

            $stmtPedido = $this->db->prepare("
                SELECT p.*, u.email, u.nome, u.telefone, u.cpf
                FROM pedidos p
                JOIN usuario u ON u.id_usuario = p.id_usuario
                WHERE p.id_pedido = :id_pedido
            ");
            $stmtPedido->execute([':id_pedido' => $id_pedido]);
            $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);

            if (!$pedido) {
                http_response_code(404);
                echo json_encode(["error" => "Pedido não encontrado"]);
                return;
            }

            if (!$this->isAdmin()) {
                $sessionUserId = $this->getLoggedUserId();
                if ($sessionUserId && (int)$pedido['id_usuario'] !== $sessionUserId) {
                    http_response_code(403);
                    echo json_encode(["error" => "Acesso negado"]);
                    return;
                }
            }

            if ($pedido['status'] === 'PAGO') {
                http_response_code(400);
                echo json_encode(["error" => "Pedido já foi pago"]);
                return;
            }

            $formaPagamento = strtoupper((string)($pedido['forma_pagamento'] ?? ''));
            $valorPedido = (float)($pedido['valor_total'] ?? 0);
            $valorParaPagamento = $valorPedido;


            $metodosPermitidos = match ($formaPagamento) {
                'PARCELADO' => ['CARTAO'],
                'MENSAL', 'AVISTA' => ['PIX', 'BOLETO', 'CARTAO'],
                default => ['PIX', 'BOLETO', 'CARTAO', 'WHATSAPP'],
            };
            if (!in_array($metodo, $metodosPermitidos, true)) {
                http_response_code(400);
                echo json_encode(["error" => "Método de pagamento não permitido para este plano."]);
                return;
            }

            if ($metodo === 'CARTAO') {
                if ($formaPagamento === 'PARCELADO') {
                    $valorParaPagamento = $valorPedido;
                } elseif (in_array($formaPagamento, ['MENSAL', 'AVISTA'], true)) {
                    $installments = 1;
                    $valorParaPagamento = $valorPedido;
                } else {
                    http_response_code(400);
                    echo json_encode(["error" => "Metodo de cartao nao permitido para este plano."]);
                    return;
                }
            }

            if ($metodo === 'WHATSAPP') {
                $this->registrarPagamento($id_pedido, $metodo, $pedido['valor_total'], 'PENDENTE', null, null, null);

                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Entre em contato conosco via WhatsApp para confirmar o pagamento",
                    "id_pagamento" => $this->db->lastInsertId()
                ]);
                return;
            }


            $stmtItens = $this->db->prepare("
                SELECT pi.*, c.nome
                FROM pedido_item pi
                JOIN curso c ON c.id_curso = pi.id_curso
                WHERE pi.id_pedido = :id_pedido
            ");
            $stmtItens->execute([':id_pedido' => $id_pedido]);
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);


            $valorUnitario = count($itens) > 0 ? $valorParaPagamento / count($itens) : $valorParaPagamento;


            $itemsFormatados = array_map(function ($item) use ($valorUnitario) {
                return [
                    'nome' => $item['nome'],
                    'quantity' => 1,
                    'valor' => $valorUnitario
                ];
            }, $itens);


            $items = PagBankService::formatItems($itemsFormatados);
            $customer = PagBankService::formatCustomer($pedido);

            $reference = "PEDIDO-{$id_pedido}-" . time();
            $notificationUrl = $this->getValidNotificationUrl();


            $resultado = match ($metodo) {
                'CARTAO' => $this->processarCartao($id_pedido, $reference, $items, $customer, $senderHash, $cardToken, $cardData, $installments, $valorParaPagamento),
                'PIX'    => $this->processarPix($id_pedido, $reference, $items, $customer),
                'BOLETO' => $this->processarBoleto($id_pedido, $reference, $items, $customer, (float) $pedido['valor_total'], $notificationUrl),
                default  => ['success' => false, 'error' => 'Método desconhecido']
            };

            if (!$resultado['success']) {
                $details = (string)($resultado['error'] ?? 'Erro desconhecido');
                $detailsLower = strtolower($details);
                $credentialError = strpos($detailsLower, 'invalid credential') !== false
                    || strpos($detailsLower, 'authorization header') !== false;

                if ($credentialError) {
                    $details = 'Credencial PagBank invalida. Verifique PAGBANK_TOKEN e PAGBANK_SANDBOX no servidor.';
                }

                http_response_code(400);
                echo json_encode([
                    "error" => "Erro ao processar pagamento",
                    "details" => $details,
                    "debug" => $resultado['debug'] ?? ($resultado['data'] ?? null)
                ]);
                return;
            }



            $status_pagamento = 'PENDENTE';
            if ($metodo === 'CARTAO' && isset($resultado['dados']['charges'][0]['status'])) {
                $status_pagamento = $this->mapearStatusPagBank((string)$resultado['dados']['charges'][0]['status']);
            }

            $parcelasRegistro = null;
            $valorParcelaRegistro = null;
            if ($metodo === 'CARTAO') {
                $parcelasRegistro = max(1, (int)$installments);
                $valorParcelaRegistro = round($valorParaPagamento / $parcelasRegistro, 2);
            }

            $id_pagamento = $this->registrarPagamento(
                $id_pedido,
                $metodo,
                $valorParaPagamento,
                $status_pagamento,
                $resultado['codigo_gateway'] ?? null,
                $parcelasRegistro,
                $valorParcelaRegistro
            );

            if ($status_pagamento === 'APROVADO') {
                $this->atualizarStatusPagamento($id_pagamento, 'APROVADO', $resultado['dados'] ?? []);
            }

            http_response_code(201);
            echo json_encode([
                "success" => true,
                "id_pagamento" => $id_pagamento,
                "id_pedido" => $id_pedido,
                "metodo" => $metodo,
                "dados" => $resultado['dados'] ?? null,
                "qr_code" => $resultado['qr_code'] ?? null,
                "link_boleto" => $resultado['link_boleto'] ?? null,
                "expiration_date" => $resultado['expiration_date'] ?? null
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "error" => "Erro ao criar pagamento",
                "message" => $e->getMessage()
            ]);
        }
    }



    private function processarCartao(int $id_pedido, string $reference, array $items, array $customer, ?string $senderHash, ?string $cardToken, ?array $cardData, int $installments = 1, float $valorTotal = 0.0): array
    {


        if (!$cardToken && !$cardData) {
            return [
                'success' => false,
                'error' => 'Token do cartão ou dados do cartão são obrigatórios'
            ];
        }

        if ($valorTotal <= 0) {
            return [
                'success' => false,
                'error' => 'Valor total invalido para pagamento'
            ];
        }

        $valorTotal = (int) round($valorTotal * 100);
        $notificationUrl = $this->getValidNotificationUrl();

        $params = [
            'reference'        => $reference,
            'customer'         => $customer,
            'items'            => $items,
            'amount'           => $valorTotal,
            'notification_url' => $notificationUrl,
            'installments'     => max(1, min($installments, 12)),
        ];


        if ($cardToken) {
            $params['card_encrypted'] = $cardToken;
            $resultado = $this->pagBank->createCardOrder($params);
        } else if ($cardData) {
            $params['card_data'] = $cardData;
            $resultado = $this->pagBank->createCardOrderDirect($params);
        }

        if (!$resultado['success']) {
            $errorMsg = 'Erro ao processar cartão';

            if (isset($resultado['data']['error_messages'])) {
                $errorMsg = $resultado['data']['error_messages'][0]['description'] ?? $errorMsg;
            } elseif (isset($resultado['data']['message'])) {
                $errorMsg = $resultado['data']['message'];
            }

            return [
                'success' => false,
                'error' => $errorMsg,
                'debug' => $resultado['data'] ?? []
            ];
        }

        $sessionId = $resultado['data']['id'] ?? null;

        return [
            'success' => true,
            'codigo_gateway' => $sessionId,
            'dados' => $resultado['data']
        ];
    }



    private function processarPix(int $id_pedido, string $reference, array $items, array $customer): array
    {
        $stmtPedido = $this->db->prepare("SELECT valor_total FROM pedidos WHERE id_pedido = :id");
        $stmtPedido->execute([':id' => $id_pedido]);
        $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);

        $valorTotal = (int)($pedido['valor_total'] * 100);
        $valorTotal = $this->getSandboxGatewayAmount($valorTotal, 'PIX');

        error_log('PagBank PIX - Valor total: ' . $pedido['valor_total'] . ' => ' . $valorTotal . ' centavos');

        $params = [
            'reference'        => $reference,
            'items'            => $items,
            'customer'         => $customer,
            'amount'           => $valorTotal,
            'notification_url' => $this->getValidNotificationUrl(),
            'expiration_date'  => date('Y-m-d\TH:i:sP', strtotime('+' . self::PIX_EXPIRATION_MINUTES . ' minutes')),
        ];

        error_log('PagBank PIX - Params: ' . json_encode($params));

        $resultado = $this->pagBank->createOrder($params);


        error_log('PagBank PIX Response Code: ' . ($resultado['code'] ?? 'N/A'));
        error_log('PagBank PIX Success: ' . ($resultado['success'] ? 'true' : 'false'));
        error_log('PagBank PIX Data: ' . json_encode($resultado['data']));

        if (!$resultado['success']) {
            $errorMsg = 'Erro ao gerar PIX';


            if (isset($resultado['data']['error_messages'])) {
                $errorMsg = $resultado['data']['error_messages'][0]['description'] ?? $errorMsg;
            } elseif (isset($resultado['data']['message'])) {
                $errorMsg = $resultado['data']['message'];
            } elseif (isset($resultado['error'])) {
                $errorMsg = $resultado['error'];
            }

            error_log('PagBank PIX Error: ' . $errorMsg);

            return [
                'success' => false,
                'error' => $errorMsg,
                'debug' => [
                    'code' => $resultado['code'],
                    'raw_data' => $resultado['data']
                ]
            ];
        }


        $orderId = $resultado['data']['id'] ?? null;


        $qrCodeData = $resultado['data']['qr_codes'][0] ?? null;

        if (!$qrCodeData) {
            error_log('PagBank PIX: QR Code não encontrado na resposta');
            return [
                'success' => false,
                'error' => 'QR Code não foi gerado pela API PagBank',
                'debug' => $resultado['data']
            ];
        }

        $qrCode = $qrCodeData['text'] ?? null;
        $qrCodeId = $qrCodeData['id'] ?? null;


        $qrCodeImageUrl = null;
        $qrCodeBase64Url = null;

        if (isset($qrCodeData['links'])) {
            foreach ($qrCodeData['links'] as $link) {
                if ($link['rel'] === 'QRCODE.PNG') {
                    $qrCodeImageUrl = $link['href'];
                } elseif ($link['rel'] === 'QRCODE.BASE64') {
                    $qrCodeBase64Url = $link['href'];
                }
            }
        }

        error_log('PagBank PIX Success: Order ID = ' . $orderId . ', QR Code ID = ' . $qrCodeId);

        return [
            'success' => true,
            'codigo_gateway' => $orderId,
            'qr_code_id' => $qrCodeId,
            'qr_code' => $qrCode,
            'qr_code_text' => $qrCode,
            'copy_and_paste' => $qrCode,
            'qr_code_image_url' => $qrCodeImageUrl,
            'qr_code_base64_url' => $qrCodeBase64Url,
            'expiration_date' => $qrCodeData['expiration_date'] ?? null,
            'dados' => $resultado['data']
        ];
    }



    private function processarBoleto(int $id_pedido, string $reference, array $items, array $customer, float $valorTotal, string $notificationUrl): array
    {
        $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
        $dueDate = (clone $now)->modify('+3 days')->format('Y-m-d');

        $params = [
            'reference'        => $reference,
            'items'            => $items,
            'customer'         => $customer,
            'amount'           => $this->getSandboxGatewayAmount((int) round($valorTotal * 100), 'BOLETO'),
            'notification_url' => $notificationUrl,
            'due_date'         => $dueDate,
            'days_until_expiration' => 3,
            'template'         => 'COBRANCA',
        ];

        $resultado = $this->pagBank->createBoletoOrder($params);

        if (!$resultado['success']) {
            $errorMsg = $resultado['data']['error_messages'][0]['description']
                ?? $resultado['data']['message']
                ?? $resultado['error']
                ?? 'Erro ao gerar boleto';

            return [
                'success' => false,
                'error' => $errorMsg,
                'debug' => $resultado['data'] ?? []
            ];
        }

        $sessionId = $resultado['data']['id'] ?? null;


        $links = $resultado['data']['charges'][0]['links'] ?? [];
        $boletoUrl = null;
        foreach ($links as $link) {
            $rel = $link['rel'] ?? '';
            if (in_array($rel, ['PAYMENT', 'BOLETO.PDF', 'PAYMENT_LINK', 'SELF'])) {
                $boletoUrl = $link['href'] ?? null;
                break;
            }
        }
        if (!$boletoUrl && isset($links[0]['href'])) {
            $boletoUrl = $links[0]['href'];
        }

        return [
            'success' => true,
            'codigo_gateway' => $sessionId,
            'link_boleto' => $boletoUrl,
            'dados' => $resultado['data']
        ];
    }



    private function registrarPagamento(
        int $id_pedido,
        string $metodo,
        $valor,
        string $status,
        ?string $codigo_gateway,
        ?int $parcelas = null,
        ?float $valor_parcela = null
    ): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO pagamento (id_pedido, metodo, valor, parcelas, valor_parcela, status, codigo_gateway)
            VALUES (:id_pedido, :metodo, :valor, :parcelas, :valor_parcela, :status, :codigo_gateway)
        ");

        $stmt->execute([
            ':id_pedido' => $id_pedido,
            ':metodo' => $metodo,
            ':valor' => $valor,
            ':parcelas' => $parcelas,
            ':valor_parcela' => $valor_parcela,
            ':status' => $status,
            ':codigo_gateway' => $codigo_gateway
        ]);

        return (int) $this->db->lastInsertId();
    }



    public function show(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT
                p.*,
                pe.status AS pedido_status,
                pe.id_usuario AS pedido_usuario_id,
                TIMESTAMPDIFF(SECOND, p.criado_em, NOW()) AS idade_segundos
            FROM pagamento p
            JOIN pedidos pe ON pe.id_pedido = p.id_pedido
            WHERE p.id_pagamento = :id
        ");

        $stmt->execute([':id' => $id]);
        $pagamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pagamento) {
            http_response_code(404);
            echo json_encode(["error" => "Pagamento não encontrado"]);
            return;
        }

        if (!$this->isAdmin()) {
            $sessionUserId = $this->getLoggedUserId();
            if (!$sessionUserId) {
                $this->requireLogin();
            }
            if ((int)$pagamento['pedido_usuario_id'] !== $sessionUserId) {
                http_response_code(403);
                echo json_encode(["error" => "Acesso negado"]);
                return;
            }
        }


        if ($pagamento['status'] === 'PENDENTE' && $this->isPixExpired($pagamento)) {
            $this->atualizarStatusPagamento($id, 'RECUSADO');
            $pagamento['status'] = 'RECUSADO';
        } elseif ($pagamento['status'] === 'PENDENTE' && $pagamento['codigo_gateway']) {
            $resultado = $this->pagBank->getTransaction($pagamento['codigo_gateway']);

            if ($resultado['success'] && isset($resultado['data']['status'])) {
                $novoStatus = $this->mapearStatusPagBank($resultado['data']['status']);

                if ($novoStatus !== 'PENDENTE') {
                    $this->atualizarStatusPagamento($id, $novoStatus, $resultado['data']);
                    $pagamento['status'] = $novoStatus;
                }
            }
        }

        http_response_code(200);
        echo json_encode($pagamento);
    }

    public function latestByPedido(int $idPedido): void
    {
        $stmt = $this->db->prepare("
            SELECT
                p.*,
                pe.id_usuario AS pedido_usuario_id,
                pe.status AS pedido_status,
                TIMESTAMPDIFF(SECOND, p.criado_em, NOW()) AS idade_segundos
            FROM pagamento p
            JOIN pedidos pe ON pe.id_pedido = p.id_pedido
            WHERE p.id_pedido = :id_pedido
            ORDER BY p.id_pagamento DESC
            LIMIT 1
        ");
        $stmt->execute([':id_pedido' => $idPedido]);
        $pagamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pagamento) {
            http_response_code(404);
            echo json_encode(["error" => "Pagamento não encontrado para este pedido"]);
            return;
        }

        if (!$this->isAdmin()) {
            $sessionUserId = $this->getLoggedUserId();
            if (!$sessionUserId) {
                $this->requireLogin();
            }
            if ((int)$pagamento['pedido_usuario_id'] !== $sessionUserId) {
                http_response_code(403);
                echo json_encode(["error" => "Acesso negado"]);
                return;
            }
        }

        $pixExpired = $this->isPixExpired($pagamento);
        if ($pagamento['status'] === 'PENDENTE' && $pixExpired) {
            $this->atualizarStatusPagamento((int)$pagamento['id_pagamento'], 'RECUSADO');
            $pagamento['status'] = 'RECUSADO';
        }

        $response = [
            'id_pagamento' => (int)$pagamento['id_pagamento'],
            'id_pedido' => (int)$pagamento['id_pedido'],
            'metodo' => $pagamento['metodo'],
            'status' => $pagamento['status'],
            'parcelas' => isset($pagamento['parcelas']) ? (int)$pagamento['parcelas'] : null,
            'valor_parcela' => isset($pagamento['valor_parcela']) ? (float)$pagamento['valor_parcela'] : null,
            'criado_em' => $pagamento['criado_em'] ?? null,
            'atualizado_em' => $pagamento['atualizado_em'] ?? null,
            'codigo_gateway' => $pagamento['codigo_gateway'] ?? null,
            'is_pix_expired' => $pixExpired,
            'expiration_date' => null,
            'link_boleto' => null,
        ];

        if ($pagamento['metodo'] === 'PIX' && !empty($pagamento['criado_em'])) {
            $exp = strtotime((string)$pagamento['criado_em'] . ' +' . self::PIX_EXPIRATION_MINUTES . ' minutes');
            if ($exp !== false) {
                // Mantemos no mesmo formato/localidade de `criado_em` para evitar deslocamento de fuso no front.
                $response['expiration_date'] = date('Y-m-d H:i:s', $exp);
            }
        }

        if ($pagamento['metodo'] === 'BOLETO' && !empty($pagamento['codigo_gateway'])) {
            $orderResult = $this->pagBank->getOrder((string)$pagamento['codigo_gateway']);
            if ($orderResult['success'] && isset($orderResult['data'])) {
                $response['link_boleto'] = $this->extractBoletoLink($orderResult['data']);
            }
        }

        http_response_code(200);
        echo json_encode($response);
    }

    public function approveForDevelopmentByPedido(int $idPedido): void
    {
        if (!$this->isDevManualApprovalEnabled()) {
            http_response_code(403);
            echo json_encode(["error" => "Funcionalidade disponivel apenas em desenvolvimento"]);
            return;
        }

        $stmtPedido = $this->db->prepare("
            SELECT id_pedido, id_usuario, status
            FROM pedidos
            WHERE id_pedido = :id_pedido
            LIMIT 1
        ");
        $stmtPedido->execute([':id_pedido' => $idPedido]);
        $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            http_response_code(404);
            echo json_encode(["error" => "Pedido nao encontrado"]);
            return;
        }

        if (!$this->isAdmin()) {
            $sessionUserId = $this->getLoggedUserId();
            if (!$sessionUserId) {
                $this->requireLogin();
            }
            if ((int)$pedido['id_usuario'] !== $sessionUserId) {
                http_response_code(403);
                echo json_encode(["error" => "Acesso negado"]);
                return;
            }
        }

        $stmtPagamento = $this->db->prepare("
            SELECT id_pagamento, status
            FROM pagamento
            WHERE id_pedido = :id_pedido
            ORDER BY id_pagamento DESC
            LIMIT 1
        ");
        $stmtPagamento->execute([':id_pedido' => $idPedido]);
        $pagamento = $stmtPagamento->fetch(PDO::FETCH_ASSOC);

        if (!$pagamento) {
            http_response_code(404);
            echo json_encode(["error" => "Pagamento nao encontrado para este pedido"]);
            return;
        }

        if ((string)$pagamento['status'] !== 'APROVADO') {
            $this->atualizarStatusPagamento((int)$pagamento['id_pagamento'], 'APROVADO');
        }

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "id_pedido" => (int)$idPedido,
            "id_pagamento" => (int)$pagamento['id_pagamento'],
            "status_pagamento" => "APROVADO",
            "status_pedido" => "PAGO",
            "message" => "Pedido aprovado manualmente para ambiente de desenvolvimento"
        ]);
    }

    private function extractBoletoLink(array $dados): ?string
    {
        $links = $dados['charges'][0]['links'] ?? [];
        $boletoUrl = null;

        foreach ($links as $link) {
            $rel = $link['rel'] ?? '';
            if (in_array($rel, ['PAYMENT', 'BOLETO.PDF', 'PAYMENT_LINK', 'SELF'], true)) {
                $boletoUrl = $link['href'] ?? null;
                break;
            }
        }

        if (!$boletoUrl && isset($links[0]['href'])) {
            $boletoUrl = $links[0]['href'];
        }

        return $boletoUrl;
    }

    private function isPixExpired(array $pagamento): bool
    {
        if (($pagamento['metodo'] ?? null) !== 'PIX' || empty($pagamento['criado_em'])) {
            return false;
        }

        if (isset($pagamento['idade_segundos']) && is_numeric($pagamento['idade_segundos'])) {
            return ((int)$pagamento['idade_segundos']) >= (self::PIX_EXPIRATION_MINUTES * 60);
        }

        $createdAt = strtotime((string)$pagamento['criado_em']);
        if ($createdAt === false) {
            return false;
        }

        return (time() - $createdAt) >= (self::PIX_EXPIRATION_MINUTES * 60);
    }



    private function getValidNotificationUrl(): string
    {
        $customWebhookUrl = trim((string)($_ENV['PAGBANK_WEBHOOK_URL'] ?? getenv('PAGBANK_WEBHOOK_URL') ?? ''));
        if ($customWebhookUrl !== '') {
            return rtrim($customWebhookUrl, '/');
        }

        $appUrl = trim($_ENV['APP_URL'] ?? '');
        $lowerAppUrl = strtolower($appUrl);


        if ($appUrl === '' || strpos($lowerAppUrl, 'localhost') !== false || strpos($lowerAppUrl, '127.0.0.1') !== false) {
            return 'https://webhook.site/c6c54ab3-516b-4e86-be89-36ceb6f6fdb9';
        }


        if (
            strpos($lowerAppUrl, 'webhook.site') !== false ||
            strpos($lowerAppUrl, 'ngrok') !== false ||
            strpos($lowerAppUrl, 'devtunnels') !== false ||
            strpos($lowerAppUrl, '/api/webhooks/pagbank') !== false
        ) {
            return rtrim($appUrl, '/');
        }


        return rtrim($appUrl, '/') . '/api/webhooks/pagbank';
    }

    private function getSandboxGatewayAmount(int $originalAmountCents, string $metodo): int
    {
        if (!$this->isSandboxApprovalModeEnabled()) {
            return $originalAmountCents;
        }

        if (!in_array($metodo, ['PIX', 'BOLETO'], true)) {
            return $originalAmountCents;
        }

        $configuredAmount = (int)($_ENV['PAGBANK_SANDBOX_APPROVAL_VALUE_CENTS']
            ?? getenv('PAGBANK_SANDBOX_APPROVAL_VALUE_CENTS')
            ?? self::SANDBOX_APPROVAL_DEFAULT_CENTS);

        if ($configuredAmount <= 0) {
            return $originalAmountCents;
        }

        error_log("PagBank Sandbox Approval Mode ativo para {$metodo}: usando {$configuredAmount} centavos no gateway (pedido mantém valor original).");
        return $configuredAmount;
    }

    private function isSandboxApprovalModeEnabled(): bool
    {
        $sandbox = filter_var(
            $_ENV['PAGBANK_SANDBOX'] ?? getenv('PAGBANK_SANDBOX') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $forceApproval = filter_var(
            $_ENV['PAGBANK_SANDBOX_FORCE_APPROVAL'] ?? getenv('PAGBANK_SANDBOX_FORCE_APPROVAL') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        return $sandbox && $forceApproval;
    }

    private function isDevManualApprovalEnabled(): bool
    {
        $rawEnabled = $_ENV['PAGBANK_ENABLE_DEV_APPROVAL_BUTTON'] ?? getenv('PAGBANK_ENABLE_DEV_APPROVAL_BUTTON');
        $enabled = $rawEnabled === false || $rawEnabled === null
            ? true
            : filter_var($rawEnabled, FILTER_VALIDATE_BOOLEAN);
        if (!$enabled) {
            return false;
        }

        $sandbox = filter_var(
            $_ENV['PAGBANK_SANDBOX'] ?? getenv('PAGBANK_SANDBOX') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        return $sandbox;
    }



    private function mapearStatusPagBank(string $statusPagBank): string
    {
        return match ($statusPagBank) {
            'approved', 'APPROVED' => 'APROVADO',
            'declined', 'DECLINED', 'refused', 'REFUSED' => 'RECUSADO',
            'pending', 'PENDING' => 'PENDENTE',
            default => 'PENDENTE'
        };
    }



    private function atualizarStatusPagamento(int $id_pagamento, string $status, array $dados = []): void
    {
        $stmtStatusAtual = $this->db->prepare("
            SELECT
                pg.id_pedido,
                pg.status AS status_pagamento,
                pe.status AS status_pedido
            FROM pagamento pg
            JOIN pedidos pe ON pe.id_pedido = pg.id_pedido
            WHERE pg.id_pagamento = :id
            LIMIT 1
        ");
        $stmtStatusAtual->execute([':id' => $id_pagamento]);
        $statusAtual = $stmtStatusAtual->fetch(PDO::FETCH_ASSOC);

        if (!$statusAtual) {
            return;
        }

        $pedidoJaPago = strtoupper((string)($statusAtual['status_pedido'] ?? '')) === 'PAGO';

        $stmt = $this->db->prepare("
            UPDATE pagamento 
            SET status = :status, atualizado_em = NOW()
            WHERE id_pagamento = :id
        ");

        $stmt->execute([
            ':id' => $id_pagamento,
            ':status' => $status
        ]);


        if ($status === 'APROVADO') {
            $idPedido = (int)$statusAtual['id_pedido'];
            $stmtPed = $this->db->prepare("
                UPDATE pedidos
                SET status = 'PAGO', atualizado_em = NOW()
                WHERE id_pedido = :id
            ");
            $stmtPed->execute([':id' => $idPedido]);

            if (!$pedidoJaPago) {
                PedidoAprovadoNotifier::notificar($this->db, $idPedido);
            }
        }
    }
}
