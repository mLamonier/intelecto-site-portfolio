<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Gerenciar Usuários';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Intelecto</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👥</text></svg>">
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #48bb78;
        }

        .alert-close {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: inherit;
            opacity: 0.6;
            transition: opacity 0.3s;
        }

        .alert-close:hover {
            opacity: 1;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .empty-state h2 {
            color: #2d3748;
            margin-bottom: 12px;
            font-size: 24px;
        }

        .empty-state p {
            color: #718096;
            margin-bottom: 30px;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        thead {
            background: #292929;
        }

        th {
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-danger {
            background: #fed7d7;
            color: #742a2a;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin: 0 3px;
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

        .btn-edit {
            background: #4299e1;
            color: white;
        }

        .btn-edit:hover {
            background: #3182ce;
        }

        .btn-delete {
            background: #f56565;
            color: white;
            cursor: pointer;
            border: none;
        }

        .btn-delete:hover {
            background: #e53e3e;
        }

        .loading {
            text-align: center;
            padding: 60px;
            font-size: 18px;
            color: #718096;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>👥 <?php echo $page_title; ?></h1>
            <div class="btn-group">
                <a href="../index.php" class="btn btn-secondary">← Voltar ao Dashboard</a>
                <a href="form.php" class="btn btn-primary">+ Novo Usuário</a>
            </div>
        </div>

        <div class="content" id="content">
            <div id="alertContainer"></div>
            <div style="background: #f7fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="margin-bottom: 15px; color: #333;">🔍 Filtros</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr 200px; gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Nome do Usuário</label>
                        <input type="text" id="filtroNome" placeholder="Digite o nome..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Telefone</label>
                        <input type="text" id="filtroTelefone" placeholder="Digite o telefone..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Status</label>
                        <select id="filtroAtivo" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                            <option value="">Todos</option>
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                </div>
                <div id="filtroResultado" style="margin-top: 10px; font-size: 13px; color: #666;"></div>
            </div>
            <div class="loading">⏳ Carregando usuários...</div>
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
        const filtros = {
            nome: '',
            telefone: '',
            ativo: ''
        };
        let debounceFiltro = null;

        // Mostra mensagens de sucesso/erro da URL
        const urlParams = new URLSearchParams(window.location.search);
        const alertContainer = document.getElementById('alertContainer');

        if (urlParams.has('sucesso')) {
            alertContainer.innerHTML = `
                <div class="alert alert-success">
                    <span>✅ ${decodeURIComponent(urlParams.get('sucesso'))}</span>
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            `;
        }

        if (urlParams.has('erro')) {
            alertContainer.innerHTML = `
                <div class="alert alert-danger">
                    <span>❌ ${decodeURIComponent(urlParams.get('erro'))}</span>
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            `;
        }

        function mostrarErro(mensagem) {
            let container = document.getElementById('alertContainer');
            if (!container) {
                const content = document.getElementById('content');
                container = document.createElement('div');
                container.id = 'alertContainer';
                content.prepend(container);
            }
            container.innerHTML = `
                <div class="alert alert-danger">
                    <span>❌ ${mensagem}</span>
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            `;
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function mostrarSucesso(mensagem) {
            let container = document.getElementById('alertContainer');
            if (!container) {
                const content = document.getElementById('content');
                container = document.createElement('div');
                container.id = 'alertContainer';
                content.prepend(container);
            }
            container.innerHTML = `
                <div class="alert alert-success">
                    <span>✅ ${mensagem}</span>
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            `;
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Deleta um usuário
        async function deletarUsuario(id) {
            if (!confirm('Tem certeza que deseja remover este usuário?')) return;

            try {
                const response = await fetch(`${API_URL}/usuarios/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const result = await parseJsonResponse(response);

                if (response.ok && result.sucesso) {
                    mostrarSucesso('Usuário removido com sucesso!');
                    setTimeout(() => carregarUsuarios(), 1000);
                } else {
                    const mensagem = result.mensagem || result.erro || 'Erro ao deletar usuário';
                    mostrarErro(mensagem);
                }
            } catch (e) {
                mostrarErro('Erro: ' + e.message);
            }
        }

        // Carrega os usuários
        async function carregarUsuarios(page = 1) {
            currentPage = page;
            const content = document.getElementById('content');
            try {
                const params = new URLSearchParams();
                params.set('page', page);
                params.set('per_page', perPage);
                if (filtros.nome) params.set('nome', filtros.nome);
                const telefoneDigits = filtros.telefone.replace(/\D/g, '');
                if (telefoneDigits) params.set('telefone', telefoneDigits);
                if (filtros.ativo !== '') params.set('ativo', filtros.ativo);

                const response = await fetch(`${API_URL}usuarios&${params.toString()}`);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const result = await parseJsonResponse(response);
                const usuarios = result.data || result;
                totalPages = result.total_pages || 1;
                const temUsuarios = Array.isArray(usuarios) && usuarios.length > 0;
                const totalUsuarios = Number.isFinite(result.total) ? result.total : usuarios.length;
                if (!Array.isArray(usuarios)) {
                    throw new Error('Resposta inválida');
                }

                content.innerHTML = `
                    <div style="background: #f7fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin-bottom: 15px; color: #333;">Filtros</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 200px; gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Nome</label>
                                <input type="text" id="filtroNome" placeholder="Filtrar por nome..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Telefone</label>
                                <input type="text" id="filtroTelefone" placeholder="Filtrar por telefone..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Status</label>
                                <select id="filtroAtivo" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                                    <option value="">Todos</option><option value="1">Ativo</option><option value="0">Inativo</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top: 12px;">
                            <button type="button" id="btnLimparFiltros" class="btn btn-secondary">Limpar filtros</button>
                        </div>
                    </div>
                    <div style="background: #f7fafc; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                        <strong>${totalUsuarios} usuário(s)</strong>
                        <span> | Página ${currentPage} de ${result.total_pages || 1}</span>
                    </div>
                    ${temUsuarios ? `
                        <table><thead><tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Status</th><th>Ações</th></tr></thead>
                        <tbody id="tbody-usuarios">
                            ${usuarios.map(u => `<tr>
                                <td>#${u.id_usuario}</td><td>${u.nome}</td><td>${u.email}</td><td>${u.telefone||'-'}</td>
                                <td><span class="badge ${u.ativo==1?'badge-success':'badge-danger'}">${u.ativo==1?'Ativo':'Inativo'}</span></td>
                                <td>
                                    <a href="form.php?id=${u.id_usuario}" class="btn-small btn-edit">Editar</a>
                                    <button onclick="deletarUsuario(${u.id_usuario})" class="btn-small btn-delete">Remover</button>
                                </td></tr>`).join('')}
                        </tbody></table>
                        <div class="pagination">
                            <button ${currentPage === 1 ? 'disabled' : ''} onclick="carregarUsuarios(${currentPage - 1})" class="btn-small">← Anterior</button>
                            <span>Página ${currentPage} de ${result.total_pages || 1}</span>
                            <button ${currentPage >= (result.total_pages || 1) ? 'disabled' : ''} onclick="carregarUsuarios(${currentPage + 1})" class="btn-small">Próxima →</button>
                        </div>
                    ` : `
                        <div class="empty-state">
                            <div class="empty-icon">Usuários</div>
                            <h2>Nenhum usuário encontrado</h2>
                        </div>
                    `}
                `;

                setTimeout(() => {
                    const n = document.getElementById('filtroNome');
                    const t = document.getElementById('filtroTelefone');
                    const a = document.getElementById('filtroAtivo');
                    const b = document.getElementById('btnLimparFiltros');
                    if (n) n.value = filtros.nome;
                    if (t) t.value = filtros.telefone;
                    if (a) a.value = filtros.ativo;

                    if (n) n.addEventListener('input', () => aplicarFiltros('input'));
                    if (t) t.addEventListener('input', () => aplicarFiltros('input'));
                    if (a) a.addEventListener('change', () => aplicarFiltros('change'));
                    if (b) b.addEventListener('click', () => {
                        filtros.nome = '';
                        filtros.telefone = '';
                        filtros.ativo = '';
                        if (n) n.value = '';
                        if (t) t.value = '';
                        if (a) a.value = '';
                        carregarUsuarios(1);
                    });
                }, 50);
            } catch (e) {
                content.innerHTML = `<div class="alert alert-danger">❌ Erro: ${e.message}</div>`;
            }
        }

        // Exclui usuário
        async function excluirUsuario(id, nome) {
            if (!confirm(`Tem certeza que deseja excluir o usuário "${nome}"?`)) {
                return;
            }

            try {
                const response = await fetch(`${API_URL}/usuarios/${id}`, {
                    method: 'DELETE'
                });

                const resultado = await parseJsonResponse(response);

                if (response.ok) {
                    carregarUsuarios();
                } else {
                    const total = typeof resultado.total_pedidos !== 'undefined' ? parseInt(resultado.total_pedidos, 10) : null;
                    if (!isNaN(total) && total > 0) {
                        const msg = `Não é possível excluir o usuário "${nome}" porque tem ${total} pedido(s) cadastrado(s) com ele.`;
                        mostrarErro(msg);
                    } else {
                        mostrarErro(resultado.mensagem || resultado.erro || 'Erro ao excluir usuário');
                    }
                }
            } catch (error) {
                mostrarErro('Erro ao excluir usuário: ' + error.message);
            }
        }

        // Função para aplicar filtros (server-side)
        function aplicarFiltros(tipo = 'change') {
            const filtroNome = document.getElementById('filtroNome')?.value.trim() || '';
            const filtroTelefone = document.getElementById('filtroTelefone')?.value || '';
            const filtroAtivo = document.getElementById('filtroAtivo')?.value || '';

            filtros.nome = filtroNome;
            filtros.telefone = filtroTelefone;
            filtros.ativo = filtroAtivo;

            if (debounceFiltro) clearTimeout(debounceFiltro);
            const delay = tipo === 'input' ? 350 : 0;
            debounceFiltro = setTimeout(() => carregarUsuarios(1), delay);
        }

        // Carrega usuários na inicialização
        document.addEventListener('DOMContentLoaded', () => carregarUsuarios());
    </script>
</body>

</html>
