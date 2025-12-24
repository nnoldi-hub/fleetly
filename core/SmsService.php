<?php
// core/SmsService.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

class SmsService {
    private $db;
    private $settings;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadSettings();
    }
    
    /**
     * Încarcă setările SMS din baza de date sau configurare
     */
    private function loadSettings() {
        try {
            $row = $this->db->fetch(
                "SELECT setting_value FROM system_settings WHERE setting_key = 'sms_settings'"
            );
            
            $defaults = [
                'provider' => 'twilio',
                'enabled' => false,
                'from' => '',
                'account_sid' => '',
                'auth_token' => '',
                'http_url' => '',
                'http_method' => 'GET',
                'http_params' => '',
                'sms_default_to' => ''
            ];
            
            if ($row && !empty($row['setting_value'])) {
                $decoded = json_decode($row['setting_value'], true);
                if (is_array($decoded)) {
                    $this->settings = array_merge($defaults, $decoded);
                    return;
                }
            }
            
            $this->settings = $defaults;
        } catch (Throwable $e) {
            error_log("[SmsService] Eroare la încărcarea setărilor: " . $e->getMessage());
            $this->settings = [
                'provider' => 'twilio',
                'enabled' => false,
                'from' => '',
                'account_sid' => '',
                'auth_token' => ''
            ];
        }
    }
    
    /**
     * Verifică dacă serviciul SMS este configurat și activ
     */
    public function isConfigured(): bool {
        if (($this->settings['provider'] ?? 'twilio') === 'twilio') {
            return !empty($this->settings['account_sid']) && 
                   !empty($this->settings['auth_token']) && 
                   !empty($this->settings['from']);
        }
        
        // Pentru HTTP gateway
        return !empty($this->settings['http_url']);
    }
    
    /**
     * Normalizează numărul de telefon la formatul E.164
     */
    private function normalizePhoneNumber(string $phone): array {
        $originalPhone = $phone;
        
        // Elimină spații, liniuțe, paranteze și puncte
        $phone = preg_replace('/[\s\-\(\)\.]/', '', $phone);
        
        // Convertește 00 în +
        if (strpos($phone, '00') === 0) {
            $phone = '+' . substr($phone, 2);
        }
        
        // Adaugă + la început dacă lipsește
        if ($phone !== '' && $phone[0] !== '+') {
            $phone = '+' . $phone;
        }
        
        // Validează formatul E.164 (+XXXXXXXXXXX)
        if (!preg_match('/^\+[0-9]{8,15}$/', $phone)) {
            return [
                'success' => false, 
                'error' => "Număr de telefon invalid: $originalPhone (se așteaptă format +XXXXXXXXXXX)"
            ];
        }
        
        return ['success' => true, 'phone' => $phone];
    }
    
    /**
     * Trimite SMS prin Twilio
     */
    private function sendViaTwilio(string $to, string $message): array {
        try {
            $sid = $this->settings['account_sid'] ?? '';
            $token = $this->settings['auth_token'] ?? '';
            $from = $this->settings['from'] ?? '';
            
            if (empty($sid) || empty($token) || empty($from)) {
                return [
                    'success' => false, 
                    'error' => 'Configurați Twilio: Account SID, Auth Token și From Number în setările de notificări.'
                ];
            }
            
            $client = new Client($sid, $token);
            
            $twilioMessage = $client->messages->create(
                $to,
                [
                    'from' => $from,
                    'body' => $message
                ]
            );
            
            return [
                'success' => true,
                'sid' => $twilioMessage->sid,
                'status' => $twilioMessage->status,
                'message' => 'SMS trimis cu succes prin Twilio'
            ];
            
        } catch (TwilioException $e) {
            error_log("[SmsService] Twilio Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Eroare Twilio: ' . $e->getMessage()
            ];
        } catch (Throwable $e) {
            error_log("[SmsService] Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Eroare la trimiterea SMS: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Trimite SMS prin HTTP gateway generic
     */
    private function sendViaHttp(string $to, string $message): array {
        try {
            $url = $this->settings['http_url'] ?? '';
            
            if (empty($url)) {
                return [
                    'success' => false,
                    'error' => 'Configurați URL-ul gateway-ului HTTP în setările de notificări.'
                ];
            }
            
            $method = strtoupper($this->settings['http_method'] ?? 'GET');
            $paramsTemplate = $this->settings['http_params'] ?? '';
            
            // Înlocuiește placeholders
            $params = str_replace(
                ['{to}', '{message}'],
                [urlencode($to), urlencode($message)],
                $paramsTemplate
            );
            
            $ch = curl_init();
            
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            } else {
                $separator = (strpos($url, '?') === false) ? '?' : '&';
                curl_setopt($ch, CURLOPT_URL, $url . $separator . $params);
            }
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'message' => 'SMS trimis cu succes prin HTTP gateway',
                    'response' => $response
                ];
            }
            
            return [
                'success' => false,
                'error' => "Gateway HTTP răspuns $httpCode: $response" . ($error ? " (Eroare cURL: $error)" : '')
            ];
            
        } catch (Throwable $e) {
            error_log("[SmsService] HTTP Gateway Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Eroare la trimiterea SMS prin HTTP: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Trimite un SMS
     * 
     * @param string $to Număr de telefon destinatar (format E.164 preferat: +40712345678)
     * @param string $message Mesajul SMS (max 160 caractere pentru 1 SMS)
     * @param array|null $customSettings Setări personalizate (opțional)
     * @return array ['success' => bool, 'error' => string|null, 'data' => array|null]
     */
    public function send(string $to, string $message, ?array $customSettings = null): array {
        // Folosește setări personalizate dacă sunt furnizate
        if ($customSettings !== null) {
            $oldSettings = $this->settings;
            $this->settings = array_merge($this->settings, $customSettings);
        }
        
        // Validează configurarea
        if (!$this->isConfigured()) {
            $result = [
                'success' => false,
                'error' => 'Serviciul SMS nu este configurat. Accesați setările de notificări pentru a configura Twilio.'
            ];
            if (isset($oldSettings)) {
                $this->settings = $oldSettings;
            }
            return $result;
        }
        
        // Normalizează numărul de telefon
        $normalized = $this->normalizePhoneNumber($to);
        if (!$normalized['success']) {
            if (isset($oldSettings)) {
                $this->settings = $oldSettings;
            }
            return $normalized;
        }
        
        $to = $normalized['phone'];
        
        // Limitează mesajul la 160 caractere pentru a evita costuri mari
        if (mb_strlen($message) > 160) {
            $message = mb_substr($message, 0, 157) . '...';
        }
        
        // Trimite prin provider-ul configurat
        $provider = $this->settings['provider'] ?? 'twilio';
        
        if ($provider === 'twilio') {
            $result = $this->sendViaTwilio($to, $message);
        } else {
            $result = $this->sendViaHttp($to, $message);
        }
        
        // Restaurează setările originale dacă au fost folosite setări personalizate
        if (isset($oldSettings)) {
            $this->settings = $oldSettings;
        }
        
        // Loghează rezultatul
        if ($result['success']) {
            error_log("[SmsService] SMS trimis cu succes către $to");
        } else {
            error_log("[SmsService] Eroare la trimiterea SMS către $to: " . ($result['error'] ?? 'Unknown error'));
        }
        
        return $result;
    }
    
    /**
     * Obține setările curente
     */
    public function getSettings(): array {
        return $this->settings;
    }
    
    /**
     * Actualizează setările
     */
    public function updateSettings(array $newSettings): bool {
        try {
            $this->settings = array_merge($this->settings, $newSettings);
            
            $value = json_encode($this->settings);
            
            $this->db->query(
                "INSERT INTO system_settings (setting_key, setting_value, setting_type, description) 
                 VALUES ('sms_settings', ?, 'json', 'Setări SMS') 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$value, $value]
            );
            
            return true;
        } catch (Throwable $e) {
            error_log("[SmsService] Eroare la actualizarea setărilor: " . $e->getMessage());
            return false;
        }
    }
}
