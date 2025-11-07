# 🚀 Deployment Guide - Hostico Shared Hosting

Ghid complet pentru upload și configurare Fleet Management pe **Hostico** (sau orice alt shared hosting cu cPanel).

---

## 📋 Pregătire Locală

### 1. Verificare Finală

Înainte de upload, testați local **toate** funcționalitățile critice:

- ✅ Login SuperAdmin și Admin Companie
- ✅ Adăugare vehicule, șoferi, documente
- ✅ Import CSV masiv (descărcare template + upload)
- ✅ Export CSV/PDF vehicule
- ✅ Trimitere notificări (testare email SMTP)
- ✅ Rapoarte și grafice (Fleet Overview, Costuri, etc.)

### 2. Instalare Dependințe Production

```bash
cd c:\wamp64\www\fleet-management
composer install --no-dev --optimize-autoloader
```

**Ce face această comandă:**
- Instalează doar pachete necesare în producție (exclude PHPUnit)
- Optimizează autoloader-ul pentru performanță maximă

### 3. Verificare Fișiere de Configurare

**Asigură-te că există fișierele template:**

```bash
# Verifică în File Explorer:
c:\wamp64\www\fleet-management\config\database.example.php  ✓
c:\wamp64\www\fleet-management\config\mail.example.php      ✓
```

**NU urca pe server:**
- `config/database.php` (credențiale locale)
- `config/mail.php` (setări SMTP locale)
- `logs/*.log` (loguri locale)
- `uploads/**` (fișiere de test)

---

## 🌐 Configurare Domeniu pe Hostico

### 1. Achiziție Hosting

- Accesează [hostico.ro](https://www.hostico.ro)
- Alege plan **Starter** sau **Business** (minim PHP 8.1)
- Comandă certificat SSL (Let's Encrypt gratuit)

### 2. Configurare Domeniu

**cPanel → Domenii:**

1. Dacă domeniul este nou:
   - `Addon Domains` → Add Domain
   - Domain Name: `yourdomain.com`
   - Document Root: `public_html/fleet-management`

2. Dacă este domeniul principal:
   - Document Root deja setat: `public_html/`

### 3. Activare SSL

**cPanel → SSL/TLS Status:**

```
1. Găsește domeniul tău în listă
2. Click "Run AutoSSL" sau "Install Certificate"
3. Așteaptă 2-5 minute → Status: "Secure" ✓
```

**Verificare SSL:**
```
https://yourdomain.com → Lacăt verde în browser
```

---

## 📤 Upload Fișiere pe Server

### Metodă 1: FTP/SFTP (FileZilla)

#### Instalare FileZilla

- Download: [filezilla-project.org](https://filezilla-project.org)
- Instalează și deschide FileZilla Client

#### Conexiune la Hostico

**File → Site Manager → New Site:**

```
Protocol: SFTP - SSH File Transfer Protocol
Host: ftp.yourdomain.com (sau IP server din cPanel)
Port: 22
Logon Type: Normal
User: cpanel_username (din cPanel)
Password: cpanel_password
```

**Connect** → Acceptă certificat → Conectat!

#### Transfer Fișiere

**Local (stânga):** `c:\wamp64\www\fleet-management`
**Remote (dreapta):** `/home/cpanel_user/public_html/`

**Drag & Drop toate folderele:**
```
api/
assets/
config/          → Upload doar *.example.php
core/
includes/
modules/
scripts/
sql/
tools/
uploads/         → Upload doar .gitkeep (fără conținut)
composer.json
index.php
phpunit.xml.dist
README.md
.htaccess        → IMPORTANT! Asigură-te că e uploaded
```

**NU urca:**
- ❌ `vendor/` (regenerat pe server)
- ❌ `logs/` (creat automat)
- ❌ `config/database.php`
- ❌ `config/mail.php`
- ❌ `.git/` (nu e nevoie pe production)

#### Verificare Upload

```
/home/cpanel_user/public_html/
├── api/
├── assets/
├── config/
│   ├── config.php
│   ├── database.example.php  ✓
│   ├── mail.example.php      ✓
│   └── routes.php
├── core/
├── index.php
├── .htaccess                 ✓
└── ...
```

### Metodă 2: cPanel File Manager

**cPanel → File Manager:**

1. Navighează la `public_html/`
2. Click **Upload** (dreapta sus)
3. Selectează fișiere (max 256MB per upload)
4. După upload: **Extract** pentru arhive `.zip`

**Pași:**
```bash
# Local: Creează arhivă
cd c:\wamp64\www\fleet-management
# Exclude vendor/, logs/, .git/
tar -czf fleet.tar.gz --exclude=vendor --exclude=logs --exclude=.git *

# Upload fleet.tar.gz prin File Manager
# Apoi în File Manager: Click dreapta pe fleet.tar.gz → Extract
```

---

## 🔧 Configurare Server (cPanel)

### 1. Instalare Composer

**cPanel → Terminal (dacă disponibil):**

```bash
cd public_html/fleet-management
composer install --no-dev --optimize-autoloader
```

**Dacă Terminal nu e disponibil:**

Contactează suport Hostico să ruleze comanda pentru tine, SAU:

```bash
# Local: Regenerează vendor/ cu --no-dev
composer install --no-dev --optimize-autoloader

# Upload folder vendor/ complet (3000+ fișiere, durează)
# Apoi șterge vendor/ local și revenă la composer install normal
```

### 2. Setare Permisiuni Foldere

**cPanel → File Manager:**

Navighează la `public_html/fleet-management/`:

**Click dreapta → Change Permissions:**

```
uploads/           → 775 (rwxrwxr-x)
logs/              → 775 (rwxrwxr-x)
config/            → 755 (rwxr-xr-x)
toate celelalte    → 755 (rwxr-xr-x)
```

**Structură permisiuni:**
```
drwxr-xr-x    api/
drwxr-xr-x    assets/
drwxr-xr-x    config/
drwxrwxr-x    logs/         ← Writable pentru PHP
drwxrwxr-x    uploads/      ← Writable pentru PHP
-rw-r--r--    index.php
-rw-r--r--    .htaccess
```

### 3. Creare Bază de Date

**cPanel → MySQL Databases:**

#### 3.1. Creare DB

```
Database Name: cpanel_fleet_core
→ Create Database
```

**Notează:**
- Database: `cpanel_username_fleet_core` (prefix automat)

#### 3.2. Creare User

```
Username: cpanel_fleetuser
Password: [generează parolă sigură - 16+ caractere]
→ Create User
```

**Notează:**
- User: `cpanel_username_fleetuser`
- Password: `XyZ123...` (salvează în manager parole!)

#### 3.3. Atribuire Privilegii

```
User: cpanel_username_fleetuser
Database: cpanel_username_fleet_core
Privileges: ALL PRIVILEGES ✓
→ Add
```

### 4. Import Schema BD

**cPanel → phpMyAdmin:**

1. Selectează DB: `cpanel_username_fleet_core`
2. Tab **Import**
3. **Choose File** → selectează `sql/schema.sql` (local)
4. Format: **SQL**
5. **Go** (Import)

**Verificare:**
```
Structure → Afișează tabele:
- companies
- users
- roles
- permissions
- audit_logs
- vehicle_types
- notification_settings
→ Total ~20 tabele ✓
```

**Optional: Date Demo**
```
Import → sql/sample_data.sql
→ Companie demo + utilizatori test
```

### 5. Configurare `database.php`

**cPanel → File Manager:**

```bash
# Navighează la config/
Click dreapta pe database.example.php → Copy
Redenumește copia în: database.php
Click dreapta pe database.php → Edit
```

**Editează cu valorile de la pasul 3:**

```php
<?php
return [
    'host' => 'localhost',              // sau IP MySQL din cPanel
    'port' => 3306,
    'database' => 'cpanel_username_fleet_core',  // DB creat mai devreme
    'username' => 'cpanel_username_fleetuser',   // User creat
    'password' => 'XyZ123...PASSWORD...',        // Parola salvată
    'charset' => 'utf8mb4',
];
```

**Save Changes** → Permisiuni: `644 (rw-r--r--)`

### 6. Configurare `config.php`

**File Manager → config/config.php → Edit:**

```php
<?php
// config/config.php

// URL de bază (HTTPS obligatoriu în producție!)
define('BASE_URL', 'https://yourdomain.com/');

// Sau pentru subdirector:
// define('BASE_URL', 'https://yourdomain.com/fleet-management/');

define('APP_NAME', 'Fleet Management');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Europe/Bucharest');

// Debug mode OFF în producție!
define('DEBUG_MODE', false);
```

**IMPORTANT:** Dacă aplicația e în subdirector (`/fleet-management/`), include-l în `BASE_URL`!

### 7. Configurare Email SMTP (Opțional)

**File Manager → config/ → Copy mail.example.php → mail.php:**

```php
<?php
return [
    'enabled' => true,
    'driver' => 'smtp',
    'smtp' => [
        'host' => 'smtp.yourdomain.com',     // sau smtp.gmail.com
        'port' => 587,                        // sau 465 pentru SSL
        'username' => 'noreply@yourdomain.com',
        'password' => 'smtp_password_here',
        'encryption' => 'tls',                // sau 'ssl'
        'timeout' => 10,
    ],
    'from' => [
        'email' => 'noreply@yourdomain.com',
        'name' => 'Fleet Management System',
    ],
];
```

**Pentru Gmail:**
```php
'host' => 'smtp.gmail.com',
'port' => 587,
'username' => 'your-email@gmail.com',
'password' => 'app_password_here',  // Nu parola Gmail, ci App Password!
'encryption' => 'tls',
```

**Generare App Password Gmail:**
```
1. Google Account → Security
2. 2-Step Verification → App passwords
3. Select app: Mail → Device: Other (Fleet Management)
4. Generate → Copiază parola de 16 caractere
```

---

## ✅ Testare Aplicație

### 1. Verificare Acces Principal

**Browser:** `https://yourdomain.com`

**Așteptat:**
- Pagină login Fleet Management
- Fără erori 404/500
- SSL activ (lacăt verde)

**Dacă vezi erori:**

#### Eroare: "500 Internal Server Error"

**Cauze posibile:**

1. **Lipsește `.htaccess`**
   ```bash
   # Verifică în File Manager dacă există:
   /public_html/fleet-management/.htaccess
   
   # Dacă lipsește, creează-l cu conținut:
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```

2. **Permisiuni greșite**
   ```bash
   # File Manager: Change Permissions pe toate folderele/fișierele
   Folders: 755
   Files: 644
   uploads/: 775
   logs/: 775
   ```

3. **Eroare PHP syntax**
   ```bash
   # cPanel → Error Log (sau logs/error_log)
   # Verifică ultimele linii pentru erori PHP
   ```

#### Eroare: "404 Not Found"

**Cauze:**

1. **BASE_URL greșit în `config/config.php`**
   ```php
   // Dacă aplicația e în subdirector:
   define('BASE_URL', 'https://yourdomain.com/fleet-management/');
   //                                              ^^^^ Include subdirectorul!
   ```

2. **mod_rewrite dezactivat**
   ```bash
   # Contactează suport Hostico să activeze:
   "Vă rog să activați mod_rewrite Apache pentru domeniul meu"
   ```

#### Eroare: "Database connection failed"

**Cauze:**

1. **Credențiale greșite în `config/database.php`**
   ```bash
   # Verifică:
   - Username are prefix cpanel_username_
   - Database are prefix cpanel_username_
   - Password corect (fără spații extra)
   ```

2. **User fără privilegii**
   ```bash
   # cPanel → MySQL Databases
   # Current Databases → Check user are ALL PRIVILEGES pe DB
   ```

### 2. Login SuperAdmin

**Credentials (din `sql/sample_data.sql`):**

```
Username: superadmin
Password: Admin123!
```

**Dacă nu merge login:**

```sql
# phpMyAdmin → SQL Tab:
SELECT * FROM users WHERE role = 'superadmin';

# Dacă nu există, creează manual:
INSERT INTO users (username, email, password, role, status) VALUES
('superadmin', 'admin@yourdomain.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 'active');

# Parola: password (hashul de mai sus)
# Schimbă după primul login!
```

### 3. Verificare Funcționalități

**După login SuperAdmin:**

- ✅ Dashboard SuperAdmin afișat
- ✅ Meniu: Companii, Utilizatori, Setări
- ✅ Click **Companii** → Afișează lista (gol sau cu date demo)

**Adaugă companie test:**

```
Companii → Adaugă Companie
- Nume: Test Company SRL
- Email: admin@test.com
- Plan: Professional
- Max Users: 15
- Max Vehicles: 100
→ Salvează

Verifică:
- Companie apare în listă ✓
- Click "Act as" → Intri în modul intervenție ✓
- Banner albastru "Modul Intervenție" vizibil ✓
```

**Test funcționalități companie:**

```
Dashboard Companie → Vehicule → Adaugă Vehicul
- Număr înmatriculare: B-TEST-01
- Marcă: Dacia
- Model: Logan
- An: 2020
- Tip: Autoturism Personal (1)
→ Salvează

Verifică:
- Vehicul apare în listă ✓
- Click "Export CSV" → Descarcă fișier ✓
- Click "Export PDF" → Descarcă fișier ✓
```

**Test import CSV:**

```
Dashboard → Import CSV Masiv
- Click "Descarcă Template Vehicule" ✓
- Deschide în Excel, completează o linie de test
- Salvează ca CSV UTF-8
- Upload fișier
→ Verifică raport import (success/errors)
```

**Test notificări (dacă SMTP configurat):**

```
Dashboard → Notificări → Setări
- Click "Trimite Test Email"
→ Verifică inbox (inclusiv Spam) ✓
```

---

## 🔔 Configurare Cron Job Notificări

**cPanel → Cron Jobs:**

### Adaugă Cron Job

```
Common Settings: Custom
Minute: */5 (la fiecare 5 minute)
Hour: *
Day: *
Month: *
Weekday: *

Command:
/usr/local/bin/php /home/cpanel_username/public_html/fleet-management/scripts/process_notifications.php >> /home/cpanel_username/logs/cron_notifications.log 2>&1
```

**Explicație comandă:**
- `/usr/local/bin/php` = PHP CLI (verifică path cu suport Hostico)
- `/home/.../process_notifications.php` = Script procesare notificări
- `>> .../cron_notifications.log` = Redirect output în log
- `2>&1` = Capturează și erorile

**Verificare execuție:**

```bash
# După 5 minute, verifică log:
File Manager → logs/cron_notifications.log

# Conținut așteptat:
[2025-01-07 14:05:01] Procesare notificări început...
[2025-01-07 14:05:02] 0 notificări trimise
[2025-01-07 14:05:02] Procesare completă
```

---

## 🔒 Securitate Production

### 1. Schimbare Parole Implicite

**SuperAdmin:**
```sql
# phpMyAdmin → SQL:
UPDATE users SET password = '$2y$10$NEW_BCRYPT_HASH_HERE' WHERE username = 'superadmin';

# Generare hash nou cu tools/hash.php:
# Local: php tools/hash.php "NewSecurePassword123!"
# Copiază hashul în SQL-ul de mai sus
```

### 2. Dezactivare DEBUG Mode

**config/config.php:**
```php
define('DEBUG_MODE', false);  // IMPORTANT în producție!
```

### 3. Protecție Directoare

**Adaugă `index.html` gol în:**
```
uploads/
logs/
config/
sql/
scripts/
tools/
```

**Conținut `index.html`:**
```html
<!-- Access denied -->
```

**Sau în `.htaccess` din fiecare folder:**
```apache
Order deny,allow
Deny from all
```

### 4. Backup Automat

**cPanel → Backup Wizard:**

```
Full Backup → Generate
Backup Destination: Home Directory
Email: your-email@domain.com
→ Generate Backup (zilnic/săptămânal)
```

**Sau Cron Job custom:**

```bash
# cPanel → Cron Jobs → Adaugă:
0 2 * * * /usr/local/bin/php /home/cpanel_username/public_html/fleet-management/scripts/backup.php
```

**Creează `scripts/backup.php`:**
```php
<?php
$date = date('Y-m-d_H-i-s');
$backup_dir = '/home/cpanel_username/backups/';

// Backup BD
exec("mysqldump -u DB_USER -pDB_PASS DB_NAME > {$backup_dir}db_{$date}.sql");

// Backup uploads
exec("tar -czf {$backup_dir}uploads_{$date}.tar.gz /home/.../uploads/");

// Cleanup (păstrează ultimele 30 zile)
exec("find {$backup_dir} -type f -mtime +30 -delete");

echo "[" . date('Y-m-d H:i:s') . "] Backup complet\n";
```

---

## 📊 Monitorizare & Logs

### 1. Error Log PHP

**cPanel → Metrics → Errors:**

```
Ultimele erori PHP
- Fatal errors
- Warnings
- Notices
```

**Sau File Manager:**
```
public_html/error_log (Apache error log)
logs/php_errors.log (custom PHP log)
```

### 2. Access Log

**cPanel → Metrics → Raw Access:**

```
Descarcă access-logs/yourdomain.com
→ Analizează trafic, IP-uri, user agents
```

### 3. Bandwidth Usage

**cPanel → Metrics → Bandwidth:**

```
Verifică consum lunar
- HTTP
- FTP
- Mail
- Total
```

---

## 🆘 Troubleshooting

### Erori Frecvente

#### 1. "PHP Version too old"

**Soluție:**
```
cPanel → Select PHP Version
→ Selectează PHP 8.1 (sau 8.2)
→ Set as current
```

#### 2. "Composer not found"

**Soluție:**
```
# Contact Hostico support:
"Vă rog să rulați comanda pentru mine:
cd /home/cpanel_username/public_html/fleet-management
composer install --no-dev --optimize-autoloader"

# Sau upload manual vendor/ (nu recomandat)
```

#### 3. "Memory limit exceeded"

**Soluție:**
```
cPanel → Select PHP Version → Options
memory_limit = 256M
→ Save
```

#### 4. "Upload file too large"

**Soluție:**
```
cPanel → Select PHP Version → Options
upload_max_filesize = 20M
post_max_size = 25M
→ Save
```

#### 5. "Session error"

**Soluție:**
```bash
# File Manager: Verifică permisiuni
/tmp/ → 777 (rwxrwxrwx)

# Sau schimbă session.save_path în php.ini:
session.save_path = "/home/cpanel_username/tmp"
```

---

## 📞 Suport Hostico

### Contact

- **Website:** [hostico.ro/contact](https://www.hostico.ro/contact)
- **Email:** suport@hostico.ro
- **Telefon:** +40 xxx xxx xxx
- **Ticket:** cPanel → Support → Open Ticket

### Informații de Furnizat

Când deschizi ticket, menționează:

```
Subiect: Configurare aplicație PHP Fleet Management

Detalii:
- Domeniu: yourdomain.com
- Plan hosting: [Starter/Business/etc.]
- Problemă: [descrie eroarea]
- Pași reproduși: [1, 2, 3...]
- Logs: [copiază ultimele 10 linii din error_log]
- Screenshot: [atașează dacă e relevant]
```

---

## ✅ Checklist Final

Înainte de a considera deployment-ul complet:

- [ ] **SSL activ** (https:// funcționează)
- [ ] **Login SuperAdmin** (test credentials)
- [ ] **Adăugare companie** (creare BD tenant automat)
- [ ] **Modul intervenție** (Act as company)
- [ ] **CRUD vehicule** (adaugă/editează/șterge)
- [ ] **Import CSV** (descarcă template + upload)
- [ ] **Export CSV/PDF** (descarcă fișiere)
- [ ] **Notificări email** (test SMTP)
- [ ] **Rapoarte** (Fleet Overview, Costuri, Mentenanță)
- [ ] **Cron job** (notificări automate la fiecare 5 min)
- [ ] **Backup** (configurare zilnică)
- [ ] **Permisiuni** (uploads/ logs/ writable)
- [ ] **DEBUG_MODE = false** (producție)
- [ ] **Parole schimbate** (SuperAdmin + DB user)
- [ ] **Documentație** (README.md + DEPLOYMENT.md accesibile)

---

## 🎓 Next Steps

După deployment reușit:

1. **Instruire utilizatori:** Sesiune demo pentru echipă (login, adăugare vehicule, rapoarte)
2. **Configurare avansată:** Integrare SMS, API externe, backup offsite
3. **Monitoring:** Google Analytics, Sentry pentru error tracking
4. **Optimizări:** CDN pentru assets, Redis cache (dacă disponibil)
5. **Marketing:** Landing page publică, link în footer aplicație

---

**🎉 Deployment Complet! Fleet Management este LIVE!**

Pentru întrebări sau probleme, consultă [README.md](README.md) sau contactează echipa de suport.
