<?php
declare(strict_types=1);

/**
 * Send a plain-text email. Uses PHPMailer over SMTP when it is installed and mail.smtp.enabled is true,
 * otherwise falls back to PHP's mail() (works on GoDaddy cPanel hosting).
 * Returns true on success. Failures are logged, never shown to visitors.
 */
function send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    if (!valid_email($to)) {
        return false;
    }
    $fromEmail = (string) cfg('mail.from_email');
    $fromName  = (string) cfg('mail.from_name', cfg('app.name'));
    $subject   = str_replace(["\r", "\n"], ' ', $subject);
    $replyTo   = ($replyTo && valid_email($replyTo)) ? $replyTo : null;

    $autoload = APP_ROOT . '/vendor/autoload.php';
    if (cfg('mail.smtp.enabled') && is_file($autoload)) {
        require_once $autoload;
        try {
            $m = new PHPMailer\PHPMailer\PHPMailer(true);
            $m->isSMTP();
            $m->Host       = (string) cfg('mail.smtp.host');
            $m->Port       = (int) cfg('mail.smtp.port', 465);
            $m->SMTPAuth   = true;
            $m->Username   = (string) cfg('mail.smtp.username');
            $m->Password   = (string) cfg('mail.smtp.password');
            $m->SMTPSecure = (string) cfg('mail.smtp.secure', 'ssl');
            $m->CharSet    = 'UTF-8';
            $m->setFrom($fromEmail, $fromName);
            $m->addAddress($to);
            if ($replyTo) {
                $m->addReplyTo($replyTo);
            }
            $m->Subject = $subject;
            $m->Body    = $body;
            $m->send();
            return true;
        } catch (Throwable $e) {
            error_log('[sap mail] SMTP failed: ' . $e->getMessage());
            return false;
        }
    }

    $headers = [
        'From: ' . mb_encode_mimeheader($fromName) . ' <' . $fromEmail . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    if ($replyTo) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    if (PHP_SAPI === 'cli-server' || cfg('mail.log_only')) {
        // Local development: write the email to a log instead of sending.
        error_log("[sap mail] To: $to | Subject: $subject\n$body");
        return true;
    }
    $ok = @mail($to, mb_encode_mimeheader($subject), $body, implode("\r\n", $headers), '-f' . $fromEmail);
    if (!$ok) {
        error_log('[sap mail] mail() failed for ' . $to);
    }
    return $ok;
}

function notify_business(string $subject, string $body, ?string $replyTo = null): bool
{
    return send_mail((string) cfg('business.email'), $subject, $body, $replyTo);
}

function mail_footer(): string
{
    return "\n\n--\n" . cfg('app.name') . "\n" . abs_url('/');
}
