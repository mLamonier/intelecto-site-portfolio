<?php

header('Content-Type: application/json');
const PIX_EXPIRATION_MINUTES = 15;

try {
    
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(["error" => "Dados inválidos"]);
        exit;
    }

    
    error_log("PagBank Webhook: " . json_encode($input));

    
    require_once __DIR__ . '/../config/db.php';
    $database = new Database();
    $pdo = $database->getConnection();

    
    $order_id = $input['order_id'] ?? $input['id'] ?? null;
    $reference_id = $input['reference_id'] ?? null;
    $charge_id = $input['charge_id'] ?? null;
    $status = $input['status'] ?? null;

    
    if ($order_id) {
        
        if ($reference_id && preg_match('/PEDIDO-(\d+)-/', $reference_id, $matches)) {
            $id_pedido = $matches[1];

            
            $novo_status = match ($status) {
                'PAID' => 'PAGO',
                'DECLINED', 'CANCELED' => 'CANCELADO',
                'PENDING' => 'PENDENTE',
                default => 'PENDENTE'
            };

            if ($novo_status === 'PAGO') {
                $stmtPag = $pdo->prepare("
                    SELECT
                        metodo,
                        criado_em,
                        TIMESTAMPDIFF(SECOND, criado_em, NOW()) AS idade_segundos
                    FROM pagamento
                    WHERE id_pedido = :id_pedido
                    ORDER BY id_pagamento DESC
                    LIMIT 1
                ");
                $stmtPag->execute([':id_pedido' => $id_pedido]);
                $pagamento = $stmtPag->fetch(PDO::FETCH_ASSOC);

                if ($pagamento && isPixExpiredForWebhook($pagamento)) {
                    $novo_status = 'CANCELADO';
                }
            }

            $stmt = $pdo->prepare("
                UPDATE pagamento 
                SET status = :status, id_pagbank = :id_pagbank
                WHERE id_pedido = :id_pedido 
                ORDER BY id_pagamento DESC 
                LIMIT 1
            ");

            $stmt->execute([
                ':status' => $novo_status,
                ':id_pagbank' => $order_id,
                ':id_pedido' => $id_pedido
            ]);

            
            if ($novo_status === 'PAGO') {
                $stmt = $pdo->prepare("UPDATE pedidos SET status = 'PAGO' WHERE id_pedido = :id");
                $stmt->execute([':id' => $id_pedido]);
            }

            error_log("Pagamento atualizado: PEDIDO-$id_pedido -> $novo_status");
        }
    }

    
    if ($charge_id && $reference_id && preg_match('/PEDIDO-(\d+)-/', $reference_id, $matches)) {
        $id_pedido = $matches[1];
        $charge_status = $input['charge_status'] ?? $status ?? null;

        $novo_status = match ($charge_status) {
            'PAID' => 'PAGO',
            'DECLINED', 'CANCELED' => 'CANCELADO',
            'PENDING' => 'PENDENTE',
            default => 'PENDENTE'
        };

        if ($novo_status === 'PAGO') {
            $stmtPag = $pdo->prepare("
                SELECT
                    metodo,
                    criado_em,
                    TIMESTAMPDIFF(SECOND, criado_em, NOW()) AS idade_segundos
                FROM pagamento
                WHERE id_pedido = :id_pedido
                ORDER BY id_pagamento DESC
                LIMIT 1
            ");
            $stmtPag->execute([':id_pedido' => $id_pedido]);
            $pagamento = $stmtPag->fetch(PDO::FETCH_ASSOC);

            if ($pagamento && isPixExpiredForWebhook($pagamento)) {
                $novo_status = 'CANCELADO';
            }
        }

        $stmt = $pdo->prepare("
            UPDATE pagamento 
            SET status = :status, id_pagbank = :id_pagbank
            WHERE id_pedido = :id_pedido 
            ORDER BY id_pagamento DESC 
            LIMIT 1
        ");

        $stmt->execute([
            ':status' => $novo_status,
            ':id_pagbank' => $charge_id,
            ':id_pedido' => $id_pedido
        ]);

        if ($novo_status === 'PAGO') {
            $stmt = $pdo->prepare("UPDATE pedidos SET status = 'PAGO' WHERE id_pedido = :id");
            $stmt->execute([':id' => $id_pedido]);
        }

        error_log("Charge atualizado: PEDIDO-$id_pedido -> $novo_status");
    }

    http_response_code(200);
    echo json_encode(["success" => true, "message" => "Webhook processado"]);
} catch (Exception $e) {
    error_log("Erro no webhook: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
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
