<?php

session_start();

require_once __DIR__ . '/../api/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/site.php';

$database = new Database();
$db = $database->getConnection();

$feedback = '';
$feedbackTipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!CSRF::validate()) {
        $feedback = 'Requisição inválida. Tente novamente.';
        $feedbackTipo = 'erro';
    } else {
        $email = trim($_POST['email'] ?? '');

        if ($email === '') {
            $feedback = 'Informe seu e-mail.';
            $feedbackTipo = 'erro';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $feedback = 'E-mail inválido.';
            $feedbackTipo = 'erro';
        } else {
            try {
                
                $stmt = $db->prepare('SELECT id_usuario, nome FROM usuario WHERE email = :email AND ativo = 1 LIMIT 1');
                $stmt->bindValue(':email', $email);
                $stmt->execute();
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($usuario) {
                    
                    $token = bin2hex(random_bytes(32));
                    $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

                    
                    $stmt = $db->prepare('
                        INSERT INTO password_reset (id_usuario, token, expira_em)
                        VALUES (:id_usuario, :token, :expira_em)
                    ');
                    $stmt->bindValue(':id_usuario', $usuario['id_usuario'], PDO::PARAM_INT);
                    $stmt->bindValue(':token', $token);
                    $stmt->bindValue(':expira_em', $expira);
                    $stmt->execute();

                    
                    Mailer::enviarRecuperacaoSenha($email, $usuario['nome'], $token);

                    $feedback = 'Se o e-mail estiver cadastrado, enviaremos instruções de redefinição.';
                    $feedbackTipo = 'sucesso';
                } else {
                    
                    $feedback = 'Se o e-mail estiver cadastrado, enviaremos instruções de redefinição.';
                    $feedbackTipo = 'sucesso';
                }
            } catch (PDOException $e) {
                $feedback = 'Erro ao processar solicitação. Tente novamente.';
                $feedbackTipo = 'erro';
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
    <title>Recuperar Senha - Admin</title>
    <style>
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
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 420px;
        }

        h1 {
            margin: 0 0 12px;
            color: #111;
            font-size: 22px;
        }

        p {
            margin: 0 0 20px;
            color: #555;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        input {
            width: 388px;
            max-width: 70vw;
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

        .links a:hover {
            color: #ee5a5a;
        }

        button {
            width: 100%;
            margin-top: 14px;
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
    </style>
</head>

<body>
    <div class="container">
        <h1>Recuperar senha</h1>
        <p>Informe seu e-mail. Se existir no sistema, enviaremos instruções para redefinição.</p>
        <?php if ($feedback): ?>
            <div class="feedback feedback-<?php echo htmlspecialchars($feedbackTipo); ?>"><?php echo htmlspecialchars($feedback); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <?php echo CSRF::input(); ?>
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required placeholder="seu@email.com">
            <button type="submit">Enviar</button>
        </form>
        <div class="links">
            <a href="index.php">Voltar ao dashboard</a>
        </div>
    </div>
</body>

</html>
