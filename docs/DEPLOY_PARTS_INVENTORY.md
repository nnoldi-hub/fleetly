# DEPLOYMENT: Modul Gestiune Piese Atelier

## Prezentare

Acest ghid descrie pasii pentru deploy-ul modulului de gestiune piese in aplicatia FleetManagement.

## Fisiere Noi Create

### 1. Model
- `modules/service/models/Part.php` - Model pentru gestiune piese

### 2. Controller
- `modules/service/controllers/PartsController.php` - Controller pentru operatii CRUD piese

### 3. Views
- `modules/service/views/parts/index.php` - Lista piese cu filtre si statistici
- `modules/service/views/parts/form.php` - Formular adaugare/editare piesa
- `modules/service/views/parts/view.php` - Detalii piesa cu istoric

### 4. Database
- `sql/migrations/010_service_parts_inventory.sql` - Schema tabele si date demo

### 5. Documentatie
- `modules/service/PARTS_INVENTORY_README.md` - Documentatie completa modul

### 6. Configuratie
- `config/routes.php` - Rute noi adaugate (liniile 245-253)

## Pasii de Deployment

### PASUL 1: Backup Database
```bash
# Pe server
mysqldump -u username -p database_name > backup_before_parts_$(date +%Y%m%d).sql
```

### PASUL 2: Upload Fisiere

Copiati urmatoarele fisiere pe server:

```bash
# Model
/modules/service/models/Part.php

# Controller
/modules/service/controllers/PartsController.php

# Views
/modules/service/views/parts/index.php
/modules/service/views/parts/form.php
/modules/service/views/parts/view.php

# Migration
/sql/migrations/010_service_parts_inventory.sql

# Documentation
/modules/service/PARTS_INVENTORY_README.md

# Config (actualizat cu rutele noi)
/config/routes.php
```

### PASUL 3: Executare Migrare Database

**IMPORTANT**: Rulati acest SQL pe baza de date TENANT (nu CORE!)

#### Optiunea A: Via cPanel phpMyAdmin
1. Login in cPanel → phpMyAdmin
2. Selectati database TENANT (ex: `wclsgzyf_fm_tenant_1`)
3. Click tab "SQL"
4. Copiati continutul din `sql/migrations/010_service_parts_inventory.sql`
5. Click "Execute"
6. Verificati ca s-au creat 3 tabele:
   - `service_parts`
   - `service_parts_usage`
   - `service_parts_transactions`

#### Optiunea B: Via SSH
```bash
mysql -u wclsgzyf_dbuser -p wclsgzyf_fm_tenant_1 < sql/migrations/010_service_parts_inventory.sql
```

### PASUL 4: Verificare Tabele Create

Rulati in phpMyAdmin:
```sql
-- Verificare tabele
SHOW TABLES LIKE 'service_parts%';

-- Verificare date demo
SELECT COUNT(*) FROM service_parts;
-- Ar trebui sa returneze 8 (piese demo)

-- Verificare triggers
SHOW TRIGGERS LIKE 'service_parts%';
-- Ar trebui sa arate 3 triggers pentru update costs
```

### PASUL 5: Clear Cache PHP

Pe server (via SSH sau File Manager):
```bash
# Clear opcache
echo "<?php opcache_reset(); ?>" > clear_cache.php
# Apoi accesati: https://domeniu.ro/clear_cache.php
# Dupa aceea stergeti fisierul

# SAU restart PHP-FPM (daca aveti acces)
sudo systemctl restart php-fpm
```

Sau in cPanel → MultiPHP INI Editor → schimbati o setare mica si salvati (forteaza reload).

### PASUL 6: Adaugare Link in Sidebar

Editati `includes/sidebar.php` si adaugati sub sectiunea Atelier:

```php
<!-- In sectiunea SERVICE/ATELIER -->
<li class="nav-item">
    <a class="nav-link <?php echo $currentPage === 'service-parts' ? 'active' : ''; ?>" 
       href="/service/parts">
        <i class="bi bi-boxes"></i>
        <span>Piese</span>
    </a>
</li>
```

### PASUL 7: Test Functionalitati

#### 7.1. Acces Lista Piese
- Navigati la: `https://domeniu.ro/service/parts`
- Ar trebui sa vedeti dashboard cu:
  - 8 piese in inventar (date demo)
  - Valoare totala stoc
  - Statistica piese
  - Lista piese cu filtre

#### 7.2. Adaugare Piesa Noua
- Click "Adauga Piesa"
- Completati formular:
  - Cod piesa: `TEST-001`
  - Nume: `Piesa Test`
  - Categorie: `Test`
  - Pret achizitie: `10.00`
  - Pret vanzare: `15.00`
  - Cantitate: `5`
  - Stoc minim: `2`
- Salvati
- Verificati ca apare in lista

#### 7.3. Ajustare Stoc
- Click pe o piesa → buton "Ajusteaza Stoc"
- Testati:
  - Adaugare 10 bucati (intrare)
  - Scadere 2 bucati (iesire)
- Verificati ca stocul se actualizeaza corect
- Verificati istoricul tranzactiilor

#### 7.4. Detalii Piesa
- Click pe o piesa din lista
- Verificati:
  - Informatii complete
  - Card-uri statistici (stoc, folosit, disponibil)
  - Istoric utilizare (gol initial)
  - Istoric tranzactii (ar trebui sa arate intrarile/iesirile)

#### 7.5. Filtre
- Testati cautare dupa nume
- Testati filtru categorie
- Testati checkbox "Doar stoc minim"

#### 7.6. Stergere Date Demo (Optional)
Daca nu doriti datele demo:
```sql
DELETE FROM service_parts WHERE id <= 8;
```

### PASUL 8: Verificare Logs

Verificati ca nu sunt erori in logs:
```bash
tail -f logs/app.log
tail -f logs/error.log
```

Sau in cPanel → Errors (ultimele erori PHP).

## Troubleshooting

### Problema: 404 Not Found la /service/parts
**Solutie**:
1. Verificati ca `config/routes.php` are rutele noi (liniile 245-253)
2. Clear cache PHP (vezi Pasul 5)
3. Logout/Login pentru refresh sesiune
4. Verificati ca fisierul `PartsController.php` exista in locatia corecta

### Problema: Eroare SQL "Table doesn't exist"
**Solutie**:
1. Verificati ca ati rulat migratia pe database-ul TENANT (nu CORE!)
2. Verificati ca numele database este corect in `companies` table
3. Rulati manual SQL-ul din `010_service_parts_inventory.sql`

### Problema: Trigger error la insert/update
**Solutie**:
1. Verificati ca triggers s-au creat corect:
   ```sql
   SHOW TRIGGERS LIKE 'service_parts%';
   ```
2. Daca lipsesc, rulati manual sectiunea DELIMITER din migration SQL
3. MySQL user trebuie sa aiba permisiune CREATE TRIGGER

### Problema: Stoc nu se scade la utilizare in Work Order
**Solutie**:
1. Verificati ca triggers sunt active
2. Verificati ca tabelul `service_work_orders` are coloana `parts_cost`
3. Daca nu exista, adaugati:
   ```sql
   ALTER TABLE service_work_orders ADD COLUMN parts_cost DECIMAL(10,2) DEFAULT 0 AFTER labor_cost;
   ```

### Problema: Permission denied la acces
**Solutie**:
1. Verificati ca utilizatorul este autentificat
2. Verificati permisiuni rol in `Auth::checkAuth()`
3. Verificati ca user are company_id setat corect

## Post-Deployment Checklist

- [ ] Database backup efectuat
- [ ] Fisiere uploadate pe server
- [ ] SQL migration rulat pe TENANT database
- [ ] 3 tabele create (service_parts, service_parts_usage, service_parts_transactions)
- [ ] 3 triggers create (update_work_order_parts_cost*)
- [ ] 8 piese demo existente (optional)
- [ ] PHP cache cleared
- [ ] Link adaugat in sidebar
- [ ] Test: Lista piese functioneaza
- [ ] Test: Adaugare piesa functioneaza
- [ ] Test: Ajustare stoc functioneaza
- [ ] Test: Detalii piesa functioneaza
- [ ] Test: Filtre functioneaza
- [ ] Logs verificate - fara erori
- [ ] Git commit + push

## Git Commands

```bash
# Add files
git add modules/service/models/Part.php
git add modules/service/controllers/PartsController.php
git add modules/service/views/parts/
git add sql/migrations/010_service_parts_inventory.sql
git add modules/service/PARTS_INVENTORY_README.md
git add config/routes.php

# Commit
git commit -m "Add parts inventory management to internal workshop

Features:
- Parts CRUD with stock management
- Stock transactions history (in/out/adjustments)
- Low stock alerts and monitoring
- Integration with work orders
- Parts usage tracking
- Stock value statistics
- Categories and filters

Database:
- service_parts table
- service_parts_usage table
- service_parts_transactions table
- Auto-update triggers for work order costs

Files:
- Model: Part.php
- Controller: PartsController.php
- Views: index, form, view
- Migration: 010_service_parts_inventory.sql
- Documentation: PARTS_INVENTORY_README.md
- Routes: Added 9 new routes for parts management"

# Push
git push origin main
```

## Rollback (Daca Este Necesar)

Daca intampinati probleme si doriti sa reveniti:

### 1. Database Rollback
```sql
-- Drop triggers
DROP TRIGGER IF EXISTS update_work_order_parts_cost;
DROP TRIGGER IF EXISTS update_work_order_parts_cost_update;
DROP TRIGGER IF EXISTS update_work_order_parts_cost_delete;

-- Drop tables
DROP TABLE IF EXISTS service_parts_transactions;
DROP TABLE IF EXISTS service_parts_usage;
DROP TABLE IF EXISTS service_parts;

-- Restore from backup
-- mysql -u user -p database < backup_before_parts_YYYYMMDD.sql
```

### 2. File Rollback
```bash
# Git revert
git revert HEAD
git push origin main

# Sau stergeti manual fisierele noi create
```

### 3. Routes Rollback
Editati `config/routes.php` si stergeti liniile 245-253 (rutele pentru piese).

## Contact Support

Pentru asistenta:
- Email: support@fleetmanagement.ro
- Documentation: /modules/service/PARTS_INVENTORY_README.md
- Logs: /logs/app.log, /logs/error.log

---

**Data Deploy**: _____________  
**Deployed By**: _____________  
**Status**: ☐ Success ☐ Failed ☐ Rolled Back  
**Notes**: ___________________
