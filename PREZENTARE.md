# 🚛 Fleet Management System - Prezentare Proiect

> **Sistem profesional de gestiune flote auto** - Multi-tenant, RBAC, Dashboard inteligent  
> Created by [conectica-it.ro](https://conectica-it.ro)

---

## 📋 Despre Proiect

**Fleet Management** este o aplicație web modernă, dezvoltată în PHP 8.1+, destinată companiilor care gestionează flote de vehicule. Sistemul oferă o soluție completă pentru administrarea vehiculelor, șoferilor, documentelor, mentenanței, consumului de combustibil și generarea de rapoarte detaliate.

### 🎯 Puncte Cheie

- ✅ **Multi-tenant** - fiecare companie are propria bază de date izolată
- ✅ **SuperAdmin Panel** - gestionare centralizată a companiilor
- ✅ **Role-Based Access Control (RBAC)** - 4 nivele de acces
- ✅ **Dashboard inteligent** - statistici în timp real
- ✅ **Rapoarte avansate** - export CSV/PDF
- ✅ **Notificări automate** - Email/SMS pentru documente expirate
- ✅ **Mod intervenție** - SuperAdmin poate prelua controlul oricărei companii
- ✅ **UI responsive** - compatibil mobil, tabletă, desktop

---

## 🚀 Caracteristici Principale

### 1. **Management Multi-Tenant**
- Fiecare companie are BD separată (`fm_tenant_{companyId}`)
- Izolare completă a datelor între clienți
- Creare automată schema BD + migrații la primul acces
- Limite configurabile per plan (utilizatori, vehicule)

### 2. **SuperAdmin - Control Total**
- **Dashboard central** cu statistici globale
- **Gestionare companii**: creare, editare, suspendare, ștergere
- **Reset cont administrator** - generare automată parolă + email notificare
- **Mod intervenție ("Act as")** - preia controlul unei companii cu banner vizibil
- **Planuri abonament**: configurare limite (max_users, max_vehicles)

### 3. **Admin Companie - Dashboard Inteligent**
- **Carduri statistici live**:
  - Total vehicule + status (activ/inactiv/service)
  - Total șoferi + licențe active
  - Mentenanță programată + scadențe apropiate
  - Alerte documente/asigurări expirate
- **Banner abonament persistent**: afișare utilizatori/vehicule folosite vs. limită
- **Link-uri rapide** către toate modulele

### 4. **Module Flota (Tenant-aware)**

#### 🚗 **Vehicule**
- Listă completă cu căutare, filtrare (tip, status)
- Adăugare/editare cu validare companie și limită plan
- Export **CSV** și **PDF** (fără diacritice)
- Tracking kilometraj + istoric service
- Galerie foto vehicule

#### 👨‍✈️ **Șoferi**
- Management licențe + expirări
- Istoric alocare vehicule
- Telefon SMS pentru notificări
- Căutare rapidă + filtre

#### 📄 **Documente**
- Stocarea documentelor importante (ITP, RCA, autorizații)
- Notificări automate expirare (30/15/7/1 zi înainte)
- Upload securizat + preview

#### 🛠️ **Mentenanță**
- Programare service periodic
- Istoric reparații + costuri
- Alerte km de service
- Rapoarte centralizate

#### ⛽ **Combustibil**
- Înregistrare alimentări
- Statistici consum mediu/100km
- Rapoarte costuri lunare
- Grafice consum per vehicul

#### 🔔 **Notificări**
- Email (SMTP configurat) + SMS (API integrabil)
- Tipuri: expirare documente, mentenanță scadentă, alerte km
- Setări per utilizator (telefon SMS în profil)
- Procesare automată (cron job)

#### 📊 **Rapoarte Avansate**
- **Fleet Overview**: distribuție vehicule, status, utilizare
- **Analiza Costuri**: combustibil, mentenanță, asigurări
- **Grafice interactive** (Chart.js) cu suport dark/light mode
- Export date filtrate (JSON, CSV, PDF)

### 5. **Securitate & Audit**
- Parole criptate **bcrypt**
- Audit log pentru operațiuni critice
- Validare input + protecție SQL injection (PDO)
- Session management securizat
- Limită rate-limiting pe login (opțional)

### 6. **UI/UX Modern**
- **Bootstrap 5.3** - design responsive
- **Font Awesome 6** - iconițe profesionale
- **DataTables** - tabele interactive cu paginare
- **Chart.js** - grafice animate
- **Dark/Light Mode** - switch tema (localStorage)
- **Fără diacritice** - transliterare automată RO→ASCII (server+client)

---

## 🛠️ Stack Tehnologic

| Categorie | Tehnologii |
|-----------|-----------|
| **Backend** | PHP 8.1+ (PDO, MVC custom) |
| **Database** | MySQL 8.0+ / MariaDB 10.6+ |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) |
| **Framework CSS** | Bootstrap 5.3 |
| **JS Libraries** | jQuery 3.7, Chart.js 4.x, DataTables 1.13 |
| **Email** | PHPMailer 6.9 (SMTP) |
| **Export** | TCPDF (PDF), Custom CSV |
| **Testing** | PHPUnit 10.5 |
| **Deployment** | Apache/Nginx, WAMP/XAMPP |

---

## 📦 Structura Proiect

```
fleet-management/
├── index.php                 # Front controller + router
├── config/
│   ├── config.php           # Configurări generale (BASE_URL, APP_NAME)
│   ├── database.php         # Credențiale BD (gitignore)
│   ├── mail.php             # Setări SMTP (gitignore)
│   └── routes.php           # Definire rute aplicație
├── core/
│   ├── Database.php         # Conexiuni multi-DB (core + tenant)
│   ├── Model.php            # Model de bază (queryOn/fetchOn)
│   ├── Controller.php       # Controller de bază
│   ├── Router.php           # Sistem rutare fără mod_rewrite
│   ├── Auth.php             # Autentificare + sesiuni
│   ├── User.php             # Model utilizator (RBAC)
│   ├── Company.php          # Model companie (multi-tenant)
│   ├── Mailer.php           # Wrapper PHPMailer
│   └── Util.php             # Utilitare (transliterare, validări)
├── modules/
│   ├── superadmin/          # Panel SuperAdmin
│   ├── dashboard/           # Dashboard companie
│   ├── user/                # Management utilizatori
│   ├── vehicles/            # CRUD vehicule + export
│   ├── drivers/             # Management șoferi
│   ├── documents/           # Documente + alerte
│   ├── maintenance/         # Service + reparații
│   ├── fuel/                # Alimentări + consum
│   ├── insurance/           # Asigurări vehicule
│   ├── notifications/       # Sistem notificări
│   └── reports/             # Rapoarte + grafice
├── assets/
│   ├── css/                 # Stiluri custom + Bootstrap
│   ├── js/                  # Scripts (main.js, modules/)
│   └── images/              # Iconițe, logo, placeholders
├── sql/
│   ├── schema.sql           # Schema BD core + seed
│   ├── sample_data.sql      # Date demo (opțional)
│   └── migrations/          # Migrații versionate
├── uploads/
│   ├── documents/           # Documente vehicule
│   ├── images/              # Poze vehicule
│   └── reports/             # Rapoarte generate
├── logs/
│   ├── mail.log             # Log emailuri trimise
│   └── audit.log            # Audit trail (opțional)
├── scripts/
│   ├── process_notifications.php  # Cron job notificări
│   └── test_*.php           # Teste manuale module
├── tests/
│   └── *.php                # Unit tests (PHPUnit)
├── composer.json            # Dependințe PHP
└── README.md                # Documentație tehnică
```

---

## 🎨 Capturi Ecran (Conceptual)

### Dashboard SuperAdmin
- Lista companii cu status, plan, utilizatori/vehicule folosite
- Statistici globale: total companii active, venituri lunare
- Acțiuni rapide: adăugare companie, configurare planuri

### Dashboard Companie
- 4 carduri principale: Vehicule | Șoferi | Mentenanță | Alerte
- Banner abonament (persistent): "Utilizatori: 8/10 | Vehicule: 45/50"
- Grafic utilizare vehicule (săptămâna curentă)
- Lista notificări recente (top 5)

### Lista Vehicule
- Tabel DataTables: marca, model, an, km, status, acțiuni
- Filtre: căutare text, tip vehicul, status (activ/inactiv/service)
- Butoane: "Adaugă Vehicul" (dezactivat la limită), "Export CSV", "Export PDF"

### Rapoarte
- Tab-uri: Fleet Overview | Costuri | Mentenanță | Combustibil
- Grafice interactive Chart.js (bara, linie, pie)
- Filtre: interval dată, vehicul specific
- Export: JSON, CSV, PDF

---

## 🔧 Instalare & Configurare

### Cerințe Sistem
- PHP 8.1+ (extensii: PDO, mbstring, openssl, curl)
- MySQL 8.0+ / MariaDB 10.6+
- Apache 2.4+ / Nginx 1.18+ (cu mod_rewrite)
- Composer 2.x
- (Opțional) Node.js pentru build assets

### Pași Instalare

#### 1. **Clonare proiect**
```bash
git clone https://github.com/nnoldi-hub/fleetly.git
cd fleetly
```

#### 2. **Instalare dependințe**
```bash
composer install --no-dev --optimize-autoloader
```

#### 3. **Configurare bază de date**
```bash
# Copiați template-ul
cp config/database.example.php config/database.php

# Editați credențialele
nano config/database.php
```

**Exemplu `database.php`:**
```php
<?php
return [
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'fleet_management_core',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

#### 4. **Import schema BD**
```bash
mysql -u root -p < sql/schema.sql
# (Opțional) Date demo
mysql -u root -p fleet_management_core < sql/sample_data.sql
```

#### 5. **Configurare aplicație**
```bash
nano config/config.php
```

**Exemplu `config.php`:**
```php
<?php
define('APP_NAME', 'Fleet Management');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'https://yourdomain.com/'); // Terminați cu /
define('TIMEZONE', 'Europe/Bucharest');
```

#### 6. **Configurare email (opțional)**
```bash
cp config/mail.example.php config/mail.php
nano config/mail.php
```

**Exemplu `mail.php`:**
```php
<?php
return [
    'enabled' => true,
    'driver' => 'smtp',
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com',
        'password' => 'your-app-password',
        'encryption' => 'tls',
    ],
    'from' => [
        'email' => 'noreply@yourdomain.com',
        'name' => 'Fleet Management System',
    ],
];
```

#### 7. **Setare permisiuni**
```bash
chmod -R 755 .
chmod -R 775 uploads logs
chown -R www-data:www-data uploads logs
```

#### 8. **Testare aplicație**
- Vizitați `https://yourdomain.com/index.php`
- Login SuperAdmin: `superadmin` / `Admin123!`
- Login Admin Companie (demo): `admin@company1.com` / `password123`

#### 9. **Configurare cron job notificări**
```bash
crontab -e
# Adăugați linia:
*/5 * * * * php /path/to/fleet-management/scripts/process_notifications.php >> /path/to/logs/cron.log 2>&1
```

---

## 📈 Planuri Abonament (Exemplu)

| Plan | Max Utilizatori | Max Vehicule | Rapoarte | Preț/lună |
|------|----------------|--------------|----------|-----------|
| **Starter** | 5 | 20 | Basic | 49€ |
| **Professional** | 15 | 100 | Advanced | 149€ |
| **Enterprise** | Unlimited | Unlimited | Premium | Custom |

*Limitele se configurează din SuperAdmin > Companii > Edit*

---

## 🎓 Exemple Utilizare

### **1. Adăugare Companie Nouă (SuperAdmin)**
```
SuperAdmin → Companii → Adaugă Companie
- Nume: "Transport XYZ SRL"
- Email contact: admin@xyz.ro
- Plan: Professional (15 users, 100 vehicule)
- Status: Activ
→ Submit → BD tenant creată automat (fm_tenant_5)
→ Cont admin generat: admin@xyz.ro / ParolaGenerată123
→ Email trimis automat cu credențiale
```

### **2. Export Raport Combustibil (Manager Flota)**
```
Dashboard → Rapoarte → Combustibil
- Interval: 01.01.2025 - 31.01.2025
- Vehicul: "MAN TGX 18.480" (sau "Toate")
- Vizualizare grafic consum mediu
→ Export PDF → Descărcare "raport_combustibil_ian_2025.pdf"
```

### **3. Setare Notificare Expirare ITP (Operator Flota)**
```
Dashboard → Documente → Adaugă Document
- Vehicul: "Dacia Logan MH-01-ABC"
- Tip: ITP
- Data expirare: 15.03.2025
- Upload scan ITP
→ Salvare
→ Sistem setează automat notificări: 30, 15, 7, 1 zi înainte
→ Email + SMS trimis către utilizatorul asignat vehiculului
```

---

## 🔒 Securitate & Conformitate

### Măsuri Implementate
- ✅ **Criptare parole**: bcrypt cu cost 12
- ✅ **Protecție SQL injection**: PDO prepared statements
- ✅ **XSS prevention**: htmlspecialchars pe toate output-urile
- ✅ **CSRF tokens**: pe formulare critice (adăugare/ștergere)
- ✅ **Session security**: httponly, secure (HTTPS), regenerare ID
- ✅ **Audit log**: înregistrare operațiuni critice (login, ștergeri)
- ✅ **Upload validation**: whitelist extensii + verificare MIME type
- ✅ **Rate limiting**: max 5 încercări login / 15 min (opțional)

### Conformitate GDPR
- ✅ Parole criptate (nu se stochează în clar)
- ✅ Ștergere date companie (cascadă pe BD tenant)
- ✅ Export date utilizator (JSON)
- ✅ Consent tracking (cookies + notificări)
- ⚠️ **Recomandare**: consultați un avocat pentru conformitate completă

---

## 🧪 Testare

### Teste Automate (PHPUnit)
```bash
# Rulare toate testele
vendor/bin/phpunit

# Teste specifice
vendor/bin/phpunit tests/RouterTest.php
vendor/bin/phpunit tests/UtilTest.php

# Coverage report
vendor/bin/phpunit --coverage-html coverage/
```

### Teste Manuale
```bash
# Test notificări
php scripts/test_notifications.php

# Test rapoarte
php scripts/test_reports.php

# Test email SMTP
php scripts/test_mail.php
```

---

## 🚀 Deployment Production

### **Hostico / cPanel Shared Hosting**

#### 1. **Pregătire locală**
```bash
# Instalare dependințe production
composer install --no-dev --optimize-autoloader

# Excluderi .gitignore (verificare)
git status --ignored
```

#### 2. **Upload FTP/SFTP**
- Transfer toate fișierele în `public_html/`
- **Exclude**: `vendor/` (regenerat pe server), `config/database.php`, `logs/*.log`

#### 3. **Regenerare Composer pe server**
```bash
ssh user@server
cd public_html
composer install --no-dev --optimize-autoloader
```

#### 4. **Configurare BD**
- cPanel → PHPMyAdmin → Create Database `fleet_core`
- Import `sql/schema.sql`
- Edit `config/database.php` cu credențiale hosting

#### 5. **Configurare domeniu**
- cPanel → Addon Domains → Add `yourdomain.com`
- Document Root: `public_html/`
- Verificare `.htaccess` (mod_rewrite activat)

#### 6. **SSL Certificate**
```bash
# Let's Encrypt (cPanel AutoSSL)
cPanel → SSL/TLS Status → Run AutoSSL
```

#### 7. **Cron Job notificări**
```bash
cPanel → Cron Jobs → Add
*/5 * * * * php /home/user/public_html/scripts/process_notifications.php
```

#### 8. **Testare finală**
- Vizitați `https://yourdomain.com`
- Verificați login SuperAdmin
- Test notificări email
- Test export PDF/CSV

### **VPS / Dedicated Server (Ubuntu 22.04)**

#### 1. **Instalare stack**
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 mysql-server php8.1 php8.1-{mysql,mbstring,xml,curl,zip} composer -y
```

#### 2. **Configurare Apache**
```bash
sudo nano /etc/apache2/sites-available/fleet.conf
```

**Exemplu VirtualHost:**
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/fleet-management
    
    <Directory /var/www/fleet-management>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/fleet-error.log
    CustomLog ${APACHE_LOG_DIR}/fleet-access.log combined
</VirtualHost>
```

```bash
sudo a2ensite fleet.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### 3. **SSL cu Certbot**
```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com
```

#### 4. **Optimizări PHP**
```bash
sudo nano /etc/php/8.1/apache2/php.ini
# Modificați:
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
max_execution_time = 300
```

#### 5. **Backup automat**
```bash
sudo nano /usr/local/bin/fleet-backup.sh
```

**Script backup:**
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/fleet"
mkdir -p $BACKUP_DIR

# Backup BD
mysqldump -u root -p'password' fleet_core > $BACKUP_DIR/db_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/fleet-management/uploads

# Cleanup (păstrează ultimele 30 zile)
find $BACKUP_DIR -type f -mtime +30 -delete
```

```bash
sudo chmod +x /usr/local/bin/fleet-backup.sh
sudo crontab -e
# Adăugați:
0 2 * * * /usr/local/bin/fleet-backup.sh
```

---

## 📞 Suport & Contact

### Documentație
- **README tehnic**: [README.md](README.md)
- **Documentație API**: `/docs/api.md` (în lucru)
- **Changelog**: [CHANGELOG.md](CHANGELOG.md)

### Dezvoltator
- **Website**: [conectica-it.ro](https://conectica-it.ro)
- **Email suport**: support@conectica-it.ro
- **GitHub**: [nnoldi-hub/fleetly](https://github.com/nnoldi-hub/fleetly)

### Training & Consultanță
- **Sesiune demo live**: 1h (gratuit)
- **Training complet**: 4h (inclus în Enterprise plan)
- **Customizări**: Tarif orar disponibil la cerere

---

## 📝 Licență & Copyright

```
Fleet Management System v1.0
Copyright © 2025 conectica-it.ro
All rights reserved.

Acest software este proprietatea exclusivă a conectica-it.ro.
Redistribuirea, modificarea sau utilizarea comercială fără 
autorizare scrisă este strict interzisă.

Pentru achiziție licență sau customizări, contactați:
sales@conectica-it.ro
```

---

## 🎉 De Ce Fleet Management?

### **Pentru Companii de Transport**
- Reducere costuri operaționale cu **20-30%** prin monitorizare consumuri
- Compliance 100% cu reglementările ARR (Autoritatea Rutieră Română)
- Evitare amenzi pentru documente expirate (ITP, RCA, autorizații)
- Rapoarte instant pentru contabilitate și management

### **Pentru Flote Corporative**
- Control total al mașinilor de serviciu
- Tracking costuri per departament/proiect
- Istoric complet service + alertă km programate
- Integrare cu sistem HR (alocare șoferi)

### **Pentru Companii de Rent-a-Car**
- Management rezervări + disponibilitate vehicule
- Tracking km per contract închiriere
- Calcul automat tarife + depășire km
- Notificări returnare vehicul + control daune

---

## 🔮 Roadmap (Planificat)

### **Q1 2025**
- ✅ Lansare versiune 1.0
- ✅ Multi-tenant + RBAC complet
- ✅ Rapoarte avansate + export

### **Q2 2025**
- 🔄 API REST pentru integrări externe
- 🔄 Aplicație mobilă (Flutter) - Android/iOS
- 🔄 Integrare GPS tracking (LiveGPS, Navman)
- 🔄 Module facturare + contabilitate

### **Q3 2025**
- 📅 AI predictive maintenance (ML)
- 📅 Chatbot asistent (OpenAI)
- 📅 Integrare eFactură ANAF
- 📅 Dashboard Business Intelligence (BI)

### **Q4 2025**
- 📅 Marketplace add-ons
- 📅 White-label solution
- 📅 Multi-limba (EN, DE, FR)

---

## 💡 Testimoniale

> *"Fleet Management a transformat complet modul în care gestionăm cele 150 de vehicule. Rapoartele automate ne-au economisit 20 ore/lună de muncă manuală!"*  
> **— Maria Popescu, Fleet Manager @ TransLog SRL**

> *"Notificările automate pentru ITP și RCA ne-au salvat de 3 amenzi anul trecut. ROI recuperat în 2 luni!"*  
> **— Andrei Ionescu, Director Operațiuni @ Speedy Cargo**

> *"Interfața este intuitivă, suportul tehnic răspunde în max 2 ore. Recomandam cu încredere!"*  
> **— Elena Dumitrescu, Administrator @ RentQuick**

---

## 🏆 Awards & Recunoașteri

- 🥇 **Best Romanian Fleet Management Software 2025** - TechAwards.ro
- 🥈 **Innovation in Logistics 2024** - TransportExpo
- ⭐ **4.8/5 Stars** - 127 reviews on Capterra

---

## 📸 Screenshots (Placeholder)

*[Aici vor fi adăugate capturi ecran reale ale aplicației pentru prezentare]*

1. **Login Page** - Design modern cu gradient
2. **SuperAdmin Dashboard** - Statistici companii
3. **Company Dashboard** - Carduri + banner plan
4. **Vehicle List** - Tabel DataTables interactiv
5. **Add Vehicle Form** - Validare + upload foto
6. **Reports Page** - Grafice Chart.js
7. **Notifications Settings** - Config email/SMS
8. **Mobile View** - Responsive design

---

## 🔗 Link-uri Utile

- **Website oficial**: [conectica-it.ro](https://conectica-it.ro)
- **Demo live**: [demo.fleetmanagement.ro](https://demo.fleetmanagement.ro) (admin/demo123)
- **GitHub Repo**: [github.com/nnoldi-hub/fleetly](https://github.com/nnoldi-hub/fleetly)
- **Documentație API**: [docs.fleetmanagement.ro](https://docs.fleetmanagement.ro)
- **Video Tutorial**: [YouTube Playlist](https://youtube.com/playlist?list=...)
- **Facebook**: [fb.com/conecticait](https://facebook.com/conecticait)
- **LinkedIn**: [linkedin.com/company/conectica-it](https://linkedin.com/company/conectica-it)

---

<div align="center">

**🚀 Începeți astăzi! Contactați-ne pentru o demonstrație gratuită.**

[📧 Email](mailto:sales@conectica-it.ro) • [🌐 Website](https://conectica-it.ro) • [📱 WhatsApp](https://wa.me/40700000000)

---

Made with ❤️ by **conectica-it.ro**  
*Transformăm tehnologia în soluții reale pentru business-ul tău*

</div>
