<?php
// modules/notifications/models/NotificationTemplate.php

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Model.php';

class NotificationTemplate extends Model {
    protected $table = 'notification_templates';
    
    /**
     * Get template by slug (cu priority pe customizare per companie)
     * @param string $slug document_expiry, insurance_expiry, etc.
     * @param int|null $companyId null = global template
     * @return array|null
     */
    public function getBySlug($slug, $companyId = null) {
        // Prioritate: template custom per companie > template global
        if ($companyId) {
            $sql = "SELECT * FROM notification_templates 
                    WHERE slug = ? AND (company_id = ? OR company_id IS NULL)
                    ORDER BY company_id DESC, id DESC
                    LIMIT 1";
            $result = $this->db->fetch($sql, [$slug, $companyId]);
        } else {
            $sql = "SELECT * FROM notification_templates 
                    WHERE slug = ? AND company_id IS NULL AND enabled = 1
                    LIMIT 1";
            $result = $this->db->fetch($sql, [$slug]);
        }
        
        if ($result && !empty($result['available_variables'])) {
            $result['available_variables'] = json_decode($result['available_variables'], true) ?: [];
        }
        
        return $result ?: null;
    }
    
    /**
     * Render template cu variabile (substitution engine)
     * @param string $slug
     * @param array $variables ex: ['vehicle_plate' => 'B-123-ABC', 'days_until_expiry' => 15]
     * @param string $channel email|sms|push|in_app
     * @param int|null $companyId
     * @return array|null ['subject' => '...', 'body' => '...']
     */
    public function render($slug, $variables, $channel = 'email', $companyId = null) {
        $template = $this->getBySlug($slug, $companyId);
        
        if (!$template) {
            // Fallback: generate from variables if template missing
            return $this->generateFallback($slug, $variables, $channel);
        }
        
        if (!$template['enabled']) {
            return null; // Template dezactivat
        }
        
        // Select content fields based on channel
        switch ($channel) {
            case 'email':
                $subject = $template['email_subject'] ?? '';
                $body = $template['email_body'] ?? '';
                break;
            case 'sms':
                $subject = null;
                $body = $template['sms_body'] ?? '';
                break;
            case 'push':
                $subject = $template['push_title'] ?? '';
                $body = $template['push_body'] ?? '';
                break;
            case 'in_app':
                $subject = $template['in_app_title'] ?? '';
                $body = $template['in_app_message'] ?? '';
                break;
            default:
                $subject = $template['email_subject'] ?? '';
                $body = $template['email_body'] ?? '';
        }
        
        // Replace variables
        $subject = $this->replaceVariables($subject, $variables);
        $body = $this->replaceVariables($body, $variables);
        
        return [
            'subject' => $subject,
            'body' => $body,
            'priority' => $template['default_priority'] ?? 'medium',
            'template_id' => $template['id']
        ];
    }
    
    /**
     * Replace variabile în text: {{variable_name}} → valoare
     */
    private function replaceVariables($text, $variables) {
        if (empty($text)) return '';
        
        foreach ($variables as $key => $value) {
            // Convert value to string
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        
        // Remove unreplaced variables (optional: show placeholder sau gol)
        $text = preg_replace('/\{\{[^}]+\}\}/', '', $text);
        
        return $text;
    }
    
    /**
     * Generate fallback message dacă template lipsește
     */
    private function generateFallback($slug, $variables, $channel) {
        $defaults = [
            'document_expiry' => [
                'subject' => 'Document în expirare: ' . ($variables['document_type'] ?? 'document'),
                'body' => sprintf(
                    'Documentul %s pentru vehiculul %s expiră în %s zile (%s).',
                    $variables['document_type'] ?? 'necunoscut',
                    $variables['vehicle_plate'] ?? 'necunoscut',
                    $variables['days_until_expiry'] ?? '?',
                    $variables['expiry_date'] ?? '?'
                )
            ],
            'insurance_expiry' => [
                'subject' => 'Asigurare în expirare: ' . ($variables['insurance_type'] ?? 'asigurare'),
                'body' => sprintf(
                    'Asigurarea %s pentru vehiculul %s expiră în %s zile (%s). Reînnoiți urgent!',
                    $variables['insurance_type'] ?? 'necunoscută',
                    $variables['vehicle_plate'] ?? 'necunoscut',
                    $variables['days_until_expiry'] ?? '?',
                    $variables['expiry_date'] ?? '?'
                )
            ],
            'maintenance_due' => [
                'subject' => 'Mentenanță scadentă: ' . ($variables['vehicle_plate'] ?? 'vehicul'),
                'body' => sprintf(
                    'Vehiculul %s necesită mentenanță: %s. Scadență: %s',
                    $variables['vehicle_plate'] ?? 'necunoscut',
                    $variables['maintenance_type'] ?? 'necunoscută',
                    $variables['due_date'] ?? '?'
                )
            ]
        ];
        
        if (isset($defaults[$slug])) {
            return array_merge($defaults[$slug], ['priority' => 'medium', 'template_id' => null]);
        }
        
        return [
            'subject' => 'Notificare Fleet Management',
            'body' => 'Aveți o notificare nouă.',
            'priority' => 'medium',
            'template_id' => null
        ];
    }
    
    /**
     * Get all active templates (global + per company)
     */
    public function getAllActive($companyId = null) {
        $sql = "SELECT * FROM notification_templates WHERE enabled = 1";
        $params = [];
        
        if ($companyId) {
            $sql .= " AND (company_id = ? OR company_id IS NULL)";
            $params[] = $companyId;
        } else {
            $sql .= " AND company_id IS NULL";
        }
        
        $sql .= " ORDER BY slug ASC, company_id DESC";
        
        try {
            $results = $this->db->fetchAll($sql, $params);
            
            foreach ($results as &$result) {
                if (!empty($result['available_variables'])) {
                    $result['available_variables'] = json_decode($result['available_variables'], true) ?: [];
                }
            }
            
            return $results;
        } catch (Throwable $e) {
            return [];
        }
    }
    
    /**
     * Create template
     */
    public function create($data) {
        // Validate slug
        if (empty($data['slug']) || !preg_match('/^[a-z0-9_]+$/', $data['slug'])) {
            return ['success' => false, 'message' => 'Invalid slug format'];
        }
        
        // Check duplicate
        $existing = $this->getBySlug($data['slug'], $data['company_id'] ?? null);
        if ($existing) {
            return ['success' => false, 'message' => 'Template with this slug already exists'];
        }
        
        // Encode available_variables
        if (isset($data['available_variables']) && is_array($data['available_variables'])) {
            $data['available_variables'] = json_encode($data['available_variables']);
        }
        
        $sql = "INSERT INTO notification_templates 
                (slug, name, description, email_subject, email_body, sms_body, 
                 push_title, push_body, in_app_title, in_app_message,
                 available_variables, default_priority, enabled, company_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['slug'],
            $data['name'],
            $data['description'] ?? null,
            $data['email_subject'] ?? null,
            $data['email_body'] ?? null,
            $data['sms_body'] ?? null,
            $data['push_title'] ?? null,
            $data['push_body'] ?? null,
            $data['in_app_title'] ?? null,
            $data['in_app_message'] ?? null,
            $data['available_variables'] ?? '[]',
            $data['default_priority'] ?? 'medium',
            $data['enabled'] ?? 1,
            $data['company_id'] ?? null
        ];
        
        try {
            $this->db->query($sql, $params);
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Insert failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update template
     */
    public function update($id, $data) {
        $updates = [];
        $params = [];
        
        $allowedFields = [
            'name', 'description', 'email_subject', 'email_body', 'sms_body',
            'push_title', 'push_body', 'in_app_title', 'in_app_message',
            'available_variables', 'default_priority', 'enabled'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'available_variables' && is_array($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = json_encode($data[$field]);
                } else {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }
        
        $params[] = $id;
        $sql = "UPDATE notification_templates SET " . implode(', ', $updates) . " WHERE id = ?";
        
        try {
            $this->db->query($sql, $params);
            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete template
     */
    public function delete($id) {
        $sql = "DELETE FROM notification_templates WHERE id = ?";
        
        try {
            $this->db->query($sql, [$id]);
            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Clone template (global → per company customization)
     */
    public function cloneForCompany($sourceId, $companyId) {
        $source = $this->db->fetch("SELECT * FROM notification_templates WHERE id = ?", [$sourceId]);
        
        if (!$source) {
            return ['success' => false, 'message' => 'Source template not found'];
        }
        
        // Check dacă deja există template custom pentru această companie
        $existing = $this->getBySlug($source['slug'], $companyId);
        if ($existing && $existing['company_id'] == $companyId) {
            return ['success' => false, 'message' => 'Company-specific template already exists'];
        }
        
        $data = [
            'slug' => $source['slug'],
            'name' => $source['name'] . ' (Customizat)',
            'description' => $source['description'],
            'email_subject' => $source['email_subject'],
            'email_body' => $source['email_body'],
            'sms_body' => $source['sms_body'],
            'push_title' => $source['push_title'],
            'push_body' => $source['push_body'],
            'in_app_title' => $source['in_app_title'],
            'in_app_message' => $source['in_app_message'],
            'available_variables' => $source['available_variables'],
            'default_priority' => $source['default_priority'],
            'enabled' => 1,
            'company_id' => $companyId
        ];
        
        return $this->create($data);
    }
    
    /**
     * Test template rendering cu date sample
     */
    public function testRender($templateId, $sampleData = null) {
        $template = $this->db->fetch("SELECT * FROM notification_templates WHERE id = ?", [$templateId]);
        
        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }
        
        // Sample data default
        if (!$sampleData) {
            $sampleData = [
                'vehicle_plate' => 'B-123-ABC',
                'document_type' => 'ITP',
                'insurance_type' => 'RCA',
                'maintenance_type' => 'Revizie periodică',
                'days_until_expiry' => 15,
                'expiry_date' => date('d.m.Y', strtotime('+15 days')),
                'due_date' => date('d.m.Y', strtotime('+7 days')),
                'current_km' => '125000',
                'action_url' => 'https://example.com/action'
            ];
        }
        
        $channels = ['email', 'sms', 'push', 'in_app'];
        $rendered = [];
        
        foreach ($channels as $channel) {
            $result = $this->render($template['slug'], $sampleData, $channel, $template['company_id']);
            $rendered[$channel] = $result;
        }
        
        return ['success' => true, 'rendered' => $rendered, 'sample_data' => $sampleData];
    }
}
?>
