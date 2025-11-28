# ⏰ CRON JOBS - Quick Reference

## 📦 Setup Rapid (cPanel / Hostico)

### 1️⃣ Generare Notificări (Daily 6AM)
```
Minute: 0
Hour: 6
Day: *
Month: *
Weekday: *
Command: /usr/bin/php8.3 /home/wclsgzyf/public_html/scripts/cron_generate_notifications.php >> /home/wclsgzyf/logs/cron_notifications.log 2>&1
```

### 2️⃣ Procesare Email Queue (Every 5min)
```
Minute: */5
Hour: *
Day: *
Month: *
Weekday: *
Command: /usr/bin/php8.3 /home/wclsgzyf/public_html/scripts/process_notifications_queue.php >> /home/wclsgzyf/logs/cron_queue.log 2>&1
```

---

## 🧪 Testare Rapidă

```bash
# Test complet configurare
php scripts/test_cron_setup.php

# Test manual generare notificări
php scripts/cron_generate_notifications.php

# Test manual procesare queue
php scripts/process_notifications_queue.php

# Verifică logs
tail -f logs/cron_queue.log
```

---

## 🔍 Comenzi Utile SQL

```sql
-- Verifică notificări generate astăzi
SELECT COUNT(*) FROM notifications WHERE DATE(created_at) = CURDATE();

-- Verifică queue pending
SELECT COUNT(*) FROM notification_queue WHERE sent = 0 AND attempts < max_attempts;

-- Rate de succes ultimele 24h
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN sent = 1 THEN 1 ELSE 0 END) as sent,
    ROUND(SUM(CASE WHEN sent = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate
FROM notification_queue
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Ultimele erori
SELECT notification_id, error_message, attempts 
FROM notification_queue 
WHERE sent = 0 AND error_message IS NOT NULL 
ORDER BY updated_at DESC 
LIMIT 5;
```

---

## 🚨 Troubleshooting Express

| Problemă | Verificare | Soluție |
|----------|-----------|---------|
| Cron nu rulează | `grep CRON /var/log/syslog` | Verifică path PHP și permisiuni |
| Nu generează notificări | Rulează manual script | Verifică dacă există expirări în 30 zile |
| Email nu se trimit | `SELECT * FROM notification_queue WHERE sent=0` | Verifică config SendGrid |
| Prea multe email-uri | Verifică rate limiting | Ajustează `daysBefore` la 7-14 |

---

## 📊 Monitorizare Health

✅ **Sistem OK dacă:**
- Generare rulează zilnic fără erori
- Queue processor success rate > 95%
- Queue pending < 50 items constant
- Logs fără "FATAL ERROR"

⚠️ **Atenție dacă:**
- Queue > 100 items acumulate
- Success rate < 90%
- Multe "retry" în logs
- Utilizatori raportează lipsa email-uri

---

## 📝 Paths Important

| Fișier | Path |
|--------|------|
| Generare | `scripts/cron_generate_notifications.php` |
| Queue Processor | `scripts/process_notifications_queue.php` |
| Test Setup | `scripts/test_cron_setup.php` |
| Logs Notificări | `logs/cron_notifications.log` |
| Logs Queue | `logs/cron_queue.log` |
| Config Mail | `config/mail.php` |
| Ghid Complet | `CRON_SETUP.md` |

---

**Need Help?** Rulează: `php scripts/test_cron_setup.php`
