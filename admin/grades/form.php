<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/config.php';

function api_url(string $path): string
{
    global $apiBase;
    $path = ltrim($path, '/');

    if (strpos($apiBase, '?route=') !== false) {
        return $apiBase . $path;
    }

    return rtrim($apiBase, '/') . '/' . $path;
}

function api_request(string $method, string $path, ?array $body = null): array
{
    $url = api_url($path);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = ['Content-Type: application/json'];

    $method = strtoupper($method);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode($response ?? '', true);

    return [
        'ok' => ($httpCode >= 200 && $httpCode < 300),
        'http' => $httpCode,
        'url' => $url,
        'raw' => $response,
        'curl_error' => $curlErr,
        'json' => $decoded
    ];
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$grade = null;

if ($isEdit) {
    $resp = api_request('GET', "grades/$id");
    if (!$resp['ok'] || !is_array($resp['json']) || isset($resp['json']['erro'])) {
        http_response_code(500);
        echo "<h2>Erro ao carregar grade para edição</h2>";
        echo "<p><b>URL:</b> " . htmlspecialchars($resp['url'] ?? '') . "</p>";
        echo "<p><b>HTTP:</b> " . htmlspecialchars((string)($resp['http'] ?? '')) . "</p>";
        echo "<pre style='white-space: pre-wrap'>" . htmlspecialchars($resp['raw'] ?? '') . "</pre>";
        exit;
    }
    $grade = $resp['json'];
}

function field($arr, $key, $default = '')
{
    if (!is_array($arr)) return $default;
    return $arr[$key] ?? $default;
}

function listImageAssets(): array
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
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            continue;
        }
        $relative = str_replace($assetsRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    $paths[] = 'assets/' . $relative;
    }

    sort($paths, SORT_NATURAL | SORT_FLAG_CASE);
    return $paths;
}

$imageAssets = listImageAssets();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $meses = (int)($_POST['meses'] ?? 0);
    $descricaoCurta = trim($_POST['descricao_curta'] ?? '');
    $descricaoLonga = trim($_POST['descricao_longa_md'] ?? '');
    $idCategoria = isset($_POST['id_categoria']) && $_POST['id_categoria'] !== '' ? (int)$_POST['id_categoria'] : null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $imagemCardAtual = trim($_POST['imagem_card_atual'] ?? '');
    $imagemDetalheAtual = trim($_POST['imagem_detalhe_atual'] ?? '');
    $imagemCardInput = trim($_POST['imagem_card'] ?? '');
    $imagemDetalheInput = trim($_POST['imagem_detalhe'] ?? '');
    $clearImagemCard = isset($_POST['clear_imagem_card']) ? (int)$_POST['clear_imagem_card'] : 0;
    $clearImagemDetalhe = isset($_POST['clear_imagem_detalhe']) ? (int)$_POST['clear_imagem_detalhe'] : 0;

    
    $imagemCardValue = null;
    if ($clearImagemCard) {
        $imagemCardValue = null;
    } elseif ($imagemCardInput !== '') {
        $imagemCardValue = $imagemCardInput;
    } else {
        $imagemCardValue = ($imagemCardAtual !== '') ? $imagemCardAtual : null;
    }

    $imagemDetalheValue = null;
    if ($clearImagemDetalhe) {
        $imagemDetalheValue = null;
    } elseif ($imagemDetalheInput !== '') {
        $imagemDetalheValue = $imagemDetalheInput;
    } else {
        $imagemDetalheValue = ($imagemDetalheAtual !== '') ? $imagemDetalheAtual : null;
    }

    if ($nome === '') {
        header('Location: form.php' . ($isEdit ? '?id=' . $id : '') . '&erro=' . urlencode('Nome é obrigatório'));
        exit;
    }

    if ($meses <= 0) {
        header('Location: form.php' . ($isEdit ? '?id=' . $id : '') . '&erro=' . urlencode('Duração (meses) é obrigatória'));
        exit;
    }

    
    $valorPresencial = $_POST['valor_presencial'] ?? '';
    $valorEad = $_POST['valor_ead'] ?? '';
    $valorMatricula = $_POST['valor_matricula'] ?? '';

    
    $descontoParcelado = $_POST['desconto_parcelado'] ?? '';
    $descontoAvista = $_POST['desconto_avista'] ?? '';

    
    $usaValoresMensaisPadrao = isset($_POST['usa_valores_mensais_padrao']) ? 1 : 0;
    $usaDescontosPadrao = isset($_POST['usa_descontos_padrao']) ? 1 : 0;

    
    $payload = [
        'nome' => $nome,
        'slug' => ($slug !== '' ? $slug : null),

        
        'meses' => $meses,
        'meses_duracao' => $meses,

        'descricao_curta' => $descricaoCurta,
        'descricao_longa_md' => ($descricaoLonga !== '' ? $descricaoLonga : null),
        'id_categoria' => $idCategoria,
        'categoria' => $idCategoria,

        'ativo' => $ativo,

        
        'imagem_card' => $imagemCardValue,
        'imagem_detalhe' => $imagemDetalheValue,

        
        'valor_mensal_presencial' => ($valorPresencial !== '' ? (float)$valorPresencial : null),
        'valor_presencial' => ($valorPresencial !== '' ? (float)$valorPresencial : null),
        'valor_mensal_ead' => ($valorEad !== '' ? (float)$valorEad : null),
        'valor_ead' => ($valorEad !== '' ? (float)$valorEad : null),
        'valor_matricula' => ($valorMatricula !== '' ? (float)$valorMatricula : null),

        
        'desconto_parcelado' => ($descontoParcelado !== '' ? (float)$descontoParcelado : null),
        'desconto_avista' => ($descontoAvista !== '' ? (float)$descontoAvista : null),

        
        'usa_valores_mensais_padrao' => $usaValoresMensaisPadrao,
        'usa_valores_padrao' => $usaValoresMensaisPadrao,
        'usa_descontos_padrao' => $usaDescontosPadrao,
    ];

    if ($isEdit) {
        $resp = api_request('PUT', "grades/$id", $payload);
        if (!$resp['ok']) {
            $msg = $resp['json']['erro'] ?? $resp['json']['mensagem'] ?? ('HTTP ' . $resp['http']);
            header('Location: form.php?id=' . $id . '&erro=' . urlencode($msg));
            exit;
        }
        header('Location: list.php?sucesso=' . urlencode('Grade atualizada com sucesso'));
        exit;
    } else {
        $resp = api_request('POST', "grades", $payload);
        if (!$resp['ok']) {
            $msg = $resp['json']['erro'] ?? $resp['json']['mensagem'] ?? ('HTTP ' . $resp['http']);
            header('Location: form.php?erro=' . urlencode($msg));
            exit;
        }
        header('Location: list.php?sucesso=' . urlencode('Grade criada com sucesso'));
        exit;
    }
}

$nomeV = $isEdit ? field($grade, 'nome') : '';
$slugV = $isEdit ? field($grade, 'slug') : '';
$mesesV = $isEdit ? (field($grade, 'meses_duracao', field($grade, 'meses', 0))) : 0;
$descCurtaV = $isEdit ? field($grade, 'descricao_curta') : '';
$descLongaV = $isEdit ? field($grade, 'descricao_longa_md') : '';
$ativoV = $isEdit ? (int)field($grade, 'ativo', 1) : 1;

$configDefaults = [];
$cfgResp = api_request('GET', 'config');
if ($cfgResp['ok'] && is_array($cfgResp['json'])) {
    foreach ($cfgResp['json'] as $item) {
        if (isset($item['chave']) && isset($item['valor'])) {
            $configDefaults[$item['chave']] = $item['valor'];
        }
    }
}

$getCfg = function (string $key, $default = '') use ($configDefaults) {
    return $configDefaults[$key] ?? $default;
};

$valorPresencialV = $isEdit ? field($grade, 'valor_mensal_presencial', field($grade, 'valor_presencial', '')) : $getCfg('VALOR_MENSAL_PRESENCIAL_PADRAO', '');
$valorEadV = $isEdit ? field($grade, 'valor_mensal_ead', field($grade, 'valor_ead', '')) : $getCfg('VALOR_MENSAL_EAD_PADRAO', '');
$valorMatriculaV = $isEdit ? field($grade, 'valor_matricula', '') : $getCfg('VALOR_MATRICULA_PADRAO', '');

$descontoParceladoV = $isEdit ? field($grade, 'desconto_parcelado', '') : $getCfg('DESCONTO_PARCELADO_PADRAO', '5');
$descontoAvistaV = $isEdit ? field($grade, 'desconto_avista', '') : $getCfg('DESCONTO_AVISTA_PADRAO', '10');

$usaValoresMensaisPadraoV = $isEdit ? (int)field($grade, 'usa_valores_padrao', field($grade, 'usa_valores_mensais_padrao', 1)) : 1;
$usaDescontosPadraoV = $isEdit ? (int)field($grade, 'usa_descontos_padrao', 1) : 1;

$placeholderPresencial = $getCfg('VALOR_MENSAL_PRESENCIAL_PADRAO', '0.00');
$placeholderEad = $getCfg('VALOR_MENSAL_EAD_PADRAO', '0.00');
$placeholderMatricula = $getCfg('VALOR_MATRICULA_PADRAO', '0.00');
$placeholderDescontoParcelado = $getCfg('DESCONTO_PARCELADO_PADRAO', '5');
$placeholderDescontoAvista = $getCfg('DESCONTO_AVISTA_PADRAO', '10');

$categorias = [];
$categoriasResp = api_request('GET', 'categorias/todas&per_page=200');
if ($categoriasResp['ok'] && is_array($categoriasResp['json'])) {
    if (isset($categoriasResp['json']['data']) && is_array($categoriasResp['json']['data'])) {
        $categorias = $categoriasResp['json']['data'];
    } elseif (array_values($categoriasResp['json']) === $categoriasResp['json']) {
        $categorias = $categoriasResp['json'];
    }
}

$categoriaIdV = $isEdit ? field($grade, 'id_categoria', '') : '';

$title = $isEdit ? 'Editar Grade' : 'Nova Grade';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($title) ?> - Intelecto</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url("<?= htmlspecialchars(asset_url('assets/background branco.png')) ?>");
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: #fff;
            padding: 22px 26px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .header h1 {
            font-size: 20px;
            color: #333;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: 0;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
        }

        .btn-secondary {
            background: #607D8B;
            color: #fff;
        }

        .btn-primary {
            background: #4CAF50;
            color: #fff;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            padding: 20px;
        }

        .row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .col {
            flex: 1;
            min-width: 260px;
        }

        label {
            display: block;
            font-size: 12px;
            color: #555;
            margin-bottom: 6px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #ddd;
            outline: none;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .muted {
            color: #777;
            font-size: 13px;
            margin-top: 8px;
        }

        .alert {
            margin-bottom: 12px;
            padding: 12px 14px;
            border-radius: 10px;
        }

        .alert-err {
            background: #ffebee;
            color: #c62828;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
            align-items: center;
        }

        .check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .check input {
            width: auto;
        }

        .file-input-group {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .file-input-wrapper {
            display: flex;
            gap: 20px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .file-input-item {
            flex: 1;
            min-width: 250px;
        }

        .file-input-item label {
            display: block;
            margin-bottom: 8px;
        }

        .file-input-item input[type="file"] {
            padding: 8px;
            cursor: pointer;
        }

        .file-preview {
            margin-top: 12px;
            max-width: 150px;
            border-radius: 8px;
            border: 1px solid #ddd;
            overflow: hidden;
        }

        .file-preview img {
            width: 100%;
            height: auto;
            display: block;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <h1><?= htmlspecialchars($title) ?></h1>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn btn-secondary" href="list.php">← Voltar</a>
                <?php if ($isEdit): ?>
                    <a class="btn btn-secondary" href="cursos.php?id=<?= (int)$id ?>">📚 Gerenciar cursos</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-err">❌ <?= htmlspecialchars($_GET['erro']) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col">
                        <label>Nome *</label>
                        <input name="nome" value="<?= htmlspecialchars($nomeV) ?>" required />
                    </div>
                    <div class="col">
                        <label>Slug (opcional)</label>
                        <input name="slug" value="<?= htmlspecialchars($slugV) ?>" placeholder="ex: pacote-office" />
                        <div class="muted">Se deixar vazio, a API pode gerar automaticamente.</div>
                    </div>
                </div>

                <div class="row" style="margin-top: 12px;">
                    <div class="col">
                        <label>Categoria</label>
                        <select name="id_categoria">
                            <option value="">Sem categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <?php
                                $categoriaId = (int)($categoria['id_categoria'] ?? 0);
                                $selected = ((string)$categoriaIdV !== '' && (int)$categoriaIdV === $categoriaId) ? 'selected' : '';
                                ?>
                                <option value="<?= $categoriaId ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($categoria['nome'] ?? ('Categoria #' . $categoriaId)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="muted">Essa categoria sera usada para o filtro em <code>/cursos?categoria=id</code>.</div>
                    </div>
                    <div class="col">
                        <label>Duração (meses) *</label>
                        <input type="number" min="1" step="1" name="meses" value="<?= htmlspecialchars((string)$mesesV) ?>" required />
                    </div>
                    <div class="col">
                        <label>Descrição curta</label>
                        <input name="descricao_curta" value="<?= htmlspecialchars($descCurtaV) ?>" />
                    </div>
                </div>

                <div class="row" style="margin-top: 12px;">
                    <div class="col" style="min-width: 100%;">
                        <label>Descrição longa (HTML)</label>
                        <p style="font-size: 12px; color: #666; margin: 4px 0 8px 0;">
                            Use HTML para formatar. Exemplo:<br>
                            <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">&lt;h3&gt;Descrição&lt;/h3&gt;&lt;p&gt;Texto...&lt;/p&gt;&lt;h3&gt;Onde posso atuar?&lt;/h3&gt;&lt;p&gt;Texto...&lt;/p&gt;</code>
                        </p>
                        <textarea name="descricao_longa_md" rows="8"><?= htmlspecialchars($descLongaV) ?></textarea>
                    </div>
                </div>

                
                <div class="file-input-group">
                    <h3 style="font-size: 16px; margin-bottom: 12px; color: #333;">🖼️ Imagens</h3>

                    <div class="file-input-wrapper">
                        <div class="file-input-item">
                            <label for="imagem_detalhe"><strong>Imagem Grande (Detalhe) *</strong></label>
                            <p style="font-size: 12px; color: #777; margin-bottom: 8px;">Será exibida na página de detalhes da grade</p>
                            <input type="text" id="imagem_detalhe" name="imagem_detalhe" list="gradeImageAssets" placeholder="assets/grades/exemplo.jpg" value="<?= htmlspecialchars($isEdit ? ($grade['imagem_detalhe'] ?? '') : '') ?>" />
                            <input type="hidden" name="imagem_detalhe_atual" value="<?= $isEdit ? htmlspecialchars($grade['imagem_detalhe'] ?? '') : '' ?>" />
                            <input type="hidden" name="clear_imagem_detalhe" id="clear_imagem_detalhe" value="0" />
                            <datalist id="gradeImageAssets">
                                <?php foreach ($imageAssets as $path): ?>
                                    <option value="<?= htmlspecialchars($path) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <button type="button" class="btn btn-secondary" id="btn_clear_detalhe" style="margin-top:8px;">🧹 Limpar imagem</button>
                            <?php if ($isEdit && !empty($grade['imagem_detalhe'])): ?>
                                <div class="file-preview" id="current_detalhe">
                                    <img src="<?= htmlspecialchars(asset_url($grade['imagem_detalhe'] ?? '')) ?>" alt="Imagem detalhe atual" />
                                </div>
                                <p style="font-size: 11px; color: #999; margin-top: 8px;">Imagem atual: <?= htmlspecialchars($grade['imagem_detalhe']) ?></p>
                            <?php endif; ?>
                            <div id="preview_detalhe"></div>
                        </div>

                        <div class="file-input-item">
                            <label for="imagem_card"><strong>Imagem Pequena (Card) *</strong></label>
                            <p style="font-size: 12px; color: #777; margin-bottom: 8px;">Será exibida nos cards da homepage</p>
                            <input type="text" id="imagem_card" name="imagem_card" list="gradeImageAssets" placeholder="assets/grades/exemplo.jpg" value="<?= htmlspecialchars($isEdit ? ($grade['imagem_card'] ?? '') : '') ?>" />
                            <input type="hidden" name="imagem_card_atual" value="<?= $isEdit ? htmlspecialchars($grade['imagem_card'] ?? '') : '' ?>" />
                            <input type="hidden" name="clear_imagem_card" id="clear_imagem_card" value="0" />
                            <button type="button" class="btn btn-secondary" id="btn_clear_card" style="margin-top:8px;">🧹 Limpar imagem</button>
                            <?php if ($isEdit && !empty($grade['imagem_card'])): ?>
                                <div class="file-preview" id="current_card">
                                    <img src="<?= htmlspecialchars(asset_url($grade['imagem_card'] ?? '')) ?>" alt="Imagem card atual" />
                                </div>
                                <p style="font-size: 11px; color: #999; margin-top: 8px;">Imagem atual: <?= htmlspecialchars($grade['imagem_card']) ?></p>
                            <?php endif; ?>
                            <div id="preview_card"></div>
                        </div>
                    </div>
                </div>

                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <h3 style="font-size: 16px; margin-bottom: 12px; color: #333;">💵 Valores das Mensalidades</h3>

                    <div class="check" style="margin-bottom: 12px;">
                        <input type="checkbox" id="usa_valores_mensais_padrao" name="usa_valores_mensais_padrao" <?= ($usaValoresMensaisPadraoV ? 'checked' : '') ?> />
                        <label for="usa_valores_mensais_padrao" style="margin:0;">Usar valores mensais padrão (configurados em Configurações)</label>
                    </div>

                    <div class="row">
                        <div class="col">
                            <label>Mensalidade Presencial *</label>
                            <input type="number" step="0.01" min="0" name="valor_presencial" value="<?= htmlspecialchars($valorPresencialV) ?>" placeholder="<?= htmlspecialchars($placeholderPresencial) ?>" required />
                            <div class="muted">Valor base da mensalidade presencial</div>
                        </div>
                        <div class="col">
                            <label>Mensalidade EAD *</label>
                            <input type="number" step="0.01" min="0" name="valor_ead" value="<?= htmlspecialchars($valorEadV) ?>" placeholder="<?= htmlspecialchars($placeholderEad) ?>" required />
                            <div class="muted">Valor base da mensalidade EAD</div>
                        </div>
                        <div class="col">
                            <label>Valor Matrícula</label>
                            <input type="number" step="0.01" min="0" name="valor_matricula" value="<?= htmlspecialchars($valorMatriculaV) ?>" placeholder="<?= htmlspecialchars($placeholderMatricula) ?>" />
                            <div class="muted">Cobrado apenas no plano mensal</div>
                        </div>
                    </div>

                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                        <h4 style="font-size: 14px; margin-bottom: 12px; color: #333;">🎯 Descontos nos Planos</h4>

                        <div class="check" style="margin-bottom: 12px;">
                            <input type="checkbox" id="usa_descontos_padrao" name="usa_descontos_padrao" <?= ($usaDescontosPadraoV ? 'checked' : '') ?> />
                            <label for="usa_descontos_padrao" style="margin:0;">Usar descontos padrão (configurados em Configurações)</label>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label>Desconto Plano Parcelado (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="desconto_parcelado" value="<?= htmlspecialchars($descontoParceladoV) ?>" placeholder="<?= htmlspecialchars($placeholderDescontoParcelado) ?>" />
                                <div class="muted">Desconto aplicado na mensalidade do plano parcelado (ex: 5 = 5%)</div>
                            </div>
                            <div class="col">
                                <label>Desconto Plano À Vista (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="desconto_avista" value="<?= htmlspecialchars($descontoAvistaV) ?>" placeholder="<?= htmlspecialchars($placeholderDescontoAvista) ?>" />
                                <div class="muted">Desconto aplicado na mensalidade do plano à vista (ex: 10 = 10%)</div>
                            </div>
                        </div>

                        <p style="font-size: 12px; color: #666; margin-top: 12px; padding: 10px; background: #f0f9ff; border-radius: 6px;">
                            ℹ️ <strong>Como funciona:</strong><br>
                            • <strong>Mensal:</strong> mensalidade + matrícula<br>
                            • <strong>Parcelado:</strong> mensalidade com desconto, sem matrícula, até <?= $mesesV ?: 'X' ?> parcelas<br>
                            • <strong>À Vista:</strong> mensalidade com desconto, sem matrícula
                        </p>
                    </div>
                </div>

                <div class="check">
                    <input type="checkbox" id="ativo" name="ativo" <?= ($ativoV ? 'checked' : '') ?> />
                    <label for="ativo" style="margin:0;">Ativo</label>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">💾 Salvar</button>
                    <a class="btn btn-secondary" href="list.php">Cancelar</a>
                </div>
            </form>
        </div>

    </div>

    <script>
        
        
        document.getElementById('imagem_detalhe')?.addEventListener('change', function() {
            const flag = document.getElementById('clear_imagem_detalhe');
            if (flag) flag.value = '0';
        });
        document.getElementById('imagem_card')?.addEventListener('change', function() {
            const flag = document.getElementById('clear_imagem_card');
            if (flag) flag.value = '0';
        });

        
        document.getElementById('btn_clear_detalhe')?.addEventListener('click', function() {
            const input = document.getElementById('imagem_detalhe');
            const flag = document.getElementById('clear_imagem_detalhe');
            const preview = document.getElementById('preview_detalhe');
            const current = document.getElementById('current_detalhe');
            if (input) input.value = '';
            if (flag) flag.value = '1';
            if (preview) preview.innerHTML = '';
            if (current) current.remove();
        });

        document.getElementById('btn_clear_card')?.addEventListener('click', function() {
            const input = document.getElementById('imagem_card');
            const flag = document.getElementById('clear_imagem_card');
            const preview = document.getElementById('preview_card');
            const current = document.getElementById('current_card');
            if (input) input.value = '';
            if (flag) flag.value = '1';
            if (preview) preview.innerHTML = '';
            if (current) current.remove();
        });

        
        document.querySelector('form').addEventListener('submit', function(e) {
            const camposValores = [
                'valor_presencial',
                'valor_ead',
                'valor_matricula',
                'percentual_parcelamento'
            ];

            camposValores.forEach(name => {
                const input = document.querySelector(`input[name="${name}"]`);
                if (input && input.value.trim() === '' && input.placeholder) {
                    input.value = input.placeholder;
                }
            });
        });
    </script>

</body>

</html>
