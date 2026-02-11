<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Gerenciar Pedidos';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Intelecto</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛒</text></svg>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url("<?= htmlspecialchars(asset_url('assets/background branco.png')) ?>");
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1500px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            position: relative;
        }

        .header h1 {
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 28px;
        }

        .back-btn {
            position: absolute;
            left: 30px;
            padding: 12px 24px;
            background: #4a5568;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #2d3748;
        }

        .btn-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #25bd31;
            color: white;
        }

        .btn-primary:hover {
            background: #189723;
        }

        .btn-secondary {
            background: #292929;
            color: white;
        }

        .btn-secondary:hover {
            background: #3b4557;
        }

        .btn-ghost {
            background: transparent;
            color: #4a5568;
            border: 1px solid #cbd5e0;
        }

        .btn-ghost:hover {
            border-color: #4a5568;
            color: #2d3748;
        }

        .content {
            background: white;
            padding: 24px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            color: #4a5568;
            font-weight: 600;
            font-size: 13px;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            min-width: 180px;
        }

        .stats-line {
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 14px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        thead {
            background: #292929;
        }

        th {
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .badge-status-pendente {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-status-pago {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-status-cancelado {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-tipo-grade {
            background: #e9d8fd;
            color: #553c9a;
        }

        .badge-tipo-personalizada {
            background: #cffafe;
            color: #155e75;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #4a5568;
        }

        .empty-state h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #2d3748;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 12px;
            display: flex;
            gap: 10px;
            align-items: center;
            font-weight: 600;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin: 0 3px;
            border: 1px solid #cbd5e0;
            background: white;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-small:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .inline-status {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }

        .inline-status select {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 13px;
        }

        .btn-small {
            padding: 8px 10px;
            font-size: 13px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
        }

        .btn-view {
            background: #4299e1;
            color: white;
        }

        .btn-view:hover {
            background: #2b6cb0;
        }

        .loading {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
            font-size: 16px;
        }

        code {
            background: #edf2f7;
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 12px;
        }

        @media (max-width: 960px) {

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
            }

            thead {
                display: none;
            }

            tr {
                margin-bottom: 15px;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                overflow: hidden;
            }

            td {
                border: none;
                border-bottom: 1px solid #e2e8f0;
                position: relative;
                padding-left: 50%;
            }

            td:before {
                position: absolute;
                top: 12px;
                left: 12px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: 700;
                color: #4a5568;
            }

            td:nth-of-type(1):before {
                content: 'Pedido';
            }

            td:nth-of-type(2):before {
                content: 'Cliente';
            }

            td:nth-of-type(3):before {
                content: 'Tipo';
            }

            td:nth-of-type(4):before {
                content: 'Financeiro';
            }

            td:nth-of-type(5):before {
                content: 'Status';
            }

            td:nth-of-type(6):before {
                content: 'Criado';
            }

            td:nth-of-type(7):before {
                content: 'Ações';
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🛒 <?php echo $page_title; ?></h1>
            <div class="btn-group">
                <a href="../index.php" class="btn btn-secondary">← Voltar ao Dashboard</a>
            </div>
        </div>

        <div class="content" id="content">
            <div class="filters">
                <div class="filter-group">
                    <label for="statusFilter">Status</label>
                    <select id="statusFilter">
                        <option value="">Todos</option>
                        <option value="PENDENTE">Pendente</option>
                        <option value="PAGO">Pago</option>
                        <option value="CANCELADO">Cancelado</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="usuarioFilter">ID do usuário</label>
                    <input type="number" id="usuarioFilter" placeholder="ex: 1">
                </div>
                <div class="filter-group">
                    <label for="clienteFilter">Cliente (nome/e-mail)</label>
                    <input type="text" id="clienteFilter" placeholder="buscar...">
                </div>
                <div class="filter-group">
                    <label for="dataInicioFilter">Data inicial</label>
                    <input type="date" id="dataInicioFilter">
                </div>
                <div class="filter-group">
                    <label for="dataFimFilter">Data final</label>
                    <input type="date" id="dataFimFilter">
                </div>
                <div class="filter-group">
                    <button class="btn btn-primary" id="btnFiltrar">Filtrar</button>
                </div>
                <div class="filter-group">
                    <button class="btn btn-ghost" id="btnLimpar">Limpar filtros</button>
                </div>
            </div>

            <div id="alertContainer"></div>
            <div class="loading">⏳ Carregando pedidos...</div>
        </div>
    </div>

    <script>
async function parseJsonResponse(response) {
    const text = await response.text();
    const clean = text.replace(/^\uFEFF/, "");
    return JSON.parse(clean);
}


        const API_URL = '<?php echo $apiBase; ?>';
        let currentPage = 1;
        let totalPages = 1;
        const perPage = 20;
        let currentFilters = {
            status: '',
            usuarioId: '',
            cliente: '',
            dataInicio: '',
            dataFim: ''
        };

        function formatDateInput(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function getDefaultDateRange() {
            const dataFim = new Date();
            const dataInicio = new Date();
            dataInicio.setDate(dataFim.getDate() - 29);
            return {
                dataInicio: formatDateInput(dataInicio),
                dataFim: formatDateInput(dataFim)
            };
        }

        function applyFiltersToDom() {
            const statusEl = document.getElementById('statusFilter');
            const usuarioEl = document.getElementById('usuarioFilter');
            const clienteEl = document.getElementById('clienteFilter');
            const dataInicioEl = document.getElementById('dataInicioFilter');
            const dataFimEl = document.getElementById('dataFimFilter');

            if (statusEl) statusEl.value = currentFilters.status || '';
            if (usuarioEl) usuarioEl.value = currentFilters.usuarioId || '';
            if (clienteEl) clienteEl.value = currentFilters.cliente || '';
            if (dataInicioEl) dataInicioEl.value = currentFilters.dataInicio || '';
            if (dataFimEl) dataFimEl.value = currentFilters.dataFim || '';
        }

        function readFiltersFromDom() {
            const statusEl = document.getElementById('statusFilter');
            const usuarioEl = document.getElementById('usuarioFilter');
            const clienteEl = document.getElementById('clienteFilter');
            const dataInicioEl = document.getElementById('dataInicioFilter');
            const dataFimEl = document.getElementById('dataFimFilter');
            let dataInicio = dataInicioEl ? dataInicioEl.value : '';
            let dataFim = dataFimEl ? dataFimEl.value : '';

            if (dataInicio && dataFim && dataInicio > dataFim) {
                const temp = dataInicio;
                dataInicio = dataFim;
                dataFim = temp;
            }

            return {
                status: statusEl ? statusEl.value : '',
                usuarioId: usuarioEl ? usuarioEl.value.trim() : '',
                cliente: clienteEl ? clienteEl.value.trim() : '',
                dataInicio,
                dataFim
            };
        }

        function resetFiltersToDefaultRange() {
            const { dataInicio, dataFim } = getDefaultDateRange();
            currentFilters = {
                status: '',
                usuarioId: '',
                cliente: '',
                dataInicio,
                dataFim
            };
            applyFiltersToDom();
        }

        function getFiltersMarkup() {
            const filters = document.querySelector('.filters');
            if (filters) {
                return filters.innerHTML;
            }
            return `
                <div class="filter-group">
                    <label for="statusFilter">Status</label>
                    <select id="statusFilter">
                        <option value="">Todos</option>
                        <option value="PENDENTE">Pendente</option>
                        <option value="PAGO">Pago</option>
                        <option value="CANCELADO">Cancelado</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="usuarioFilter">ID do usuário</label>
                    <input type="number" id="usuarioFilter" placeholder="ex: 1">
                </div>
                <div class="filter-group">
                    <label for="clienteFilter">Cliente (nome/e-mail)</label>
                    <input type="text" id="clienteFilter" placeholder="buscar...">
                </div>
                <div class="filter-group">
                    <label for="dataInicioFilter">Data inicial</label>
                    <input type="date" id="dataInicioFilter">
                </div>
                <div class="filter-group">
                    <label for="dataFimFilter">Data final</label>
                    <input type="date" id="dataFimFilter">
                </div>
                <div class="filter-group">
                    <button class="btn btn-primary" id="btnFiltrar">Filtrar</button>
                </div>
                <div class="filter-group">
                    <button class="btn btn-ghost" id="btnLimpar">Limpar filtros</button>
                </div>
            `;
        }

        function getAlertContainer() {
            let container = document.getElementById('alertContainer');
            if (!container) {
                const content = document.getElementById('content');
                container = document.createElement('div');
                container.id = 'alertContainer';
                content.prepend(container);
            }
            return container;
        }

        function setAlert(message, type = 'danger') {
            const container = getAlertContainer();
            container.innerHTML = `
                <div class="alert ${type === 'success' ? 'alert-success' : 'alert-danger'}">${message}</div>
            `;
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function clearAlert() {
            getAlertContainer().innerHTML = '';
        }

        function formatMoney(value) {
            const num = Number(value || 0);
            return num.toLocaleString('pt-BR', {
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

        function badgeTipo(tipo) {
            if (tipo === 'PERSONALIZADA') {
                return '<span class="badge badge-tipo-personalizada">Grade Personalizada</span>';
            }
            return '<span class="badge badge-tipo-grade">Grade Pré-Montada</span>';
        }

        function formatFormaPagamento(forma) {
            const map = {
                'MENSAL': 'MENSAL',
                'AVISTA': 'À VISTA',
                'PARCELADO': 'PARCELADO CARTÃO'
            };
            return map[forma] || forma;
        }

        async function carregarPedidos(page = 1) {
            currentPage = page;
            clearAlert();
            const content = document.getElementById('content');
            let loadingEl = content.querySelector('.loading');
            if (!loadingEl) {
                loadingEl = document.createElement('div');
                loadingEl.className = 'loading';
                loadingEl.textContent = '⏳ Carregando pedidos...';
                content.appendChild(loadingEl);
            }
            loadingEl.style.display = 'block';

            currentFilters = readFiltersFromDom();
            const status = currentFilters.status;
            const usuarioId = currentFilters.usuarioId;
            const cliente = currentFilters.cliente;
            const dataInicio = currentFilters.dataInicio;
            const dataFim = currentFilters.dataFim;

            const params = new URLSearchParams();
            params.set('page', page);
            params.set('per_page', perPage);
            if (status) params.set('status', status);
            if (usuarioId) params.set('id_usuario', usuarioId);
            if (cliente) params.set('cliente', cliente);
            if (dataInicio) params.set('data_inicio', dataInicio);
            if (dataFim) params.set('data_fim', dataFim);

            const url = `${API_URL}pedidos&${params.toString()}`;

            try {
                const response = await fetch(url);

                // Verificar se a resposta é JSON válido
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error('Resposta do servidor não é JSON. Erro: ' + text.substring(0, 200));
                }

                const result = await parseJsonResponse(response);

                if (!response.ok) {
                    throw new Error(result.erro || result.error || 'Erro ao carregar pedidos');
                }

                totalPages = result.total_pages || 1;
                renderTabela(result);
            } catch (error) {
                document.getElementById('content').innerHTML = `
                    <div id="alertContainer"></div>
                    <div class="empty-state">
                        <h2>Erro ao carregar pedidos</h2>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }

        function renderTabela(result) {
            const pedidos = result.data || [];
            const content = document.getElementById('content');

            if (pedidos.length === 0) {
                const filtersMarkup = getFiltersMarkup();
                content.innerHTML = `
                    <div class="filters">
                        ${filtersMarkup}
                    </div>
                    <div id="alertContainer"></div>
                    <div class="empty-state">
                        <h2>Nenhum pedido encontrado</h2>
                        <p>Altere os filtros ou aguarde novas vendas.</p>
                    </div>
                `;
                wireFilters();
                return; // Early return to avoid further processing
            }

            const filtersMarkup = getFiltersMarkup();
            content.innerHTML = `
                <div class="filters">
                    ${filtersMarkup}
                </div>
                <div id="alertContainer">${getAlertContainer().innerHTML}</div>
                <div class="stats-line">
                    <strong>${result.total || pedidos.length} pedido(s)</strong>
                    <span>| Página ${currentPage} de ${result.total_pages || 1}</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Financeiro</th>
                            <th>Status</th>
                            <th>Criado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${pedidos.map(p => renderLinha(p)).join('')}
                    </tbody>
                </table>
                <div class="pagination">
                    <button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" class="btn-small pagination-btn" id="btnPrev">← Anterior</button>
                    <span> Página ${currentPage} de ${result.total_pages || 1} </span>
                    <button ${currentPage >= (result.total_pages || 1) ? 'disabled' : ''} data-page="${currentPage + 1}" class="btn-small pagination-btn" id="btnNext">Próxima →</button>
                </div>
            `;

            wireFilters();

            // Wire pagination buttons
            const paginationBtns = document.querySelectorAll('.pagination-btn:not([disabled])');
            paginationBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const nextPage = parseInt(btn.getAttribute('data-page'));
                    carregarPedidos(nextPage);
                });
            });
        }

        function renderLinha(p) {
            const usuario = p.usuario_nome ? `<strong>${p.usuario_nome}</strong><br><small>${p.usuario_email || ''}</small>` : `<strong>Usuário #${p.id_usuario}</strong>`;
            const tipoLabel = p.tipo === 'GRADE' ? 'Grade Pré-Montada' : 'Grade Personalizada';
            const gradeInfo = p.tipo === 'GRADE' && p.grade_nome ? p.grade_nome : (p.tipo === 'PERSONALIZADA' ? '<em>Customizada</em>' : '<em>Sem grade informada</em>');

            const formaPagamentoFormatada = formatFormaPagamento(p.forma_pagamento);
            const valorLabel = formatMoney(p.valor_total);
            const modalidade = p.modalidade || '-';
            const meses = p.meses_duracao ? `${p.meses_duracao} meses` : '';
            const horas = p.horas_total ? `${p.horas_total}h` : '';

            return `
                <tr>
                    <td><strong>#${p.id_pedido}</strong><br><code>ID Usuário: ${p.id_usuario}</code></td>
                    <td>${usuario}</td>
                    <td>
                        ${badgeTipo(p.tipo)}<br>
                        <small>${gradeInfo}</small>
                    </td>
                    <td>
                        <div><strong>${valorLabel}</strong></div>
                        <small>${formaPagamentoFormatada} · ${modalidade}${meses ? ' · ' + meses : ''}${horas ? ' · ' + horas : ''}</small>
                    </td>
                    <td>
                        ${badgeStatus(p.status)}<br>
                        <div class="inline-status">
                            <select id="select-status-${p.id_pedido}">
                                <option value="PENDENTE" ${p.status === 'PENDENTE' ? 'selected' : ''}>Pendente</option>
                                <option value="PAGO" ${p.status === 'PAGO' ? 'selected' : ''}>Pago</option>
                                <option value="CANCELADO" ${p.status === 'CANCELADO' ? 'selected' : ''}>Cancelado</option>
                            </select>
                            <button class="btn-small btn-ghost" onclick="salvarStatus(${p.id_pedido})">Salvar</button>
                        </div>
                    </td>
                    <td>${formatDate(p.criado_em)}</td>
                    <td class="actions">
                        <a class="btn-small btn-view" href="detalhe.php?id=${p.id_pedido}">🔍 Detalhes</a>
                    </td>
                </tr>
            `;
        }

        async function salvarStatus(id) {
            const select = document.getElementById(`select-status-${id}`);
            const novoStatus = select ? select.value : null;
            if (!novoStatus) return;

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
                carregarPedidos(currentPage);
            } catch (error) {
                setAlert(error.message || 'Erro ao atualizar status.', 'danger');
            }
        }

        function wireFilters() {
            const btnFiltrar = document.getElementById('btnFiltrar');
            const btnLimpar = document.getElementById('btnLimpar');
            applyFiltersToDom();

            if (btnFiltrar) {
                btnFiltrar.onclick = () => carregarPedidos(1);
            }

            if (btnLimpar) {
                btnLimpar.onclick = () => {
                    resetFiltersToDefaultRange();
                    carregarPedidos(1);
                };
            }
        }

        // Inicializa
        resetFiltersToDefaultRange();
        carregarPedidos();
    </script>
</body>

</html>
