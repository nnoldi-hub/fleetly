# Ghid instalare pe Hostico (multi‑tenant)

Acest document descrie pașii de configurare ai bazei de date și încărcarea aplicației pe Hostico, folosind modul multi‑tenant (o bază per companie) cu prefix cPanel.

> Presupuneri
> - Ai creat deja o bază centrală (ex: `wclsgzyf_fleetly`) în cPanel → MySQL Databases.
> - Ai utilizator MySQL (ex: `wclsgzyf_nnoldi`) cu parolă și privilegii suficiente (CREATE DATABASE).
> - Prefixul contului tău cPanel este `wclsgzyf_` (înlocuiește dacă e altul).

---

## 1) Importă „baza centrală” (core)

Scop: tabelele de autentificare, companii, roluri, permisiuni, utilizatori (SuperAdmin).

1. Intră în cPanel → phpMyAdmin → selectează baza: `wclsgzyf_fleetly`.
2. Tab „Import” → încarcă fișierul din repo: `sql/install_core.sql` → Execute.
3. Verificare:
   - În `wclsgzyf_fleetly` apar tabelele `companies`, `users`, `roles`, `permissions`, `user_sessions`, etc.
   - Utilizator implicit: `superadmin` / parola `Admin123!`.

Notă: scriptul nu creează DB (rulează pe DB selectată).

---

## 2) Configurează aplicația pentru multi‑tenant

Pe server (NU în Git), creează fișierul `config/database.override.php`:

```php
<?php
return [
  'host' => 'localhost',
  'db'   => 'wclsgzyf_fleetly',     // baza centrală (core)
  'user' => 'wclsgzyf_nnoldi',
  'pass' => 'PAROLA_TA',           // nu îl commita în Git!
  'tenancy_mode'     => 'multi',   // activăm multi‑tenant
  'tenant_db_prefix' => 'wclsgzyf_'// prefixul cPanel
];
```

Opțional (dacă folosești ENV din cPanel):
- FM_ENV=prod
- FM_BASE_URL=https://domeniu-tau.ro
- FM_TENANCY_MODE=multi
- FM_TENANT_DB_PREFIX=wclsgzyf_

---

## 3) Urcă aplicația pe Hosting

1. În cPanel → File Manager → urcă fișierele aplicației în document root (ex: `public_html` sau un subfolder).
2. Asigură-te că `config/database.override.php` e prezent doar pe server (nu în Git).
3. Dacă folosești mod_rewrite, păstrează `.htaccess` la rădăcina aplicației.

---

## 4) Creează o companie și declanșează provisionarea automată

1. Autentifică-te: `superadmin` / `Admin123!`.
2. Creează o companie nouă din interfața de SuperAdmin.
3. Accesează aplicația ca acea companie (sau autentifică un utilizator al companiei).
4. Ce se întâmplă automat:
   - Se construiește numele DB: `wclsgzyf_fm_tenant_<companyId>`.
   - Dacă nu există, aplicația o creează și instalează schema flotă (vehicule, șoferi, documente, mentenanță, combustibil, notificări).
   - Schema include deja câmpurile moderne de notificări (`user_id`, `is_read`, `read_at`, `related_type`, `action_url`).
5. Verificare:
   - În phpMyAdmin apare DB-ul `wclsgzyf_fm_tenant_<companyId>` cu tabelele de flotă.

---

## 5) Fallback manual (numai dacă nu ai CREATE DATABASE)

Dacă auto‑crearea e blocată:
1. Creează manual baza: `wclsgzyf_fm_tenant_<companyId>`.
2. Adaugă userul `wclsgzyf_nnoldi` la această bază cu ALL PRIVILEGES.
3. În phpMyAdmin → selectează DB-ul → Import `sql/install_tenant_template.sql`.
4. Repetă pentru fiecare companie nouă.

---

## 6) Testare rapidă

- Loghează-te ca utilizator al companiei create.
- Accesează modulele `Vehicule`, `Șoferi`, `Documente`, `Mentenanță`, `Combustibil`.
- Toate datele sunt izolate per companie (DB tenant dedicată).

---

## 7) Troubleshooting

- „Access denied … to database …”
  - cPanel → „Add User To Database”: adaugă `wclsgzyf_nnoldi` cu privilegii pe `wclsgzyf_fleetly` și pe `wclsgzyf_fm_tenant_*`.

- „Unknown database wclsgzyf_fm_tenant_…”
  - Verifică `tenancy_mode = multi` în `database.override.php`.
  - Verifică `tenant_db_prefix = 'wclsgzyf_'`.
  - Dacă tot apare, creează manual DB-ul și importă `sql/install_tenant_template.sql`.

- S-a creat DB-ul dar fără tabele
  - Rulează `sql/install_tenant_template.sql` în DB-ul tenant afectat.

- Notificări fără coloanele `user_id`/`is_read`
  - Folosește template-ul nou (deja inclus). Pentru DB-uri tenant mai vechi, rulează migrația: `sql/migrations/2025_11_05_001_add_user_and_read_columns_to_notifications.sql`.

---

## 8) Securitate și bune practici

- NU commita `config/database.override.php` în repository.
- Setează permisiuni restrictive pe fișierele de config.
- Schimbă parola implicită a SuperAdminului imediat după instalare.
- Configurează un BASE_URL corect pentru producție.

---

## 9) (Opțional) Date demo

Dorești date exemplu (vehicule/șoferi) în DB-urile tenant? Spune-ne și îți furnizăm un seed compatibil.
