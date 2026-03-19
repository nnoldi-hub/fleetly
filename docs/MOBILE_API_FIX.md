# 📱 Mobile API - Deployment Fix Guide

> **Data:** 19 Martie 2026  
> **Problemă:** Aplicația mobilă nu se poate conecta la API  
> **Cauză:** 500 Internal Server Error pe https://fleetly.ro/api/v1/

---

## 🔍 Diagnostic

API-ul returnează **500 Internal Server Error**. Acest lucru poate fi cauzat de:

1. **Folder `/vendor/` lipsă** - dependențele PHP nu sunt instalate pe server
2. **Config database.php incorect** - setările bazei de date nu sunt configurate
3. **Fișiere API lipsă** - nu toate fișierele din `/api/v1/` sunt pe server

---

## 🛠️ Pași de Rezolvare

### Pasul 1: Upload fișiere actualizate

Urcă următoarele fișiere pe server via FTP/cPanel File Manager:

```
/api/v1/.htaccess           → ACTUALIZAT (fix RewriteBase)
/api/v1/index.php           → ACTUALIZAT (fix CORS)
/api/v1/test-api.php        → NOU (diagnostic)
```

### Pasul 2: Rulează testul de diagnostic

După upload, accesează în browser:

```
https://fleetly.ro/api/v1/test-api.php
```

Acest fișier va arăta:
- ✅ / ❌ Versiune PHP
- ✅ / ❌ Extensii PHP necesare
- ✅ / ❌ vendor/autoload.php există
- ✅ / ❌ Firebase JWT library
- ✅ / ❌ config/database.php
- ✅ / ❌ Fișiere API core
- ✅ / ❌ Conexiune bază de date

### Pasul 3: Rezolvă problemele raportate

#### 3.1 Dacă `vendor/autoload.php` lipsește:

**Opțiunea A - Upload manual:**
```
Urcă întregul folder /vendor/ pe server via FTP
(aproximativ 50MB, poate dura câteva minute)
```

**Opțiunea B - Composer pe server (dacă ai SSH):**
```bash
cd ~/public_html  # sau unde e instalat site-ul
composer install --no-dev --optimize-autoloader
```

#### 3.2 Dacă `config/database.php` lipsește sau e greșit:

Creează/editează pe server `/config/database.php`:

```php
<?php
// Database configuration for production
define('DB_HOST', 'localhost');          // Hostico folosește localhost
define('DB_NAME', 'nume_baza_date');     // Numele BD din cPanel
define('DB_USER', 'utilizator_bd');       // User BD din cPanel
define('DB_PASS', 'parola_bd');           // Parola BD
define('DB_CHARSET', 'utf8mb4');
```

**Unde găsești datele:**
- cPanel → MySQL Databases → vezi numele BD și user-ul creat

#### 3.3 Dacă conexiunea la baza de date eșuează:

1. Verifică credențialele în `database.php`
2. În cPanel, asigură-te că user-ul are privilegii pe baza de date
3. Verifică că baza de date conține tabelele (a fost importat backup.sql)

### Pasul 4: Verifică că API-ul funcționează

După rezolvarea problemelor, testează login-ul direct:

```powershell
# PowerShell test
Invoke-RestMethod -Uri "https://fleetly.ro/api/v1/auth/login" `
  -Method POST `
  -ContentType "application/json" `
  -Body '{"email":"admin@test.com","password":"parola"}'
```

Sau în browser accesează: `https://fleetly.ro/api/v1/test-api.php`

---

## 📁 Structură Necesară pe Server

```
fleetly.ro/
├── api/
│   └── v1/
│       ├── .htaccess             ← ACTUALIZAT
│       ├── index.php             ← ACTUALIZAT  
│       ├── test-api.php          ← NOU
│       ├── core/
│       │   ├── ApiResponse.php
│       │   ├── ApiRouter.php
│       │   └── JwtHandler.php
│       ├── middleware/
│       │   └── AuthMiddleware.php
│       └── controllers/
│           ├── AuthController.php
│           ├── VehicleController.php
│           ├── DriverController.php
│           ├── DashboardController.php
│           ├── DocumentController.php
│           ├── MaintenanceController.php
│           ├── FuelController.php
│           ├── InsuranceController.php
│           └── NotificationController.php
├── config/
│   └── database.php              ← CONFIG PRODUCȚIE
├── core/
│   ├── Database.php
│   └── ... alte fișiere
├── vendor/
│   ├── autoload.php              ← NECESAR
│   ├── firebase/php-jwt/         ← NECESAR
│   └── ... alte pachete
└── ...
```

---

## 🔧 Fișiere Modificate Azi

| Fișier | Modificare |
|--------|------------|
| `/api/v1/.htaccess` | Eliminat RewriteBase hardcodat pentru compatibilitate |
| `/api/v1/index.php` | CORS permis pentru aplicații mobile native |
| `/api/v1/test-api.php` | Fișier nou de diagnostic |

---

## 📱 După Fixare

După ce API-ul funcționează (test-api.php arată toate ✅):

1. **Testează din aplicația mobilă** - ar trebui să poți face login
2. **Șterge test-api.php** de pe server (conține informații sensibile)

---

## ❓ Probleme Comune

**Q: Aplicația se învârte și nu se conectează**
A: API-ul nu răspunde. Verifică test-api.php pentru diagnostic.

**Q: Login eșuează cu "Network error"**
A: 500 error pe server. Verifică logurile PHP sau test-api.php.

**Q: Primesc 404 pe toate endpoint-urile**
A: .htaccess nu funcționează. Verifică mod_rewrite în cPanel.

**Q: test-api.php afișează HTML în loc de JSON**
A: PHP nu procesează fișierele. Verifică configurarea serverului.
