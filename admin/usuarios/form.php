<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$usuario = [
    'nome' => '',
    'email' => '',
    'telefone' => '',
    'cpf' => '',
    'ativo' => 1,
    'admin' => 0
];

if ($isEdit) {
    $json = @file_get_contents($apiBase . '/usuarios/' . $id);
    if ($json !== false) {
        $usuario = json_decode($json, true);
    }
}

if (!empty($usuario['cpf'])) {
    $cpfSomenteNumeros = preg_replace('/\D/', '', (string)$usuario['cpf']);
    if (strlen($cpfSomenteNumeros) === 11) {
        $usuario['cpf'] = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfSomenteNumeros);
    }
}

$erro = null;
$sucesso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'cpf' => trim($_POST['cpf'] ?? ''),
        'ativo' => isset($_POST['ativo']) ? 1 : 0
    ];

    
    if (!$isEdit) {
        $dados['admin'] = isset($_POST['admin']) ? 1 : 0;
    }

    
    $usuario = array_merge($usuario, $dados);

    
    if (!empty($_POST['senha'])) {
        $dados['senha'] = $_POST['senha'];
    }

    
    if (empty($dados['nome'])) {
        $erro = 'Nome é obrigatório';
    } elseif (empty($dados['email'])) {
        $erro = 'E-mail é obrigatório';
    } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido';
    } else {
        
        $ch = curl_init();

        if ($isEdit) {
            curl_setopt($ch, CURLOPT_URL, $apiBase . '/usuarios/' . $id);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } else {
            curl_setopt($ch, CURLOPT_URL, $apiBase . '/usuarios');
            curl_setopt($ch, CURLOPT_POST, true);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            header('Location: list.php?sucesso=' . urlencode($isEdit ? 'Usuário atualizado com sucesso!' : 'Usuário cadastrado com sucesso!'));
            exit;
        } else {
            $respostaApi = json_decode($response, true);
            $erro = $respostaApi['erro'] ?? 'Erro ao salvar usuário';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Editar' : 'Novo' ?> Usuário - Intelecto</title>
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

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4CAF50;
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
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #4CAF50;
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
            <h1><?= $isEdit ? '✏️ Editar Usuário' : '➕ Novo Usuário' ?></h1>
            <a href="list.php" class="btn btn-secondary">← Voltar</a>
        </div>

        <div class="content">
            <?php if ($erro): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Nome Completo <span class="required">*</span></label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required maxlength="100">
                </div>

                <div class="form-group">
                    <label>E-mail <span class="required">*</span></label>
                    <input type="email" name="email" autocomplete="email" value="<?= htmlspecialchars($usuario['email']) ?>" required maxlength="100">
                    <div class="help-text">Será usado para login no sistema</div>
                </div>

                <div class="form-group">
                    <label>Telefone</label>
                    <input type="tel" name="telefone" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" maxlength="20" placeholder="(00) 00000-0000">
                </div>

                <div class="form-group">
                    <label>CPF</label>
                    <input type="text" name="cpf" inputmode="numeric" value="<?= htmlspecialchars($usuario['cpf'] ?? '') ?>" maxlength="14" placeholder="000.000.000-00">
                    <div class="help-text">Use apenas números ou formato com pontos e traço</div>
                </div>

                <div class="form-group">
                    <label>Senha <?= $isEdit ? '(deixe em branco para não alterar)' : '<span class="required">*</span>' ?></label>
                    <input type="password" name="senha" autocomplete="new-password" minlength="6" <?= !$isEdit ? 'required' : '' ?>>
                    <div class="help-text">
                        <?= $isEdit ? 'Preencha apenas se desejar alterar a senha' : 'Mínimo de 6 caracteres' ?>
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="ativo" id="ativo" value="1" <?= ($usuario['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <label for="ativo">Usuário ativo</label>
                    </div>
                </div>

                <?php if (!$isEdit): ?>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="admin" id="admin" value="1" <?= !empty($usuario['admin']) ? 'checked' : '' ?>>
                            <label for="admin">Usuario administrador</label>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $isEdit ? '💾 Salvar Alterações' : '➕ Cadastrar Usuário' ?>
                    </button>
                    <a href="list.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
