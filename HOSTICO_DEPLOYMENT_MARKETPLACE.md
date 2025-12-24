# 🚀 DEPLOYMENT HOSTICO - Marketplace MVP

## 📦 Fișiere de Upload pe Server

### 1. Module Marketplace (NOU - Tot folder-ul)
Upload via FTP în `public_html/modules/`:
```
modules/marketplace/
├── index.php
├── test-installation.php
├── controllers/
│   ├── CartController.php
│   ├── CheckoutController.php
│   ├── MarketplaceController.php
│   ├── OrderController.php
│   ├── ProductController.php
│   └── admin/
│       ├── CatalogAdminController.php
│       ├── DashboardController.php
│       └── OrderAdminController.php
├── models/
│   ├── Cart.php
│   ├── Category.php
│   ├── Order.php
│   ├── OrderItem.php
│   └── Product.php
└── services/
    (gol deocamdată)
```

### 2. SQL Migration (pentru phpMyAdmin)
Fișier: `sql/migrations/2024_12_24_marketplace_phase1_production.sql` (îl creez mai jos)

### 3. Sidebar Modificat
Upload via FTP:
```
includes/sidebar.php  (modificat - adăugat link Marketplace)
```

### 4. Upload Directory (creează pe server)
Creează manual pe server via FTP sau File Manager:
```
uploads/marketplace/products/
```
**Permissions: 755 sau 777** (trebuie să fie writable)

### 5. Documentație (OPȚIONAL)
```
docs/MARKETPLACE_DEVELOPMENT_PLAN.md
docs/MARKETPLACE_MVP_QUICKSTART.md
MARKETPLACE_INSTALLATION_COMPLETE.md
```

---

## 🗄️ Bază de Date

### Care bază de date actualizez?
**CORE DATABASE** (baza principală fleet_management)

**NU tenant databases!** Marketplace este la nivel de sistem, nu per-tenant.

### Cum identific core database?
Vezi în `config/database.php`:
- Variabila `DB_NAME` = core database (probabil `u123456_fleetmanagement` sau similar pe Hostico)

---

## 📝 Script SQL pentru phpMyAdmin

Am creat un script **SAFE pentru production** care:
- ✅ Verifică dacă tabelele există (CREATE IF NOT EXISTS)
- ✅ NU șterge date existente
- ✅ Adaugă doar seed data dacă tabelele sunt goale
- ✅ Poate fi rulat de multiple ori fără probleme

Fișierul: `sql/migrations/2024_12_24_marketplace_phase1_production.sql`

---

## 🔧 Pași Deployment - GHID PAS CU PAS

### Pasul 1: Backup Database (IMPORTANT!)
În phpMyAdmin pe Hostico:
1. Selectează database-ul core
2. Tab "Export"
3. Format: SQL
4. Click "Go"
5. Salvează backup local (safety first!)

### Pasul 2: Upload Fișiere via FTP

**Opțiunea A - FileZilla/FTP Client:**
1. Conectează-te la FTP Hostico
2. Navighează la `public_html/`
3. Upload folder `modules/marketplace/` complet
4. Upload `includes/sidebar.php` (suprascrie)

**Opțiunea B - File Manager Hostico:**
1. Login cPanel Hostico
2. File Manager → public_html
3. Upload ZIP cu marketplace
4. Extract pe server
5. Upload sidebar.php

### Pasul 3: Creează Upload Directory
Via File Manager sau FTP:
```
public_html/uploads/marketplace/products/
```
**Set Permissions: 755** (click dreapta → Change Permissions)

### Pasul 4: Import SQL în phpMyAdmin

1. **Login phpMyAdmin** pe Hostico
2. **Selectează database-ul CORE** (fleet_management sau similar)
3. Click tab **"SQL"**
4. **Copy-paste** conținutul din `sql/migrations/2024_12_24_marketplace_phase1_production.sql`
5. **Scroll jos** și click **"Go"**
6. Verifică mesaj success: "Query OK, X rows affected"

### Pasul 5: Verificare Instalare

Deschide în browser:
```
https://your-domain.com/modules/marketplace/test-installation.php
```

Ar trebui să vezi:
- ✅ 5 tabele create
- ✅ 4 categorii
- ✅ 14 produse

### Pasul 6: Login și Test

1. **Login** la aplicație
2. **User normal:** Vezi "Marketplace" în sidebar (verde)
3. **SuperAdmin:** Vezi "Marketplace Admin" în sidebar

---

## 🚨 Troubleshooting

### Eroare: "Table already exists"
**Nu-i problemă!** Scriptul este safe, poți rula din nou.

### Eroare: "Permission denied" la upload imagini
```bash
# Via SSH (dacă ai acces):
chmod 755 uploads/marketplace/products/

# Via File Manager:
Click dreapta pe folder → Change Permissions → 755
```

### Produsele nu apar
Verifică în phpMyAdmin:
```sql
SELECT * FROM mp_products WHERE is_active = 1;
```

### Eroare 404 pe /marketplace/
Verifică că ai upload `modules/marketplace/index.php` corect.

### Views nu se încarcă
Normal! Views-urile NU sunt create încă. Vei vedea erori până le creăm.

---

## 📋 Checklist Deployment

**Pre-deployment:**
- [ ] Backup database făcut
- [ ] Fișiere pregătite local
- [ ] Acces FTP/cPanel verificat

**Upload:**
- [ ] Folder `modules/marketplace/` uploaded
- [ ] `includes/sidebar.php` suprascris
- [ ] Directory `uploads/marketplace/products/` creat
- [ ] Permissions 755 setate pe uploads

**Database:**
- [ ] Database core identificat
- [ ] Script SQL rulat în phpMyAdmin
- [ ] Verificat: 5 tabele, 4 categorii, 14 produse

**Testing:**
- [ ] test-installation.php verificat
- [ ] Login ca user normal → vezi Marketplace în menu
- [ ] Login ca SuperAdmin → vezi Marketplace Admin în menu

---

## ⚠️ Important de Știut

### 1. Views Lipsesc!
Backend-ul este complet, dar **views-urile NU sunt create**.

După deployment, dacă accesezi marketplace-ul, vei primi erori până creăm views-urile.

### 2. Email Configuration
Verifică `config/mail.php` pe server să aibă setări SMTP corecte pentru emailuri de confirmare comenzi.

### 3. BASE_URL Configuration
Verifică în `config/config.php` pe server:
```php
define('BASE_URL', 'https://your-domain.com/');
```

### 4. Database Connection
Verifică `config/database.php` pe server are credentials corecte pentru Hostico.

---

## 🎯 După Deployment

### Testare Rapidă (fără views):
Poți testa direct în phpMyAdmin:

**Test 1 - Verifică produse:**
```sql
SELECT p.name, c.name as category, p.price 
FROM mp_products p 
JOIN mp_categories c ON p.category_id = c.id 
LIMIT 5;
```

**Test 2 - Adaugă în cart manual:**
```sql
INSERT INTO mp_cart (company_id, user_id, product_id, quantity, price)
VALUES (1, 1, 1, 2, 450.00);
```

**Test 3 - Verifică cart:**
```sql
SELECT * FROM mp_cart WHERE company_id = 1;
```

### Next: Creare Views
După ce confirmi că backend-ul funcționează pe Hostico, putem crea views-urile!

---

## 📞 Suport

Dacă întâmpini probleme:
1. Verifică error logs: `public_html/logs/` sau cPanel Error Log
2. Testează local înainte (localhost funcționează?)
3. Verifică permissions pe uploads
4. Confirmă că database import a fost success

---

🎉 **Gata! Urmează instrucțiunile și marketplace backend va fi live pe Hostico!**
