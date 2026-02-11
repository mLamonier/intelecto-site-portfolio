<?php

require_once __DIR__ . '/../includes/auth_admin.php';

require_once __DIR__ . '/../includes/config.php';

$id = $_GET['id'] ?? null;

$curso = null;

$isEdit = false;

if ($id) {

    $isEdit = true;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $apiBase . 'cursos/' . $id);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    curl_close($ch);

    $curso = json_decode($response, true);

    if (!$curso || isset($curso['erro'])) {

        header('Location: list.php?erro=Curso não encontrado');

        exit;

    }

}

function listPdfAssets(): array

{

    $root = dirname(__DIR__, 2);

    $assetsRoot = $root . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets';

    $paths = [];

    if (!is_dir($assetsRoot)) {

        return $paths;

    }

    $rii = new RecursiveIteratorIterator(

        new RecursiveDirectoryIterator($assetsRoot, FilesystemIterator::SKIP_DOTS)

    );

    foreach ($rii as $file) {

        if (!$file->isFile()) {

            continue;

        }

        if (strtolower($file->getExtension()) !== 'pdf') {

            continue;

        }

        $relative = str_replace($assetsRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());

        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

        $paths[] = 'assets/' . $relative;

    }

    sort($paths, SORT_NATURAL | SORT_FLAG_CASE);

    return $paths;

}

$pdfAssets = listPdfAssets();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdfConteudo = trim($_POST['pdf_conteudo'] ?? '');

    if (!isset($mensagemErro)) {

        $data = [

            'nome' => $_POST['nome'] ?? '',

            'slug' => $_POST['slug'] ?? '',

            'categoria' => $_POST['categoria'] ?? null,

            'horas' => (int)($_POST['horas'] ?? 0),

            'descricao_curta' => $_POST['descricao_curta'] ?? '',

            'descricao_longa_md' => $_POST['descricao_longa_md'] ?? '',

            'pdf_conteudo' => ($pdfConteudo !== '' ? $pdfConteudo : null),

            'link_aula_demo' => $_POST['link_aula_demo'] ?? '',

            'pode_montar_grade' => isset($_POST['pode_montar_grade']) ? 1 : 0,

            'ativo' => isset($_POST['ativo']) ? 1 : 0

        ];

        $ch = curl_init();

        if ($isEdit) {

            

            curl_setopt($ch, CURLOPT_URL, $apiBase . 'cursos/' . $id);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        } else {

            

            curl_setopt($ch, CURLOPT_URL, $apiBase . 'cursos');

            curl_setopt($ch, CURLOPT_POST, true);

        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {

            header('Location: list.php?sucesso=' . ($isEdit ? 'Curso atualizado' : 'Curso criado') . ' com sucesso');

            exit;

        } else {

            $erro = json_decode($response, true);

            $mensagemErro = $erro['erro'] ?? 'Erro ao salvar curso';

        }

    }

}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $isEdit ? 'Editar' : 'Novo' ?> Curso - Intelecto</title>

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📚</text></svg>">

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

        input[type="url"],

        select,

        textarea {

            width: 100%;

            padding: 12px 15px;

            border: 1px solid #ddd;

            border-radius: 8px;

            font-size: 14px;

            font-family: inherit;

            transition: border-color 0.3s;

        }

        select {

            cursor: pointer;

            background: white;

        }

        input:focus,

        select:focus,

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

        input[type="file"] {

            width: 100%;

            padding: 10px;

            border: 2px dashed #ddd;

            border-radius: 8px;

            font-size: 14px;

            cursor: pointer;

            background: #fafafa;

            transition: all 0.3s;

        }

        input[type="file"]:hover {

            border-color: #4CAF50;

            background: #f0f8f0;

        }

        .file-info {

            font-size: 12px;

            color: #666;

            margin-top: 8px;

            padding: 8px;

            background: #f5f5f5;

            border-radius: 4px;

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

            <h1><?= $isEdit ? '✏️ Editar Curso' : '➕ Novo Curso' ?></h1>

            <a href="list.php" class="btn btn-secondary">← Voltar</a>

        </div>

        <div class="content">

            <?php if (isset($mensagemErro)): ?>

                <div class="alert alert-error">❌ <?= htmlspecialchars($mensagemErro) ?></div>

            <?php endif; ?>

            <form method="POST" id="formCurso" enctype="multipart/form-data">

                <div class="form-group">

                    <label>Nome do Curso <span class="required">*</span></label>

                    <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($curso['nome'] ?? '') ?>" required>

                </div>

                <div class="form-group">

                    <label>Slug (URL amigável) <span class="required">*</span></label>

                    <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($curso['slug'] ?? '') ?>" required>

                    <div class="help-text">Exemplo: excel-avancado (usado na URL do curso)</div>

                </div>

                <div class="form-group">

                    <label>Categoria</label>

                    <select id="selectCategoria">

                        <option value="">Carregando categorias...</option>

                    </select>

                    <input type="hidden" name="categoria" id="categoria" value="<?= htmlspecialchars($curso['categoria'] ?? '') ?>">

                    <div class="help-text">Selecione a categoria do curso</div>

                </div>

                <div class="form-group">

                    <label>Carga Horária <span class="required">*</span></label>

                    <input type="number" name="horas" value="<?= $curso['horas'] ?? '' ?>" min="1" required>

                    <div class="help-text">Total de horas do curso</div>

                </div>

                <div class="form-group">

                    <label>Descrição Curta <span class="required">*</span></label>

                    <textarea name="descricao_curta" required><?= htmlspecialchars($curso['descricao_curta'] ?? '') ?></textarea>

                    <div class="help-text">Resumo do curso (exibido nos cards)</div>

                </div>

                <div class="form-group">

                    <label>Descrição Longa (Markdown)</label>

                    <textarea name="descricao_longa_md" rows="6"><?= htmlspecialchars($curso['descricao_longa_md'] ?? '') ?></textarea>

                    <div class="help-text">Descrição detalhada do curso em Markdown</div>

                </div>

                <div class="form-group">

                    <label>PDF do Conteúdo Programático</label>

                    <input

                        type="text"

                        name="pdf_conteudo"

                        list="pdfAssetsList"

                        value="<?= htmlspecialchars($curso['pdf_conteudo'] ?? '') ?>"

                        placeholder="assets/pdfs/exemplo.pdf">

                    <datalist id="pdfAssetsList">

                        <?php foreach ($pdfAssets as $path): ?>

                            <option value="<?= htmlspecialchars($path) ?>"></option>

                        <?php endforeach; ?>

                    </datalist>

                    <?php if (!empty($curso['pdf_conteudo'])): ?>

                        <div class="file-info">

                            📄 PDF atual: <strong><?= basename($curso['pdf_conteudo']) ?></strong>

                            <br>

                            <small>Selecione outro caminho para substituir</small>

                        </div>

                    <?php endif; ?>

                    <div class="help-text">Selecione um PDF já existente em assets.</div>

                </div>

                <div class="form-group">

                    <label>Link da Aula Demonstrativa</label>

                    <input type="url" name="link_aula_demo" value="<?= htmlspecialchars($curso['link_aula_demo'] ?? '') ?>">

                </div>

                <div class="form-group">

                    <div class="checkbox-group">

                        <input type="checkbox" name="pode_montar_grade" id="pode_montar_grade"

                            <?= ($curso['pode_montar_grade'] ?? 1) ? 'checked' : '' ?>>

                        <label for="pode_montar_grade">Pode aparecer na montagem de grade personalizada</label>

                    </div>

                </div>

                <div class="form-group">

                    <div class="checkbox-group">

                        <input type="checkbox" name="ativo" id="ativo"

                            <?= ($curso['ativo'] ?? 1) ? 'checked' : '' ?>>

                        <label for="ativo">Curso ativo</label>

                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit" class="btn btn-primary">

                        <?= $isEdit ? '💾 Salvar Alterações' : '➕ Criar Curso' ?>

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

        const cursoAtual = <?= json_encode($curso) ?>;

        // Carrega as categorias

        async function carregarCategorias() {

            const select = document.getElementById('selectCategoria');

            try {

                const response = await fetch(`${API_URL}/categorias`);

                if (!response.ok) {

                    throw new Error('Erro ao carregar categorias');

                }

                const categorias = await parseJsonResponse(response);

                // Limpa o select

                select.innerHTML = '<option value="">-- Selecione uma categoria --</option>';

                // Adiciona as categorias ativas ordenadas

                categorias

                    .filter(cat => cat.ativo == 1)

                    .sort((a, b) => (a.ordem || 0) - (b.ordem || 0))

                    .forEach(cat => {

                        const option = document.createElement('option');

                        option.value = cat.nome;

                        option.textContent = cat.nome;

                        // Se estiver editando, seleciona a categoria atual

                        if (cursoAtual && cursoAtual.categoria == cat.nome) {

                            option.selected = true;

                            document.getElementById('categoria').value = cat.nome;

                        }

                        select.appendChild(option);

                    });

            } catch (error) {

                select.innerHTML = '<option value="">⚠️ Erro ao carregar. Tente recarregar a página.</option>';

            }

        }

        // Quando mudar a categoria no select, atualiza o campo hidden

        document.getElementById('selectCategoria').addEventListener('change', function(e) {

            document.getElementById('categoria').value = e.target.value;

        });

        // Gera slug automaticamente ao digitar o nome

        document.getElementById('nome').addEventListener('input', function(e) {

            const slug = e.target.value

                .toLowerCase()

                .normalize('NFD')

                .replace(/[\u0300-\u036f]/g, '')

                .replace(/[^a-z0-9]+/g, '-')

                .replace(/^-+|-+$/g, '');

            document.getElementById('slug').value = slug;

        });

        // Carrega categorias ao iniciar

        carregarCategorias();

    </script>

</body>

</html>
