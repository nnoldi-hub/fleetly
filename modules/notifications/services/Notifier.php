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
            $r();
            $w('EHLO localhost'); $r();
            if ($encr === 'tls') {
                $w('STARTTLS'); $resp = $r();
                if (strpos($resp, '220') !== 0) { fclose($fp); return [false, 'Serverul nu acceptă STARTTLS']; }
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($fp); return [false, 'Negocierea TLS a eșuat']; }
                $w('EHLO localhost'); $r();
            }
            if (!empty($smtp['username'])) {
                $w('AUTH LOGIN'); $r();
                $w(base64_encode($smtp['username'])); $r();
                $w(base64_encode($smtp['password'] ?? '')); $auth = $r();
                if (strpos($auth, '235') !== 0) { 
                    fclose($fp); 
                    return [false, 'Autentificarea SMTP a eșuat. Răspuns server: ' . trim($auth)]; 
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
        $sms = $sms ?: $this->loadSmsSettings();
        // Normalize destination number to E.164-like format
        $toRaw = $to;
        $to = preg_replace('/[\s\-\(\)\.]/', '', (string)$to);
        if (strpos($to, '00') === 0) { $to = '+' . substr($to, 2); }
        if ($to !== '' && $to[0] !== '+') { $to = '+' . $to; }
        if (!preg_match('/^\+[0-9]{8,15}$/', $to)) {
            return [false, 'Număr de telefon invalid: ' . htmlspecialchars($toRaw) . ' (așteptat format +XXXXXXXXXXX)'];
        }
        try {
            if (($sms['provider'] ?? 'twilio') === 'twilio') {
                $sid = $sms['account_sid'] ?? '';
                $token = $sms['auth_token'] ?? '';
                $from = $sms['from'] ?? '';
                if (!$sid || !$token || !$from) return [false, 'Configurați Twilio: Account SID, Auth Token și From Number.'];
                $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
                $data = http_build_query(['To' => $to, 'From' => $from, 'Body' => $message]);
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_USERPWD => $sid . ':' . $token,
                    CURLOPT_TIMEOUT => 20
                ]);
                $res = curl_exec($ch);
                $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err = curl_error($ch);
                curl_close($ch);
                if ($http >= 200 && $http < 300) return [true, ''];
                return [false, 'Twilio API ' . $http . ' ' . $res . ($err? (' Err: '.$err):'')];
            }
            $url = $sms['http_url'] ?? '';
            if (!$url) return [false, 'Configurați URL-ul gateway-ului HTTP'];
            $method = strtoupper($sms['http_method'] ?? 'GET');
            $paramsT = $sms['http_params'] ?? '';
            $params = str_replace(['{to}','{message}'], [urlencode($to), urlencode($message)], $paramsT);
            $ch = curl_init($url);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            } else {
                $sep = (strpos($url, '?') === false) ? '?' : '&';
                curl_setopt($ch, CURLOPT_URL, $url . $sep . $params);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            $res = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($http >= 200 && $http < 300) return [true, ''];
            return [false, 'Gateway HTTP ' . $http . ' ' . $res . ($err? (' Err: '.$err):'')];
        } catch (Throwable $e) { return [false, $e->getMessage()]; }
    }
}
