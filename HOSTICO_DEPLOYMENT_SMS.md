# Deployment SMS Feature pe Hostico

**Data**: 24 Decembrie 2025  
**Feature**: Notificări SMS prin Twilio

## 📋 Pre-deployment Checklist

- [x] Cod testat local
- [x] Commit pe Git (`ac9e6bf`)
- [x] Push pe GitHub
- [ ] Backup bază de date Hostico
- [ ] Backup fișiere Hostico

## 🚀 Deployment Steps

### Pas 1: Conectare SSH la Hostico

```bash
ssh username@your-domain.com
# SAU folosește cPanel Terminal
```

### Pas 2: Backup

```bash
# Backup bază de date
mysqldump -u db_user -p db_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup fișiere (opțional)
cd /home/username
tar -czf fleet-backup-$(date +%Y%m%d).tar.gz public_html/
```

### Pas 3: Pull ultimele modificări

```bash
cd /home/username/public_html
# SAU calea ta specifică

# Pull de pe Git
git pull origin main

# Ar trebui să vezi:
# - core/SmsService.php
# - docs/SMS_*.md
# - test_sms_twilio.php
# - composer.json (updated)
```

### Pas 4: Instalare Twilio SDK

**Metoda 1: cPanel Terminal (Recomandat)**

```bash
cd /home/username/public_html
composer install --no-dev --optimize-autoloader
```

**Metoda 2: SSH**

```bash
cd /path/to/project
/usr/local/bin/composer install --no-dev --optimize-autoloader
# SAU
php composer.phar install --no-dev --optimize-autoloader
```

**Metoda 3: Local apoi Upload (dacă Composer nu funcționează)**

```powershell
# Local pe Windows
cd C:\wamp64\www\fleet-management
composer install --no-dev --optimize-autoloader

# Apoi încarcă folderul vendor/ pe Hostico via FTP/cPanel File Manager
# ⚠️ ATENȚIE: vendor/ poate fi mare (~10MB), verifică spațiul disponibil
```

### Pas 5: Verificare instalare

```bash
# Verifică că Twilio SDK este instalat
ls -la vendor/twilio/

# Ar trebui să vezi:
# vendor/twilio/sdk/

# Test rapid
php -r "require 'vendor/autoload.php'; echo class_exists('Twilio\Rest\Client') ? 'OK' : 'FAIL';"
# Ar trebui să afișeze: OK
```

### Pas 6: Permisiuni fișiere

```bash
# Dacă sunt probleme de permisiuni
chmod 644 core/SmsService.php
chmod 644 test_sms_twilio.php
chmod -R 755 vendor/twilio/
```

### Pas 7: Test instalare

```bash
php test_sms_twilio.php
```

Ar trebui să vezi:
```
=== Test Integrare SMS Twilio ===

1. Verificare Twilio SDK... ✓ Instalat
2. Inițializare SmsService... ✓ OK
3. Verificare configurare... ✗ NU este configurat
```

Dacă vezi "✓ Instalat", deployment-ul tehnic este OK!

## ⚙️ Configurare Twilio pe Hostico

### Pas 8: Obține credențiale Twilio

1. Login pe https://www.twilio.com/
2. Dashboard > Account Info:
   - **Account SID**: ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   - **Auth Token**: (click Show)
3. Phone Numbers > Manage > Buy a number:
   - Selectează România (+40)
   - Bifează "SMS"
   - Cumpără numărul

### Pas 9: Configurare în aplicație

**Opțiune A: Prin interfață web**

1. Accesează: `https://your-domain.com/`
2. Login ca **superadmin**
3. Meniu: **Notificări** > **Setări** > **SMS**
4. Completează formular:
   ```
   Provider: Twilio
   From Number: +40xxxxxxxxx  (numărul Twilio)
   Account SID: ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   Auth Token: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```
5. Salvează
6. Testează cu butonul "Trimite SMS de test"

**Opțiune B: Direct în baza de date** (dacă interfața nu funcționează)

```sql
-- Conectează-te la MySQL (cPanel > phpMyAdmin)
INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
VALUES (
  'sms_settings',
  '{"provider":"twilio","enabled":true,"from":"+40XXXXXXXXX","account_sid":"ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx","auth_token":"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"}',
  'json',
  'Setări SMS'
)
ON DUPLICATE KEY UPDATE 
  setting_value = VALUES(setting_value);
```

**⚠️ IMPORTANT**: Înlocuiește valorile cu credențialele tale reale!

### Pas 10: Configurare Cron Job

**cPanel > Cron Jobs**

```bash
# Procesare coadă notificări (la fiecare 5 minute)
*/5 * * * * cd /home/username/public_html && /usr/local/bin/php scripts/process_notifications_queue.php > /dev/null 2>&1

# SAU cu logging
*/5 * * * * cd /home/username/public_html && /usr/local/bin/php scripts/process_notifications_queue.php >> logs/cron.log 2>&1
```

**Verificare cale PHP:**
```bash
which php
# SAU
/usr/local/bin/php -v
```

Folosește calea corectă în cron job.

## ✅ Verificare finală

### Test 1: Verificare clasă SmsService

```bash
php -r "
require 'vendor/autoload.php';
require 'core/SmsService.php';
\$sms = new SmsService();
echo 'SmsService OK' . PHP_EOL;
"
```

### Test 2: Verificare configurare

Accesează în browser:
```
https://your-domain.com/modules/notifications/index.php?action=settings
```

Secțiunea SMS ar trebui să fie vizibilă.

### Test 3: Trimitere SMS de test

1. În interfață: Notificări > Setări > SMS
2. Introdu numărul tău: +40712345678
3. Click "Trimite SMS de test"
4. Verifică telefonul

### Test 4: Verificare cron

```bash
# Rulează manual
cd /home/username/public_html
php scripts/process_notifications_queue.php

# Verifică output-ul
cat logs/cron.log
```

## 🐛 Troubleshooting Hostico

### Problema 1: "Class 'Twilio\Rest\Client' not found"

**Cauză**: Twilio SDK nu este instalat corect.

**Soluție**:
```bash
cd /path/to/project
rm -rf vendor/
composer clear-cache
composer install --no-dev --optimize-autoloader
```

### Problema 2: "composer: command not found"

**Cauză**: Composer nu este disponibil în PATH.

**Soluție**:
```bash
# Găsește calea completă
which composer
# SAU
/usr/local/bin/composer --version

# Folosește calea completă
/usr/local/bin/composer install --no-dev
```

**Alternativă**: Încarcă manual folderul `vendor/` via FTP.

### Problema 3: Cron job nu rulează

**Verificări**:

1. **Cale PHP corectă?**
   ```bash
   which php
   ```

2. **Permisiuni script**:
   ```bash
   chmod +x scripts/process_notifications_queue.php
   ```

3. **Test manual**:
   ```bash
   /usr/local/bin/php scripts/process_notifications_queue.php
   ```

4. **Verifică log-uri cron**:
   - cPanel > Cron Jobs > Cron Email
   - SAU logs/cron.log

### Problema 4: "Permission denied" pe vendor/

**Soluție**:
```bash
chmod -R 755 vendor/
chown -R username:username vendor/
```

### Problema 5: SMS-uri nu se trimit

**Verificări**:

1. **Configurare corectă?**
   ```sql
   SELECT * FROM system_settings WHERE setting_key = 'sms_settings';
   ```

2. **Credențiale Twilio valide?**
   - Login pe Twilio Dashboard
   - Verifică Account SID și Auth Token

3. **Număr de telefon valid?**
   - Format: +40712345678 (cu +)
   - Verificat în Twilio (pentru trial account)

4. **Coada funcționează?**
   ```sql
   SELECT * FROM notification_queue WHERE channel = 'sms' AND status = 'pending';
   ```

5. **Log-uri**:
   ```bash
   tail -f logs/notifications.log
   ```

### Problema 6: "trial account" restrictions

**Cauză**: Contul Twilio trial poate trimite SMS doar către numere verificate.

**Soluție**:
1. Twilio Console > Verified Caller IDs
2. Adaugă și verifică numărul de telefon destinatar
3. **SAU** upgrade la cont paid

## 📊 Monitorizare post-deployment

### 1. Verifică log-urile

```bash
# Log notificări
tail -f logs/notifications.log

# Log cron
tail -f logs/cron.log

# Log PHP errors (variază pe hosting)
tail -f /home/username/public_html/error_log
```

### 2. Monitorizează coada

```sql
-- SMS-uri în coadă
SELECT COUNT(*) as pending_sms 
FROM notification_queue 
WHERE channel = 'sms' AND status = 'pending';

-- SMS-uri trimise azi
SELECT COUNT(*) as sent_today
FROM notification_queue 
WHERE channel = 'sms' 
  AND status = 'sent' 
  AND DATE(sent_at) = CURDATE();

-- SMS-uri eșuate
SELECT * FROM notification_queue 
WHERE channel = 'sms' 
  AND status = 'failed' 
ORDER BY last_attempt_at DESC 
LIMIT 10;
```

### 3. Dashboard Twilio

- Login pe https://www.twilio.com/console
- Monitor > Logs > Messaging
- Verifică:
  - SMS-uri trimise
  - Erori
  - Costuri

## 💰 Costuri Twilio

**Trial**: $15 credit gratuit  
**SMS România**: ~$0.08 per mesaj  
**Număr telefon**: ~$1/lună  

**Estimare** (100 SMS/zi):
- 3000 SMS × $0.08 = $240/lună
- Număr: $1/lună
- **Total**: ~$241/lună

**Protecție costuri** în aplicație:
- Rate limit: 20 SMS/oră, 100 SMS/zi per companie
- Truncare automată la 160 caractere

## 🔐 Securitate post-deployment

1. **Verifică permisiuni**:
   ```bash
   # Fișiere: 644
   # Directoare: 755
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   
   # Protejează vendor/
   chmod -R 755 vendor/
   ```

2. **Verifică .gitignore**:
   - `vendor/` nu ar trebui committat (se regenerează cu Composer)
   - `config/database.php` exclus
   - `logs/` exclus

3. **Backup regulat**:
   ```bash
   # Cron daily backup (2 AM)
   0 2 * * * mysqldump -u user -p'pass' dbname > /backups/db_$(date +\%Y\%m\%d).sql
   ```

## 📋 Post-deployment Checklist

- [ ] Git pull executat cu succes
- [ ] Composer install finalizat (vendor/twilio/ există)
- [ ] SmsService.php încărcat
- [ ] test_sms_twilio.php funcționează
- [ ] Credențiale Twilio configurate în aplicație
- [ ] SMS de test trimis cu succes
- [ ] Cron job configurat și funcțional
- [ ] Utilizatori au număr telefon în profil
- [ ] Preferințe SMS activate pentru utilizatori test
- [ ] Monitorizare activă (logs + Twilio Dashboard)
- [ ] Backup pre-deployment salvat

## 📞 Suport

**Probleme tehnice**:
- Verifică `logs/notifications.log`
- Rulează `php test_sms_twilio.php`
- Consultă [SMS_TWILIO_SETUP.md](docs/SMS_TWILIO_SETUP.md)

**Probleme Twilio**:
- Twilio Support: https://www.twilio.com/help
- Twilio Docs: https://www.twilio.com/docs/sms

**Probleme Hostico**:
- Support Hostico: https://www.hostico.ro/contact
- Knowledge Base: https://www.hostico.ro/ajutor

---

**Deployment completat! SMS notifications via Twilio sunt live! 🎉**
