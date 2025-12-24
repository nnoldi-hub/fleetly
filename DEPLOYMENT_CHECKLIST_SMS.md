# 🚀 Checklist Deployment Hostico - SMS Feature

## Pre-Deployment

- [x] Feature testat local ✓
- [x] Twilio SDK instalat (v8.10.0) ✓
- [x] Cod committat pe Git (ac9e6bf) ✓
- [x] Cod push-uit pe GitHub ✓
- [x] Documentație creată ✓
- [ ] Review cod final
- [ ] Backup local făcut

## Deployment pe Hostico

### Partea 1: Backup și Pregătire

- [ ] **Backup bază de date Hostico**
  ```bash
  mysqldump -u user -p dbname > backup_$(date +%Y%m%d).sql
  ```

- [ ] **Backup fișiere (opțional)**
  ```bash
  tar -czf fleet-backup.tar.gz public_html/
  ```

- [ ] **Verificare spațiu disponibil**
  ```bash
  df -h
  # Twilio SDK necesită ~10MB în vendor/
  ```

### Partea 2: Deploy Cod

- [ ] **Conectare SSH/cPanel Terminal**
  ```bash
  ssh username@domain.com
  # SAU cPanel > Terminal
  ```

- [ ] **Navigate la proiect**
  ```bash
  cd /home/username/public_html
  ```

- [ ] **Pull de pe Git**
  ```bash
  git pull origin main
  ```
  
  Verifică că vezi:
  - [ ] core/SmsService.php
  - [ ] test_sms_twilio.php
  - [ ] docs/SMS_*.md
  - [ ] composer.json (updated)

### Partea 3: Instalare Dependențe

- [ ] **Instalare Twilio SDK**
  
  **Opțiune A: Composer pe server**
  ```bash
  composer install --no-dev --optimize-autoloader
  # SAU
  /usr/local/bin/composer install --no-dev --optimize-autoloader
  ```
  
  **Opțiune B: Upload manual vendor/**
  ```powershell
  # Pe Windows local:
  composer install --no-dev
  # Apoi upload vendor/ via FTP/File Manager
  ```

- [ ] **Verificare instalare**
  ```bash
  ls -la vendor/twilio/sdk/
  # Ar trebui să existe
  ```

- [ ] **Test PHP**
  ```bash
  php -r "require 'vendor/autoload.php'; echo class_exists('Twilio\Rest\Client') ? 'OK' : 'FAIL';"
  # Ar trebui: OK
  ```

### Partea 4: Permisiuni

- [ ] **Set permisiuni corecte**
  ```bash
  chmod 644 core/SmsService.php
  chmod 644 test_sms_twilio.php
  chmod -R 755 vendor/
  chmod -R 775 logs/ uploads/
  ```

### Partea 5: Configurare Twilio

- [ ] **Credențiale Twilio obținute**
  - [ ] Account SID
  - [ ] Auth Token
  - [ ] From Number (+40xxxxxxxxx)

- [ ] **Configurare în aplicație**
  
  Accesează: `https://your-domain.com/`
  
  - [ ] Login ca superadmin
  - [ ] Notificări > Setări > SMS
  - [ ] Completează formular:
    - [ ] Provider: Twilio
    - [ ] From Number: +40xxxxxxxxx
    - [ ] Account SID: ACxxxxxxxx...
    - [ ] Auth Token: xxxxxxxx...
  - [ ] Salvează

- [ ] **Test SMS**
  - [ ] Click "Trimite SMS de test"
  - [ ] Introdu numărul tău: +40712345678
  - [ ] Verifică că primești SMS-ul

### Partea 6: Cron Jobs

- [ ] **Configurare Cron pentru procesare coadă**
  
  cPanel > Cron Jobs:
  ```
  */5 * * * * cd /home/username/public_html && /usr/local/bin/php scripts/process_notifications_queue.php >> logs/cron.log 2>&1
  ```
  
  Verificări:
  - [ ] Cale PHP corectă (`which php`)
  - [ ] Cale proiect corectă
  - [ ] Permisiuni script (chmod +x)

- [ ] **Test manual cron**
  ```bash
  cd /home/username/public_html
  php scripts/process_notifications_queue.php
  # Verifică output și errors
  ```

### Partea 7: Configurare Utilizatori

- [ ] **Verificare numere telefon**
  - [ ] Utilizatorii au telefon în profil (format: +40712345678)
  - [ ] Verificat în DB:
    ```sql
    SELECT id, username, email, phone FROM users WHERE phone IS NOT NULL;
    ```

- [ ] **Activare preferințe SMS**
  - [ ] Utilizatori test: Notificări > Preferințe > bifează SMS
  - [ ] Verificat în DB:
    ```sql
    SELECT * FROM notification_preferences WHERE sms_enabled = 1;
    ```

## Post-Deployment Testing

### Test 1: Verificare instalare

- [ ] **Test SmsService**
  ```bash
  php test_sms_twilio.php
  ```
  Ar trebui:
  - [ ] ✓ Twilio SDK instalat
  - [ ] ✓ SmsService inițializat
  - [ ] ✓ Configurat (dacă ai setat credențialele)

### Test 2: Test SMS manual

- [ ] **Prin interfață**
  - [ ] Notificări > Setări > SMS > "Trimite SMS de test"
  - [ ] Verificat SMS primit pe telefon

- [ ] **Prin script**
  ```bash
  php test_sms_twilio.php
  # Urmează pașii interactivi
  ```

### Test 3: Test notificare completă

- [ ] **Creare notificare test**
  - [ ] Creează asigurare care expiră în 30 zile
  - [ ] Notificări > "Generează Notificări"
  - [ ] Verifică că notificarea apare în listă

- [ ] **Procesare coadă**
  ```bash
  php scripts/process_notifications_queue.php
  # SAU așteaptă 5 minute pentru cron
  ```

- [ ] **Verificare SMS primit**
  - [ ] SMS ajunge pe telefon
  - [ ] Conținut corect
  - [ ] Număr expeditor corect

### Test 4: Monitorizare

- [ ] **Check logs**
  ```bash
  tail -f logs/notifications.log
  tail -f logs/cron.log
  ```

- [ ] **Check bază de date**
  ```sql
  -- SMS-uri în coadă
  SELECT * FROM notification_queue WHERE channel = 'sms' ORDER BY created_at DESC LIMIT 10;
  
  -- SMS-uri trimise azi
  SELECT COUNT(*) FROM notification_queue 
  WHERE channel = 'sms' AND status = 'sent' AND DATE(sent_at) = CURDATE();
  ```

- [ ] **Check Twilio Dashboard**
  - [ ] Login pe twilio.com
  - [ ] Monitor > Logs > Messaging
  - [ ] Verifică SMS-uri trimise și costuri

## Troubleshooting

### Dacă apar probleme:

**Problem: "Class Twilio\Rest\Client not found"**
- [ ] Verificat că vendor/twilio/sdk/ există
- [ ] Rulat `composer install` din nou
- [ ] Verificat require 'vendor/autoload.php'

**Problem: "composer: command not found"**
- [ ] Găsit calea: `which composer`
- [ ] Folosit cale completă: `/usr/local/bin/composer`
- [ ] SAU upload manual vendor/

**Problem: SMS-uri nu se trimit**
- [ ] Verificat configurare: system_settings.sms_settings
- [ ] Verificat credențiale Twilio (Account SID, Auth Token)
- [ ] Verificat număr telefon destinatar (format +40...)
- [ ] Pentru trial: verificat număr în Twilio Verified Caller IDs
- [ ] Verificat logs: `tail -f logs/notifications.log`

**Problem: Cron nu rulează**
- [ ] Verificat cale PHP: `which php`
- [ ] Verificat permisiuni script: `chmod +x scripts/process_notifications_queue.php`
- [ ] Test manual: `php scripts/process_notifications_queue.php`
- [ ] Verificat log cron: `tail -f logs/cron.log`

## Rollback Plan (Dacă ceva merge rău)

- [ ] **Restore bază de date**
  ```bash
  mysql -u user -p dbname < backup_YYYYMMDD.sql
  ```

- [ ] **Restore cod**
  ```bash
  git reset --hard HEAD~1
  # SAU
  git checkout 2a20dff  # commit anterior
  ```

- [ ] **Clear vendor/**
  ```bash
  rm -rf vendor/
  git checkout vendor/  # dacă era în Git
  ```

## Post-Deployment Verification

- [ ] **Funcționalitate de bază**
  - [ ] Login funcționează
  - [ ] Dashboard se încarcă
  - [ ] Module principale (vehicule, etc.) funcționează
  - [ ] Email-uri se trimit încă

- [ ] **Feature SMS**
  - [ ] Configurare SMS accesibilă
  - [ ] SMS de test funcționează
  - [ ] Notificări SMS se procesează
  - [ ] Rate limiting funcționează (verificat în DB)

- [ ] **Performance**
  - [ ] Pagini se încarcă rapid (<3s)
  - [ ] Procesare coadă rapid (<5s pentru 100 items)
  - [ ] Memoria PHP OK (nu depășește limit)

- [ ] **Monitoring activ**
  - [ ] Logs se populează corect
  - [ ] Twilio Dashboard arată trafic
  - [ ] Cron job-uri rulează conform planificat

## Documentation & Handoff

- [ ] **Documentație actualizată**
  - [ ] README.md actualizat ✓
  - [ ] HOSTICO_DEPLOYMENT_SMS.md creat ✓
  - [ ] SMS_QUICK_START.md disponibil ✓

- [ ] **Informare utilizatori**
  - [ ] Email către admins despre feature nou
  - [ ] Tutorial SMS în aplicație
  - [ ] Documentație user-facing actualizată

- [ ] **Handoff către suport**
  - [ ] Lista cu probleme cunoscute
  - [ ] Proceduri troubleshooting
  - [ ] Contact Twilio support

## Final Sign-off

- [ ] **Deployment completat cu succes**
- [ ] **Toate testele passed**
- [ ] **Monitoring activ**
- [ ] **Backup salvat și verificat**
- [ ] **Documentație completă**
- [ ] **Echipa informată**

---

**Deployed by**: _______________  
**Date**: _______________  
**Git commit**: ac9e6bf  
**Sign-off**: _______________

---

## 📊 Metrics to Track

### Prima săptămână:

- Total SMS trimise
- Rate de succes (sent vs failed)
- Cost total Twilio
- Feedback utilizatori
- Probleme raportate

### Prima lună:

- Volum lunar SMS
- Cost lunar total
- ROI (comparativ cu email)
- Optimizări necesare

---

**🎉 Deployment completat! Urmăriți metrics și feedback!**
