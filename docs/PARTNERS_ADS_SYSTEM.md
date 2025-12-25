# 🤝 Sistem Parteneri & Reclame - Fleet Management

## Descriere

Sistemul de **Parteneri & Reclame** permite SuperAdmin-ului să creeze și să gestioneze link-uri către firme partenere care oferă servicii și produse relevante pentru managementul flotelor de vehicule:

- **Piese Auto** - Furnizori de piese și accesorii
- **Cauciucuri** - Magazine și service-uri de anvelope
- **Asigurări Auto** - Companii de asigurări RCA, CASCO
- **Roviniete** - Platforme pentru achiziția de roviniete
- **Combustibil** - Stații și carduri de flotă
- **Service Auto** - Ateliere și service-uri
- **Leasing & Finanțare** - Soluții financiare
- **GPS & Monitorizare** - Sisteme de tracking

## Funcționalități

### Pentru SuperAdmin
- ✅ Adăugare/editare/ștergere parteneri
- ✅ Gestionare categorii de parteneri
- ✅ Upload logo și banner pentru fiecare partener
- ✅ Setare oferte speciale și coduri promoționale
- ✅ Activare/dezactivare parteneri
- ✅ Marcare parteneri ca "Featured" (recomandați)
- ✅ Setare perioadă de valabilitate pentru oferte
- ✅ Vizualizare statistici (vizualizări, click-uri)

### Pentru Utilizatori (Administratori Flotă)
- ✅ Vizualizare parteneri pe categorii
- ✅ Căutare parteneri
- ✅ Filtrare după categorie
- ✅ Vizualizare detalii partener
- ✅ Copiere cod promoțional
- ✅ Redirect către site-ul partenerului (cu tracking)

## Structura Fișierelor

```
modules/marketplace/
├── controllers/
│   ├── PartnerController.php          # Controller vizualizare utilizatori
│   └── admin/
│       └── PartnerAdminController.php # Controller admin SuperAdmin
├── models/
│   ├── Partner.php                    # Model partener
│   └── PartnerCategory.php            # Model categorie
├── views/
│   ├── partners/
│   │   ├── index.php                  # Pagina principală parteneri
│   │   └── show.php                   # Detalii partener
│   └── admin/
│       └── partners/
│           ├── index.php              # Lista parteneri admin
│           ├── form.php               # Formular create/edit partener
│           ├── categories.php         # Lista categorii
│           └── category-form.php      # Formular categorie
└── index.php                          # Router actualizat
```

## Tabele Baza de Date

### `mp_partner_categories`
Categorii pentru parteneri (Piese Auto, Cauciucuri, etc.)

### `mp_partners`
Partenerii/furnizorii cu:
- Informații de bază (nume, descriere)
- Text promoțional
- Contact (telefon, email, adresă)
- Link-uri (website)
- Imagini (logo, banner)
- Oferte (discount, cod promoțional)
- Setări (activ, featured, ordine, valabilitate)
- Statistici (vizualizări, click-uri)

### `mp_partner_stats`
Log pentru tracking vizualizări și click-uri

## Accesare

### SuperAdmin
- **Gestionare Parteneri**: `modules/marketplace/?action=admin-partners`
- **Gestionare Categorii**: `modules/marketplace/?action=admin-partner-categories`

### Utilizatori
- **Vizualizare Parteneri**: `modules/marketplace/?action=partners`
- **Detalii Partener**: `modules/marketplace/?action=partner-show&id={id}`

## Link-uri în Sidebar

- **SuperAdmin**: Link "Parteneri & Reclame" (albastru) în meniul admin
- **Utilizatori**: Link "Parteneri & Oferte" (cyan) în meniul principal

## Instalare

### 1. Rulare Migrație SQL
```sql
-- Rulează fișierul:
sql/migrations/2024_12_25_marketplace_partners_ads.sql
```

### 2. Creare Directoare Upload
```
uploads/marketplace/logos/
uploads/marketplace/banners/
```

### 3. Permisiuni
Asigură-te că directoarele de upload au permisiuni de scriere (755 sau 775).

## Categorii Default

După instalare, vor fi create automat 8 categorii:
1. Piese Auto
2. Cauciucuri
3. Asigurări Auto
4. Roviniete
5. Combustibil
6. Service Auto
7. Leasing & Finanțare
8. GPS & Monitorizare

## Parteneri Demo

Sunt adăugați 4 parteneri demo pentru testare:
1. AutoParts Pro (Piese Auto)
2. TyreKing România (Cauciucuri)
3. Asigurări Rapid (Asigurări)
4. eRovinieta.ro (Roviniete)

Aceștia pot fi editați sau șterși din panoul de admin.

---

**Versiune**: 1.0  
**Data**: 25 Decembrie 2024
