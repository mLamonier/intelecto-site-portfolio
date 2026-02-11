<?php

require_once __DIR__ . '/../../includes/site.php';

class Mailer
{
    private $to;
    private $subject;
    private $message;
    private $headers;

    public function __construct()
    {
        $this->headers = "MIME-Version: 1.0\r\n";
        $this->headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $this->headers .= "From: noreply@intelecto.com.br\r\n";
    }

    

    public static function enviarRecuperacaoSenha($email, $nome, $token)
    {
        $link = self::getAdminResetUrl($token);

        $assunto = "Recuperação de Senha - Intelecto";
        $mensagem = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px; border-radius: 8px;'>
                <h2 style='color: #dc2626;'>Recuperação de Senha</h2>
                <p>Olá <strong>{$nome}</strong>,</p>
                <p>Você solicitou a recuperação de sua senha na plataforma Intelecto.</p>
                <p style='margin: 20px 0;'>
                    <a href='{$link}' style='display: inline-block; background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                        Redefinir Senha
                    </a>
                </p>
                <p style='color: #666; font-size: 13px;'>
                    Esse link expira em 24 horas.<br>
                    Se você não solicitou, ignore este e-mail.
                </p>
                <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                <p style='color: #999; font-size: 12px;'>
                    © 2026 Intelecto Profissionalizantes. Todos os direitos reservados.
                </p>
            </div>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@intelecto.com.br\r\n";

        return mail($email, $assunto, $mensagem, $headers);
    }

    private static function getAdminResetUrl($token)
    {
        return self::getSiteUrl() . 'admin/reset-password.php?token=' . urlencode((string)$token);
    }

    private static function getSiteUrl()
    {
        $siteUrl = '';
        $envContent = site_env_content();

        if ($envContent !== null) {
            if (preg_match('/^SITE_URL=(.+)$/m', $envContent, $match)) {
                $siteUrl = trim($match[1], '\'"');
            }

            if ($siteUrl === '' && preg_match('/^APP_URL=(.+)$/m', $envContent, $match)) {
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
}
