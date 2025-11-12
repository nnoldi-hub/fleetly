# 🔔 Arhitectură Modernă Sistem Notificări Fleet-Management

## 1. Structura Bazei de Date

### 1.1. Tabel `notification_preferences` (NOU)
```sql
CREATE TABLE notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    
    -- Canale activate
    email_enabled TINYINT(1) DEFAULT 1,
    sms_enabled TINYINT(1) DEFAULT 0,
    push_enabled TINYINT(1) DEFAULT 0,
    in_app_enabled TINYINT(1) DEFAULT 1,
    
    -- Tipuri de notificări (JSON array: ["document_expiry", "insurance_expiry", "maintenance_due"])
    enabled_types JSON DEFAULT '["document_expiry","insurance_expiry","maintenance_due"]',
    
    -- Frecvență trimitere
    frequency ENUM('immediate', 'daily', 'weekly') DEFAULT 'immediate',
    
    -- Contact info (override pentru user.email/phone)
    email VARCHAR(255) NULL COMMENT 'Override email (dacă diferit de users.email)',
    phone VARCHAR(20) NULL COMMENT 'Override telefon pentru SMS',
    push_token VARCHAR(512) NULL COMMENT 'Firebase/OneSignal token pentru push',
    
    -- Prioritate minimă pentru notificări
    min_priority ENUM('low', 'medium', 'high') DEFAULT 'low',
    
    -- Broadcast la toată compania (doar pentru admin/manager)
    broadcast_to_company TINYINT(1) DEFAULT 0,
    
    -- Zile înainte de expirare pentru alertă
    days_before_expiry INT DEFAULT 30,
    
    -- Timezone pentru schedulare
    timezone VARCHAR(50) DEFAULT 'Europe/Bucharest',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_prefs (user_id),
    KEY idx_company (company_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Migrare din `system_settings`:**
- Key actual: `notifications_prefs_user_{id}` (JSON)
- Migrăm automat în `notification_preferences` cu script PHP

### 1.2. Tabel `notification_queue` (NOU)
```sql
CREATE TABLE notification_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL COMMENT 'FK la notifications.id',
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    
    channel ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    
    -- Date necesare pentru trimitere
    recipient_email VARCHAR(255) NULL,
    recipient_phone VARCHAR(20) NULL,
    recipient_push_token VARCHAR(512) NULL,
    
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    
    -- Status procesare
    status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    
    -- Schedulare
    scheduled_at TIMESTAMP NULL COMMENT 'Pentru frecvență daily/weekly',
    processed_at TIMESTAMP NULL,
    
    error_message TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_status_scheduled (status, scheduled_at),
    KEY idx_notification (notification_id),
    KEY idx_company (company_id),
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 1.3. Tabel `notification_templates` (NOU)
```sql
CREATE TABLE notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE COMMENT 'document_expiry, insurance_expiry, etc.',
    name VARCHAR(255) NOT NULL,
    
    -- Template per canal
    email_subject VARCHAR(255) NULL,
    email_body TEXT NULL,
    sms_body VARCHAR(160) NULL COMMENT 'Max 160 caractere',
    push_title VARCHAR(100) NULL,
    push_body VARCHAR(200) NULL,
    in_app_title VARCHAR(255) NULL,
    in_app_message TEXT NULL,
    
    -- Variabile disponibile (JSON array: ["vehicle_plate", "days_until_expiry", "document_type"])
    available_variables JSON DEFAULT '[]',
    
    -- Default priority
    default_priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    
    -- Activare/dezactivare
    enabled TINYINT(1) DEFAULT 1,
    
    -- Multi-tenancy: NULL = global (toate companiile), sau specific per company_id
    company_id INT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_slug (slug),
    KEY idx_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Populare template-uri default:**
```sql
INSERT INTO notification_templates (slug, name, email_subject, email_body, sms_body, in_app_title, in_app_message, available_variables, default_priority) VALUES
('document_expiry', 'Document în Expirare', 
 'Document {{document_type}} expiră în {{days_until_expiry}} zile', 
 'Bună ziua,\n\nDocumentul {{document_type}} pentru vehiculul {{vehicle_plate}} va expira în {{days_until_expiry}} zile ({{expiry_date}}).\n\nVă rugăm să îl reînnoiți cât mai curând.\n\nAccesați: {{action_url}}',
 'Document {{document_type}} pentru {{vehicle_plate}} expiră în {{days_until_expiry}} zile',
 'Document în expirare',
 'Documentul {{document_type}} pentru vehiculul {{vehicle_plate}} expiră în {{days_until_expiry}} zile.',
 '["vehicle_plate", "document_type", "days_until_expiry", "expiry_date", "action_url"]',
 'medium'),
 
('insurance_expiry', 'Asigurare în Expirare',
 'Asigurare {{insurance_type}} expiră în {{days_until_expiry}} zile',
 'Bună ziua,\n\nAsigurarea {{insurance_type}} pentru vehiculul {{vehicle_plate}} va expira în {{days_until_expiry}} zile ({{expiry_date}}).\n\nVă rugăm să o reînnoiți urgent.\n\nAccesați: {{action_url}}',
 'Asigurare {{insurance_type}} pt {{vehicle_plate}} expiră în {{days_until_expiry}} zile',
 'Asigurare în expirare',
 'Asigurarea {{insurance_type}} pentru vehiculul {{vehicle_plate}} expiră în {{days_until_expiry}} zile.',
 '["vehicle_plate", "insurance_type", "days_until_expiry", "expiry_date", "action_url"]',
 'high'),
 
('maintenance_due', 'Mentenanță Scadentă',
 'Mentenanță necesară pentru {{vehicle_plate}}',
 'Bună ziua,\n\nVehiculul {{vehicle_plate}} necesită mentenanță: {{maintenance_type}}.\n\nScadență: {{due_date}}\n\nProgramați serviciul: {{action_url}}',
 'Mentenanță {{vehicle_plate}}: {{maintenance_type}}',
 'Mentenanță scadentă',
 'Vehiculul {{vehicle_plate}} necesită mentenanță: {{maintenance_type}}.',
 '["vehicle_plate", "maintenance_type", "due_date", "action_url"]',
 'medium');
```

### 1.4. Actualizare Tabel `documents`
```sql
ALTER TABLE documents 
ADD COLUMN expiry_status ENUM('active', 'expiring_soon', 'expired') 
GENERATED ALWAYS AS (
    CASE 
        WHEN expiry_date < CURDATE() THEN 'expired'
        WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
        ELSE 'active'
    END
) STORED,
ADD INDEX idx_expiry_status (expiry_status);
```

---

## 2. Componente Software

### 2.1. NotificationPreferenceModel (NOU)
**Locație:** `modules/notifications/models/NotificationPreference.php`

```php
class NotificationPreference extends Model {
    protected $table = 'notification_preferences';
    
    // CRUD operations
    public function getByUserId($userId);
    public function createOrUpdate($userId, $data);
    public function getDefaultPreferences($userId, $companyId);
    
    // Migration helper
    public static function migrateFromSystemSettings($userId);
}
```

### 2.2. NotificationQueue Model (NOU)
**Locație:** `modules/notifications/models/NotificationQueue.php`

```php
class NotificationQueue extends Model {
    protected $table = 'notification_queue';
    
    public function enqueue($notificationId, $userId, $channel, $data);
    public function getPending($limit = 100);
    public function markAsSent($id);
    public function markAsFailed($id, $error);
    public function retryFailed();
}
```

### 2.3. NotificationTemplate Model (NOU)
**Locație:** `modules/notifications/models/NotificationTemplate.php`

```php
class NotificationTemplate extends Model {
    protected $table = 'notification_templates';
    
    public function getBySlug($slug, $companyId = null);
    public function render($slug, $variables, $channel = 'email');
    public function getAllActive($companyId = null);
    
    // Substitution engine
    private function replaceVariables($template, $vars);
}
```

### 2.4. DocumentStatusUpdater Service (NOU)
**Locație:** `modules/notifications/services/DocumentStatusUpdater.php`

```php
class DocumentStatusUpdater {
    /**
     * Marchează documente ca expired/expiring_soon automat
     * Rulează zilnic via cron
     */
    public function updateExpiredDocuments();
    public function updateExpiringInsurance();
    public function updateMaintenanceDue();
    
    // Logging
    public function logStatusChanges($changes);
}
```

### 2.5. NotificationQueueProcessor (NOU)
**Locație:** `modules/notifications/services/NotificationQueueProcessor.php`

```php
class NotificationQueueProcessor {
    /**
     * Procesează queue-ul de notificări
     * Rulează la fiecare 5 minute via cron
     */
    public function processQueue($limit = 100);
    public function processByChannel($channel);
    public function handleFailures();
    
    // Rate limiting pentru SMS/Email API
    private function checkRateLimit($channel);
}
```

---

## 3. Fluxuri de Lucru

### 3.1. Generare Notificări (Zilnic 06:00)
```
cron_generate_notifications.php
├── DocumentStatusUpdater::updateExpiredDocuments()
│   └── UPDATE documents SET status = 'expired' WHERE expiry_date < CURDATE()
│
├── NotificationGenerator::runForCompany($companyId)
│   ├── Query documents/insurance/maintenance cu expiry_status = 'expiring_soon'
│   ├── NotificationTemplate::render('document_expiry', $vars)
│   ├── Notification::create($data) → INSERT notifications
│   └── NotificationQueue::enqueue($notificationId, $userId, 'email', $data)
│
└── NotificationLog::log('cron_generation', 'success', [...])
```

### 3.2. Procesare Queue (La fiecare 5 minute)
```
scripts/process_notifications_queue.php
├── NotificationQueueProcessor::processQueue(100)
│   ├── SELECT FROM notification_queue WHERE status='pending' AND scheduled_at <= NOW() LIMIT 100
│   ├── Pentru fiecare:
│   │   ├── Check user preferences: NotificationPreference::getByUserId()
│   │   ├── Skip dacă canalul e dezactivat (email_enabled = 0)
│   │   ├── Notifier::sendEmail() sau sendSms() sau sendPush()
│   │   ├── UPDATE notification_queue SET status='sent', processed_at=NOW()
│   │   └── UPDATE notifications SET status='sent', sent_at=NOW()
│   │
│   └── Retry failed (attempts < max_attempts):
│       └── UPDATE notification_queue SET status='pending', attempts=attempts+1
│
└── NotificationLog::log('queue_processing', 'success', ['sent'=>N, 'failed'=>M])
```

### 3.3. Trimitere Imediată (La crearea manuală)
```
Notification::create($data)
├── INSERT INTO notifications (...)
├── NotificationPreference::getByUserId($userId)
├── Dacă frequency='immediate':
│   ├── Pentru fiecare canal activ (email/sms):
│   │   ├── NotificationQueue::enqueue(..., scheduled_at=NOW())
│   │   └── (procesare asincronă prin cron)
│   └── SAU trimitere directă (fallback dacă queue e dezactivat)
│
└── Dacă frequency='daily'/'weekly':
    └── NotificationQueue::enqueue(..., scheduled_at=CALCULATED_TIME)
```

---

## 4. Interfețe Utilizator

### 4.1. Setări Utilizator (`modules/notifications/views/preferences.php`)
**Formular per utilizator:**
- ☑️ **Canale:** Email | SMS | Push | In-App
- ☑️ **Tipuri:** Documente | Asigurări | Mentenanță | Alte alerte
- 🕒 **Frecvență:** Imediat | Zilnic (06:00) | Săptămânal (Luni 09:00)
- 📧 **Email:** user@company.ro (override)
- 📱 **Telefon:** +40 XXX XXX XXX (override)
- ⚡ **Prioritate minimă:** Low | Medium | High
- 📅 **Zile înainte expirare:** 30 zile (slider 7-90)
- 🌍 **Timezone:** Europe/Bucharest

### 4.2. Dashboard Superadmin (`modules/superadmin/views/notifications_dashboard.php`)
**Cross-tenant analytics:**
- 📊 Total notificări trimise (toate companiile)
- 📈 Rate de deschidere email/SMS per companie
- ⚠️ Failed notifications + retry status
- 🏢 Top 10 companii cu cele mai multe notificări
- 🔔 Forțare generare notificări pentru o companie
- 📤 Export rapoarte CSV/PDF

**Query exemplu:**
```sql
SELECT c.name, COUNT(n.id) as notifications_count, 
       SUM(CASE WHEN n.status='sent' THEN 1 ELSE 0 END) as sent_count
FROM companies c
LEFT JOIN notifications n ON c.id = n.company_id
WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY c.id
ORDER BY notifications_count DESC;
```

### 4.3. Template Manager Admin (`modules/notifications/views/templates.php`)
**CRUD pentru template-uri:**
- Lista toate template-urile (global + per company)
- Add/Edit template cu preview variabile
- Test template cu date sample
- Clone template global → customizare per companie

---

## 5. Securitate Multi-Tenancy

### 5.1. Verificări Obligatorii
```php
// În toate query-urile de notificări:
WHERE notifications.company_id = :current_company_id

// În NotificationQueue::processQueue():
$userCompanyId = Auth::getInstance()->user()->company_id;
WHERE notification_queue.company_id = $userCompanyId

// În Superadmin dashboard:
if (!Auth::isSuperAdmin()) {
    throw new UnauthorizedException();
}
```

### 5.2. Rate Limiting (Anti-spam)
```php
class NotificationRateLimiter {
    const MAX_EMAIL_PER_HOUR = 100;
    const MAX_SMS_PER_DAY = 50;
    
    public function checkLimit($companyId, $channel);
    public function incrementCounter($companyId, $channel);
}
```

---

## 6. Scalabilitate & Performance

### 6.1. Queue System (Opțional pentru viitor)
**Dacă flota crește > 1000 vehicule/companie:**
- Integrare **RabbitMQ** sau **Redis Queue**
- Worker procese PHP separate pentru trimitere
- Horizontal scaling: multiple workers

**Actualizare:**
```php
// În NotificationQueue::enqueue():
if (QUEUE_DRIVER === 'redis') {
    Redis::push('notifications:email', json_encode($payload));
} else {
    // Fallback DB queue
    $this->db->insert('notification_queue', $data);
}
```

### 6.2. Caching Preferințe
```php
// Cache preferințe utilizatori în Redis/Memcached
$prefs = Cache::remember('notification_prefs_' . $userId, 3600, function() use ($userId) {
    return NotificationPreference::getByUserId($userId);
});
```

### 6.3. Indexare Optimă
```sql
-- Index compus pentru query-uri frecvente
CREATE INDEX idx_queue_processing ON notification_queue(status, scheduled_at, company_id);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read, created_at);
```

---

## 7. Cron Jobs

### 7.1. Schedule Complet
```bash
# 1. Actualizare status documente + generare notificări (06:00 zilnic)
0 6 * * * /usr/local/bin/php /home/wclsgzyf/public_html/scripts/cron_generate_notifications.php >> /home/wclsgzyf/public_html/logs/cron_generate.log 2>&1

# 2. Procesare queue notificări (la fiecare 5 minute)
*/5 * * * * /usr/local/bin/php /home/wclsgzyf/public_html/scripts/process_notifications_queue.php >> /home/wclsgzyf/public_html/logs/cron_queue.log 2>&1

# 3. Retry failed notifications (la fiecare oră)
0 * * * * /usr/local/bin/php /home/wclsgzyf/public_html/scripts/retry_failed_notifications.php >> /home/wclsgzyf/public_html/logs/cron_retry.log 2>&1

# 4. Cleanup notificări vechi (04:00 zilnic)
0 4 * * * /usr/local/bin/php /home/wclsgzyf/public_html/scripts/cleanup_notifications.php >> /home/wclsgzyf/public_html/logs/cron_cleanup.log 2>&1
```

---

## 8. Migrare Pas cu Pas

### Etapa 1: Pregătire
- ✅ CREATE TABLE notification_preferences, notification_queue, notification_templates
- ✅ Script migrare din system_settings → notification_preferences
- ✅ Populare template-uri default

### Etapa 2: Cod
- ✅ NotificationPreference, NotificationQueue, NotificationTemplate models
- ✅ DocumentStatusUpdater, NotificationQueueProcessor services
- ✅ Actualizare NotificationGenerator să folosească template-uri
- ✅ UI preferences.php (per user)

### Etapa 3: Testing
- ✅ Test migrare preferințe (script)
- ✅ Test generare notificări cu template-uri
- ✅ Test queue processing (manual + cron)
- ✅ Test cross-tenant isolation (security)

### Etapa 4: Producție
- ✅ Deploy pe Hostico (upload files)
- ✅ Rulare migrări SQL
- ✅ Setup cron jobs noi
- ✅ Monitor logs primele 48h

### Etapa 5: Superadmin Dashboard (opțional)
- ✅ Analytics cross-tenant
- ✅ Template manager
- ✅ Export rapoarte

---

## 9. Validări & Fallback-uri

### 9.1. Validări Înainte de Trimitere
```php
// În NotificationQueueProcessor::processQueue()
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Încearcă SMS dacă disponibil
    if ($smsEnabled && $phone) {
        $this->fallbackToSms($notification, $phone);
    }
}

if (!$this->checkRateLimit($companyId, 'sms')) {
    // Queue pentru mai târziu (scheduled_at = +1 hour)
    $this->reschedule($queueItem, '+1 hour');
}
```

### 9.2. Audit Complet
```php
NotificationLog::log('queue_processing', 'partial_failure', [
    'sent' => 85,
    'failed' => 15,
    'failures' => [
        ['queue_id' => 123, 'error' => 'Invalid email'],
        ['queue_id' => 124, 'error' => 'SMS quota exceeded']
    ]
]);
```

---

## 10. Recomandări Profesionale

### ✅ Best Practices Implementate
1. **Multi-tenancy strict:** Toate query-urile filtrate pe `company_id`
2. **Queue-based sending:** Evită blocking în aplicație
3. **Template engine:** Separare conținut de logică
4. **Rate limiting:** Protecție anti-spam și economie costuri SMS
5. **Comprehensive logging:** Audit trail pentru debugging și compliance
6. **Fallback mechanisms:** Email → SMS dacă email invalid
7. **Scalable architecture:** Pregătit pentru Redis/RabbitMQ

### 🔮 Viitor (Când crește flota)
- **Microservice dedicat:** Separate notifications service cu REST API
- **WebSocket notifications:** Real-time push în browser (Socket.io + Redis)
- **AI-powered scheduling:** Optimizare timing trimitere pe bază de engagement rates
- **Multi-language templates:** i18n support pentru companii internaționale

---

## Concluzie

**DA, SE ADAPTEAZĂ PERFECT** la fleet-management! 

Arhitectura propusă:
- ✅ Respectă multi-tenancy existent
- ✅ Extinde sistemul actual (backward compatible)
- ✅ Scalabilă pentru 100-10,000 vehicule
- ✅ Profesională (queue, templates, preferences)
- ✅ Securitate enterprise-grade
- ✅ Ușor de întreținut și extins

**Timp implementare estimat:** 2-3 zile full development
