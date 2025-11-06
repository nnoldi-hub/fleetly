# Fleet Management (PHP) — Multi‑tenant, RBAC, fara diacritice (UI)

Acesta este un sistem de administrare flota multi‑tenant, cu panou SuperAdmin, mod interventie (act as), rutare fara mod_rewrite, si izolare pe baza de date per companie. Proiectul ruleaza pe WAMP/LAMP, scris in PHP (PDO) cu un MVC simplu.

## Noutati (2025‑11‑06)

- Email SMTP: configurabil in `config/mail.php` (PHPMailer via Composer sau fallback mail()/log).
- Reset admin: trimite email cu credentialele noi (daca email existent si mail activat).
- Limita vehicule: impusa la creare (lista arata folosite/max, buton Add dezactivat la limita).
- Exporturi vehicule: CSV si PDF, ambele fara diacritice.
- Teste de baza (PHPUnit): normalizare router si transliterare RO→ASCII.

## Stare curenta

- Functional in mediu fara mod_rewrite (rute calculate din `index.php`).
- Multi‑tenancy: datele de flota sunt stocate in BD‑uri separate per companie (auto‑create + auto‑migrate la prima utilizare).
- SuperAdmin: gestiune companii (listare, adaugare, editare, (de)suspendare), reset cont admin, „mod interventie” (act as) cu banner vizibil + „iesire din modul interventie”.
- Dashboard companie: carduri cu numar vehicule/soferi/mentenanta/alerte (dinamic, tenant‑aware) + banner abonament (plan/limite) persistent.
- Management utilizatori (in admin companie): lista/adaugare/editare/stergere, respecta limita `max_users` a companiei.
- Module flota (tenant‑aware): vehicule, soferi, documente, asigurari, mentenanta, combustibil, notificari, rapoarte (minimum viabil, extensibile).
- UI fara diacritice: transliterare globala (server‑side + client‑side) pentru afisare consistenta.
- Securitate de baza: autentificare, parole bcrypt, audit log pentru actiuni critice.

> Nu exista inca un set de teste automate; aplicatia a fost validata manual in acest mediu.

## Caracteristici implementate

### Rutare fara mod_rewrite
- `index.php` normalizeaza calea si ruteaza catre controllere folosind un Router simplu.
- Functioneaza si din subdirector (ex: `/fleet-management`).

### Multi‑tenancy pe companie
- Baza centrala (core) contine: `companies`, `users`, `roles`, `permissions`, `audit_logs`, etc.
- Fiecare companie are propriul DB de tenant: `fm_tenant_{companyId}`.
- La selectarea companiei (login user companie sau „act as”) se configureaza automat PDO pentru tenant.
- La prima conectare, schema de tenant este creata/actualizata si seed‑uita (ex: `vehicle_types`).

### SuperAdmin
- Dashboard SuperAdmin.
- Companii:
  - Listare, cautare, filtru dupa status/abonament.
  - Adaugare companie: creeaza automat BD tenant; optional creeaza cont admin (parola generata daca lipseste).
  - Editare si schimbare status (activ/suspendat).
  - Reset cont administrator: seteaza username/email optional si o parola noua (sau o genereaza); compatibil cu scheme diferite `users` (`role` sau `role_id` + `roles`). Daca nu exista admin, il creeaza automat.
- Mod interventie: „act as company” (banner vizibil, buton de iesire).

### Admin companie
- Dashboard cu statistici tenant‑aware + banner abonament (persistent):
  - Utilizatori: `folositi / max_users`
  - Vehicule: `folosite / max_vehicles`
  - Link‑uri rapide catre module.
  - Setarea limitelor se face din SuperAdmin > Companii > Edit (campurile `max_users` si `max_vehicles`).
- Utilizatori (`/users`):
  - Lista + adaugare + editare + stergere, cu verificare limita `max_users`.
  - Roluri afisate in romana fara diacritice („Administrator Firma”, „Manager Flota”, „Sofer”, „Operator Flota”).
- Module flota (in BD tenant): vehicule, soferi, documente, asigurari, mentenanta, combustibil, notificari, rapoarte — implementate minimal, extensibile.

### UI fara diacritice (afisare)
- Server‑side: `index.php` aplica un filtru global de output care translitereaza diacriticele romanesti (aai st/AAI ST) in ASCII si curata artefactele „??”.
  - Debug per pagina: adauga `?keep_diacritics=1` in URL pentru a dezactiva filtrul la cerere.
- Client‑side: `assets/js/main.js` translitereaza textul vizibil la incarcare si urmareste mutatiile DOM pentru a pastra UI‑ul consistent.
  - Poti exclude o portiune de UI adaugand atributul `data-keep-diacritics` pe elementul container.

### Securitate & audit
- Parole: `password_hash` (bcrypt).
- Audit log pentru operatiuni importante (creare/actualizare/stergere entitati, resetare cont admin, etc.).

## Structura proiect (scurt)

```
index.php                # Front controller, rute globale, filtru no‑diacritics
config/                  # Config app & DB
core/                    # Kernel (Database, Model, Controller, Router, Auth, User, Company)
modules/
  dashboard/             # Dashboard companie (stats + abonament)
  superadmin/            # SuperAdmin (companies, act-as, reset admin)
  user/                  # Management utilizatori (companie)
  vehicles, drivers, documents, maintenance, fuel, insurance, reports, notifications
assets/                  # CSS/JS/imagini (main.js include transliterare UI)
sql/                     # schema.sql, sample_data.sql, migrations/
uploads/                 # uploads structurate
```

## Instalare & configurare

1. PHP 8.1+ / MySQL, WAMP/LAMP.
2. Configureaza DB in `config/database.php` (user cu permisiuni `CREATE DATABASE`).
3. Seteaza `BASE_URL` in `config/config.php` (ex: `http://localhost/fleet-management/`).
4. Acceseaza aplicatia in browser; tabelele centrale sunt folosite direct, iar DB‑urile tenant se creeaza la adaugarea/folosirea unei companii.

> Nota: La crearea unei companii, BD tenant `fm_tenant_{companyId}` este creata automat si populata cu schema minima (vehicule/soferi/documente/mentenanta/combustibil/notificari + seed pentru `vehicle_types`).

### Configurare email (SMTP)

- Editeaza `config/mail.php` si seteaza:
  - `enabled` => `true` pentru a porni trimiterea emailurilor
  - `driver` => `smtp` (recomandat) sau `mail`
  - `smtp.host`, `smtp.port`, `smtp.username`, `smtp.password`, `smtp.encryption`
  - `from.email` si `from.name`
- Optional: instaleaza PHPMailer pentru SMTP robust (altfel se foloseste `mail()` sau log local):

```powershell
cd c:\wamp64\www\fleet-management
composer install
```

Emailurile trimise sau simulate se logheaza in `logs/mail.log` cand `enabled=false` sau daca trimiterea esueaza.

## Fluxuri cheie

- Autentificare:
  - SuperAdmin (din `users` cu rol superadmin) are acces la zona SuperAdmin.
  - Utilizator companie seteaza automat DB‑ul tenant pentru sesiune.
- Mod interventie (SuperAdmin):
  - „Act as company” seteaza DB tenant si afiseaza un banner albastru; „stop acting” revine la contextul SuperAdmin.
- Reset cont admin (companie):
  - Din editarea companiei, poti reseta/parola/username/email pentru admin; daca nu exista un admin, se creeaza automat.
  - Daca exista email, se trimite o notificare cu credentialele (daca `config/mail.php` este activat).

## Cunoscut / Limitari / Next steps

- Email notificari: optional, neconfigurat — se poate trimite parola noua prin SMTP.
- Rapoarte avansate: pot fi extinse (charturi, exporturi dedicate, filtrare complexa).
- Teste automate: nu exista inca — recomandat sa se adauge PHPUnit pentru core + controllere.
- Hardening: continuat pe schema `roles`/`users` eterogene — curent sunt tratate principalele variante.

## Exporturi CSV / PDF (fara diacritice)

- Vehicule: pagina lista are butoane pentru `Export CSV` si `Export PDF`.
- CSV: campurile sunt transliterate la ASCII pentru compatibilitate.
- PDF: generator minimalist integrat (`core/pdf_exporter.php`), ASCII‑safe. Pentru layout avansat se poate integra Dompdf/TCPDF.

Rute disponibile pentru vehicule:

```
/vehicles/export?format=csv|pdf&search=&type=&status=
```
Parametrii `search`, `type`, `status` sunt optionali (aceiasi ca in lista UI) si se aplica si la export.

## Limite de plan (vehicule)

- Pe lista vehicule se afiseaza contorul `folosite / max_vehicles`.
- Butonul „Adauga Vehicul” este dezactivat cand limita este atinsa.
- In fluxul de creare, inserarea este blocata cand `used >= max_vehicles`.

## Teste (PHPUnit)

1. Instaleaza dependintele (necesita Composer):

```powershell
cd c:\wamp64\www\fleet-management
composer install
```

2. Ruleaza testele:

```powershell
vendor\bin\phpunit -c phpunit.xml.dist
```

Testele curente acopera:
- Normalizarea path‑urilor in Router (trailing slash, query string)
- Translitterarea diacriticelor romanesti la ASCII (Util::transliterateRO)

## Troubleshooting

- SMTP nu trimite: verifica `config/mail.php` (enabled, host, port, user, parola, encryption), ruleaza `composer install`, apoi reincearca resetarea admin. Daca tot nu merge, consulta `logs/mail.log`.
- CSV cu caractere ciudate: exportul elimina diacriticele; daca vezi artefacte, deschide fisierul cu encoding UTF‑8 sau Excel cu Data > From Text/CSV (UTF‑8).
- PDF gol sau formatat simplu: generatorul intern e minimalist; pentru layout bogat, instaleaza Dompdf/TCPDF si inlocuim rapid implementarea.

## Dezvoltare

- Rute se adauga in `index.php` (`$router->addRoute(method, path, Controller, action)`).
- Modelele extind `Model` si folosesc `Database::queryOn/fetchOn` pentru a rula pe Core/Tenant corect in functie de tabel.
- Pentru subdirector, `index.php` calculeaza corect calea; foloseste `BASE_URL` pentru asset‑uri.

## Sfaturi UI

- Banner abonament este „permanent” (nu se auto‑inchide). Pentru alte alerte persistente, adauga clasa `alert-permanent`.
- Diacritice: pentru pastrarea diacriticelor intr‑un fragment UI foloseste `data-keep-diacritics` pe container.

## Securitate (rezumat)

- Parole criptate cu bcrypt; nu se logheaza parole in clar (in afara afisarii ocazionale a parolelor generate nou in UI, o singura data, dupa resetare/creare).
- Audit log pentru actiuni — recomandat sa pastrezi logurile pe termen lung.

---

Daca ai nevoie de: export CSV/PDF fara diacritice, emailuri la resetarea parolei, sau limitari suplimentare (ex. `max_vehicles` la creare), spune si le adaug rapid.# Fleet Management

A lightweight PHP MVC fleet management app with reports and notifications.

## Quickstart

1. Create database and seed
- Import `sql/schema.sql`
- (Optional) Import `sql/sample_data.sql`
- Visit `/test_db.php` to verify connection

2. Run locally (WAMP/XAMPP)
- Place the project under your web root, e.g. `c:\wamp64\www\fleet-management`
- Access `http://localhost/fleet-management/index.php`

3. Configure notifications
- App → Notificări → Setări: complete Email (SMTP) and/or SMS, then use “Trimite test”
- Per-user phone: Profil utilizator → „Telefon SMS” (stored as `user_{id}_sms_to`)

4. Background sender (Windows Task Scheduler)
- Script: `scripts/process_notifications.php`
- Recommended: every 5 minutes

5. Smoke tests
- Notifications: `php scripts/test_notifications.php`
- Reports: `php scripts/test_reports.php`

## Reports
- Pages under `modules/reports/views/`
- JSON endpoints for charts: `/reports/fleet-overview-data`, `/reports/cost-data`, `/reports/maintenance-data`, `/reports/fuel-consumption-data`
- Export: POST `/reports/export` with `report_type`, `export_format`, `date_from`, `date_to`, `vehicle_id`

## Notes
- Uses Bootstrap 5.3 and Chart.js with theme-aware colors
- Router is BASE_URL aware; frontend JS reads `window.BASE_URL`
- If you deploy in a subfolder, the app should still work thanks to BASE_URL-safe links
