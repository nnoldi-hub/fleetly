<?php

class Mailer {
    private static function config(): array {
        $cfgPath = __DIR__ . '/../config/mail.php';
        if (!file_exists($cfgPath)) { // try project root config
            $cfgPath = __DIR__ . '/../config/mail.php';
        }
        $conf = @include $cfgPath;
        return is_array($conf) ? $conf : ['enabled' => false];
    }

    private static function logFallback(string $to, string $subject, string $body, ?string $error = null): void {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $line = date('c') . ' TO=' . $to . ' SUBJ=' . $subject . ' ERROR=' . ($error ?: 'none') . "\n";
        $line .= $body . "\n---\n";
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'mail.log', $line, FILE_APPEND);
    }

    public static function send(string $to, string $subject, string $htmlBody, ?string $textBody = null, array $opts = []): bool {
        $conf = self::config();
        if (empty($conf['enabled'])) {
            self::logFallback($to, $subject, $textBody ?: strip_tags($htmlBody));
            return true; // consider ok in disabled mode
        }

        $fromEmail = $conf['from']['email'] ?? 'no-reply@example.com';
        $fromName  = $conf['from']['name'] ?? 'Fleet Management';

        // Prefer PHPMailer if available (via Composer)
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                if (($conf['driver'] ?? 'smtp') === 'smtp') {
                    $mail->isSMTP();
                    $mail->Host = $conf['smtp']['host'] ?? '';
                    $mail->Port = (int)($conf['smtp']['port'] ?? 587);
                    $mail->SMTPAuth = true;
                    $mail->Username = $conf['smtp']['username'] ?? '';
                    $mail->Password = $conf['smtp']['password'] ?? '';
                    $enc = $conf['smtp']['encryption'] ?? 'tls';
                    if ($enc) { $mail->SMTPSecure = $enc; }
                    $mail->Timeout = (int)($conf['smtp']['timeout'] ?? 10);
                } else {
                    $mail->isMail();
                }

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($to);
                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body = $htmlBody;
                $mail->AltBody = $textBody ?: strip_tags($htmlBody);
                $mail->send();
                return true;
            } catch (\Throwable $e) {
                self::logFallback($to, $subject, $textBody ?: strip_tags($htmlBody), $e->getMessage());
                return false;
            }
        }

        // Fallback to mail()
        $headers = [];
        $headers[] = 'From: ' . ($fromName ? ($fromName . ' <' . $fromEmail . '>') : $fromEmail);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $ok = @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
        if (!$ok) {
            self::logFallback($to, $subject, $textBody ?: strip_tags($htmlBody), 'mail() failed');
        }
        return $ok;
    }
}
