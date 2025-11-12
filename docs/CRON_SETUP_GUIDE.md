# CRON JOBS CONFIGURATION GUIDE
# Notification System V2 - Queue Processing & Maintenance

## Overview
Această configurație asigură procesarea automată a notificărilor, reîncercarea celor eșuate și curățarea periodică a datelor vechi.

---

## 🔧 CPANEL CONFIGURATION

### Acces cPanel Cron Jobs
1. Login la cPanel (https://your-domain.com:2083)
2. Mergi la **Advanced** → **Cron Jobs**
3. Adaugă următoarele 4 cron jobs:

---

## 📋 CRON JOBS TO ADD

### 1. QUEUE PROCESSOR (Procesare Queue - Fiecare 5 minute)
**Frecvență:** La fiecare 5 minute
**Command:**
```bash
*/5 * * * * php /home/username/public_html/scripts/process_notifications_queue.php >> /home/username/cron_logs/queue_processor.log 2>&1
```

**Explicație:**
- `*/5 * * * *` = La fiecare 5 minute
- Procesează până la 100 notificări din queue
- Trimite email/SMS/push conform preferințelor utilizatorilor
- Respectă quiet hours și rate limits
- Output salvat în `cron_logs/queue_processor.log`

**Settings în cPanel:**
- Common Settings: `Every 5 minutes (* * * * *)`
- Command: vezi mai sus (schimbă `/home/username/` cu calea ta reală)

---

### 2. RETRY FAILED (Reîncearcă Eșuate - Fiecare Oră)
**Frecvență:** La fiecare oră, la minut 15
**Command:**
```bash
15 * * * * php /home/username/public_html/scripts/retry_failed_notifications.php >> /home/username/cron_logs/retry_failed.log 2>&1
```

**Explicație:**
- `15 * * * *` = La minut 15 din fiecare oră (ex: 00:15, 01:15, etc.)
- Re-încearcă notificările eșuate (max 3 attempts)
- Exponential backoff: 1h, 2h, 4h între încercări
- Anulează după max_attempts atins
- Output salvat în `cron_logs/retry_failed.log`

**Settings în cPanel:**
- Minute: `15`
- Hour: `*` (Every Hour)
- Day: `*`
- Month: `*`
- Weekday: `*`

---

### 3. DAILY NOTIFICATIONS (Generare Zilnică - 06:00 AM)
**Frecvență:** Zilnic la 06:00 dimineața
**Command:**
```bash
0 6 * * * php /home/username/public_html/scripts/cron_generate_notifications.php >> /home/username/cron_logs/daily_notifications.log 2>&1
```

**Explicație:**
- `0 6 * * *` = La 06:00 AM zilnic
- Actualizează status expirări (documents, insurance, maintenance)
- Generează notificări pentru utilizatori conform preferințelor
- Trimite rezumate zilnice (frequency=daily)
- Output salvat în `cron_logs/daily_notifications.log`

**Settings în cPanel:**
- Common Settings: `Once Per Day (0 0 * * *)`
- Apoi schimbă Hour la `6`

---

### 4. CLEANUP (Curățare Date - 04:00 AM Zilnic)
**Frecvență:** Zilnic la 04:00 dimineața
**Command:**
```bash
0 4 * * * php /home/username/public_html/scripts/cleanup_notifications.php >> /home/username/cron_logs/cleanup.log 2>&1
```

**Explicație:**
- `0 4 * * *` = La 04:00 AM zilnic
- Șterge queue items sent/cancelled > 30 zile
- Șterge notificări citite > 90 zile
- Șterge log-uri > 180 zile (6 luni)
- Resetează rate limit counters expirați
- Optimizează tabele MySQL
- Output salvat în `cron_logs/cleanup.log`

**Settings în cPanel:**
- Common Settings: `Once Per Day (0 0 * * *)`
- Apoi schimbă Hour la `4`

---

## 📁 SETUP DIRECTORY STRUCTURE

### Creează director pentru log-uri cron:
```bash
mkdir -p /home/username/cron_logs
chmod 755 /home/username/cron_logs
```

### Permisii pentru script-uri:
```bash
chmod +x /home/username/public_html/scripts/*.php
```

---

## 🔍 MONITORING & TROUBLESHOOTING

### Verificare log-uri:
```bash
# Queue processor (ultim run)
tail -100 /home/username/cron_logs/queue_processor.log

# Retry failed
tail -50 /home/username/cron_logs/retry_failed.log

# Daily notifications
tail -50 /home/username/cron_logs/daily_notifications.log

# Cleanup
tail -50 /home/username/cron_logs/cleanup.log
```

### Log format (color-coded în CLI):
```
[2025-01-12 06:05:23] [INFO] === NOTIFICATION QUEUE PROCESSOR START ===
[2025-01-12 06:05:23] [INFO] Queue backlog: 45 items
[2025-01-12 06:05:25] [SUCCESS] Sent: 42
[2025-01-12 06:05:25] [WARNING] Failed: 3
[2025-01-12 06:05:25] [INFO] === QUEUE PROCESSOR FINISHED (EXIT CODE: 0) ===
```

### Verificare status în DB:
```sql
-- Queue backlog
SELECT status, COUNT(*) as count 
FROM notification_queue 
GROUP BY status;

-- Failed items
SELECT id, channel, attempts, error_message, scheduled_at 
FROM notification_queue 
WHERE status = 'failed' 
ORDER BY scheduled_at DESC 
LIMIT 10;

-- Recent log entries
SELECT * FROM notification_logs 
WHERE type = 'queue_processing' 
ORDER BY created_at DESC 
LIMIT 20;
```

---

## ⚙️ CONFIGURATION OPTIONS

### Custom retention periods (cleanup script):
```bash
# Sintaxă: php cleanup_notifications.php [queue_days] [notification_days] [log_days]

# Exemplu: Șterge queue > 14 zile, notificări > 60 zile, log-uri > 90 zile
0 4 * * * php /home/.../cleanup_notifications.php 14 60 90
```

### Custom processing limits:
```bash
# Sintaxă: php process_notifications_queue.php [limit]

# Exemplu: Procesează max 50 items per run (pentru testing)
*/5 * * * * php /home/.../process_notifications_queue.php 50
```

---

## 🚨 ALERTS & NOTIFICATIONS

### Email alerts on failure (cPanel):
1. În **Cron Jobs**, secțiunea **Cron Email**
2. Setează: `your-admin-email@example.com`
3. Vei primi email dacă script-ul returnează EXIT CODE != 0

### Exit codes:
- `0` = Success
- `1` = Warning (ex: multe failures în queue)
- `2` = Fatal error (ex: DB connection failed)

---

## 📊 PERFORMANCE TUNING

### High-volume environments (>1000 notifications/day):

1. **Increase queue processor frequency:**
   ```bash
   */2 * * * *  # Every 2 minutes instead of 5
   ```

2. **Increase processing limits:**
   ```bash
   php process_notifications_queue.php 500  # Process 500 items per run
   ```

3. **Add dedicated channel processors (optional):**
   ```bash
   # Email only (fast)
   */3 * * * * php /home/.../process_notifications_queue.php email 200
   
   # SMS only (rate-limited)
   */10 * * * * php /home/.../process_notifications_queue.php sms 50
   ```

### Low-volume environments (<100 notifications/day):

1. **Reduce queue processor frequency:**
   ```bash
   */15 * * * *  # Every 15 minutes
   ```

2. **Increase cleanup retention:**
   ```bash
   php cleanup_notifications.php 60 180 365  # Keep data longer
   ```

---

## ✅ VERIFICATION CHECKLIST

- [ ] Toate cele 4 cron jobs adăugate în cPanel
- [ ] Directorul `cron_logs/` creat și accesibil
- [ ] Permisii executabile pe script-uri (`chmod +x`)
- [ ] PHP CLI disponibil (test: `php -v`)
- [ ] Calea absolută corectă în comenzi (`/home/username/...`)
- [ ] Email alerts configurate în cPanel
- [ ] Testare manuală: `php scripts/process_notifications_queue.php`
- [ ] Verificare primul run în log-uri după 5 minute
- [ ] Monitorizare `notification_queue` în DB (status distribution)

---

## 🔗 RELATED FILES

- **Scripts:**
  - `scripts/process_notifications_queue.php`
  - `scripts/retry_failed_notifications.php`
  - `scripts/cleanup_notifications.php`
  - `scripts/cron_generate_notifications.php` (existing)

- **Services:**
  - `modules/notifications/services/NotificationQueueProcessor.php`
  - `modules/notifications/services/DocumentStatusUpdater.php`

- **Models:**
  - `modules/notifications/models/NotificationQueue.php`
  - `modules/notifications/models/NotificationPreference.php`
  - `modules/notifications/models/NotificationTemplate.php`

- **Documentation:**
  - `docs/NOTIFICATION_ARCHITECTURE.md`
  - `docs/NOTIFICATION_V2_IMPLEMENTATION.md`
  - `modules/notifications/README.md`

---

## 📞 SUPPORT

Dacă întâmpini probleme:

1. Verifică log-urile cron (`cron_logs/*.log`)
2. Verifică `notification_logs` table în DB
3. Rulează manual: `php scripts/process_notifications_queue.php`
4. Verifică permisii și căi absolute
5. Testează PHP CLI: `php -v` și `which php`

---

**Last Updated:** 2025-01-12  
**Version:** Notification System V2
