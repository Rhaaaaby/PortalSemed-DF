<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public static function isConfigured(): bool
    {
        return self::configurationError() === null;
    }

    public static function configurationError(): ?string
    {
        foreach (['APP_URL', 'SMTP_HOST', 'SMTP_USER', 'SMTP_PASSWORD', 'MAIL_FROM_EMAIL', 'MAIL_FROM_NAME'] as $name) {
            if (trim((string) (getenv($name) ?: '')) === '') {
                return "A variável {$name} não está configurada no ambiente do portal.";
            }
        }

        $appUrl = (string) getenv('APP_URL');
        $urlParts = parse_url($appUrl);
        $port = filter_var(getenv('SMTP_PORT') ?: '587', FILTER_VALIDATE_INT);
        $security = strtolower((string) (getenv('SMTP_SECURE') ?: 'tls'));

        if (filter_var(getenv('MAIL_FROM_EMAIL'), FILTER_VALIDATE_EMAIL) === false) {
            return 'MAIL_FROM_EMAIL deve conter um endereço de e-mail válido, geralmente o mesmo valor de SMTP_USER.';
        }
        if (filter_var($appUrl, FILTER_VALIDATE_URL) === false ||
            !is_array($urlParts) ||
            !in_array(strtolower($urlParts['scheme'] ?? ''), ['http', 'https'], true) ||
            empty($urlParts['host']) ||
            isset($urlParts['user']) || isset($urlParts['pass']) ||
            isset($urlParts['query']) || isset($urlParts['fragment'])) {
            return 'APP_URL deve conter somente o endereço HTTP ou HTTPS do portal, sem credenciais, parâmetros ou fragmento.';
        }
        if ($port === false || $port < 1 || $port > 65535) {
            return 'SMTP_PORT deve ser uma porta válida entre 1 e 65535.';
        }
        if (!in_array($security, ['tls', 'ssl', 'none'], true)) {
            return 'SMTP_SECURE deve ser tls, ssl ou none.';
        }

        return null;
    }

    public static function sendPasswordReset(string $email, string $name, string $token): void
    {
        if (!self::isConfigured()) {
            throw new \RuntimeException('A configuração SMTP do sistema está incompleta.');
        }

        $url = rtrim((string) getenv('APP_URL'), '/') .
            '/recuperar-senha.html?token=' . rawurlencode($token);
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = (string) getenv('SMTP_USER');
        $mail->Password = (string) getenv('SMTP_PASSWORD');
        $mail->Port = (int) (getenv('SMTP_PORT') ?: 587);
        $security = strtolower((string) (getenv('SMTP_SECURE') ?: 'tls'));
        if ($security === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($security === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($security !== 'none') {
            throw new \RuntimeException('O modo de segurança SMTP configurado é inválido.');
        }

        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom((string) getenv('MAIL_FROM_EMAIL'), (string) getenv('MAIL_FROM_NAME'));
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de senha - Portal SEMED';
        $mail->Body = '<p>Olá, ' . $safeName . '.</p>' .
            '<p>Recebemos uma solicitação para redefinir a senha da sua conta no Portal SEMED.</p>' .
            '<p><a href="' . $safeUrl . '">Redefinir minha senha</a></p>' .
            '<p>Este link expira em 1 hora. Se você não solicitou a redefinição, ignore esta mensagem.</p>';
        $mail->AltBody = "Olá, {$name}.\n\n" .
            "Para redefinir a senha do Portal SEMED, acesse: {$url}\n\n" .
            'Este link expira em 1 hora. Se você não solicitou a redefinição, ignore esta mensagem.';
        $mail->send();
    }
}
