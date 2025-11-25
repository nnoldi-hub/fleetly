# Configurare Cron Jobs pentru Notificări Automate

## 📋 Prezentare Generală

Sistemul de notificări necesită 2 cron jobs pentru funcționare automată:

1. **Generare Notificări** - Rulează zilnic pentru a crea notificări despre evenimente viitoare
2. **Procesare Queue Email** - Rulează la fiecare 5 minute pentru trimitere email-uri

---

## 🔧 1. Generare Automată Notificări

### Script: `scripts/cron_generate_notifications.php`

**Ce face:**
- Verifică asigurări care expiră în următoarele 30 zile
- Verifică mentenanță scadentă
- Verifică documente (ITP, Rovinieta) ce expiră
- Generează notificări pentru toți utilizatorii companiei

**Rulare recomandată:** Zilnic la 06:00 AM

### Configurare cPanel (Hostico)

1. Accesează **cPanel → Advanced → Cron Jobs**
2. Selectează **Common Settings: Once Per Day (0 6 * * *)**
3. Command:
```bash
/usr/bin/php8.3 /home/wclsxxx/public_html/scripts/cron_generate_notifications.php >> /home/wclsxxx/logs/cron_notifications.log 2>&1
```

### Configurare Linux/SSH

Editează crontab:
```bash
crontab -e
```

Adaugă:
```bash
# Generare notificări zilnic la 6 AM
0 6 * * * /usr/bin/php /path/to/fleetly/scripts/cron_generate_notifications.php >> /path/to/logs/cron_notifications.log 2>&1
```

### Configurare Windows Task Scheduler

1. Deschide **Task Scheduler**
2. Create Basic Task → Name: "Fleet Notifications Generator"
3. Trigger: **Daily at 6:00 AM**
4. Action: **Start a program**
   - Program: `C:\php\php.exe`
   - Arguments: `C:\wamp64\www\fleet-management\scripts\cron_generate_notifications.php`

---

## 📧 2. Procesare Queue Email

### Script: `scripts/process_notifications_queue.php`

**Ce face:**
- Procesează notificări în așteptare din queue
- Verifică preferințe utilizator (canale active, quiet hours)
- Aplică rate limiting per company/channel
- Trimite email-uri prin SendGrid
- Retry automat pentru trimiteri eșuate (max 3 încercări)

**Rulare recomandată:** La fiecare 5 minute

### Configurare cPanel (Hostico)

1. Accesează **cPanel → Advanced → Cron Jobs**
2. Selectează **Common Settings: Twice Per Hour (*/5 * * * *)**
3. Command:
```bash
/usr/bin/php8.3 /home/wclsxxx/public_html/scripts/process_notifications_queue.php >> /home/wclsxxx/logs/cron_queue.log 2>&1
```

### Configurare Linux/SSH

```bash
# Procesare queue la fiecare 5 minute
*/5 * * * * /usr/bin/php /path/to/fleetly/scripts/process_notifications_queue.php >> /path/to/logs/cron_queue.log 2>&1
```

### Configurare Windows Task Scheduler

1. Create Basic Task → Name: "Fleet Notifications Queue Processor"
2. Trigger: **Daily**
3. Repeat task every: **5 minutes**
4. For a duration of: **Indefinitely**
5. Action: **Start a program**
   - Program: `C:\php\php.exe`
   - Arguments: `C:\wamp64\www\fleet-management\scripts\process_notifications_queue.php`

---

## 🧪 Testare

### Test Manual Generare Notificări

SSH sau Terminal:
```bash
php /path/to/scripts/cron_generate_notifications.php
```

Browser (doar pentru debug):
```
https://fleetly.ro/scripts/cron_generate_notifications.php
```

**Output așteptat:**
```
[2025-11-25 06:00:01] Pornire generare automată notificări
[2025-11-25 06:00:02] Companie 1: evenimente generate=5
[2025-11-25 06:00:03] Finalizat. Companii procesate=1 Total evenimente=5
```

### Test Manual Procesare Queue

SSH sau Terminal:
```bash
php /path/to/scripts/process_notifications_queue.php
```

**Output așteptat:**
```
[2025-11-25 08:15:01] [INFO] === NOTIFICATION QUEUE PROCESSOR START ===
[2025-11-25 08:15:01] [INFO] Queue backlog: 5 items
[2025-11-25 08:15:01] [INFO] Processing up to 100 items...
[2025-11-25 08:15:03] [INFO] Processing completed in 2.15s
[2025-11-25 08:15:03] [SUCCESS] Sent: 5
[2025-11-25 08:15:03] [INFO] Failed: 0
[2025-11-25 08:15:03] [SUCCESS] Success rate: 100%
[2025-11-25 08:15:03] [INFO] === QUEUE PROCESSOR FINISHED (EXIT CODE: 0) ===
```

---

## 📊 Monitorizare

### Verificare Logs

cPanel File Manager → `logs/` folder:
- `cron_notifications.log` - Log generare notificări
- `cron_queue.log` - Log procesare email queue

SSH:
```bash
# Ultimele 50 linii din log generare
tail -50 /path/to/logs/cron_notifications.log

# Ultimele 50 linii din log queue
tail -50 /path/to/logs/cron_queue.log

# Monitorizare live
tail -f /path/to/logs/cron_queue.log
```

### Verificare Database

```sql
-- Verifică notificări generate astăzi
SELECT COUNT(*) as total_today 
FROM notifications 
WHERE DATE(created_at) = CURDATE();

-- Verifică queue pending
SELECT COUNT(*) as pending 
FROM notification_queue 
WHERE sent = 0 AND attempts < max_attempts;

-- Verifică rate de succes ultimele 24h
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN sent = 1 THEN 1 ELSE 0 END) as sent,
    ROUND(SUM(CASE WHEN sent = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate
FROM notification_queue
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Verifică ultimele erori
SELECT id, notification_id, error_message, attempts, created_at
FROM notification_queue
WHERE sent = 0 AND error_message IS NOT NULL
ORDER BY updated_at DESC
LIMIT 10;
```

---

## ⚙️ Configurare Avansată

### Parametri Opționali

#### Generare Notificări - Zile înainte de expirare

Editează în `cron_generate_notifications.php` sau setează în preferințe utilizator:
```php
$daysBefore = 30; // Implicit 30 zile
```

#### Queue Processor - Limită procesare

Trimite parametru pentru a limita numărul de items procesate:
```bash
php process_notifications_queue.php 50  # Procesează max 50 items
```

### Rate Limiting

Configurare în `config/config.php`:
```php
// Email rate limits per company
define('NOTIFICATION_EMAIL_RATE_LIMIT_PER_HOUR', 100);
define('NOTIFICATION_EMAIL_RATE_LIMIT_PER_DAY', 1000);
```

### Quiet Hours (Nu deranja)

Configurare în preferințe utilizator sau default în processor:
```php
// Nu trimite email între 22:00 - 08:00
$quietHoursStart = 22;
$quietHoursEnd = 8;
```

---

## 🚨 Troubleshooting

### Problema: Cron job nu rulează

**Verificare:**
```bash
# Verifică cron logs
grep CRON /var/log/syslog
# sau
tail -f /var/log/cron
```

**Soluții:**
- Verifică permisiuni script: `chmod +x scripts/*.php`
- Verifică path PHP: `which php` sau `which php8.3`
- Testează manual scriptul din CLI

### Problema: Script rulează dar nu generează notificări

**Verificare:**
1. Rulează manual și verifică output
2. Verifică dacă există asigurări/documente ce expiră în următoarele 30 zile
3. Verifică logs pentru erori

```bash
php scripts/cron_generate_notifications.php
```

### Problema: Email-uri nu se trimit

**Verificare:**
1. Verifică queue:
```sql
SELECT * FROM notification_queue WHERE sent = 0 ORDER BY created_at DESC LIMIT 10;
```

2. Verifică configurare SendGrid în `config/mail.php`
3. Rulează manual processor:
```bash
php scripts/process_notifications_queue.php
```

4. Verifică `notification_logs` pentru erori:
```sql
SELECT * FROM notification_logs 
WHERE action = 'queue_processing_failed' 
ORDER BY created_at DESC 
LIMIT 10;
```

### Problema: Prea multe email-uri (spam)

**Soluție:**
1. Ajustează `daysBefore` la o valoare mai mare (ex: 7, 14 zile)
2. Activează quiet hours în preferințe utilizator
3. Ajustează rate limiting în config

---

## 📝 Best Practices

1. **Backup înainte de configurare**
   ```bash
   mysqldump -u user -p database > backup_before_cron.sql
   ```

2. **Monitorizare regulată**
   - Verifică logs săptămânal
   - Monitorizează rate de succes email
   - Verifică feedback utilizatori

3. **Optimizare performanță**
   - Queue processor: max 100 items/run
   - Rate limiting: 100 email/oră per company
   - Cleanup notification_logs lunar

4. **Securitate**
   - Logs outside public_html
   - Restrict CLI-only pentru queue processor
   - Validare SendGrid API key

---

## 📞 Support

Pentru probleme sau întrebări:
- Verifică `logs/` folder pentru erori detaliate
- Consultă `NOTIFICATION_ARCHITECTURE.md` pentru arhitectură
- Rulează `scripts/test_notifications.php` pentru diagnosticare

---

**Ultima actualizare:** 25 noiembrie 2025  
**Versiune:** 2.0 - Production Ready
