<?php

require_once __DIR__ . '/site.php';

class Mailer
{
    private static $mailFrom = '';
    private static $mailHost = '';
    private static $mailPort = 587;
    private static $mailUser = '';
    private static $mailPass = '';
    private static $mailError = 'Nenhum erro';



    public static function inicializar()
    {
        $env_content = site_env_content();
        if ($env_content !== null) {


            preg_match('/MAIL_FROM=(.+)/', $env_content, $match);
            self::$mailFrom = isset($match[1]) ? trim($match[1], '\'"') : '';

            preg_match('/MAIL_HOST=(.+)/', $env_content, $match);
            self::$mailHost = isset($match[1]) ? trim($match[1], '\'"') : '';

            preg_match('/MAIL_PORT=(.+)/', $env_content, $match);
            self::$mailPort = isset($match[1]) ? (int)trim($match[1], '\'"') : 587;

            preg_match('/MAIL_USER=(.+)/', $env_content, $match);
            self::$mailUser = isset($match[1]) ? trim($match[1], '\'"') : '';

            preg_match('/MAIL_PASS=(.+)/', $env_content, $match);
            self::$mailPass = isset($match[1]) ? trim($match[1], '\'"') : '';
        }
    }



    public static function enviar($email, $assunto, $mensagem)
    {
        if (empty(self::$mailFrom)) {
            self::inicializar();
        }


        $resultado = self::tentarPHPMailer($email, $assunto, $mensagem);
        if ($resultado === true) {
            self::$mailError = 'Enviado via PHPMailer/SMTP';
            return true;
        }


        $resultado = self::tentarMailNativo($email, $assunto, $mensagem);
        if ($resultado === true) {
            self::$mailError = 'Enviado via mail() nativo';
            return true;
        }


        $resultado = self::salvarEmArquivo($email, $assunto, $mensagem);
        if ($resultado) {
            return true;
        }

        return false;
    }



    private static function tentarPHPMailer($email, $assunto, $mensagem)
    {
        try {

            $paths = [
                __DIR__ . '/../vendor/autoload.php',
                __DIR__ . '/../../vendor/autoload.php',
            ];

            $phpmailer_encontrado = false;
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    @require_once $path;
                    $phpmailer_encontrado = true;
                    break;
                }
            }

            if (!$phpmailer_encontrado) {
                self::$mailError = 'Autoload do Composer não encontrado';
                return null;
            }

            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                self::$mailError = 'Classe PHPMailer não encontrada após autoload';
                return null;
            }

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = self::$mailHost;
            $mail->SMTPAuth = true;
            $mail->Username = self::$mailUser;
            $mail->Password = self::$mailPass;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = self::$mailPort;
            $mail->Timeout = 10;
            $mail->CharSet = 'UTF-8';



            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->setFrom(self::$mailFrom, 'Intelecto');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $mensagem;

            if ($mail->send()) {
                self::$mailError = 'Enviado com sucesso via PHPMailer';
                return true;
            } else {
                self::$mailError = 'PHPMailer falhou: ' . $mail->ErrorInfo;
                return false;
            }
        } catch (\Exception $e) {
            self::$mailError = 'PHPMailer Exception: ' . $e->getMessage();
            return false;
        }
    }



    private static function tentarMailNativo($email, $assunto, $mensagem)
    {
        try {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . self::$mailFrom . "\r\n";

            if (mail($email, $assunto, $mensagem, $headers)) {
                return true;
            } else {
                self::$mailError = 'mail() nativo falhou';
                return false;
            }
        } catch (\Exception $e) {
            self::$mailError = 'mail() Exception: ' . $e->getMessage();
            return false;
        }
    }



    private static function salvarEmArquivo($email, $assunto, $mensagem)
    {
        try {
            $dir = __DIR__ . '/../logs/emails';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $timestamp = date('Y-m-d_H-i-s');
            $uid = substr(uniqid(), -8);
            $arquivo = $dir . '/email_' . $timestamp . '_' . $uid . '.eml';

            $conteudo = "From: Intelecto <" . self::$mailFrom . ">\r\n";
            $conteudo .= "To: " . $email . "\r\n";
            $conteudo .= "Subject: " . $assunto . "\r\n";
            $conteudo .= "MIME-Version: 1.0\r\n";
            $conteudo .= "Content-Type: text/html; charset=UTF-8\r\n";
            $conteudo .= "Date: " . date('r') . "\r\n";
            $conteudo .= "\r\n";
            $conteudo .= $mensagem;

            if (file_put_contents($arquivo, $conteudo)) {
                self::$mailError = 'E-mail salvo em: ' . $arquivo;
                return true;
            }

            return false;
        } catch (\Exception $e) {
            self::$mailError = 'Erro ao salvar arquivo: ' . $e->getMessage();
            return false;
        }
    }



    public static function enviarRecuperacaoSenha($email, $nome, $token)
    {
        $link_reset = self::montarLinkReset($token);

        $assunto = 'Recuperação de Senha - Intelecto';

        $mensagem = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #e01515; color: #fff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .button { display: inline-block; background-color: #dbdbdb; color: #ddd; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background-color: #f0f0f0; padding: 10px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Recuperação de Senha</h1>
                </div>
                <div class="content">
                    <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
                    <p>Você solicitou a recuperação de senha para sua conta. Clique no botão abaixo para redefinir sua senha:</p>
                    <a href="' . $link_reset . '" class="button">Redefinir Senha</a>
                    <p>Ou copie e cole este link no seu navegador:</p>
                    <p><small>' . $link_reset . '</small></p>
                    <p><strong>Este link expira em 24 horas.</strong></p>
                    <p>Se você não solicitou esta recuperação, ignore este e-mail.</p>
                </div>
                <div class="footer">
                    <p>© 2026 Intelecto. Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>';

        return self::enviar($email, $assunto, $mensagem);
    }



    public static function enviarConfirmacaoPagamento($email, $nome, $pedidoId, $valor)
    {
        $link_pedido = self::getAppUrl() . 'meus-pedidos/' . $pedidoId;

        $assunto = 'Pedido Confirmado - Intelecto';

        $mensagem = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #28a745; color: #fff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .button { display: inline-block; background-color: #dbdbdb; color: #ddd; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background-color: #f0f0f0; padding: 10px; text-align: center; font-size: 12px; }
                .order-info { background-color: white; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>✓ Pedido Confirmado!</h1>
                </div>
                <div class="content">
                    <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
                    <p>Sua compra foi confirmada com sucesso!</p>
                    <div class="order-info">
                        <p><strong>ID do Pedido:</strong> #' . $pedidoId . '</p>
                        <p><strong>Valor:</strong> R$ ' . number_format($valor, 2, ',', '.') . '</p>
                    </div>
                    <p>Você pode acompanhar seu pedido no link abaixo:</p>
                    <a href="' . $link_pedido . '" class="button">Ver Meu Pedido</a>
                    <p>Ou acesse: <small>' . $link_pedido . '</small></p>
                    <p>Agradecemos por sua compra!</p>
                </div>
                <div class="footer">
                    <p>© 2026 Intelecto. Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>';

        return self::enviar($email, $assunto, $mensagem);
    }

    public static function enviarAcessoLiberadoAgendamento($email, $nome, $nomeCurso, $nomePlano, $idUsuario, $idPedido)
    {
        $assunto = 'Acesso liberado! Agende suas aulas - Intelecto';

        $textoWhatsapp = sprintf(
            'Olá, realizei o pagamento do curso %s pelo plano %s e gostaria de agendar minhas aulas! ID de usuário: %d. ID do pedido: %d',
            $nomeCurso,
            $nomePlano,
            (int)$idUsuario,
            (int)$idPedido
        );
        $linkWhatsapp = self::montarLinkWhatsapp('5535998421176', $textoWhatsapp);

        $mensagem = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #28a745; color: #fff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .button { display: inline-block; background-color: #25d366; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                .footer { background-color: #f0f0f0; padding: 10px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Acesso liberado!</h1>
                </div>
                <div class="content">
                    <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
                    <p>Seu pagamento foi aprovado e seu acesso já está liberado.</p>
                    <p>Agora você já pode agendar suas aulas pelo WhatsApp:</p>
                    <a href="' . htmlspecialchars($linkWhatsapp) . '" class="button">Agendar aulas no WhatsApp</a>
                    <p>Se o botão não funcionar, use este link:</p>
                    <p><small>' . htmlspecialchars($linkWhatsapp) . '</small></p>
                </div>
                <div class="footer">
                    <p>© 2026 Intelecto. Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>';

        return self::enviar($email, $assunto, $mensagem);
    }



    public static function getUltimoErro()
    {
        return self::$mailError;
    }



    public static function enviarPrimeiroAcesso($email, $nome, $token)
    {
        $link_reset = self::montarLinkReset($token);

        $assunto = 'Defina sua senha e comece na Intelecto';

        $mensagem = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #e01515; color: #fff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .button { display: inline-block; background-color: #dbdbdb; color: #111; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                .footer { background-color: #f0f0f0; padding: 10px; text-align: center; font-size: 12px; }
                .highlight { font-weight: bold; color: #e01515; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Bem-vindo(a) à Intelecto</h1>
                </div>
                <div class="content">
                    <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
                    <p>Recebemos seu interesse em nossos cursos. Para acessar sua conta e acompanhar pedidos, defina sua senha de primeiro acesso.</p>
                    <a href="' . $link_reset . '" class="button">Definir Minha Senha</a>
                    <p>Ou copie e cole este link no seu navegador:</p>
                    <p><small>' . $link_reset . '</small></p>
                    <p><span class="highlight">O link expira em 24 horas.</span></p>
                    <p>Se você não solicitou, pode ignorar este e-mail.</p>
                </div>
                <div class="footer">
                    <p>© 2026 Intelecto. Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>';

        return self::enviar($email, $assunto, $mensagem);
    }



    private static function getAppUrl()
    {
        $siteUrl = '';

        $env_content = site_env_content();
        if ($env_content !== null) {

            if (preg_match('/^SITE_URL=(.+)$/m', $env_content, $match)) {
                $siteUrl = trim($match[1], '\'"');
            }

            if ($siteUrl === '' && preg_match('/^APP_URL=(.+)$/m', $env_content, $match)) {
                $appUrl = trim($match[1], '\'"');
                $lowerAppUrl = strtolower($appUrl);
                $isWebhookUrl = strpos($lowerAppUrl, 'webhook.site') !== false
                    || strpos($lowerAppUrl, 'ngrok') !== false
                    || strpos($lowerAppUrl, 'devtunnels') !== false
                    || strpos($lowerAppUrl, '/api/webhooks/pagbank') !== false;

                if (!$isWebhookUrl) {
                    $siteUrl = $appUrl;
                }
            }
        }

        if ($siteUrl !== '') {
            return rtrim($siteUrl, '/') . '/';
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        $scheme = $isHttps ? 'https' : 'http';

        if ($host !== '') {
            return $scheme . '://' . $host . site_base_path() . '/';
        }

        return 'https://cursosintelecto.com.br/';
    }



    private static function montarLinkReset($token)
    {
        return self::getAppUrl() . 'reset-password.php?token=' . urlencode((string)$token);
    }

    private static function montarLinkWhatsapp($numero, $mensagem)
    {
        $numeroLimpo = preg_replace('/\D+/', '', (string)$numero);
        return 'https://wa.me/' . $numeroLimpo . '?text=' . rawurlencode((string)$mensagem);
    }
}
