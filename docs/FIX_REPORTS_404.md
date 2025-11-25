# Fix pentru pagina 404 - Raport Flotă

## Problema
Paginile de rapoarte (ex: `/reports/fleet`) returnează eroarea 404 cu mesajul "Pagina nu a fost găsită" și în log apare eroarea SQL:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'wclsgzyf_fm_tenant_1.vehicles' doesn't exist
```
(Sistemul încerca să acceseze tabela `vehicles` care trebuie să fie în baza TENANT, nu în CORE)

## Cauza
Sistemul nu reușea să acceseze baza de date tenant corectă din cauza a două probleme:

1. **Router-ul nu procesa corect URL-urile cu `/index.php/`**: Când mod_rewrite nu este activ sau configurația Apache nu este optimizată, URL-urile conțin `/index.php/reports/fleet` iar router-ul nu extragea corect path-ul.

2. **Numele bazei de date tenant nu era configurat explicit**: Sistemul genera automat numele bazei tenant ca `{prefix}fm_tenant_{id}` (ex: `wclsgzyf_fm_tenant_1`), dar câmpul `database_name` din tabela `companies` era NULL sau incorect.

## Soluție Implementată

### 1. Fix Router - Commit 67c7208
Am îmbunătățit logica de extragere a path-ului în `index.php` pentru a detecta și procesa corect URL-urile care conțin `/index.php/` oriunde în structură.

**Fișier modificat:** `index.php`

### 2. Database Name Configuration - Commit 4ba51e1
Am modificat metoda `setTenantDatabaseByCompanyId()` din `core/Database.php` pentru a citi mai întâi câmpul `database_name` din tabela `companies` și a-l folosi ca nume explicit al bazei tenant.

**Fișier modificat:** `core/Database.php`

## Pași de Deployment pe Server

### 1. Pull ultimele modificări din Git
```bash
cd /path/to/fleet-management
git pull origin main
```

### 2. Actualizează tabela companies în baza core
Conectează-te la baza de date **CORE** (cea care conține tabela `companies` - ex: `wclsgzyf_fleetly` pentru Hostico sau `fleet_management_core` pentru local) și rulează:

```sql
-- Verifică numele companiei și ID-ul
SELECT id, name, database_name FROM companies;

-- Setează database_name explicit pentru compania ta (numele bazei TENANT, nu CORE!)
UPDATE companies SET database_name = 'wclsgzyf_fm_tenant_1' WHERE id = 1;

-- Verifică actualizarea
SELECT id, name, database_name FROM companies;
```

**Notă importantă:** 
- Conectează-te la baza **CORE** (ex: `wclsgzyf_fleetly`) care conține tabela `companies`
- Setează `database_name` la numele bazei **TENANT** (ex: `wclsgzyf_fm_tenant_1`) care conține datele flotei (vehicles, documents, etc.)

### 3. Verifică configurația database.override.php
Asigură-te că fișierul `config/database.override.php` pe server conține:

```php
<?php
return [
    'host'             => 'localhost',
    'db'               => 'wclsgzyf_fleetly',        // baza centrală (CORE) - conține companies, users, roles
    'user'             => 'wclsgzyf_username',       // utilizator cPanel
    'pass'             => 'your_password_here',      // parola din cPanel
    'tenancy_mode'     => 'multi',                   // multi-tenant activat
    'tenant_db_prefix' => 'wclsgzyf_'                // prefix cPanel pentru baze tenant
];
```

### 4. Testează aplicația
1. Logout și login din nou
2. Accesează `/reports` - ar trebui să vezi dashboard-ul de rapoarte
3. Click pe "Deschide" pentru "Raport flotă" - ar trebui să vezi pagina cu layout complet, nu 404

## Verificare logs
Dacă încă apar probleme, verifică logurile PHP:

```bash
tail -f /path/to/error_log
```

Caută linii care încep cu `[ROUTER DEBUG]` sau `[Database]` pentru informații despre rutare și conexiuni la baze de date.

## Commit History
- **67c7208** - "fix: Improve router path extraction for /index.php/ URLs"
- **4ba51e1** - "feat: Use explicit database_name from companies table for tenant DB selection"
- **7e59909** - "style: Improve layout consistency in expiring documents view"

## Note Importante

1. **Backup înainte de deployment**: Fă backup la baza de date core înainte de a rula UPDATE-ul
2. **Nume bază de date**: Verifică numele exact al bazei tenant din cPanel → MySQL Databases
3. **Permisiuni**: Asigură-te că utilizatorul MySQL are permisiuni pe ambele baze (core + tenant)
4. **Session cleanup**: După update, utilizatorii vor trebui să facă logout/login pentru a reîncărca configurația

## Troubleshooting

### Dacă tot apare 404:
1. Verifică că ai făcut `git pull` și fișierele sunt actualizate
2. Șterge cache-ul PHP (opcache_reset sau restart PHP-FPM)
3. Verifică logs pentru erori de routing

### Dacă apare eroare SQL:
1. Verifică că ai rulat UPDATE-ul pe tabela companies
2. Verifică că database_name din companies corespunde cu numele real al bazei tenant
3. Verifică că utilizatorul MySQL are permisiuni pe baza tenant

### Dacă alte pagini au probleme:
Aceasta e o modificare generală care afectează întreaga aplicație în mod pozitiv. Toate rutele ar trebui să funcționeze corect după acest fix.
