<?php

session_start();

require_once __DIR__ . '/../api/config/db.php';
require_once __DIR__ . '/includes/auth_admin.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../includes/site.php';

$database = new Database();
$db = $database->getConnection();

$erro = '';
$nome = '';
$email = '';

function validarSenhaForte($senha)
{
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]{8,}$/', $senha);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!CSRF::validate()) {
        $erro = 'Requisição inválida. Tente novamente.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if ($nome === '' || $email === '' || $senha === '' || $confirm === '') {
            $erro = 'Preencha todos os campos.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } elseif ($senha !== $confirm) {
            $erro = 'As senhas não coincidem.';
        } elseif (!validarSenhaForte($senha)) {
            $erro = 'A senha não atende aos critérios mínimos de segurança.';
        } else {
            try {
                $db->beginTransaction();

                $stmt = $db->prepare('SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1');
                $stmt->bindValue(':email', $email);
                $stmt->execute();

                if ($stmt->fetch()) {
                    $erro = 'Já existe um usuário com este e-mail.';
                    $db->rollBack();
                } else {
                    $hash = password_hash($senha, PASSWORD_BCRYPT);

                    $stmt = $db->prepare('
                        INSERT INTO usuario (nome, email, senha_hash, telefone, ativo, criado_em)
                        VALUES (:nome, :email, :senha_hash, NULL, 1, NOW())
                    ');
                    $stmt->bindValue(':nome', $nome);
                    $stmt->bindValue(':email', $email);
                    $stmt->bindValue(':senha_hash', $hash);
                    $stmt->execute();

                    $novoId = $db->lastInsertId();

                    $stmtRole = $db->prepare('INSERT INTO usuario_role (id_usuario, role) VALUES (:id_usuario, :role)');
                    $stmtRole->bindValue(':id_usuario', $novoId, PDO::PARAM_INT);
                    $stmtRole->bindValue(':role', 'ADMIN');
                    $stmtRole->execute();

                    $db->commit();
                    header('Location: login.php?registered=1');
                    exit;
                }
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $erro = 'Erro ao criar usuário. Tente novamente.';
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
    <title>Criar Conta - Admin</title>
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

        .password-strength {
            margin-top: 8px;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            display: none;
        }

        .password-strength.show {
            display: block;
        }

        .password-strength-bar {
            width: 100%;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            margin-bottom: 8px;
            overflow: hidden;
        }

        .password-strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
            border-radius: 3px;
        }

        .password-strength.weak .password-strength-fill {
            width: 33%;
            background-color: #ef4444;
        }

        .password-strength.medium .password-strength-fill {
            width: 66%;
            background-color: #f59e0b;
        }

        .password-strength.strong .password-strength-fill {
            width: 100%;
            background-color: #10b981;
        }

        .password-strength-text {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .password-strength.weak .password-strength-text {
            color: #dc2626;
        }

        .password-strength.medium .password-strength-text {
            color: #d97706;
        }

        .password-strength.strong .password-strength-text {
            color: #059669;
        }

        .password-requirements {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .password-requirements li {
            padding: 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }

        .password-requirements li::before {
            content: '○';
            font-weight: bold;
            width: 16px;
            text-align: center;
        }

        .password-requirements li.met::before {
            content: '✓';
            color: #10b981;
        }

        .password-requirements li.met {
            color: #10b981;
        }

        .password-requirements li:not(.met) {
            color: #6b7280;
        }

        .confirm-match {
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: none;
        }

        .confirm-match.show {
            display: block;
        }

        .confirm-match.match {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }

        .confirm-match.nomatch {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        button:hover {
            transform: translateY(-3px) scale(1.02);
        }

        button:active {
            transform: translateY(-1px) scale(0.98);
        }

        .feedback {
            margin-top: 12px;
            color: #111;
            background: #fee2e2;
            border: 1px solid #fecdd3;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
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

        .links a:hover {
            color: #ee5a5a;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Criar conta</h1>
        <p>Cadastre um novo administrador.</p>
        <?php if ($erro): ?>
            <div class="feedback"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <?php echo CSRF::input(); ?>
            <div class="field">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required placeholder="Seu nome" value="<?php echo htmlspecialchars($nome); ?>">
            </div>
            <div class="field">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required placeholder="seu@email.com" value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div class="field">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required placeholder="••••••••">
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
            </div>
            <div class="field">
                <label for="confirm">Confirmar senha</label>
                <input type="password" id="confirm" name="confirm" required placeholder="••••••••">
                <div id="confirmMatch" class="confirm-match"></div>
            </div>
            <button type="submit" id="btnCriarContaAdmin">Criar conta</button>
        </form>
        <div class="links">
            <a href="index.php">Voltar ao dashboard</a>
        </div>
    </div>

    <script>
        const inputSenha = document.getElementById('senha');
        const inputConfirm = document.getElementById('confirm');
        const senhaStrengthDiv = document.getElementById('senhaStrength');
        const senhaTextoDiv = document.getElementById('senhaTexto');
        const confirmMatchDiv = document.getElementById('confirmMatch');
        const btnSubmit = document.getElementById('btnCriarContaAdmin');

        
        let validacoesSenha = {};
        let senhasDiferem = false;

        
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.style.opacity = '0.5';
            btnSubmit.style.cursor = 'not-allowed';
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
                el.classList.toggle('met', validacoes[key]);
            }
            return validacoes;
        }

        function calcularForca(validacoes) {
            const atendidas = Object.values(validacoes).filter(Boolean).length;
            if (atendidas <= 2) return 'weak';
            if (atendidas <= 4) return 'medium';
            return 'strong';
        }

        function atualizarEstadoBotao() {
            const todasValidacoes = Object.values(validacoesSenha).every(v => v === true);
            const habilitado = todasValidacoes && senhasDiferem;

            if (btnSubmit) {
                btnSubmit.disabled = !habilitado;
                btnSubmit.style.opacity = habilitado ? '1' : '0.5';
                btnSubmit.style.cursor = habilitado ? 'pointer' : 'not-allowed';
            }
        }

        inputSenha.addEventListener('input', () => {
            const senha = inputSenha.value;
            if (senha === '') {
                senhaStrengthDiv.classList.remove('show', 'weak', 'medium', 'strong');
                confirmMatchDiv.classList.remove('show');
                validacoesSenha = {};
                senhasDiferem = false;
                atualizarEstadoBotao();
                return;
            }

            const validacoes = validarSenha(senha);
            const forca = calcularForca(validacoes);

            
            validacoesSenha = validacoes;

            senhaStrengthDiv.classList.add('show');
            senhaStrengthDiv.classList.remove('weak', 'medium', 'strong');
            senhaStrengthDiv.classList.add(forca);

            const textos = {
                weak: '🔴 Senha fraca - atenda a mais critérios',
                medium: '🟡 Senha média - quase lá',
                strong: '🟢 Senha forte - pronta para uso'
            };
            senhaTextoDiv.textContent = textos[forca];

            validarConfirmacao();
        });

        function validarConfirmacao() {
            const senha = inputSenha.value;
            const confirm = inputConfirm.value;

            if (confirm === '') {
                confirmMatchDiv.classList.remove('show');
                senhasDiferem = false;
                atualizarEstadoBotao();
                return true;
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
            return senhasDiferem;
        }

        inputConfirm.addEventListener('input', validarConfirmacao);

        document.querySelector('form').addEventListener('submit', (e) => {
            const senha = inputSenha.value;
            const confirm = inputConfirm.value;
            const validacoes = validarSenha(senha);
            const todasAtendidas = Object.values(validacoes).every(Boolean);

            if (!todasAtendidas) {
                e.preventDefault();
                alert('A senha não atende aos critérios de segurança mínimos.');
                return false;
            }

            if (senha !== confirm) {
                e.preventDefault();
                alert('As senhas não coincidem.');
                return false;
            }

            return true;
        });
    </script>
</body>

</html>
