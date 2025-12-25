# 🚀 Deploy Partners & Ads System pe Hostico

## 📋 Ce s-a modificat

### Fișiere NOI (trebuie urcate):
```
modules/marketplace/controllers/PartnerController.php
modules/marketplace/controllers/admin/PartnerAdminController.php
modules/marketplace/models/Partner.php
modules/marketplace/models/PartnerCategory.php
modules/marketplace/views/admin/partners/ (tot folder-ul - 4 fișiere)
modules/marketplace/views/partners/ (tot folder-ul - 2 fișiere)
sql/migrations/2024_12_25_marketplace_partners_ads.sql
docs/PARTNERS_ADS_SYSTEM.md
uploads/marketplace/logos/ (folder gol, creează dacă nu există)
uploads/marketplace/banners/ (folder gol, creează dacă nu există)
```

### Fișiere MODIFICATE (suprascrie):
```
modules/marketplace/index.php
includes/sidebar.php
```

---

## 🔄 Metoda 1: Git Pull (Recomandat)

Dacă serverul Hostico are git configurat:

```bash
# 1. Conectează-te prin SSH la Hostico
ssh user@your-domain.com

# 2. Navighează la directorul proiectului
cd public_html

# 3. Trage modificările de pe GitHub
git pull origin main

# 4. Creează directoarele pentru upload
mkdir -p uploads/marketplace/logos
mkdir -p uploads/marketplace/banners
chmod 755 uploads/marketplace/logos
chmod 755 uploads/marketplace/banners

# 5. Rulează migrația SQL
mysql -u DB_USER -p DB_NAME < sql/migrations/2024_12_25_marketplace_partners_ads.sql
```

---

## 📂 Metoda 2: FTP/File Manager Manual

### Pas 1: Urcă fișierele via FTP (FileZilla/cPanel File Manager)

1. **Controllers:**
   - Urcă `modules/marketplace/controllers/PartnerController.php`
   - Creează folder `modules/marketplace/controllers/admin/` dacă nu există
   - Urcă `modules/marketplace/controllers/admin/PartnerAdminController.php`

2. **Models:**
   - Urcă `modules/marketplace/models/Partner.php`
   - Urcă `modules/marketplace/models/PartnerCategory.php`

3. **Views Admin (creează folder):**
   - Creează `modules/marketplace/views/admin/partners/`
   - Urcă în el: `index.php`, `form.php`, `categories.php`, `category-form.php`

4. **Views Users (creează folder):**
   - Creează `modules/marketplace/views/partners/`
   - Urcă în el: `index.php`, `show.php`

5. **Fișiere modificate (suprascrie):**
   - Suprascrie `modules/marketplace/index.php`
   - Suprascrie `includes/sidebar.php`

6. **Upload folders (creează):**
   - Creează `uploads/marketplace/logos/`
   - Creează `uploads/marketplace/banners/`
   - Set permissions: 755

### Pas 2: Rulează SQL în phpMyAdmin

1. Intră în cPanel → phpMyAdmin
2. Selectează baza de date a aplicației (ex: `u123456_fleetmanagement`)
3. Click tab **SQL**
4. Copiază și lipește conținutul din:
   `sql/migrations/2024_12_25_marketplace_partners_ads.sql`
5. Click **Go** / **Execută**

Rezultat așteptat:
```
Installing Marketplace Partners & Ads System...
✅ Marketplace Partners & Ads System installed successfully!
Tables created: mp_partner_categories, mp_partners, mp_partner_stats
```

---

## ✅ Verificare după Deploy

### 1. Verifică tabelele în phpMyAdmin:
- `mp_partner_categories` (8 categorii default)
- `mp_partners` (4 parteneri demo)
- `mp_partner_stats` (gol)

### 2. Testează în browser:

**Pentru SuperAdmin:**
```
https://your-domain.com/modules/marketplace/?action=admin-partners
```
- Trebuie să vezi lista de parteneri
- Trebuie să poți adăuga/edita/șterge parteneri

**Pentru Utilizatori obișnuiți:**
```
https://your-domain.com/modules/marketplace/?action=partners
```
- Trebuie să vadă lista de parteneri și categorii

### 3. Verifică sidebar:
- Login ca SuperAdmin → trebuie să vezi "Parteneri & Reclame" (info color)
- Login ca user normal → trebuie să vezi "Parteneri & Oferte" (info color)

---

## ⚠️ Troubleshooting

### Eroare "Table already exists"
→ Normal pentru `CREATE TABLE IF NOT EXISTS`, scriptul nu va suprascrie date existente

### 404 pe pagini parteneri
→ Verifică că `modules/marketplace/index.php` a fost suprascris corect

### Logo/Banner nu se încarcă
→ Verifică permissions pe `uploads/marketplace/logos/` și `banners/` (trebuie 755 sau 775)

### Menu nu apare în sidebar
→ Verifică că `includes/sidebar.php` a fost suprascris

### Eroare "Class not found"
→ Verifică că toate fișierele din `models/` și `controllers/` au fost urcate

---

## 📞 După Deploy

1. **Șterge partenerii demo** dacă nu îi vrei:
   - SuperAdmin → Parteneri & Reclame → Șterge AutoParts Pro, TyreKing, etc.

2. **Adaugă parteneri reali:**
   - Completează informațiile firmelor cu care colaborezi
   - Urcă logo-uri (recomandat 200x200px)
   - Urcă bannere (recomandat 1200x400px)

3. **Personalizează categoriile:**
   - Poți adăuga/modifica categoriile existente

---

**⏱️ Timp estimat deploy: 10-15 minute**
**📅 Data commit: 25 Decembrie 2025**
**🔗 Commit: 050868a**
