<?php

require_once __DIR__ . '/../../includes/mailer.php';

class PedidoAprovadoNotifier
{
    public static function notificar(PDO $db, int $idPedido): bool
    {
        $stmtPedido = $db->prepare("
            SELECT
                p.id_pedido,
                p.id_usuario,
                p.forma_pagamento,
                p.status,
                g.nome AS grade_nome,
                u.nome AS usuario_nome,
                u.email AS usuario_email
            FROM pedidos p
            JOIN usuario u ON u.id_usuario = p.id_usuario
            LEFT JOIN grade g ON g.id_grade = p.id_grade
            WHERE p.id_pedido = :id_pedido
            LIMIT 1
        ");
        $stmtPedido->execute([':id_pedido' => $idPedido]);
        $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            return false;
        }

        if (strtoupper((string)($pedido['status'] ?? '')) !== 'PAGO') {
            return false;
        }

        $email = trim((string)($pedido['usuario_email'] ?? ''));
        if ($email === '') {
            return false;
        }

        $nomeCurso = self::resolverNomeCurso($db, $idPedido, (string)($pedido['grade_nome'] ?? ''));
        $nomePlano = self::formatarNomePlano((string)($pedido['forma_pagamento'] ?? ''));
        $nomeUsuario = (string)($pedido['usuario_nome'] ?? 'Aluno(a)');

        $enviado = Mailer::enviarAcessoLiberadoAgendamento(
            $email,
            $nomeUsuario,
            $nomeCurso,
            $nomePlano,
            (int)$pedido['id_usuario'],
            (int)$pedido['id_pedido']
        );

        if (!$enviado) {
            error_log('Falha ao enviar email de acesso liberado: ' . Mailer::getUltimoErro());
        }

        return $enviado;
    }

    private static function resolverNomeCurso(PDO $db, int $idPedido, string $gradeNome): string
    {
        $gradeNome = trim($gradeNome);
        if ($gradeNome !== '') {
            return $gradeNome;
        }

        $stmtCursos = $db->prepare("
            SELECT c.nome
            FROM pedido_item pi
            JOIN curso c ON c.id_curso = pi.id_curso
            WHERE pi.id_pedido = :id_pedido
            ORDER BY pi.id_pedido_item ASC
        ");
        $stmtCursos->execute([':id_pedido' => $idPedido]);
        $cursos = $stmtCursos->fetchAll(PDO::FETCH_COLUMN);

        if (!$cursos || count($cursos) === 0) {
            return 'seu curso';
        }

        if (count($cursos) === 1) {
            return (string)$cursos[0];
        }

        return (string)$cursos[0] . ' + ' . (count($cursos) - 1) . ' cursos';
    }

    private static function formatarNomePlano(string $formaPagamento): string
    {
        $plano = strtoupper(trim($formaPagamento));

        return match ($plano) {
            'MENSAL' => 'Mensal',
            'AVISTA' => 'A vista',
            'PARCELADO' => 'Parcelado',
            default => $plano !== '' ? ucfirst(strtolower($plano)) : 'Escolhido no pedido'
        };
    }
}
