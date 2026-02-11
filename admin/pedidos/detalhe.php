<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/config.php';

$pedidoId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$page_title = 'Detalhes do Pedido';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Intelecto</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧾</text></svg>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 22px 26px;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .header h1 {
            color: #2d3748;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-secondary {
            background: #4a5568;
            color: white;
        }

        .btn-secondary:hover {
            background: #2d3748;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #e53e3e;
            color: white;
        }

        .btn-danger:hover {
            background: #c53030;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(229, 62, 62, 0.4);
        }

        .content {
            background: white;
            padding: 24px;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .card {
            background: #f8fafc;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .card h3 {
            margin-bottom: 10px;
            color: #2d3748;
            font-size: 16px;
        }

        .card p {
            margin-bottom: 6px;
            color: #4a5568;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 12px;
        }

        .badge-status-pendente {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-status-pago {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-status-cancelado {
            background: #fde8e8;
            color: #9b2c2c;
        }

        .badge-tipo {
            background: #e9d8fd;
            color: #553c9a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        thead {
            background: #f1f5f9;
        }

        .status-row {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .status-row select {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #cbd5e0;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .alert-danger {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }

        .loading {
            text-align: center;
            padding: 40px 10px;
            color: #718096;
        }

        @media (max-width: 720px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🧾 <?php echo $page_title; ?> <?php if ($pedidoId) {
                                                    echo '#' . $pedidoId;
                                                } ?></h1>
            <div class="actions">
                <a href="list.php" class="btn btn-secondary">← Voltar</a>
            </div>
        </div>

        <div class="content" id="content">
            <div id="alertContainer"></div>
            <div class="loading">Carregando pedido...</div>
        </div>
    </div>

    <script>
        async function parseJsonResponse(response) {
            const text = await response.text();
            const clean = text.replace(/^\uFEFF/, "");
            return JSON.parse(clean);
        }


        const API_URL = '<?php echo $apiBase; ?>';
        const pedidoId = <?php echo $pedidoId ? (int)$pedidoId : 'null'; ?>;

        function getAlertContainer() {
            let el = document.getElementById('alertContainer');
            if (!el) {
                el = document.createElement('div');
                el.id = 'alertContainer';
                document.getElementById('content').prepend(el);
            }
            return el;
        }

        function setAlert(message, type = 'danger') {
            const el = getAlertContainer();
            el.innerHTML = `<div class="alert ${type === 'success' ? 'alert-success' : 'alert-danger'}">${message}</div>`;
        }

        function clearAlert() {
            getAlertContainer().innerHTML = '';
        }

        function formatMoney(value) {
            return Number(value || 0).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return isNaN(date.getTime()) ? '-' : date.toLocaleString('pt-BR');
        }

        function badgeStatus(status) {
            const map = {
                'PENDENTE': 'badge-status-pendente',
                'PAGO': 'badge-status-pago',
                'CANCELADO': 'badge-status-cancelado'
            };
            return `<span class="badge ${map[status] || 'badge-status-pendente'}">${status}</span>`;
        }

        async function carregarPedido() {
            if (!pedidoId) {
                document.getElementById('content').innerHTML = '<div class="alert alert-danger">ID do pedido inválido.</div>';
                return;
            }

            try {
                const response = await fetch(`${API_URL}/pedidos/${pedidoId}`);
                const pedido = await parseJsonResponse(response);

                if (!response.ok) {
                    throw new Error(pedido.erro || pedido.error || 'Erro ao carregar pedido');
                }

                renderPedido(pedido);
            } catch (error) {
                document.getElementById('content').innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
            }
        }

        function renderPedido(pedido) {
            const content = document.getElementById('content');
            const cursos = Array.isArray(pedido.cursos) ? pedido.cursos : [];

            content.innerHTML = `
                <div id="alertContainer"></div>
                <div class="grid">
                    <div class="card">
                        <h3>Pedido</h3>
                        <p><strong>ID:</strong> #${pedido.id_pedido}</p>
                        <p><strong>Tipo:</strong> ${pedido.tipo} ${pedido.tipo === 'GRADE' ? '(Grade)' : '(Personalizada)'} ${pedido.grade_nome ? '<br><small>'+pedido.grade_nome+'</small>' : ''}</p>
                        <p><strong>Modalidade:</strong> ${pedido.modalidade || '-'}</p>
                        <p><strong>Forma de pagamento:</strong> ${pedido.forma_pagamento || '—'}</p>
                        <p><strong>Valor total:</strong> ${formatMoney(pedido.valor_total)}</p>
                        <p><strong>Carga horária:</strong> ${pedido.horas_total || 0}h ${pedido.meses_duracao ? ' · '+pedido.meses_duracao+' meses' : ''}</p>
                        <p><strong>Criado em:</strong> ${formatDate(pedido.criado_em)}</p>
                    </div>
                    <div class="card">
                        <h3>Cliente</h3>
                        <p><strong>Nome:</strong> ${pedido.usuario_nome || 'Usuário #' + pedido.id_usuario}</p>
                        <p><strong>Email:</strong> ${pedido.usuario_email || '-'}</p>
                        <p><strong>Telefone:</strong> ${pedido.usuario_telefone || '-'}</p>
                        <p><strong>CPF:</strong> ${pedido.usuario_cpf || '-'}</p>
                        <p><strong>ID Usuário:</strong> ${pedido.id_usuario}</p>
                    </div>
                    <div class="card">
                        <h3>Status</h3>
                        <div class="status-row">
                            ${badgeStatus(pedido.status)}
                            <select id="statusSelect">
                                <option value="PENDENTE" ${pedido.status === 'PENDENTE' ? 'selected' : ''}>Pendente</option>
                                <option value="PAGO" ${pedido.status === 'PAGO' ? 'selected' : ''}>Pago</option>
                                <option value="CANCELADO" ${pedido.status === 'CANCELADO' ? 'selected' : ''}>Cancelado</option>
                            </select>
                            <button class="btn btn-primary" onclick="salvarStatus(${pedido.id_pedido})">Salvar</button>
                            ${pedido.status === 'PENDENTE' ? `<button class="btn btn-danger" onclick="deletarPedido(${pedido.id_pedido})">Deletar</button>` : ''}
                        </div>
                    </div>
                </div>

                <div class="card" style="margin-top: 10px;">
                    <h3>Itens do pedido</h3>
                    ${cursos.length === 0 ? '<p>Nenhum curso vinculado.</p>' : `
                        <table>
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>Carga Horária</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${cursos.map(c => `
                                    <tr>
                                        <td>${c.nome || 'Curso #' + c.id_curso}</td>
                                        <td>${c.carga_horaria != null ? c.carga_horaria + 'h' : '—'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `}
                </div>
            `;
        }

        async function salvarStatus(id) {
            const select = document.getElementById('statusSelect');
            if (!select) return;
            const novoStatus = select.value;

            try {
                const response = await fetch(`${API_URL}/pedidos/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        status: novoStatus
                    })
                });

                const result = await parseJsonResponse(response);
                if (!response.ok) {
                    throw new Error(result.erro || result.error || result.message || 'Erro ao atualizar status');
                }

                setAlert('Status atualizado com sucesso.', 'success');
                carregarPedido();
            } catch (error) {
                setAlert(error.message || 'Erro ao atualizar status.', 'danger');
            }
        }

        async function deletarPedido(id) {
            if (!confirm('Tem certeza que deseja deletar este pedido? Esta ação não pode ser desfeita.')) {
                return;
            }

            try {
                const response = await fetch(`${API_URL}/pedidos/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const result = await parseJsonResponse(response);
                if (!response.ok) {
                    throw new Error(result.erro || result.error || result.message || 'Erro ao deletar pedido');
                }

                setAlert('Pedido deletado com sucesso. Redirecionando...', 'success');
                setTimeout(() => {
                    window.location.href = 'list.php';
                }, 2000);
            } catch (error) {
                setAlert(error.message || 'Erro ao deletar pedido.', 'danger');
            }
        }

        carregarPedido();
    </script>
</body>

</html>