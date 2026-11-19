<?php

namespace App\Services;

use App\Models\PortalCustomization;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public static function isConfigured(): bool
    {
        return self::configurationError() === null;
    }

    public static function configurationError(): ?string
    {
        $transport = self::transport();
        if (!in_array($transport, ['smtp', 'brevo'], true)) {
            return 'MAIL_TRANSPORT deve ser smtp ou brevo.';
        }

        $requiredVariables = ['APP_URL', 'MAIL_FROM_EMAIL', 'MAIL_FROM_NAME'];
        $requiredVariables = array_merge(
            $requiredVariables,
            $transport === 'brevo'
                ? ['BREVO_API_KEY']
                : ['SMTP_HOST', 'SMTP_USER', 'SMTP_PASSWORD']
        );
        foreach ($requiredVariables as $name) {
            if (trim((string) (getenv($name) ?: '')) === '') {
                return "A variável {$name} não está configurada no ambiente do portal.";
            }
        }

        $appUrl = (string) getenv('APP_URL');
        $urlParts = parse_url($appUrl);

        if (filter_var(getenv('MAIL_FROM_EMAIL'), FILTER_VALIDATE_EMAIL) === false) {
            return 'MAIL_FROM_EMAIL deve conter um endereço válido cadastrado como remetente no provedor de e-mail.';
        }
        if (filter_var($appUrl, FILTER_VALIDATE_URL) === false ||
            !is_array($urlParts) ||
            !in_array(strtolower($urlParts['scheme'] ?? ''), ['http', 'https'], true) ||
            empty($urlParts['host']) ||
            isset($urlParts['user']) || isset($urlParts['pass']) ||
            isset($urlParts['query']) || isset($urlParts['fragment'])) {
            return 'APP_URL deve conter somente o endereço HTTP ou HTTPS do portal, sem credenciais, parâmetros ou fragmento.';
        }
        if ($transport === 'smtp') {
            $port = filter_var(getenv('SMTP_PORT') ?: '587', FILTER_VALIDATE_INT);
            $security = strtolower((string) (getenv('SMTP_SECURE') ?: 'tls'));
            if ($port === false || $port < 1 || $port > 65535) {
                return 'SMTP_PORT deve ser uma porta válida entre 1 e 65535.';
            }
            if (!in_array($security, ['tls', 'ssl', 'none'], true)) {
                return 'SMTP_SECURE deve ser tls, ssl ou none.';
            }
        }

        return null;
    }

    public static function sendPasswordReset(string $email, string $name, string $token): void
    {
        if (!self::isConfigured()) {
            throw new \RuntimeException('A configuração de envio de e-mail está incompleta.');
        }

        $url = rtrim((string) getenv('APP_URL'), '/') .
            '/recuperar-senha.html?token=' . rawurlencode($token);
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $branding = self::emailBranding(rtrim((string) getenv('APP_URL'), '/'));
        $safeLogoUrl = htmlspecialchars($branding['logo_url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $subject = 'Redefinição de senha - Portal SEMED';
        $html = self::passwordResetHtml(
            $safeName,
            $safeUrl,
            $safeLogoUrl,
            $branding['primary_color'],
            $branding['accent_color']
        );
        $text = "Olá, {$name}.\n\n" .
            "Para redefinir a senha do Portal SEMED, acesse este link: {$url}\n\n" .
            'Este link expira em 1 hora. Se você não solicitou a redefinição, ignore esta mensagem.';

        if (self::transport() === 'brevo') {
            self::sendViaBrevo($email, $name, $subject, $html, $text);
            return;
        }

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
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = $text;
        $mail->send();
    }

    private static function transport(): string
    {
        $transport = getenv('MAIL_TRANSPORT');

        return is_string($transport) && trim($transport) !== ''
            ? strtolower(trim($transport))
            : 'smtp';
    }

    private static function sendViaBrevo(
        string $email,
        string $name,
        string $subject,
        string $html,
        string $text
    ): void {
        $client = new Client([
            'base_uri' => 'https://api.brevo.com/v3/',
            'connect_timeout' => 5,
            'timeout' => 15,
        ]);

        try {
            $response = $client->post('smtp/email', [
                'headers' => [
                    'accept' => 'application/json',
                    'api-key' => (string) getenv('BREVO_API_KEY'),
                ],
                'json' => [
                    'sender' => [
                        'name' => (string) getenv('MAIL_FROM_NAME'),
                        'email' => (string) getenv('MAIL_FROM_EMAIL'),
                    ],
                    'to' => [
                        ['email' => $email, 'name' => $name],
                    ],
                    'subject' => $subject,
                    'htmlContent' => $html,
                    'textContent' => $text,
                ],
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Não foi possível conectar à API de e-mail do Brevo.', 0, $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException("O Brevo recusou o envio do e-mail (HTTP {$statusCode}).");
        }
    }

    private static function emailBranding(string $appUrl): array
    {
        $settings = (new PortalCustomization())->settings();
        $logoPath = $settings['logo_path'] ?? null;
        if (!is_string($logoPath) || trim($logoPath) === '') {
            throw new \RuntimeException('A logo do portal não está configurada para o e-mail.');
        }

        $logoPath = trim($logoPath);
        if ($logoPath === '/images/logo.png') {
            $logoUrl = $appUrl . $logoPath;
        } else {
            $publicLogo = ImageStorage::publicUrl($logoPath);
            if (!is_string($publicLogo) || $publicLogo === '') {
                throw new \RuntimeException('A logo configurada não tem um endereço público válido.');
            }
            $logoUrl = str_starts_with($publicLogo, '/')
                ? $appUrl . $publicLogo
                : $publicLogo;
        }

        $primaryColor = self::emailColor($settings['primary_color'] ?? null);
        $accentColor = self::emailColor($settings['accent_color'] ?? null);

        return [
            'logo_url' => $logoUrl,
            'primary_color' => $primaryColor,
            'accent_color' => $accentColor,
        ];
    }

    private static function emailColor(mixed $color): string
    {
        if (!is_string($color) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            throw new \RuntimeException('As cores do portal estão inválidas para o e-mail.');
        }

        return strtoupper($color);
    }

    private static function passwordResetHtml(
        string $safeName,
        string $safeUrl,
        string $safeLogoUrl,
        string $primaryColor,
        string $accentColor
    ): string {
        return <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de senha - Portal SEMED</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f5f4;font-family:Arial,Helvetica,sans-serif;color:#333333;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f3f5f4;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="width:100%;max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td align="center" style="padding:24px 24px 18px;background-color:{$primaryColor};">
                            <img src="{$safeLogoUrl}" alt="Logo SEMED" width="160" style="display:block;width:160px;max-width:100%;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="height:6px;background-color:{$accentColor};font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:32px 36px 36px;">
                            <h1 style="margin:0 0 20px;color:{$primaryColor};font-size:24px;line-height:1.3;">Redefinição de senha</h1>
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Olá, {$safeName}.</p>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.6;">Recebemos uma solicitação para redefinir a senha da sua conta no Portal SEMED. Clique no botão abaixo para escolher uma nova senha.</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td align="center" bgcolor="{$primaryColor}" style="border-radius:6px;">
                                        <a href="{$safeUrl}" style="display:inline-block;padding:14px 24px;border:1px solid {$primaryColor};border-radius:6px;color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;">Redefinir minha senha</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 12px;color:#555555;font-size:14px;line-height:1.6;">Este link expira em 1 hora.</p>
                            <p style="margin:0;color:#555555;font-size:14px;line-height:1.6;">Se você não solicitou a redefinição, ignore esta mensagem. Sua senha atual continuará funcionando.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:18px 24px;background-color:#f7f8f7;color:#666666;font-size:12px;line-height:1.5;">
                            Portal SEMED
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
