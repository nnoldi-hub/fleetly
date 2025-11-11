# Arhitectura Sistemului de Notificări - Fleet Management System

## 📋 Status Implementare

### ✅ Implementat (Funcțional)

#### 1. Model de Date - Multitenant

**Structura actuală:**
- **Core DB** (`wclsgzyf_fleetly`): `companies`, `users`, `roles`, `system_settings`
- **Tenant DB** (`wclsgzyf_fm_tenant_X`): `notifications`, `vehicles`, `drivers`, `documents`, `insurance`, `maintenance`

**Tabela `notifications` (tenant DB):**
```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,                    -- Utilizator individual (NULL pentru broadcast)
    company_id INT NULL,                 -- Pentru broadcast la nivel de companie
    type ENUM('insurance_expiry', 'maintenance_due', 'document_expiry', ...),
    priority ENUM('low', 'medium', 'high', 'critical'),
    vehicle_id INT NULL,
    related_id INT NULL,
    title VARCHAR(200),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,       -- Marcare citit/necitit
    created_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Tabela `system_settings` (core DB):**
```sql
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE,     -- Ex: 'smtp_settings', 'sms_settings', 'notifications_prefs_user_1'
    setting_value TEXT,                  -- JSON encodat
    setting_type ENUM('string', 'number', 'boolean', 'json'),
    description TEXT
);
```

**Settings salvate:**
- `smtp_settings` - configurare email (host, port, username, password, encryption)
- `sms_settings` - configurare SMS (Twilio/HTTP gateway)
- `notifications_prefs_user_{id}` - preferințe per utilizator:
  ```json
  {
    "enabledCategories": ["insurance_expiry", "maintenance_due", "document_expiry"],
    "methods": {"in_app": 1, "email": 1, "sms": 0},
    "daysBefore": 30,
    "minPriority": "low",
    "broadcastToCompany": 1
  }
  ```

#### 2. Logica de Broadcast

**Implementare în `Notification::create()`:**
```php
public static function create($data) {
    // Detectare broadcast: company_id setat + user_id NULL
    if (!empty($data['company_id']) && empty($data['user_id'])) {
        // Obține toți utilizatorii activi din companie
        $users = $db->fetchAll("SELECT id FROM users WHERE company_id = ? AND status = 'active'", [$data['company_id']]);
        
        // Creare notificare pentru fiecare utilizator
        foreach ($users as $user) {
            self::createSingle(array_merge($data, ['user_id' => $user['id']]));
        }
        return true;
    }
    
    // Notificare individuală
    return self::createSingle($data);
}
```

**Metode statice factory:**
- `Notification::createInsuranceExpiryNotification($insuranceId, $licensePlate, $insuranceType, $expiryDate, $priority, $companyId)`
- `Notification::createMaintenanceNotification($vehicleId, $licensePlate, $maintenanceType, $companyId)`
- `Notification::createDocumentExpiryNotification($documentId, $documentType, $expiryDate, $companyId)`

Toate verifică `Notification::getAdminBroadcastPreference($companyId)` pentru a decide broadcast vs. individual.

#### 3. Interfață de Configurare

**Admin/Manager:**
- `/notifications/settings` - Configurare completă:
  - ✅ Checkbox broadcast "Trimite notificările automate către toți utilizatorii companiei"
  - ✅ Categorii active (asigurări, mentenanță, documente, kilometraj, costuri)
  - ✅ Metode notificare (in-app, email, SMS)
  - ✅ Zile înainte de expirare (30 implicit)
  - ✅ Prioritate minimă afișată
  - ✅ Configurare SMTP (host, port, encryption, credentials)
  - ✅ Configurare SMS (Twilio/HTTP gateway)
  - ✅ Butoane test pentru email și SMS

**User normal:**
- `/profile` - Număr telefon pentru SMS
- `/settings` - Link către setări avansate (doar pentru admin)

#### 4. Fluxul de Trimitere

**Generare automată:**
```php
NotificationController::generateSystemNotifications() {
    // 1. Obține company_id utilizator curent
    $companyId = $currentUser->company_id;
    
    // 2. Verifică asigurări expirând în următoarele 30 zile
    $expiringInsurance = $insuranceModel->getExpiring(30);
    foreach ($expiringInsurance as $insurance) {
        Notification::createInsuranceExpiryNotification(..., $companyId);
    }
    
    // 3. Verifică mentenanță scadentă
    $dueMaintenance = $maintenanceModel->getDueMaintenance();
    foreach ($dueMaintenance as $maint) {
        Notification::createMaintenanceNotification(..., $companyId);
    }
}
```

**Serviciu de trimitere (`Notifier.php`):**
```php
class Notifier {
    public function sendEmail($to, $subject, $body, $smtp = null) {
        // Conexiune SMTP directă cu AUTH PLAIN
        // Suport pentru SSL (465), TLS (587), none (25)
    }
    
    public function sendSms($to, $message, $sms = null) {
        // Twilio API sau HTTP gateway generic
    }
}
```

---

## ⏳ În Curs de Implementare

### 🔧 Probleme Curente

**1. Configurare SMTP/Email**
- **Status:** Blocat de restricții anti-spam Hostico
- **Eroare:** "220 and/or bulk e-mail" - serverul shared hosting blochează trimiteri automate
- **Soluții propuse:**
  - Contactare Hostico pentru whitelist `notificari@fleetly.ro`
  - **[RECOMANDAT]** Migrare la serviciu extern:
    - SendGrid (100 email/zi gratuit)
    - Mailgun (5000 email/lună gratuit primele 3 luni)
    - Amazon SES ($0.10/1000 emailuri)

**2. Salvare Preferință Broadcast**
- **Status:** ✅ REZOLVAT (commit fb9cda5)
- **Fix:** Folosire `Auth::getInstance()->user()->role_slug` în loc de `$_SESSION['user_role']`

---

## 📝 Lipsește / De Implementat

### 1. Tabela `notification_logs` (Audit Trail)

**Propunere schemă:**
```sql
CREATE TABLE notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT,
    company_id INT,
    user_id INT,
    event_type VARCHAR(50),              -- 'insurance_expiry', 'maintenance_due', etc.
    channel ENUM('email', 'sms', 'in_app'),
    recipient VARCHAR(255),              -- Email sau telefon
    status ENUM('pending', 'sent', 'failed', 'bounced'),
    error_message TEXT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_company_date (company_id, created_at)
);
```

**Beneficii:**
- Tracking complet al notificărilor trimise
- Debug erori de trimitere
- Statistici per companie/utilizator
- Compliance și audit

### 2. Tabela `notification_templates` (Template-uri Editabile)

**Propunere schemă:**
```sql
CREATE TABLE notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NULL,                 -- NULL = template global (superadmin)
    event_type VARCHAR(50) UNIQUE,       -- 'insurance_expiry', 'maintenance_due', etc.
    channel ENUM('email', 'sms', 'in_app'),
    subject VARCHAR(255),                -- Pentru email
    body_template TEXT,                  -- HTML/Text cu variabile {{vehicle_name}}, {{expiry_date}}
    variables JSON,                      -- Lista variabilelor disponibile
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```

**Exemplu template:**
```json
{
  "event_type": "insurance_expiry",
  "channel": "email",
  "subject": "Asigurare {{insurance_type}} expiră pentru {{vehicle_name}}",
  "body_template": "Bună ziua,\n\nAsigurarea {{insurance_type}} pentru vehiculul {{vehicle_name}} ({{license_plate}}) va expira pe {{expiry_date}}.\n\nVă rugăm să reînnoiți asigurarea.\n\nCu stimă,\nEchipa Fleet Management",
  "variables": ["vehicle_name", "license_plate", "insurance_type", "expiry_date"]
}
```

**UI necesar:**
- Editor template-uri cu preview
- Selector variabile disponibile
- Testare template cu date mock

### 3. Dashboard Statistici Notificări

**Metrici propuse:**
- Total notificări trimise (ziua curentă, săptămâna curentă, luna curentă)
- Rate de succes/eșec pe canal (email vs. SMS)
- Top evenimente generate (ce tipuri de notificări sunt cele mai frecvente)
- Grafic temporal al notificărilor
- Lista erorilor recente cu detalii

### 4. Programare Automată (Cron Job)

**Fișier existent:** `scripts/process_notifications.php`

**Ce face:**
- Procesează notificările cu status 'pending'
- Trimite email/SMS conform preferințelor
- Update status la 'sent' sau 'failed'

**Configurare necesară:**
- Task Scheduler (Windows) sau crontab (Linux)
- Rulare zilnică: `0 8 * * * php /path/to/scripts/process_notifications.php`

---

## 🎯 Comparație: Arhitectura Propusă vs. Implementat

| Componentă | Propus | Implementat | Gap |
|------------|--------|-------------|-----|
| **Multitenant** | ✅ Core + Tenant DB | ✅ Implementat | - |
| **Broadcast** | ✅ company_id + user_id NULL | ✅ Implementat | - |
| **system_settings** | ✅ SMTP/SMS config | ✅ JSON în setting_value | - |
| **notification_settings** | Tabel dedicat per companie | ❌ JSON în system_settings | Tabel dedicat mai scalabil |
| **notification_templates** | ✅ Template-uri editabile | ❌ Hardcodat în cod | **MAJOR GAP** |
| **notification_logs** | ✅ Audit trail | ❌ Nu există | **MAJOR GAP** |
| **UI Admin** | Configurare + Template editor | ✅ Configurare, ❌ Editor | Editor template lipsește |
| **SMTP Extern** | SendGrid/Mailgun | ❌ SMTP direct (blocat) | **BLOCKER CURENT** |
| **Cron Job** | ✅ Programare automată | ✅ Script gata | Needs setup pe server |

---

## 🚀 Plan de Acțiune Recomandat

### Prioritate CRITICĂ
1. **Rezolvare SMTP** - Configurare SendGrid/Mailgun pentru trimitere email funcțională
2. **Testare end-to-end broadcast** - Verificare că notificările ajung la toți utilizatorii

### Prioritate ÎNALTĂ
3. **Implementare `notification_logs`** - Audit trail pentru debugging și compliance
4. **Setup cron job** - Automatizare procesare notificări pending

### Prioritate MEDIE
5. **Implementare `notification_templates`** - Template-uri editabile per companie
6. **Dashboard statistici** - Metrici și grafice pentru notificări

### Prioritate SCĂZUTĂ
7. **Refactorizare `notification_settings`** - Tabel dedicat în loc de JSON în system_settings
8. **Integrare webhook-uri** - Notificări către sisteme externe (Slack, Teams, etc.)

---

## 📚 Resurse și Referințe

**Documentație tehnică:**
- `docs/DEV_GUIDE_TENANCY_ROUTING.md` - Arhitectură multitenant
- `sql/migrations/2025_11_10_001_add_company_id_to_notifications.sql` - Migrare broadcast
- `modules/notifications/models/Notification.php` - Logică broadcast
- `modules/notifications/controllers/NotificationController.php` - Controller principal
- `modules/notifications/services/Notifier.php` - Serviciu trimitere SMTP/SMS

**Commit-uri relevante:**
- `fb9cda5` - Fix AUTH PLAIN pentru SMTP
- `a271ab8` - Fix salvare preferință broadcast
- `78d5b1b` - Implementare completă UI broadcast

**Servicii recomandate:**
- [SendGrid](https://sendgrid.com) - Email API
- [Mailgun](https://mailgun.com) - Email API
- [Twilio](https://twilio.com) - SMS API
- [Amazon SES](https://aws.amazon.com/ses/) - Email bulk

---

**Ultima actualizare:** 11 noiembrie 2025
**Autor:** AI Assistant + Developer
**Status:** Living document - se actualizează pe măsură ce implementăm
