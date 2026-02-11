<?php

session_start();

require_once __DIR__ . '/api/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/site.php';

$database = new Database();
$db = $database->getConnection();
$erro = '';
$sucesso = isset($_GET['registered']) ? 'Conta criada com sucesso. Faça login.' : '';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$hostWithPort = $_SERVER['HTTP_HOST'] ?? 'localhost';

$envHome = getenv('FRONTEND_BASE_URL');
if ($envHome) {
    $homeUrl = rtrim($envHome, '/') . '/';
} else {
    $isLocal = preg_match('/^(localhost|127\.0\.0\.1)/i', $hostWithPort) === 1;
    $hasPort = strpos($hostWithPort, ':') !== false;

    if ($isLocal) {
        
        $hostPort = $hasPort ? $hostWithPort : $hostWithPort . ':5173';
        $homeUrl = $scheme . '://' . $hostPort . '/';
    } else {
        
        $homeUrl = $scheme . '://' . $hostWithPort . '/';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!CSRF::validate()) {
        $erro = 'Requisição inválida. Tente novamente.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($email === '' || $senha === '') {
            $erro = 'Preencha e-mail e senha.';
        } else {
            try {
                
                $sql = "
                    SELECT u.id_usuario, u.nome, u.email, u.senha_hash,
                           GROUP_CONCAT(ur.role SEPARATOR ',') as roles
                    FROM usuario u
                    LEFT JOIN usuario_role ur ON ur.id_usuario = u.id_usuario
                    WHERE u.email = :email
                      AND u.ativo = 1
                    GROUP BY u.id_usuario
                    LIMIT 1
                ";

                $stmt = $db->prepare($sql);
                $stmt->bindValue(':email', $email);
                $stmt->execute();
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
                    session_regenerate_id(true);
                    
                    $rolesRaw = array_filter(explode(',', $usuario['roles'] ?? ''));
                    $roles = array_values(array_filter(array_map(static function ($role) {
                        return strtoupper(trim($role));
                    }, $rolesRaw)));

                    
                    $_SESSION['usuario_logado'] = true;
                    $_SESSION['usuario_id'] = $usuario['id_usuario'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    $_SESSION['usuario_email'] = $usuario['email'];
                    $_SESSION['usuario_roles'] = $roles;

                    
                    if (in_array('ADMIN', $roles, true)) {
                        $_SESSION['admin_logado'] = true;
                        $_SESSION['admin_id'] = $usuario['id_usuario'];
                        $_SESSION['admin_nome'] = $usuario['nome'];
                        $_SESSION['admin_email'] = $usuario['email'];
                        
                        session_write_close();
                        header('Location: ' . site_path('admin/index.php'), true, 303);
                    } else {
                        
                        session_write_close();
                        header('Location: ' . $homeUrl . 'meus-pedidos', true, 303);
                    }
                    exit;
                } else {
                    $erro = 'E-mail ou senha inválidos.';
                }
            } catch (PDOException $e) {
                $erro = 'Erro ao processar login. Tente novamente.';
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
    <title>Login - Intelecto</title>
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

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header img {
            width: 100%;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .logo {
            width: 140px;
            height: auto;
            display: block;
            margin: 0 auto 12px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-3px) scale(1.02);
        }

        .btn-login:active {
            transform: translateY(-1px) scale(0.98);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 13px;
        }

        .link {
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
            position: relative;
            transition: color 0.2s ease;
            margin-right: 12px;
        }

        .link:hover {
            color: #ee5a5a;
        }

        .criar-conta {
            margin-right: 0px;
            margin-left: 12px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <img
                class="logo"
                src="<?php echo htmlspecialchars(site_asset_path('assets/logo/Marca - Borda Branca.png')); ?>"
                alt="Intelecto Profissionalizantes">
        </div>

        <?php if ($sucesso): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($sucesso); ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars(site_path('login.php')); ?>">
            <?php echo CSRF::input(); ?>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="username"
                    required
                    placeholder="seu@email.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    autocomplete="current-password"
                    required
                    placeholder="••••••••">
            </div>

            <button type="submit" class="btn-login">
                Entrar
            </button>
        </form>

        <div class="footer-text" style="margin-top: 16px;">
            <a href="forgot-password.php" class="link">Esqueceu sua senha?</a>
            <span style="color: #999;">/</span>
            <a href="register.php" class="link criar-conta">Criar conta</a>
        </div>

        <div class="footer-text" style="margin-top: 12px;">
            <a href="<?php echo htmlspecialchars($homeUrl); ?>" class="link">Voltar à página inicial</a>
        </div>

        <div class="footer-text">
            Intelecto Profissionalizantes © 2026
        </div>
    </div>
</body>

</html>
