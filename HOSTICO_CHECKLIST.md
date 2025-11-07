# 📦 CHECKLIST UPLOAD HOSTICO

## ✅ Pași Obligatorii Înainte de Upload

### 1. Pregătire Locală
```powershell
cd c:\wamp64\www\fleet-management
composer install --no-dev --optimize-autoloader
```

### 2. Fișiere de NE-UPLOAD
**NU urca pe server:**
- ❌ `vendor/` (se regenerează pe server)
- ❌ `config/database.php` (credențiale locale)
- ❌ `config/mail.php` (setări SMTP locale)
- ❌ `logs/*.log` (loguri locale)
- ❌ `.git/` (nu e necesar)
- ❌ `uploads/**` conținut (doar structura de foldere)

### 3. Verificare Fișiere OBLIGATORII
**TREBUIE să existe pe server:**
- ✅ `.htaccess` (mod_rewrite)
- ✅ `config/database.example.php` (template)
- ✅ `config/mail.example.php` (template)
- ✅ `sql/schema.sql` (schema BD)
- ✅ `composer.json` (pentru regenerare vendor/)

---

## 🌐 Pași pe Server Hostico

### PASUL 1: Upload FTP (FileZilla)
```
Host: ftp.yourdomain.com
User: cpanel_username
Password: cpanel_password
Port: 22 (SFTP)

Upload folder complet în:
/home/cpanel_username/public_html/
```

### PASUL 2: Creare Bază de Date (cPanel → MySQL Databases)
```
1. Create Database: fleet_core
   → Notează: cpanel_username_fleet_core

2. Create User: fleetuser
   Password: [GENERAT SIGUR - 16 chars]
   → Notează: cpanel_username_fleetuser

3. Add User To Database:
   User: cpanel_username_fleetuser
   Database: cpanel_username_fleet_core
   Privileges: ALL PRIVILEGES ✓
```

### PASUL 3: Import Schema (cPanel → phpMyAdmin)
```
1. Selectează DB: cpanel_username_fleet_core
2. Import → Choose File: sql/schema.sql
3. Go (Import)
4. Verifică: ~20 tabele create ✓
```

### PASUL 4: Configurare database.php (cPanel → File Manager)
```
1. Navighează: config/
2. Copy: database.example.php → database.php
3. Edit database.php:

<?php
return [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'cpanel_username_fleet_core',
    'username' => 'cpanel_username_fleetuser',
    'password' => 'PAROLA_GENERATA_PASUL2',
    'charset' => 'utf8mb4',
];

4. Save → Permissions: 644
```

### PASUL 5: Configurare config.php
```
Edit: config/config.php

define('BASE_URL', 'https://yourdomain.com/');
// SAU pentru subdirector:
// define('BASE_URL', 'https://yourdomain.com/fleet-management/');

define('DEBUG_MODE', false);  // IMPORTANT!
```

### PASUL 6: Regenerare Composer (cPanel → Terminal)
```bash
cd public_html/fleet-management
composer install --no-dev --optimize-autoloader
```

**Dacă Terminal nu e disponibil:**
Contactează Hostico support să ruleze comanda.

### PASUL 7: Setare Permisiuni (File Manager)
```
uploads/    → 775 (rwxrwxr-x)
logs/       → 775 (rwxrwxr-x)
config/     → 755 (rwxr-xr-x)
.htaccess   → 644 (rw-r--r--)
toate altele → 755/644
```

### PASUL 8: Activare SSL (cPanel → SSL/TLS Status)
```
1. Găsește domeniu în listă
2. Run AutoSSL
3. Așteaptă 2-5 minute
4. Verifică: https://yourdomain.com (lacăt verde ✓)
```

### PASUL 9: Test Aplicație
```
Browser: https://yourdomain.com

Login SuperAdmin:
Username: superadmin
Password: Admin123!

Verifică:
✅ Dashboard încărcat
✅ Companii → Adaugă Companie (testează)
✅ Act as company (testează)
✅ Vehicule → Adaugă Vehicul
✅ Import CSV → Descarcă Template
✅ Export CSV/PDF
```

### PASUL 10: Configurare Cron Job (cPanel → Cron Jobs)
```
Minute: */5
Hour: *
Day: *
Month: *
Weekday: *

Command:
/usr/local/bin/php /home/cpanel_username/public_html/fleet-management/scripts/process_notifications.php >> /home/cpanel_username/logs/cron_notifications.log 2>&1
```

---

## 🔒 Securitate Post-Deployment

### 1. Schimbă Parolă SuperAdmin
```sql
# phpMyAdmin → SQL:
# Generează hash nou cu: php tools/hash.php "NewPassword123!"

UPDATE users 
SET password = '$2y$10$NEW_HASH_HERE' 
WHERE username = 'superadmin';
```

### 2. Configurare Email (Opțional)
```
Copy: config/mail.example.php → config/mail.php
Edit: SMTP credentials (Gmail/SendGrid/etc.)
Test: Dashboard → Notificări → Trimite Test Email
```

### 3. Backup Automat (cPanel → Backup Wizard)
```
Full Backup → Generate
Frequency: Zilnic/Săptămânal
Destination: Home Directory
Email notification: your-email@domain.com
```

---

## 📞 Contact Suport Hostico

**Dacă întâmpini probleme:**

- **Email:** suport@hostico.ro
- **Ticket:** cPanel → Support → Open Ticket
- **Telefon:** [vezi website hostico.ro]

**Template ticket:**
```
Subiect: Configurare aplicație PHP Fleet Management

Domeniu: yourdomain.com
Plan: [Starter/Business]

Cerere:
1. Vă rog să rulați composer install în directorul aplicației
2. Vă rog să verificați dacă mod_rewrite este activat
3. [Alta problemă specificată]

Detalii tehnice:
[copiază ultimele 10 linii din error_log]
```

---

## ✅ Checklist Final

Înainte de a considera deployment complet:

- [ ] **SSL activ** (https funcționează)
- [ ] **Login SuperAdmin** OK
- [ ] **Bază date** creată și populată
- [ ] **database.php** configurat corect
- [ ] **BASE_URL** setat la HTTPS
- [ ] **DEBUG_MODE = false**
- [ ] **Composer vendor/** regenerat
- [ ] **Permisiuni** uploads/logs writable
- [ ] **Cron job** notificări configurat
- [ ] **Backup** activat
- [ ] **Email SMTP** configurat (opțional)
- [ ] **Parola admin** schimbată
- [ ] **Test complet** funcționalități

---

## 🎉 Ready for Production!

Aplicația Fleet Management este acum **LIVE** și pregătită pentru utilizare!

**Next Steps:**
1. Instruire utilizatori (training session)
2. Adăugare date reale (companii, vehicule)
3. Monitorizare logs primele 7 zile
4. Feedback utilizatori și ajustări

**Documentație:**
- [README.md](README.md) - Documentație tehnică completă
- [PREZENTARE.md](PREZENTARE.md) - Overview caracteristici
- [DEPLOYMENT.md](DEPLOYMENT.md) - Ghid detaliat deployment

---

**Need help?** Consultă documentația sau contactează echipa de suport! 🚀
