<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/config.php';

$isEdit = false;
$categoriaId = null;

if (isset($_GET['id'])) {
    $isEdit = true;
    $categoriaId = (int)$_GET['id'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Editar' : 'Nova' ?> Categoria - Intelecto</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏷️</text></svg>">
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
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-secondary {
            background: #607D8B;
            color: white;
        }

        .btn-secondary:hover {
            background: #546E7A;
            transform: translateY(-2px);
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
        }

        .btn-primary:hover {
            background: #45a049;
        }

        .content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        label .required {
            color: #f44336;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .help-text {
            font-size: 13px;
            color: #999;
            margin-top: 5px;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #999;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: stretch;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><?= $isEdit ? '✏️ Editar Categoria' : '➕ Nova Categoria' ?></h1>
            <a href="list.php" class="btn btn-secondary">← Voltar</a>
        </div>

        <div class="content">
            <div class="loading" id="loading" style="display:none;">⏳ Carregando...</div>
            <div id="alertContainer"></div>

            <form id="formCategoria">
                <div class="form-group">
                    <label>Nome da Categoria <span class="required">*</span></label>
                    <input type="text" id="nome" name="nome" required placeholder="Ex: Informática">
                    <div class="help-text">Nome único da categoria</div>
                </div>

                <div class="form-group">
                    <label>Slug (URL amigável)</label>
                    <input type="text" id="slug" name="slug" placeholder="Ex: informatica">
                    <div class="help-text">Deixe em branco para gerar automaticamente</div>
                </div>

                <div class="form-group">
                    <label>Descrição</label>
                    <textarea id="descricao" name="descricao" placeholder="Descrição opcional da categoria"></textarea>
                    <div class="help-text">Descrição opcional que aparecerá no site</div>
                </div>

                <div class="form-group">
                    <label>Ordem de Exibição</label>
                    <input type="number" id="ordem" name="ordem" min="0" value="0">
                    <div class="help-text">Menor número aparece primeiro na listagem</div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="ativo" name="ativo" checked>
                        <label for="ativo">Categoria ativa</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $isEdit ? '💾 Salvar Alterações' : '➕ Criar Categoria' ?>
                    </button>
                    <a href="list.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
async function parseJsonResponse(response) {
    const text = await response.text();
    const clean = text.replace(/^\uFEFF/, "");
    return JSON.parse(clean);
}


        const API_URL = '<?php echo $apiBase; ?>';
        const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
        const categoriaId = <?= $categoriaId ?? 'null' ?>;

        function mostrarErro(mensagem) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-error">❌ ${mensagem}</div>
            `;
        }

        // Se for edição, carrega dados
        if (isEdit && categoriaId) {
            carregarCategoria();
        }

        async function carregarCategoria() {
            document.getElementById('loading').style.display = 'block';
            try {
                const response = await fetch(`${API_URL}/categorias/${categoriaId}`);
                const cat = await parseJsonResponse(response);

                document.getElementById('nome').value = cat.nome;
                document.getElementById('slug').value = cat.slug;
                document.getElementById('descricao').value = cat.descricao || '';
                document.getElementById('ordem').value = cat.ordem;
                document.getElementById('ativo').checked = cat.ativo == 1;
            } catch (error) {
                mostrarErro('Erro ao carregar categoria');
            }
            document.getElementById('loading').style.display = 'none';
        }

        document.getElementById('formCategoria').addEventListener('submit', async (e) => {
            e.preventDefault();

            const dados = {
                nome: document.getElementById('nome').value,
                slug: document.getElementById('slug').value,
                descricao: document.getElementById('descricao').value,
                ordem: parseInt(document.getElementById('ordem').value),
                ativo: document.getElementById('ativo').checked ? 1 : 0
            };

            try {
                const url = isEdit ?
                    `${API_URL}/categorias/${categoriaId}` :
                    `${API_URL}/categorias`;

                const response = await fetch(url, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dados)
                });

                const result = await parseJsonResponse(response);

                if (response.ok) {
                    window.location.href = 'list.php';
                } else {
                    mostrarErro(result.erro || 'Erro ao salvar categoria');
                }
            } catch (error) {
                mostrarErro('Erro ao salvar categoria');
            }
        });
    </script>
</body>

</html>
