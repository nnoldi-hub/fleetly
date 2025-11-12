# 🚀 Notificări V2 - Implementare Fleet Management

## ✅ Etapa 1: COMPLETĂ - Fundația Arhitecturală

### Livrabile Create:

#### 1. **Documentație Arhitecturală** 
📄 `docs/NOTIFICATION_ARCHITECTURE.md`
- Arhitectură completă multi-tier
- Structură baze de date (preferences, queue, templates)
- Fluxuri de lucru detaliate
- Best practices security & scalability
- Plan de migrare pas cu pas

#### 2. **Migrație SQL** 
📄 `sql/migrations/2025_01_12_001_notification_system_v2.sql`
- **4 Tabele noi:**
  - `notification_preferences` - Configurare per utilizator (canale, tipuri, frecvență)
  - `notification_queue` - Procesare asincronă cu retry logic
  - `notification_templates` - Template engine cu variabile
  - `notification_rate_limits` - Anti-spam & cost control
- **Extinderi:**
  - `documents.expiry_status` - Status calculat (active/expiring_soon/expired)
  - `insurance.expiry_status` - Similar pentru asigurări
  - `notifications.template_id` - Tracking template folosit
  - `notification_logs.queue_id` - Audit queue processing
- **Template-uri default populate:** document_expiry, insurance_expiry, maintenance_due, system_alert

#### 3. **Models PHP (3 noi)**

**a) NotificationPreference.php** (388 linii)
```php
// Funcționalități:
✅ getByUserId(), getOrDefault() - Load preferences
✅ createOrUpdate() - CRUD cu validare JSON
✅ isChannelEnabled(), isTypeEnabled() - Helper checks
✅ isInQuietHours() - Timezone-aware quiet hours
✅ migrateFromSystemSettings() - Import din legacy system_settings
✅ migrateAllUsers() - Bulk migration pentru toți userii
```

**b) NotificationQueue.php** (335 linii)
```php
// Funcționalități:
✅ enqueue() - Adaugă notificare în queue cu validare canal
✅ getPending() - Fetch pending items pentru procesare
✅ markAsProcessing/Sent/Failed() - Status management
✅ retryFailed() - Retry logic pentru failed items
✅ cleanup() - Purge queue items vechi
✅ getStats(), getBacklogSize() - Monitoring
✅ existsForNotification() - Duplicate prevention
```

**c) NotificationTemplate.php** (351 linii)
```php
// Funcționalități:
✅ getBySlug() - Load template cu priority (company > global)
✅ render() - Template engine cu {{variabile}} substitution
✅ getAllActive() - List templates per company
✅ create(), update(), delete() - CRUD complet
✅ cloneForCompany() - Customizare template global → company
✅ testRender() - Preview cu sample data
✅ generateFallback() - Fallback messages dacă template lipsește
```

---

## 📋 Etapa 2: Următorii Pași (Implementare Completă)

### Task 5: Services Layer

**a) DocumentStatusUpdater.php** (NOU)
```php
class DocumentStatusUpdater {
    // Rulează zilnic 06:00
    public function updateAllStatuses() {
        // UPDATE documents SET expiry_status = 
        //   CASE 
        //     WHEN expiry_date < CURDATE() THEN 'expired'
        //     WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
        //     ELSE 'active'
        //   END
        
        // Similar pentru insurance
        // Log changes în notification_logs
    }
}
```

**b) NotificationQueueProcessor.php** (NOU)
```php
class NotificationQueueProcessor {
    // Rulează la 5 minute
    public function processQueue($limit = 100) {
        $queue = new NotificationQueue();
        $items = $queue->getPending($limit);
        
        foreach ($items as $item) {
            $queue->markAsProcessing($item['id']);
            
            // Check preferences
            $prefs = NotificationPreference::getByUserId($item['user_id']);
            
            if (!$prefs->isChannelEnabled($item['channel'])) {
                $queue->cancel($item['id']);
                continue;
            }
            
            if ($prefs->isInQuietHours()) {
                // Reschedule pentru mâine
                continue;
            }
            
            // Send
            $notifier = new Notifier();
            if ($item['channel'] === 'email') {
                [$ok, $err] = $notifier->sendEmail($item['recipient_email'], $item['subject'], $item['message']);
            } elseif ($item['channel'] === 'sms') {
                [$ok, $err] = $notifier->sendSms($item['recipient_phone'], $item['message']);
            }
            
            if ($ok) {
                $queue->markAsSent($item['id']);
                // UPDATE notifications SET status='sent', sent_at=NOW()
            } else {
                $queue->markAsFailed($item['id'], $err);
            }
        }
    }
}
```

### Task 6: Refactorizare Notification Model

**Integrare cu NotificationQueue + Template:**
```php
// În Notification::createSingle()
// ÎNAINTE:
try {
    $notifier->sendEmail(...);
} catch (...) {}

// DUPĂ:
$template = new NotificationTemplate();
$prefs = new NotificationPreference();
$queue = new NotificationQueue();

// Render template
$rendered = $template->render($data['type'], [
    'vehicle_plate' => '...',
    'days_until_expiry' => '...',
    // ...
], 'email', $companyId);

$userPrefs = $prefs->getOrDefault($userId, $companyId);

// Check frequency
if ($userPrefs['frequency'] === 'immediate') {
    $scheduled_at = null; // Trimite acum
} elseif ($userPrefs['frequency'] === 'daily') {
    $scheduled_at = date('Y-m-d 06:00:00', strtotime('tomorrow'));
} elseif ($userPrefs['frequency'] === 'weekly') {
    $scheduled_at = date('Y-m-d 09:00:00', strtotime('next monday'));
}

// Enqueue pentru fiecare canal activ
if ($userPrefs['email_enabled']) {
    $queue->enqueue($notificationId, $userId, $companyId, 'email', [
        'recipient_email' => $userPrefs['email'] ?? $user['email'],
        'subject' => $rendered['subject'],
        'message' => $rendered['body'],
        'scheduled_at' => $scheduled_at
    ]);
}

if ($userPrefs['sms_enabled']) {
    $queue->enqueue($notificationId, $userId, $companyId, 'sms', [
        'recipient_phone' => $userPrefs['phone'] ?? $user['phone'],
        'message' => $rendered['body'], // SMS truncate
        'scheduled_at' => $scheduled_at
    ]);
}
```

### Task 7: UI Preferences

**modules/notifications/views/preferences.php** (NOU)
```php
// Formular cu:
// 1. Canale (checkboxes): Email, SMS, Push, In-App
// 2. Tipuri (checkboxes): Documente, Asigurări, Mentenanță
// 3. Frecvență (radio): Imediat, Zilnic (06:00), Săptămânal (Luni 09:00)
// 4. Contact override:
//    - Email: <input type="email" placeholder="user@company.ro">
//    - Telefon: <input type="tel" placeholder="+40 XXX XXX XXX">
// 5. Prioritate minimă (select): Low, Medium, High
// 6. Zile înainte expirare (slider): 7-90 zile
// 7. Quiet hours (time inputs): De la - Până la
// 8. Timezone (select): Europe/Bucharest, etc.

// POST handler:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prefs = new NotificationPreference();
    $result = $prefs->createOrUpdate($_SESSION['user_id'], $_SESSION['company_id'], [
        'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
        'sms_enabled' => isset($_POST['sms_enabled']) ? 1 : 0,
        'enabled_types' => $_POST['enabled_types'] ?? [],
        'frequency' => $_POST['frequency'] ?? 'immediate',
        'days_before_expiry' => (int)$_POST['days_before_expiry'],
        // ...
    ]);
}
```

### Task 8: Cron Scripts

**a) scripts/process_notifications_queue.php** (NOU)
```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../modules/notifications/services/NotificationQueueProcessor.php';

$processor = new NotificationQueueProcessor();
$result = $processor->processQueue(100);

echo "[" . date('Y-m-d H:i:s') . "] Processed: {$result['sent']}, Failed: {$result['failed']}\n";
```

**Cron setup:**
```bash
# Procesare queue (la 5 minute)
*/5 * * * * /usr/local/bin/php /home/wclsgzyf/public_html/scripts/process_notifications_queue.php >> /home/wclsgzyf/public_html/logs/cron_queue.log 2>&1

# Actualizare status documente + generare (06:00 zilnic)
0 6 * * * /usr/local/bin/php /home/wclsgzyf/public_html/scripts/cron_generate_notifications.php >> /home/wclsgzyf/public_html/logs/cron_generate.log 2>&1

# Retry failed (la oră)
0 * * * * /usr/local/bin/php /home/wclsgzyf/public_html/scripts/retry_failed_notifications.php >> /home/wclsgzyf/public_html/logs/cron_retry.log 2>&1

# Cleanup vechi (04:00 zilnic)
0 4 * * * /usr/local/bin/php /home/wclsgzyf/public_html/scripts/cleanup_notifications.php >> /home/wclsgzyf/public_html/logs/cron_cleanup.log 2>&1
```

**b) scripts/retry_failed_notifications.php** (NOU)
```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../modules/notifications/models/NotificationQueue.php';

$queue = new NotificationQueue();
$result = $queue->retryFailed(50);

echo "[" . date('Y-m-d H:i:s') . "] Retried: {$result['retried']} failed notifications\n";
```

**c) scripts/cleanup_notifications.php** (NOU)
```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../modules/notifications/models/NotificationQueue.php';
require_once __DIR__ . '/../modules/notifications/models/Notification.php';

// Cleanup queue items vechi
$queue = new NotificationQueue();
$queueResult = $queue->cleanup(30); // 30 zile

// Cleanup notificări citite vechi
$notification = new Notification();
$notifResult = $notification->cleanup(30);

echo "[" . date('Y-m-d H:i:s') . "] Cleaned queue: {$queueResult['affected']}, notifications: {$notifResult['affected']}\n";
```

### Task 9: Dashboard Superadmin

**modules/superadmin/views/notifications_dashboard.php** (NOU)
```php
// Layout:
// 1. KPI Cards (toți tenanții):
//    - Total notificări trimise (ultimele 30 zile)
//    - Rate de deschidere (email tracking)
//    - Queue backlog size
//    - Failed notifications count
//
// 2. Charts (Chart.js):
//    - Notificări per companie (top 10)
//    - Distribuție canale (email/sms/push)
//    - Timeline notificări (ultimele 7 zile)
//
// 3. Tabel cross-tenant:
//    - Company | Notificări | Email | SMS | Failed | Actions
//    - Butoane: Forțare generare, Export raport
//
// 4. Queue Status:
//    - Pending items în queue
//    - Average processing time
//    - Retry queue size

// Query exemplu:
$sql = "SELECT c.name, COUNT(n.id) as total, 
               SUM(CASE WHEN nq.channel='email' THEN 1 ELSE 0 END) as email_count,
               SUM(CASE WHEN nq.status='failed' THEN 1 ELSE 0 END) as failed_count
        FROM companies c
        LEFT JOIN notifications n ON c.id = n.company_id
        LEFT JOIN notification_queue nq ON n.id = nq.notification_id
        WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY c.id
        ORDER BY total DESC
        LIMIT 10";
```

---

## 🔄 Migrare și Deployment

### Step 1: Backup
```bash
mysqldump -u root -p fleet_management > backup_before_v2_$(date +%Y%m%d).sql
```

### Step 2: Rulare Migrație SQL
```bash
mysql -u root -p fleet_management < sql/migrations/2025_01_12_001_notification_system_v2.sql
```

### Step 3: Migrator Preferences (CLI)
```php
// scripts/migrate_notification_preferences.php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../modules/notifications/models/NotificationPreference.php';

$result = NotificationPreference::migrateAllUsers();

echo "Migration Results:\n";
echo "- Migrated: {$result['migrated']}\n";
echo "- Skipped (no legacy data): {$result['skipped']}\n";
echo "- Errors: " . count($result['errors']) . "\n";

if (!empty($result['errors'])) {
    foreach ($result['errors'] as $error) {
        echo "  • $error\n";
    }
}
?>
```

```bash
php scripts/migrate_notification_preferences.php
```

### Step 4: Populate Templates (already done in SQL migration)
✅ Template-uri default deja populate prin migrația SQL

### Step 5: Update Cron Jobs (Hostico cPanel)
1. Păstrează cron-urile existente pentru generare și procesare veche
2. Adaugă nou cron pentru `process_notifications_queue.php` (*/5)
3. Adaugă cron pentru `retry_failed_notifications.php` (hourly)
4. Adaugă cron pentru `cleanup_notifications.php` (daily 04:00)

### Step 6: Testing
```bash
# 1. Test manual queue processing
php scripts/process_notifications_queue.php

# 2. Test generare notificări cu template-uri
# Accesează /modules/notifications/views/list.php
# Click "Generează notificări automate"

# 3. Verifică logs
tail -f logs/cron_queue.log
tail -f logs/cron_generate.log

# 4. Check queue backlog
mysql -u root -p -e "SELECT status, COUNT(*) FROM fleet_management.notification_queue GROUP BY status;"
```

---

## 🔐 Securitate & Best Practices

### 1. Multi-Tenancy Validation
```php
// În toate query-urile queue/notifications:
WHERE company_id = :current_company_id

// În superadmin dashboard:
if (!Auth::isSuperAdmin()) {
    throw new UnauthorizedException('Acces interzis');
}
```

### 2. Rate Limiting (Anti-spam)
```php
// În NotificationQueueProcessor::processQueue()
$rateLimiter = new NotificationRateLimiter();
if (!$rateLimiter->checkLimit($companyId, 'sms')) {
    $this->rescheduleItem($item, '+1 hour');
    continue;
}
```

### 3. Input Validation
```php
// În NotificationPreference::createOrUpdate()
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    return ['success' => false, 'message' => 'Email invalid'];
}

if ($data['days_before_expiry'] < 1 || $data['days_before_expiry'] > 365) {
    return ['success' => false, 'message' => 'Zile înainte trebuie între 1-365'];
}
```

### 4. Error Handling
```php
// În toate models:
try {
    $this->db->query($sql, $params);
} catch (Throwable $e) {
    NotificationLog::log('error', 'db_error', ['sql' => $sql], null, $e->getMessage());
    return ['success' => false, 'message' => 'Database error'];
}
```

---

## 📊 Monitoring & Metrics

### Dashboard Metrics (KPI)
1. **Delivery Rate:** `(sent / total) * 100`
2. **Failure Rate:** `(failed / total) * 100`
3. **Average Processing Time:** `AVG(TIMESTAMPDIFF(SECOND, created_at, processed_at))`
4. **Queue Backlog:** `COUNT(*) WHERE status='pending'`
5. **Channel Distribution:** `GROUP BY channel`

### Alerts (Opțional - viitor)
- Queue backlog > 1000 items → Alert admin
- Failure rate > 10% → Check SMTP/SMS config
- Processing time > 5 min → Increase cron frequency

---

## 🚀 Scalability Path

### Faza 1: DB Queue (CURRENT)
✅ Compatibil cu shared hosting
✅ Nu necesită servicii externe
✅ Suficient pentru 100-500 notificări/zi

### Faza 2: Redis Queue (> 1000 vehicule)
```php
// În NotificationQueue::enqueue()
if (QUEUE_DRIVER === 'redis') {
    Redis::lpush('notifications:pending', json_encode($item));
} else {
    // Fallback DB
}
```

### Faza 3: Microservice (Enterprise)
```
┌─────────────┐
│ FleetMgmt   │
│ Main App    │
└──────┬──────┘
       │ REST API
       ▼
┌──────────────────┐
│ Notifications    │
│ Microservice     │
│ (Node.js/Go)     │
└──────┬───────────┘
       │
  ┌────┼────┐
  ▼    ▼    ▼
 SMTP  SMS  Push
       (Twilio, Firebase)
```

---

## ✅ Checklist Final

### Dezvoltare:
- [x] Arhitectură documentată
- [x] Migrație SQL creată
- [x] Models: NotificationPreference, NotificationQueue, NotificationTemplate
- [ ] Services: DocumentStatusUpdater, NotificationQueueProcessor
- [ ] Refactorizare Notification::createSingle() cu queue + templates
- [ ] UI preferences.php
- [ ] Cron scripts (4x)
- [ ] Dashboard superadmin

### Testing:
- [ ] Test migrare preferences (script)
- [ ] Test generare notificări cu template-uri
- [ ] Test queue processing (manual + cron)
- [ ] Test retry failed
- [ ] Test cleanup
- [ ] Test multi-tenant isolation
- [ ] Test quiet hours
- [ ] Test rate limiting

### Deployment:
- [ ] Backup DB production
- [ ] Rulare migrație SQL
- [ ] Rulare migrate_notification_preferences.php
- [ ] Upload files noi pe server
- [ ] Setup cron jobs
- [ ] Monitor logs 24h
- [ ] Validare cu echipa

### Documentație:
- [ ] User guide pentru preferences UI
- [ ] Admin guide pentru template management
- [ ] Superadmin guide pentru dashboard
- [ ] Troubleshooting common issues

---

## 🎯 Concluzie

**Status Actual:** 40% Complete (Fundația arhitecturală + Models)

**Următorii Pași Critici:**
1. Services layer (DocumentStatusUpdater + QueueProcessor)
2. Integrare în Notification::createSingle()
3. UI preferences pentru utilizatori
4. Testing complet

**Timp Estimat Finalizare:** 1.5-2 zile full development

**Beneficii Imediate După Lansare:**
✅ Trimitere asincronă (nu mai blochează aplicația)
✅ Retry automatic pentru failed notifications
✅ Customizare template-uri per companie
✅ Quiet hours & frequency control
✅ Scalabil pentru 10,000+ vehicule
✅ Cross-tenant analytics pentru superadmin

---

**Autor:** GitHub Copilot  
**Data:** 12 ianuarie 2025  
**Versiune:** 2.0.0-alpha
