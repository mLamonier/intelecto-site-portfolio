<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Gerenciar Grades';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades de Cursos - Intelecto</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
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

        .btn-edit {
            background: #2196F3;
            color: white;
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-edit:hover {
            background: #1976D2;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-danger:hover {
            background: #991b1b;
        }

        .btn-warning {
            background: #FF9800;
            color: white;
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-warning:hover {
            background: #F57C00;
        }

        .btn-duplicate {
            background: #6C5CE7;
            color: white;
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-duplicate:hover {
            background: #5F3DC4;
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

        .alert {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            border-left: 4px solid #4CAF50;
            color: #2e7d32;
        }

        .alert-error {
            border-left: 4px solid #f44336;
            color: #c62828;
        }

        .alert-warning {
            border-left: 4px solid #FF9800;
            color: #e65100;
        }

        .grades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .grade-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }

        .grade-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .grade-card-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .grade-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .grade-card-header {
            border-bottom: 2px solid #f0f0f0;
            padding: 25px 25px 15px 25px;
            margin-bottom: 15px;
        }

        .grade-card-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .grade-card-meses {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .grade-card-body {
            margin-bottom: 15px;
            padding: 0 25px;
        }

        .grade-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #666;
            font-size: 14px;
        }

        .grade-info-icon {
            margin-right: 8px;
        }

        .grade-valores {
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 12px;
            margin-bottom: 15px;
            border: 1px solid #eceff3;
            overflow: hidden;
        }

        .grade-valores-summary {
            list-style: none;
            cursor: pointer;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #4b5563;
            background: #f1f5f9;
        }

        .grade-valores-summary::-webkit-details-marker {
            display: none;
        }

        .grade-valores-summary::after {
            content: '▾';
            float: right;
            color: #6b7280;
            transition: transform 0.2s ease;
        }

        .grade-valores[open] .grade-valores-summary::after {
            transform: rotate(180deg);
        }

        .grade-valores-content {
            padding: 12px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .grade-valor-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .grade-valor-label {
            color: #666;
        }

        .grade-valor-value {
            font-weight: 600;
            color: #333;
            margin-left: 30px;
            white-space: nowrap;
        }

        .grade-valor-section {
            flex: 1 1 0;
            margin-bottom: 0;
            padding-bottom: 0;
            padding-right: 12px;
            border-bottom: none;
            border-right: 1px solid #e0e0e0;
        }

        .grade-valor-section:last-child {
            border-right: none;
            padding-right: 0;
        }

        .grade-matricula {
            flex-basis: 100%;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .grade-valores-content {
                flex-direction: column;
            }

            .grade-valor-section {
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
                padding-right: 0;
                padding-bottom: 10px;
            }

            .grade-valor-section:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }

            .grade-matricula {
                margin-top: 8px;
            }
        }

        .grade-card-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
            padding: 0 25px 25px 25px;
        }

        .empty-state {
            background: white;
            padding: 60px 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }

        .empty-state-text {
            color: #666;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-actions {
                flex-direction: column;
            }

            .grades-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div id="mensagens-container"></div>
        <div class="header">
            <h1>📦 Grades de Cursos</h1>
            <div class="btn-group">
                <a href="../index.php" class="btn btn-secondary">← Voltar ao Dashboard</a>
                <a href="form.php" class="btn btn-primary">+ Nova Grade</a>
            </div>
        </div>

        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($_GET['sucesso']) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['erro'])): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($_GET['erro']) ?>
            </div>
        <?php endif; ?>

        <div style="background: #f7fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px; color: #333;">🔍 Filtros</h3>
            <div style="display: grid; grid-template-columns: 1fr 200px 260px; gap: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Nome da Grade</label>
                    <input type="text" id="filtroNome" placeholder="Filtrar por nome..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Status</label>
                    <select id="filtroAtivo" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">Todos</option>
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Categoria</label>
                    <select id="filtroCategoria" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">Todas</option>
                    </select>
                </div>
            </div>
            <div id="filtroResultado" style="margin-top: 10px; font-size: 13px; color: #666;"></div>
            <div style="margin-top: 12px;">
                <button type="button" id="btnLimparFiltros" class="btn btn-secondary">Limpar filtros</button>
            </div>
        </div>

        <div id="content">
            <div style="text-align: center; padding: 40px; color: #666;">
                ⏳ Carregando grades...
            </div>
        </div>


        <div class="pagination">
            <div id="pagination-info" style="color: #666; font-size: 14px;"></div>
            <div id="pagination-controls" style="display: flex; gap: 8px;"></div>
        </div>
    </div>

    <script>
        async function parseJsonResponse(response) {
            const text = await response.text();
            const clean = text.replace(/^\uFEFF/, "");
            return JSON.parse(clean);
        }

        const API_URL = '<?php echo $apiBase; ?>';
        const SITE_BASE = <?php echo json_encode(site_base_path()); ?>;
        const ASSET_BASE = <?php echo json_encode(site_asset_path('assets')); ?>;
        let currentPage = 1;
        let totalPages = 1;
        const perPage = 12;
        const filtros = {
            nome: '',
            ativo: '',
            categoria: ''
        };
        let debounceFiltro = null;

        function assetUrl(path) {
            if (!path) return '';
            if (path.startsWith('http://') || path.startsWith('https://')) return path;

            let clean = path.replace(/^\/+/, '');

            if (clean.startsWith('frontend/public/assets/')) {
                clean = clean.replace(/^frontend\/public\//, '');
            }

            if (clean.startsWith('assets/')) {
                clean = clean.replace(/^assets\//, '');
                return `${ASSET_BASE}/${clean}`;
            }

            if (clean.startsWith('uploads/')) {
                return `${ASSET_BASE}/${clean}`;
            }

            return `${SITE_BASE}/${clean}`;
        }

        async function carregarCategoriasFiltro() {
            try {
                const response = await fetch(`${API_URL}categorias`);
                const categorias = await parseJsonResponse(response);
                const select = document.getElementById('filtroCategoria');
                if (!select || !Array.isArray(categorias)) return;

                const options = ['<option value="">Todas</option>']
                    .concat(categorias.map(cat => `<option value="${cat.id_categoria}">${cat.nome}</option>`));
                select.innerHTML = options.join('');
                if (filtros.categoria !== '') {
                    select.value = filtros.categoria;
                }
            } catch (error) {

            }
        }

        // Função para mostrar mensagens formatadas
        function mostrarMensagem(texto, tipo = 'error') {
            const container = document.getElementById('mensagens-container');
            const classes = tipo === 'error' ? 'alert alert-error' : 'alert alert-success';
            const icon = tipo === 'error' ? '❌' : '✅';

            container.innerHTML = `<div class="${classes}">${icon} ${texto}</div>`;

            if (tipo === 'success') {
                setTimeout(() => {
                    container.innerHTML = '';
                }, 5000);
            }
        }

        async function carregarGrades(page = 1) {
            currentPage = page;
            const content = document.getElementById('content');

            content.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">⏳ Carregando grades...</div>';

            try {
                const params = new URLSearchParams();
                params.set('page', page);
                params.set('per_page', perPage);
                if (filtros.categoria !== '') {
                    params.set('categoria', filtros.categoria);
                }
                if (filtros.nome) {
                    params.set('nome', filtros.nome);
                }
                if (filtros.ativo !== '') {
                    params.set('ativo', filtros.ativo);
                }

                const response = await fetch(`${API_URL}grades&${params.toString()}`);
                const result = await parseJsonResponse(response);

                if (!result.success) {
                    content.innerHTML = '<p style="color: #ef4444; text-align: center; padding: 20px;">Erro ao carregar grades.</p>';
                    return;
                }

                const grades = result.data;
                currentPage = result.page || page;
                totalPages = result.total_pages || 1;

                if (!grades || grades.length === 0) {
                    content.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Nenhuma grade encontrada.</p>';
                    atualizarPaginacao();
                    return;
                }

                const gradesHTML = grades.map(grade => {
                    const isAtivo = (grade.ativo ?? 1) == 1;
                    const meses = grade.meses_duracao ?? grade.meses ?? 0;
                    const valorPresencial = grade.valor_mensal_presencial ?? grade.valor_presencial ?? null;
                    const valorEad = grade.valor_mensal_ead ?? grade.valor_ead ?? null;
                    const valorMatricula = grade.valor_matricula ?? grade.matricula_valor ?? null;
                    const temValores = valorPresencial || valorEad;
                    const modalidadesResumo = [valorPresencial ? 'Presencial' : '', valorEad ? 'EAD' : ''].filter(Boolean).join(' e ');
                    const matriculaResumo = valorMatricula ? `, Matrícula: R$ ${parseFloat(valorMatricula).toFixed(2).replace('.', ',')}` : '';
                    const categoriaNome = grade.categoria_nome || 'Sem categoria';
                    const categoriaId = grade.id_categoria ?? '';

                    return `
                        <div class="grade-card" data-nome="${grade.nome ?? ''}" data-ativo="${grade.ativo ?? 1}" data-categoria="${categoriaId}">
                            ${grade.imagem_detalhe ? `
                                <div style="width: 100%; height: 200px; background: #f0f0f0; border-radius: 8px 8px 0 0; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                    <img src="${assetUrl(grade.imagem_detalhe)}" alt="${grade.nome ?? 'Grade'}" style="width: 100%; height: 100%; object-fit: cover;" />
                                </div>
                            ` : ''}
                            
                            <div class="grade-card-header">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <div class="grade-card-title">${grade.nome ?? 'Sem nome'}</div>
                                    <span style="padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; ${isAtivo ? 'background: #d1fae5; color: #065f46;' : 'background: #f3f4f6; color: #6b7280;'}">
                                        ${isAtivo ? '✓ Ativo' : '✕ Inativo'}
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                                    <span class="grade-card-meses">
                                        ${meses} ${meses == 1 ? 'mês' : 'meses'}
                                    </span>
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #eef2ff; color: #4338ca;">
                                        🏷️ ${categoriaNome}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grade-card-body">
                                ${grade.descricao_curta ? `
                                    <p style="color: #666; font-size: 14px; margin-bottom: 15px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; max-height: 3em;">
                                        ${grade.descricao_curta.substring(0, 150)}
                                    </p>
                                ` : ''}
                                
                                <div class="grade-info">
                                    <span class="grade-info-icon">📚</span>
                                    <span id="total-cursos-${grade.id_grade}">Carregando cursos...</span>
                                </div>
                                
                                <div class="grade-info">
                                    <span class="grade-info-icon">⏱️</span>
                                    <span id="total-horas-${grade.id_grade}">Calculando horas...</span>
                                </div>
                                
                                ${!temValores ? `
                                    <div class="grade-valores">
                                        <p style="color: #999; font-size: 12px; font-style: italic; padding: 12px;">Valores nao configurados</p>
                                    </div>
                                ` : `
                                    <details class="grade-valores">
                                        <summary class="grade-valores-summary">${modalidadesResumo}${matriculaResumo}</summary>
                                        <div class="grade-valores-content">
                                            ${valorPresencial ? `
                                                <div class="grade-valor-section">
                                                    <div style="font-weight: 600; color: #667eea; margin-bottom: 6px; font-size: 13px;">
                                                        Presencial
                                                    </div>
                                                    <div class="grade-valor-item">
                                                        <span class="grade-valor-label">Mensal:</span>
                                                        <span class="grade-valor-value">R$ ${parseFloat(valorPresencial).toFixed(2).replace('.', ',')}/mês</span>
                                                    </div>
                                                    <div class="grade-valor-item">
                                                        <span class="grade-valor-label">Total Mensal:</span>
                                                        <span class="grade-valor-value">R$ ${((parseFloat(valorPresencial) * meses) + (parseFloat(valorMatricula) || 0)).toFixed(2).replace('.', ',')}</span>
                                                    </div>
                                                    <div class="grade-valor-item">
                                                        <span class="grade-valor-label">À vista:</span>
                                                        <span class="grade-valor-value">R$ ${((parseFloat(valorPresencial) * 0.9) * meses).toFixed(2).replace('.', ',')}</span>
                                                    </div>
                                                    <div class="grade-valor-item">
                                                        <span class="grade-valor-label">Parcelado:</span>
                                                        <span class="grade-valor-value">${meses}x R$ ${((parseFloat(valorPresencial) * 0.95)).toFixed(2).replace('.', ',')}</span>
                                                    </div>
                                                </div>
                                            ` : ''}

                                            ${valorEad ? `
                                                <div class="grade-valor-section">
                                                    <div style="font-weight: 600; color: #667eea; margin-bottom: 6px; font-size: 13px;">
                                                        EAD
                                                    </div>
                                                    <div class="grade-valor-item">
                                                        <span class="grade-valor-label">Mensal:</span>
                                                        <span class="grade-valor-value">R$ ${parseFloat(valorEad).toFixed(2).replace('.', ',')}/mês</span>
                                                    </div>
                                                    <div class="grade-valor-item">
                                                        <span class="grade-valor-label">Total Mensal:</span>
                                                        <span class="grade-valor-value">R$ ${((parseFloat(valorEad) * meses) + (parseFloat(valorMatricula) || 0)).toFixed(2).replace('.', ',')}</span>
                                                    </div>
                                                    <div class="grade-valor-item">
                                                        <span class="grade-valor-label">À vista:</span>
                                                        <span class="grade-valor-value">R$ ${((parseFloat(valorEad) * 0.9) * meses).toFixed(2).replace('.', ',')}</span>
                                                    </div>
                                                    <div class="grade-valor-item">
                                                        <span class="grade-valor-label">Parcelado:</span>
                                                        <span class="grade-valor-value">${meses}x R$ ${((parseFloat(valorEad) * 0.95)).toFixed(2).replace('.', ',')}</span>
                                                    </div>
                                                </div>
                                            ` : ''}

                                            ${valorMatricula ? `
                                                <div class="grade-matricula" style="font-size:11px;color:#777;padding:8px;background:#f9fafb;border-radius:4px;">
                                                    Matrícula: R$ ${parseFloat(valorMatricula).toFixed(2).replace('.', ',')} (apenas plano mensal)
                                                </div>
                                            ` : ''}
                                        </div>
                                    </details>
                                `}
                            </div>
                            
                            <div class="grade-card-actions">
                                <a href="form.php?id=${grade.id_grade}" class="btn btn-edit">
                                    ✏️ Editar
                                </a>
                                <a href="cursos.php?id=${grade.id_grade}" class="btn btn-warning">
                                    📚 Gerenciar Cursos
                                </a>
                                <button onclick="duplicarGrade(${grade.id_grade}, '${(grade.nome ?? 'Grade').replace(/'/g, "\\'")}')" class="btn btn-duplicate">
                                    📋 Duplicar
                                </button>
                                <button onclick="excluirGrade(${grade.id_grade}, '${(grade.nome ?? 'Grade').replace(/'/g, "\\'")}')" class="btn btn-danger">
                                    🗑️ Excluir
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');

                content.innerHTML = `<div class="grades-grid">${gradesHTML}</div>`;
                grades.forEach(grade => carregarInfoGrade(grade.id_grade));
                atualizarPaginacao();
            } catch (error) {
                content.innerHTML = '<p style="color: #ef4444; text-align: center; padding: 20px;">Erro ao carregar grades.</p>';
            }
        }

        function atualizarPaginacao() {
            const paginationInfo = document.getElementById('pagination-info');
            const paginationControls = document.getElementById('pagination-controls');

            paginationInfo.style.display = 'block';
            paginationControls.style.display = 'flex';

            paginationInfo.textContent = `Página ${currentPage} de ${totalPages}`;

            paginationControls.innerHTML = `
                <button onclick="carregarGrades(1)" ${currentPage === 1 ? 'disabled' : ''} class="btn-small">
                    Primeira
                </button>
                <button onclick="carregarGrades(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="btn-small">
                    ← Anterior
                </button>
                <button onclick="carregarGrades(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="btn-small">
                    Próxima →
                </button>
                <button onclick="carregarGrades(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''} class="btn-small">
                    Última
                </button>
            `;
        }

        async function carregarInfoGrade(idGrade) {
            try {
                const response = await fetch(`${API_URL}/grades/${idGrade}/cursos`);

                if (!response.ok) {
                    throw new Error('Erro na API');
                }

                const cursos = await parseJsonResponse(response);

                if (!Array.isArray(cursos)) {
                    throw new Error('Resposta inválida');
                }

                const totalCursos = cursos.length;
                const totalHoras = cursos.reduce((sum, c) => {
                    const horas = parseInt(c.horas_personalizadas || c.horas || c.carga_horaria || 0);
                    return sum + horas;
                }, 0);

                document.getElementById(`total-cursos-${idGrade}`).textContent =
                    `${totalCursos} ${totalCursos === 1 ? 'curso' : 'cursos'}`;

                document.getElementById(`total-horas-${idGrade}`).textContent =
                    `${totalHoras} horas totais`;

            } catch (error) {
                document.getElementById(`total-cursos-${idGrade}`).textContent = '0 cursos';
                document.getElementById(`total-horas-${idGrade}`).textContent = '0 horas';
            }
        }

        async function duplicarGrade(id, nome) {
            if (!confirm(`Deseja duplicar a grade "${nome}"?\n\nSerá criada uma nova grade com cópia de todos os dados, cursos e configurações.`)) {
                return;
            }

            try {
                mostrarMensagem('Duplicando grade...', 'success');

                const resGrade = await fetch(`${API_URL}/grades/${id}`);
                if (!resGrade.ok) {
                    throw new Error('Erro ao buscar dados da grade');
                }
                const gradeOriginal = await resGrade.json();

                const resCursos = await fetch(`${API_URL}/grades/${id}/cursos`);
                if (!resCursos.ok) {
                    throw new Error('Erro ao buscar cursos da grade');
                }
                const cursosOriginais = await resCursos.json();

                const novaGrade = {
                    nome: gradeOriginal.nome + ' (Cópia)',
                    slug: null,
                    meses: gradeOriginal.meses_duracao || gradeOriginal.meses,
                    meses_duracao: gradeOriginal.meses_duracao || gradeOriginal.meses,
                    descricao_curta: gradeOriginal.descricao_curta || '',
                    descricao_longa_md: gradeOriginal.descricao_longa_md || null,
                    id_categoria: gradeOriginal.id_categoria ?? null,
                    venda_mensal: gradeOriginal.vende_mensal || 1,
                    valor_mensal_presencial: gradeOriginal.valor_mensal_presencial,
                    valor_mensal_ead: gradeOriginal.valor_mensal_ead,
                    valor_avista_presencial: gradeOriginal.valor_avista_presencial,
                    valor_avista_ead: gradeOriginal.valor_avista_ead,
                    valor_matricula: gradeOriginal.valor_matricula,
                    imagem_card: gradeOriginal.imagem_card,
                    imagem_detalhe: gradeOriginal.imagem_detalhe,
                    tipo_venda: gradeOriginal.tipo_venda,
                    preco_avista: gradeOriginal.preco_avista,
                    parcelas_maximas: gradeOriginal.parcelas_maximas,
                    percentual_parcelamento: gradeOriginal.percentual_parcelamento,
                    usa_valores_padrao: gradeOriginal.usa_valores_padrao,
                    ativo: 1
                };

                const resNovaGrade = await fetch(`${API_URL}/grades`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(novaGrade)
                });

                if (!resNovaGrade.ok) {
                    throw new Error('Erro ao criar a cópia da grade');
                }

                const respostaNovaGrade = await resNovaGrade.json();
                const novaGradeId = respostaNovaGrade.id_grade;

                if (!novaGradeId) {
                    throw new Error('ID da nova grade não retornado');
                }

                if (Array.isArray(cursosOriginais) && cursosOriginais.length > 0) {
                    for (const curso of cursosOriginais) {
                        const idCurso = curso.id_curso;
                        const horasPersonalizadas = curso.horas_personalizadas || null;

                        const resCursoAdd = await fetch(`${API_URL}/grades/${novaGradeId}/cursos`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id_curso: idCurso,
                                horas_personalizadas: horasPersonalizadas
                            })
                        });

                        if (!resCursoAdd.ok) {}
                    }
                }

                mostrarMensagem(`Grade duplicada com sucesso! ID: ${novaGradeId}`, 'success');

                setTimeout(() => {
                    window.location.href = `form.php?id=${novaGradeId}&sucesso=${encodeURIComponent('Grade duplicada com sucesso! Agora você pode fazer ajustes.')}`;
                }, 1500);

            } catch (error) {
                mostrarMensagem('Erro ao duplicar grade: ' + error.message, 'error');
                window.scrollTo(0, 0);
            }
        }

        async function excluirGrade(id, nome) {
            if (!confirm(`Tem certeza que deseja excluir a grade "${nome}"?\n\nEsta ação não pode ser desfeita!`)) {
                return;
            }

            try {
                const response = await fetch(`${API_URL}/grades/${id}`, {
                    method: 'DELETE'
                });

                const result = await parseJsonResponse(response);

                if (response.ok) {
                    window.location.href = 'list.php?sucesso=' + encodeURIComponent('Grade excluída com sucesso');
                } else {
                    const mensagem = result.erro || result.mensagem || 'Erro ao excluir grade';
                    mostrarMensagem(mensagem, 'error');
                    window.scrollTo(0, 0);
                }
            } catch (error) {
                mostrarMensagem('Erro ao excluir grade: ' + error.message, 'error');
                window.scrollTo(0, 0);
            }
        }

        function aplicarFiltros(tipo = 'change') {
            const filtroNome = document.getElementById('filtroNome')?.value.trim() || '';
            const filtroAtivo = document.getElementById('filtroAtivo')?.value || '';
            const filtroCategoria = document.getElementById('filtroCategoria')?.value || '';

            filtros.nome = filtroNome;
            filtros.ativo = filtroAtivo;
            filtros.categoria = filtroCategoria;

            if (debounceFiltro) clearTimeout(debounceFiltro);
            const delay = tipo === 'input' ? 350 : 0;
            debounceFiltro = setTimeout(() => carregarGrades(1), delay);
        }

        window.addEventListener('load', () => {
            setTimeout(() => {
                const filtroNome = document.getElementById('filtroNome');
                const filtroAtivo = document.getElementById('filtroAtivo');
                const filtroCategoria = document.getElementById('filtroCategoria');
                const btnLimpar = document.getElementById('btnLimparFiltros');

                if (filtroNome) filtroNome.value = filtros.nome;
                if (filtroAtivo) filtroAtivo.value = filtros.ativo;
                if (filtroCategoria) filtroCategoria.value = filtros.categoria;

                if (filtroNome) filtroNome.addEventListener('input', () => aplicarFiltros('input'));
                if (filtroAtivo) filtroAtivo.addEventListener('change', () => aplicarFiltros('change'));
                if (filtroCategoria) filtroCategoria.addEventListener('change', () => aplicarFiltros('change'));
                if (btnLimpar) btnLimpar.addEventListener('click', () => {
                    filtros.nome = '';
                    filtros.ativo = '';
                    filtros.categoria = '';
                    if (filtroNome) filtroNome.value = '';
                    if (filtroAtivo) filtroAtivo.value = '';
                    if (filtroCategoria) filtroCategoria.value = '';
                    carregarGrades(1);
                });
            }, 100);
        });

        document.addEventListener('DOMContentLoaded', async () => {
            await carregarCategoriasFiltro();
            carregarGrades();
        });
    </script>
</body>

</html>
