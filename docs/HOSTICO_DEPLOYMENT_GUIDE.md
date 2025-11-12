# 🚀 GHID DEPLOYMENT HOSTICO
## Notification System V2 - Actualizare Baze de Date

---

## 📋 PREGĂTIRE

### 1. Identifică Bazele de Date

Conectează-te la **phpMyAdmin** pe Hostico și identifică:

✅ **Baza CORE (principală):** `fleet_management`
✅ **Baze TENANT (companii):** 
   - `fleet_management_company_1`
   - `fleet_management_company_2`
   - etc.

**Cum verifici:** Click pe baza de date în stânga → Vezi tabelele:
- CORE are: `users`, `companies`, `system_settings`
- TENANT are: `vehicles`, `documents`, `insurance`, `maintenance`, `notifications`

---

## ⚡ DEPLOYMENT

### PASUL 1: Actualizează Baza CORE (fleet_management)

1. **Selectează baza de date CORE:**
   - Click pe `fleet_management` în phpMyAdmin (stânga)

2. **Deschide tab-ul SQL:**
   - Click pe butonul **SQL** (sus)

3. **Copiază și rulează:**
   - Deschide fișierul: `sql/migrations/hostico_deploy_core.sql`
   - Copiază ÎNTREGUL conținut
   - Paste în phpMyAdmin
   - Click **Go** (Execute)

4. **Verifică rezultatul:**
   ```sql
   SHOW TABLES LIKE 'notification%';
   ```
   
   **Trebuie să vezi:**
   - ✅ notification_preferences
   - ✅ notification_queue
   - ✅ notification_templates
   - ✅ notification_rate_limits

5. **Verifică template-urile default:**
   ```sql
   SELECT slug, name FROM notification_templates WHERE company_id IS NULL;
   ```
   
   **Trebuie să vezi 4 template-uri:**
   - ✅ document_expiry
   - ✅ insurance_expiry
   - ✅ maintenance_due
   - ✅ system_alert

---

### PASUL 2: Actualizează Bazele TENANT (pentru fiecare companie)

**IMPORTANT:** Repetă pașii de mai jos pentru FIECARE bază de date tenant!

#### 2.1. Pentru fleet_management_company_1:

1. **Selectează baza de date TENANT:**
   - Click pe `fleet_management_company_1` în phpMyAdmin

2. **Deschide tab-ul SQL**

3. **Copiază și rulează:**
   - Deschide fișierul: `sql/migrations/hostico_deploy_tenant.sql`
   - Copiază ÎNTREGUL conținut
   - Paste în phpMyAdmin
   - Click **Go**

4. **Verifică rezultatul:**
   ```sql
   SHOW COLUMNS FROM notifications;
   ```
   
   **Trebuie să vezi coloanele noi:**
   - ✅ status (ENUM: pending, sent, failed, read)
   - ✅ scheduled_at (DATETIME)
   - ✅ sent_at (DATETIME)
   - ✅ metadata (JSON)

5. **Verifică tabele auxiliare:**
   ```sql
   SHOW COLUMNS FROM documents LIKE '%expiry_status%';
   SHOW COLUMNS FROM insurance LIKE '%expiry_status%';
   SHOW COLUMNS FROM maintenance LIKE '%due_status%';
   ```

#### 2.2. Pentru fleet_management_company_2:

**Repetă exact aceiași pași ca la 2.1**, dar selectează `fleet_management_company_2`

#### 2.3. Pentru alte companii:

Continuă pentru toate bazele tenant existente.

---

## 🔍 VERIFICARE FINALĂ

### 1. Verificare CORE Database

```sql
USE fleet_management;

-- Contor tabele
SELECT 
    'notification_preferences' AS tabel, COUNT(*) AS randuri FROM notification_preferences
UNION ALL
SELECT 'notification_queue', COUNT(*) FROM notification_queue
UNION ALL
SELECT 'notification_templates', COUNT(*) FROM notification_templates
UNION ALL
SELECT 'notification_rate_limits', COUNT(*) FROM notification_rate_limits;
```

**Rezultat așteptat:**
- notification_templates: minim 4 (template-urile default)
- Celelalte pot fi 0 (se vor popula la utilizare)

### 2. Verificare TENANT Database (pentru fiecare)

```sql
USE fleet_management_company_1; -- Schimbă cu numele tenant-ului

-- Verifică structura notifications
SELECT COLUMN_NAME, DATA_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'notifications' 
  AND TABLE_SCHEMA = DATABASE()
  AND COLUMN_NAME IN ('status', 'scheduled_at', 'sent_at', 'metadata');
```

**Rezultat așteptat:** 4 rânduri (cele 4 coloane noi)

---

## 📤 UPLOAD FIȘIERE PHP

După actualizarea bazelor de date, **upload-ează fișierele PHP noi** pe server:

### Fișiere de upload (prin FTP/cPanel File Manager):

```
📁 modules/notifications/
  📁 models/
    - NotificationPreference.php
    - NotificationQueue.php
    - NotificationTemplate.php
  📁 services/
    - NotificationQueueProcessor.php
    - DocumentStatusUpdater.php
  📁 views/
    - preferences.php
  📁 controllers/
    - NotificationController.php (actualizat)

📁 modules/superadmin/
  📁 views/
    - notifications_dashboard.php
  📁 controllers/
    - SuperAdminController.php (actualizat)

📁 scripts/
  - process_notifications_queue.php
  - retry_failed_notifications.php
  - cleanup_notifications.php
  - migrate_notification_preferences.php

📁 config/
  - routes.php (actualizat - optional, dacă folosești)

📄 index.php (actualizat - cu rutele noi)
```

---

## ⏰ CONFIGURARE CRON JOBS (cPanel)

1. **Acces:** cPanel → Advanced → Cron Jobs

2. **Adaugă 4 joburi:**

### Job 1: Queue Processor (la fiecare 5 minute)
```bash
*/5 * * * * php /home/USERNAME/public_html/scripts/process_notifications_queue.php >> /home/USERNAME/logs/queue.log 2>&1
```

### Job 2: Retry Failed (la fiecare oră)
```bash
0 * * * * php /home/USERNAME/public_html/scripts/retry_failed_notifications.php >> /home/USERNAME/logs/retry.log 2>&1
```

### Job 3: Daily Generation (la 06:00 dimineața)
```bash
0 6 * * * php /home/USERNAME/public_html/scripts/cron_generate_notifications.php >> /home/USERNAME/logs/daily.log 2>&1
```

### Job 4: Cleanup (la 04:00 dimineața)
```bash
0 4 * * * php /home/USERNAME/public_html/scripts/cleanup_notifications.php >> /home/USERNAME/logs/cleanup.log 2>&1
```

**IMPORTANT:** Înlocuiește `USERNAME` cu username-ul tău Hostico!

---

## ✅ TEST FINAL

1. **Login în aplicație**
2. **Navighează la:** Notificări → Preferințe
3. **Verifică:** Formularul se încarcă corect
4. **Salvează preferințe:** Testează save
5. **Test notification:** Click "Trimite notificare test"

---

## 🆘 TROUBLESHOOTING

### Eroare: "Table already exists"
✅ **Normal!** Script-ul folosește `CREATE TABLE IF NOT EXISTS` - va sări peste tabelele existente.

### Eroare: "Column already exists"
✅ **Normal!** Script-ul verifică existența coloanelor înainte de a le adăuga.

### Eroare: "Unknown database"
❌ **Verifică:** Ai selectat baza de date corectă în phpMyAdmin (click pe nume în stânga).

### Eroare: "Access denied"
❌ **Verifică:** User-ul MySQL are permisiuni CREATE, ALTER, INSERT pe ambele baze.

### Formularul Preferințe dă 404
❌ **Verifică:** 
1. Ai rulat migrarea pe baza CORE (fleet_management)?
2. Ai upload-at fișierele PHP?
3. Ai adăugat rutele în index.php?

### Notificările nu se trimit
❌ **Verifică:**
1. Cron jobs configurate corect în cPanel?
2. Script-urile au permisiuni execute (chmod +x)?
3. Verifică logs: `/home/USERNAME/logs/queue.log`

---

## 📊 MONITORIZARE

### Dashboard SuperAdmin
**URL:** `https://yourdomain.com/superadmin/notifications/dashboard`

Vezi aici:
- KPI-uri (Total notificări, Delivery rate, Failed, Queue backlog)
- Grafice (Timeline, Channel distribution)
- Company comparison
- Recent failed notifications

### Manual Queries (phpMyAdmin)

**Coada de procesare:**
```sql
SELECT status, COUNT(*) FROM fleet_management.notification_queue GROUP BY status;
```

**Preferințe utilizatori:**
```sql
SELECT COUNT(*) FROM fleet_management.notification_preferences;
```

**Notificări trimise (pe tenant):**
```sql
SELECT status, COUNT(*) 
FROM fleet_management_company_1.notifications 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY status;
```

---

## 📝 CHECKLIST DEPLOYMENT

```
□ 1. Backup baze de date (CORE + toate TENANT)
□ 2. Rulat hostico_deploy_core.sql pe fleet_management
□ 3. Verificat 4 tabele noi în CORE
□ 4. Verificat 4 template-uri default
□ 5. Rulat hostico_deploy_tenant.sql pe company_1
□ 6. Rulat hostico_deploy_tenant.sql pe company_2
□ 7. Rulat hostico_deploy_tenant.sql pe company_N
□ 8. Upload fișiere PHP (25 fișiere)
□ 9. Configurat 4 cron jobs în cPanel
□ 10. Testat formularul Preferințe
□ 11. Testat notificare test
□ 12. Verificat SuperAdmin dashboard
□ 13. Monitorizat logs primele 24h
```

---

## 🎉 FINALIZARE

Dacă toate verificările sunt ✅, sistemul este **LIVE**!

**Documentație pentru utilizatori:** `docs/USER_GUIDE_NOTIFICATIONS.md`

**Suport tehnic:** Verifică `docs/TESTING_GUIDE.md` și `docs/TROUBLESHOOTING.md`

---

**Data deployment:** _______________  
**Deployed by:** _______________  
**Status:** □ Success □ Issues (details: ___________)
