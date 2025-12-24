<?php
// modules/notifications/services/Notifier.php

require_once __DIR__ . '/../../../core/Database.php';

class Notifier {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function loadSmtpSettings() {
        $row = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'smtp_settings'");
        $smtp = [
            'transport' => 'smtp',
            'host' => defined('EMAIL_HOST') ? EMAIL_HOST : 'smtp.example.com',
            'port' => defined('EMAIL_PORT') ? EMAIL_PORT : 587,
            'encryption' => 'tls',
            'username' => defined('EMAIL_USERNAME') ? EMAIL_USERNAME : '',
            'password' => defined('EMAIL_PASSWORD') ? EMAIL_PASSWORD : '',
            'from_email' => defined('EMAIL_USERNAME') ? EMAIL_USERNAME : 'noreply@example.com',
            'from_name' => defined('APP_NAME') ? APP_NAME : 'Fleet Management',
        ];
        if ($row && !empty($row['setting_value'])) {
            $dec = json_decode($row['setting_value'], true);
            if (is_array($dec)) { $smtp = array_replace($smtp, $dec); }
        }
        return $smtp;
    }

    public function loadSmsSettings() {
        $row = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'sms_settings'");
        $sms = [
            'provider' => 'twilio',
            'from' => '',
            'account_sid' => '',
            'auth_token' => '',
            'http_url' => '',
            'http_method' => 'GET',
            'http_params' => ''
        ];
        if ($row && !empty($row['setting_value'])) {
            $dec = json_decode($row['setting_value'], true);
            if (is_array($dec)) { $sms = array_replace($sms, $dec); }
        }
        return $sms;
    }

    public function sendEmail($to, $subject, $body, $smtp = null) {
        $smtp = $smtp ?: $this->loadSmtpSettings();
        if (($smtp['transport'] ?? 'smtp') === 'php_mail') {
            $headers = "From: " . ($smtp['from_name'] ?: 'No-Reply') . " <" . ($smtp['from_email'] ?: 'no-reply@example.com') . ">\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $ok = @mail($to, $subject, $body, $headers);
            return [$ok, $ok ? '' : 'Trimiterea prin mail() a eșuat'];
        }
        try {
            $host = $smtp['host'];
            $port = (int)$smtp['port'];
            $encr = strtolower($smtp['encryption'] ?? 'tls');
            $remote = ($encr === 'ssl') ? "ssl://{$host}:{$port}" : "{$host}:{$port}";
            $errno = 0; $errstr = '';
            $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
            if (!$fp) { return [false, "Conexiunea SMTP a eșuat: $errstr ($errno)"]; }
            stream_set_timeout($fp, 15);
            $r = function() use ($fp) { return fgets($fp, 512); };
            $w = function($cmd) use ($fp) { fwrite($fp, $cmd."\r\n"); };
            
            // Initial handshake
            $r(); // Banner 220
            $w('EHLO localhost'); 
            
            // Citim toate liniile EHLO (poate fi multi-line)
            $ehloResp = '';
            do {
                $line = $r();
                $ehloResp .= $line;
            } while (preg_match('/^250-/', $line));
            
            // STARTTLS dacă este necesar
            if ($encr === 'tls') {
                $w('STARTTLS'); $resp = $r();
                if (strpos($resp, '220') !== 0) { 
                    fclose($fp); 
                    return [false, 'Serverul nu acceptă STARTTLS. Răspuns: ' . trim($resp)]; 
                }
                
                // Încercăm mai multe metode de criptare TLS
                $cryptoMethods = [
                    STREAM_CRYPTO_METHOD_TLS_CLIENT,
                    STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
                    STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
                ];
                
                $tlsSuccess = false;
                foreach ($cryptoMethods as $method) {
                    if (@stream_socket_enable_crypto($fp, true, $method)) {
                        $tlsSuccess = true;
                        break;
                    }
                }
                
                if (!$tlsSuccess) { 
                    fclose($fp); 
                    return [false, 'Negocierea TLS a eșuat. Încercați SSL pe port 465 sau fără criptare pe port 25.']; 
                }
                
                $w('EHLO localhost'); 
                // Citim din nou EHLO după TLS
                do {
                    $line = $r();
                    $ehloResp .= $line;
                } while (preg_match('/^250-/', $line));
            }
            
            // Autentificare
            if (!empty($smtp['username'])) {
                // Încercăm AUTH PLAIN (mai comun pe shared hosting)
                $authString = base64_encode("\0" . $smtp['username'] . "\0" . ($smtp['password'] ?? ''));
                $w('AUTH PLAIN ' . $authString); 
                $auth = $r();
                
                if (strpos($auth, '235') !== 0) { 
                    fclose($fp); 
                    return [false, 'Autentificare SMTP eșuată. Răspuns: ' . trim($auth)]; 
                }
            }
            $from = $smtp['from_email'] ?: $smtp['username'];
            $w('MAIL FROM:<'.$from.'>'); $r();
            $w('RCPT TO:<'.$to.'>'); $r();
            $w('DATA'); $r();
            $headers = 'From: ' . ($smtp['from_name'] ?: 'No-Reply') . ' <' . $from . ">\r\n" .
                       'To: <' . $to . ">\r\n" .
                       'Subject: ' . $subject . "\r\n" .
                       "MIME-Version: 1.0\r\n" .
                       "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $w($headers . $body . "\r\n.");
            $r();
            $w('QUIT');
            fclose($fp);
            return [true, ''];
        } catch (Throwable $e) { return [false, $e->getMessage()]; }
    }

    public function sendSms($to, $message, $sms = null) {
        // Folosim noua clasă SmsService pentru Twilio SDK
        require_once __DIR__ . '/../../../core/SmsService.php';
        
        try {
            $smsService = new SmsService();
            $result = $smsService->send($to, $message, $sms);
            
            if ($result['success']) {
                return [true, ''];
            } else {
                return [false, $result['error'] ?? 'Eroare necunoscută la trimiterea SMS'];
            }
        } catch (Throwable $e) {
            error_log("[Notifier] SMS Error: " . $e->getMessage());
            return [false, $e->getMessage()];
        }
    }
}
