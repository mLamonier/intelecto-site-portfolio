<?php

session_start();

require_once __DIR__ . '/../api/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../includes/site.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'validar_senha_antiga') {
    header('Content-Type: application/json');

    $token = $_GET['token'] ?? '';
    $novaSenha = $_POST['senha'] ?? '';

    if ($token === '' || $novaSenha === '') {
        echo json_encode(['erro' => 'Dados inválidos']);
        exit;
    }

    try {
        $stmt = $db->prepare('SELECT id_usuario FROM password_reset WHERE token = :token AND utilizado = 0 AND expira_em > NOW() LIMIT 1');
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            echo json_encode(['erro' => 'Token inválido']);
            exit;
        }

        $usuarioId = $reset['id_usuario'];

        $stmt = $db->prepare('SELECT senha_hash FROM usuario WHERE id_usuario = :id');
        $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($novaSenha, $usuario['senha_hash'])) {
            echo json_encode(['igual' => true]);
        } else {
            echo json_encode(['igual' => false]);
        }
    } catch (PDOException $e) {
        echo json_encode(['erro' => 'Erro ao validar']);
    }
    exit;
}

$token = $_GET['token'] ?? '';
$erro = '';
$sucesso = '';
$tokenValido = false;
$usuarioId = null;

if ($token === '') {
    $erro = 'Token inválido.';
} else {
    try {
        $stmt = $db->prepare('
            SELECT id_usuario FROM password_reset
            WHERE token = :token
              AND utilizado = 0
              AND expira_em > NOW()
            LIMIT 1
        ');
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reset) {
            $tokenValido = true;
            $usuarioId = $reset['id_usuario'];
        } else {
            $erro = 'Token expirado ou inválido.';
        }
    } catch (PDOException $e) {
        $erro = 'Erro ao validar token.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValido) {
    
    if (!CSRF::validate()) {
        $erro = 'Requisição inválida. Tente novamente.';
    } else {
        $senha = $_POST['senha'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if ($senha === '' || $confirm === '') {
            $erro = 'Preencha todos os campos.';
        } elseif ($senha !== $confirm) {
            $erro = 'As senhas não coincidem.';
        } elseif (strlen($senha) < 8) {
            $erro = 'A senha deve ter no mínimo 8 caracteres.';
        } elseif (!preg_match('/[A-Z]/', $senha)) {
            $erro = 'A senha deve conter pelo menos uma letra maiúscula.';
        } elseif (!preg_match('/[a-z]/', $senha)) {
            $erro = 'A senha deve conter pelo menos uma letra minúscula.';
        } elseif (!preg_match('/\d/', $senha)) {
            $erro = 'A senha deve conter pelo menos um número.';
        } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.\/<>?\\\|`~]/', $senha)) {
            $erro = 'A senha deve conter pelo menos um caractere especial (!@#$%^&* etc).';
        } else {
            
            try {
                $stmt = $db->prepare('SELECT senha_hash FROM usuario WHERE id_usuario = :id');
                $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
                $stmt->execute();
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
                    $erro = 'A nova senha não pode ser igual à senha anterior.';
                }
            } catch (PDOException $e) {
                $erro = 'Erro ao validar senha. Tente novamente.';
            }
        }

        if (!$erro) {
            try {
                $db->beginTransaction();

                $hash = password_hash($senha, PASSWORD_BCRYPT);
                $stmt = $db->prepare('UPDATE usuario SET senha_hash = :hash WHERE id_usuario = :id');
                $stmt->bindValue(':hash', $hash);
                $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
                $stmt->execute();

                $stmt = $db->prepare('UPDATE password_reset SET utilizado = 1 WHERE token = :token');
                $stmt->bindValue(':token', $token);
                $stmt->execute();

                $db->commit();
                $sucesso = 'Senha redefinida com sucesso! Você será redirecionado para o login.';
                $tokenValido = false;

                echo '<script>
async function parseJsonResponse(response) {
    const text = await response.text();
    const clean = text.replace(/^\uFEFF/, "");
    return JSON.parse(clean);
}

setTimeout(() => window.location.href = "login.php", 2000);</script>';
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $erro = 'Erro ao redefinir senha. Tente novamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-image: url("<?php echo htmlspecialchars(site_asset_path('assets/background vermelho.png')); ?>");
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 460px;
        }

        h1 {
            margin: 0 0 10px;
            color: #111;
            font-size: 24px;
        }

        p {
            margin: 0 0 18px;
            color: #555;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .field {
            margin-bottom: 16px;
        }

        button {
            width: 100%;
            margin-top: 8px;
            padding: 12px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.35);
        }

        .feedback {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .feedback-erro {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .feedback-sucesso {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .links {
            margin-top: 16px;
            text-align: center;
        }

        .links a {
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
            position: relative;
            transition: color 0.2s ease;
        }

        .links a::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 100%;
            height: 2px;
            background: #dc2626;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.2s ease;
        }

        .links a:hover {
            color: #b91c1c;
        }

        .links a:hover::after {
            transform: scaleX(1);
        }

        
        .password-strength {
            margin-top: 8px;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .password-strength.show {
            opacity: 1;
            max-height: 300px;
        }

        .password-strength-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .password-strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .password-strength.weak .password-strength-fill {
            width: 33%;
            background: #dc2626;
        }

        .password-strength.medium .password-strength-fill {
            width: 66%;
            background: #f59e0b;
        }

        .password-strength.strong .password-strength-fill {
            width: 100%;
            background: #10b981;
        }

        .password-strength-text {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .password-strength.weak .password-strength-text {
            color: #dc2626;
        }

        .password-strength.medium .password-strength-text {
            color: #f59e0b;
        }

        .password-strength.strong .password-strength-text {
            color: #10b981;
        }

        .password-requirements {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .password-requirements li {
            font-size: 12px;
            color: #6b7280;
            padding: 4px 0;
            position: relative;
            padding-left: 20px;
        }

        .password-requirements li:before {
            content: '○';
            position: absolute;
            left: 0;
            color: #9ca3af;
        }

        .password-requirements li.met {
            color: #10b981;
        }

        .password-requirements li.met:before {
            content: '✓';
            color: #10b981;
        }

        .confirm-match {
            margin-top: 8px;
            font-size: 13px;
            font-weight: 600;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .confirm-match.show {
            opacity: 1;
            max-height: 30px;
        }

        .confirm-match.match {
            color: #10b981;
        }

        .confirm-match.nomatch {
            color: #dc2626;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Redefinir senha</h1>
        <?php if ($erro): ?>
            <div class="feedback feedback-erro"><?php echo htmlspecialchars($erro); ?></div>
            <div class="links">
                <a href="forgot-password.php">← Solicitou recuperação novamente?</a>
            </div>
        <?php elseif ($sucesso): ?>
            <div class="feedback feedback-sucesso"><?php echo htmlspecialchars($sucesso); ?></div>
            <p style="color: #666; text-align: center; margin-top: 12px;">Redirecionando...</p>
        <?php elseif ($tokenValido): ?>
            <p>Digite sua nova senha abaixo.</p>
            <form method="POST" action="">
                <?php echo CSRF::input(); ?>
                <div class="field">
                    <label for="senha">Nova senha</label>
                    <input type="password" id="senha" name="senha" required placeholder="No mínimo 8 caracteres" data-token="<?php echo htmlspecialchars($token); ?>">
                    <div id="senhaStrength" class="password-strength">
                        <div class="password-strength-bar">
                            <div class="password-strength-fill"></div>
                        </div>
                        <div class="password-strength-text" id="senhaTexto">Senha fraca</div>
                        <ul class="password-requirements">
                            <li id="req-length">Mínimo 8 caracteres</li>
                            <li id="req-upper">Mínimo 1 letra maiúscula (A-Z)</li>
                            <li id="req-lower">Mínimo 1 letra minúscula (a-z)</li>
                            <li id="req-number">Mínimo 1 número (0-9)</li>
                            <li id="req-symbol">Mínimo 1 símbolo (!@#$%^&*)</li>
                        </ul>
                    </div>
                    <div id="senhaAntiga" class="confirm-match"></div>
                </div>
                <div class="field">
                    <label for="confirm">Confirmar senha</label>
                    <input type="password" id="confirm" name="confirm" required placeholder="Repita a senha">
                    <div id="confirmMatch" class="confirm-match"></div>
                </div>
                <button type="submit">Redefinir Senha</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        const inputSenha = document.getElementById('senha');
        const inputConfirm = document.getElementById('confirm');
        const senhaStrengthDiv = document.getElementById('senhaStrength');
        const senhaTextoDiv = document.getElementById('senhaTexto');
        const confirmMatchDiv = document.getElementById('confirmMatch');
        const senhaAntigaDiv = document.getElementById('senhaAntiga');
        const submitBtn = document.querySelector('button[type="submit"]');
        const token = inputSenha ? inputSenha.getAttribute('data-token') : '';

        
        let validacoesSenha = {};
        let senhasDiferem = false;
        let senhaAntigaValida = false;

        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
        }

        const requisitos = {
            length: {
                regex: /.{8,}/,
                element: 'req-length'
            },
            upper: {
                regex: /[A-Z]/,
                element: 'req-upper'
            },
            lower: {
                regex: /[a-z]/,
                element: 'req-lower'
            },
            number: {
                regex: /\d/,
                element: 'req-number'
            },
            symbol: {
                regex: /[!@#$%^&*()_+\-=\[\]{};:'",./<>?\\|`~]/,
                element: 'req-symbol'
            }
        };

        function validarSenha(senha) {
            const validacoes = {};
            for (const [key, req] of Object.entries(requisitos)) {
                validacoes[key] = req.regex.test(senha);
                const el = document.getElementById(req.element);
                if (validacoes[key]) {
                    el.classList.add('met');
                } else {
                    el.classList.remove('met');
                }
            }
            return validacoes;
        }

        function calcularForca(validacoes) {
            const atendidas = Object.values(validacoes).filter(v => v).length;
            if (atendidas <= 2) return 'weak';
            if (atendidas <= 4) return 'medium';
            return 'strong';
        }

        function atualizarEstadoBotao() {
            const todasValidacoes = Object.values(validacoesSenha).every(v => v === true);
            const habilitado = todasValidacoes && senhasDiferem && senhaAntigaValida;

            if (submitBtn) {
                submitBtn.disabled = !habilitado;
                submitBtn.style.opacity = habilitado ? '1' : '0.5';
                submitBtn.style.cursor = habilitado ? 'pointer' : 'not-allowed';
            }
        }

        function validarSenhaAntiga(senha) {
            if (senha === '') {
                senhaAntigaDiv.classList.remove('show');
                senhaAntigaValida = false;
                atualizarEstadoBotao();
                return;
            }

            const formData = new FormData();
            formData.append('senha', senha);

            fetch('?action=validar_senha_antiga&token=' + encodeURIComponent(token), {
                    method: 'POST',
                    body: formData
                })
                .then(response => parseJsonResponse(response))
                .then(data => {
                    senhaAntigaDiv.classList.add('show');
                    if (data.igual) {
                        senhaAntigaDiv.classList.remove('match');
                        senhaAntigaDiv.classList.add('nomatch');
                        senhaAntigaDiv.textContent = '✗ A nova senha é igual à anterior. Escolha uma diferente.';
                        senhaAntigaValida = false;
                    } else {
                        senhaAntigaDiv.classList.remove('nomatch');
                        senhaAntigaDiv.classList.add('match');
                        senhaAntigaDiv.textContent = '✓ Senha diferente da anterior';
                        senhaAntigaValida = true;
                    }
                    atualizarEstadoBotao();
                })
                .catch(error => {
                    senhaAntigaDiv.classList.remove('show');
                    senhaAntigaValida = false;
                    atualizarEstadoBotao();
                });
        }

        if (inputSenha) {
            inputSenha.addEventListener('input', function() {
                const senha = this.value;

                if (senha === '') {
                    senhaStrengthDiv.classList.remove('show', 'weak', 'medium', 'strong');
                    confirmMatchDiv.classList.remove('show');
                    senhaAntigaDiv.classList.remove('show');
                    validacoesSenha = {};
                    senhaAntigaValida = false;
                    atualizarEstadoBotao();
                    return;
                }

                const validacoes = validarSenha(senha);
                const forca = calcularForca(validacoes);

                
                validacoesSenha = validacoes;

                senhaStrengthDiv.classList.add('show');
                senhaStrengthDiv.classList.remove('weak', 'medium', 'strong');
                senhaStrengthDiv.classList.add(forca);

                const textosForta = {
                    weak: '🔴 Senha fraca - atenda a mais critérios',
                    medium: '🟡 Senha média - praticamente segura',
                    strong: '🟢 Senha forte - pronta para uso'
                };
                senhaTextoDiv.textContent = textosForta[forca];

                validarConfirmacao();
                validarSenhaAntiga(senha);
            });
        }

        function validarConfirmacao() {
            const senha = inputSenha.value;
            const confirm = inputConfirm.value;

            if (confirm === '') {
                confirmMatchDiv.classList.remove('show');
                senhasDiferem = false;
                atualizarEstadoBotao();
                return;
            }

            confirmMatchDiv.classList.add('show');
            if (senha === confirm) {
                confirmMatchDiv.classList.remove('nomatch');
                confirmMatchDiv.classList.add('match');
                confirmMatchDiv.textContent = '✓ Senhas coincidem';
                senhasDiferem = true;
            } else {
                confirmMatchDiv.classList.remove('match');
                confirmMatchDiv.classList.add('nomatch');
                confirmMatchDiv.textContent = '✗ Senhas não coincidem';
                senhasDiferem = false;
            }
            atualizarEstadoBotao();
        }

        if (inputConfirm) {
            inputConfirm.addEventListener('input', validarConfirmacao);
        }
    </script>
</body>

</html>
