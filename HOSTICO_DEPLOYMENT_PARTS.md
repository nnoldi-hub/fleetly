# 🚀 DEPLOYMENT HOSTICO - Modul Gestiune Piese

## Status: READY TO DEPLOY
**Commit**: cf751bd  
**Branch**: main  
**Files**: 9 files (2079+ lines added)  
**GitHub**: ✅ Pushed successfully

---

## PASUL 1: Pull Latest Code pe Server Hostico

### Via SSH (Recomandat):
```bash
# Conectare SSH
ssh username@your-hostico-domain.ro

# Navigate to app folder
cd public_html  # sau calea catre aplicatie

# Pull latest from GitHub
git pull origin main

# Verificare fisiere noi
ls -la modules/service/models/Part.php
ls -la modules/service/controllers/PartsController.php
ls -la modules/service/views/parts/
ls -la sql/migrations/010_service_parts_inventory.sql
```

### Via cPanel File Manager (Alternative):
1. Login in cPanel
2. File Manager → Navigate to application root
3. Git Version Control → "Pull or Deploy" → "Update from Remote"
4. Sau manual: Upload fișierele din local daca Git nu e disponibil

---

## PASUL 2: Backup Database OBLIGATORIU

### Via cPanel phpMyAdmin:
1. phpMyAdmin → Select database `wclsgzyf_fm_tenant_1`
2. Tab "Export" → Quick export → Go
3. Save file: `backup_tenant_before_parts_20250125.sql`

### Via SSH:
```bash
mysqldump -u wclsgzyf_dbuser -p wclsgzyf_fm_tenant_1 > backup_tenant_before_parts_20250125.sql
```

---

## PASUL 3: Run SQL Migration

**IMPORTANT**: Rulați pe database-ul TENANT (`wclsgzyf_fm_tenant_1`), NU pe CORE!

### Metoda A: Via phpMyAdmin (Recomandat pentru Hostico)
1. Login cPanel → phpMyAdmin
2. **Select database**: `wclsgzyf_fm_tenant_1` (TENANT, NU wclsgzyf_fleetly!)
3. Click tab **"SQL"**
4. Copy ENTIRE content from: `sql/migrations/010_service_parts_inventory.sql`
5. Paste in SQL box
6. Click **"Go"** / **"Execute"**
7. Verify success messages:
   - ✅ Table `service_parts` created
   - ✅ Table `service_parts_usage` created
   - ✅ Table `service_parts_transactions` created
   - ✅ 8 rows inserted (demo data)
   - ✅ 3 triggers created

### Metoda B: Via SSH (dacă aveți acces)
```bash
mysql -u wclsgzyf_dbuser -p wclsgzyf_fm_tenant_1 < sql/migrations/010_service_parts_inventory.sql
```

### Verificare Tabele Create:
```sql
-- Run in phpMyAdmin SQL tab
USE wclsgzyf_fm_tenant_1;

-- Check tables exist
SHOW TABLES LIKE 'service_parts%';
-- Should show 3 tables

-- Check demo data
SELECT COUNT(*) as demo_parts FROM service_parts;
-- Should return 8

-- Check triggers
SHOW TRIGGERS WHERE `Table` LIKE 'service_parts%';
-- Should show 3 triggers
```

---

## PASUL 4: Clear PHP Cache

### Via cPanel MultiPHP Manager:
1. cPanel → Software → MultiPHP INI Editor
2. Change any setting slightly (ex: max_execution_time from 300 to 301)
3. Save → Change back to original value → Save again
4. This forces PHP restart

### Via SSH (dacă aveți acces):
```bash
# Find PHP-FPM service
sudo systemctl restart php-fpm
# sau
sudo service php-fpm restart
```

### Via File (Quick Method):
1. Create file: `public_html/clear_cache.php`
2. Content:
   ```php
   <?php
   if (function_exists('opcache_reset')) {
       opcache_reset();
       echo "OPcache cleared!";
   } else {
       echo "OPcache not available";
   }
   ?>
   ```
3. Access: `https://your-domain.ro/clear_cache.php`
4. Delete file after use

---

## PASUL 5: Verificare Deployment

### 5.1. Check Files Exist
Via SSH sau File Manager, verify:
- ✅ `modules/service/models/Part.php`
- ✅ `modules/service/controllers/PartsController.php`
- ✅ `modules/service/views/parts/index.php`
- ✅ `modules/service/views/parts/form.php`
- ✅ `modules/service/views/parts/view.php`
- ✅ `config/routes.php` (updated with new routes)

### 5.2. Test Access Parts Module
1. **Login** to application: `https://your-domain.ro/login`
2. **Navigate**: `/service/parts` or add directly: `https://your-domain.ro/service/parts`
3. **Expected**: Dashboard cu statistici și 8 piese demo

### 5.3. Test Add Part
1. Click **"Adauga Piesa"**
2. Fill form:
   - Cod piesa: `TEST-DEPLOY-001`
   - Nume: `Piesa Test Deploy`
   - Categorie: `Test`
   - Pret achizitie: `10.00`
   - Pret vanzare: `15.00`
   - Cantitate: `5`
3. Save
4. Verify appears in list

### 5.4. Test Stock Adjustment
1. Click on a part → **"Ajusteaza Stoc"** button
2. Add 10 pieces (intrare)
3. Verify stock updated
4. Check transaction history appears

### 5.5. Test View Details
1. Click on any part
2. Verify:
   - ✅ Statistics cards show
   - ✅ Info complete displayed
   - ✅ Transactions history visible
   - ✅ No PHP errors

---

## PASUL 6: Add Sidebar Link (Optional)

Edit `includes/sidebar.php` - Add under SERVICE section:

```php
<!-- Find the SERVICE/ATELIER section and add: -->
<li class="nav-item">
    <a class="nav-link <?php echo ($currentPage === 'service-parts') ? 'active' : ''; ?>" 
       href="/service/parts">
        <i class="bi bi-boxes"></i>
        <span>Piese</span>
    </a>
</li>
```

Commit + push:
```bash
git add includes/sidebar.php
git commit -m "Add parts inventory link to sidebar"
git push origin main
```

Then pull again on Hostico.

---

## PASUL 7: Test Production

### Critical Tests:
- [ ] Lista piese se încarcă fără erori
- [ ] Adăugare piesă nouă funcționează
- [ ] Editare piesă funcționează
- [ ] Ajustare stoc (intrare/ieșire) funcționează
- [ ] Detalii piesă afișează istoric
- [ ] Filtre (search, categorie, stoc minim) funcționează
- [ ] Nu apar erori PHP in logs
- [ ] Database triggers funcționează (check work orders)

### Check Logs:
```bash
# Via SSH
tail -50 logs/app.log
tail -50 logs/error.log

# Via cPanel
cPanel → Metrics → Errors
```

---

## 🔧 TROUBLESHOOTING

### Problem: 404 Not Found la /service/parts
**Fix:**
```bash
# Verify routes.php was updated
cat config/routes.php | grep "service/parts"
# Should show 9 routes

# Clear cache (vezi Pasul 4)
# Logout/Login
# Hard refresh browser (Ctrl+Shift+R)
```

### Problem: SQL Error "Table doesn't exist"
**Fix:**
```sql
-- Verify you're on TENANT database
SELECT DATABASE();
-- Must show: wclsgzyf_fm_tenant_1

-- Check tables
SHOW TABLES LIKE 'service_parts%';

-- If empty, re-run migration SQL
```

### Problem: Trigger Error
**Fix:**
```sql
-- Check triggers exist
SHOW TRIGGERS WHERE `Table` = 'service_parts_usage';

-- If missing, create manually:
DROP TRIGGER IF EXISTS update_work_order_parts_cost;

DELIMITER //
CREATE TRIGGER update_work_order_parts_cost AFTER INSERT ON service_parts_usage
FOR EACH ROW
BEGIN
    UPDATE service_work_orders
    SET parts_cost = (
        SELECT COALESCE(SUM(total_price), 0)
        FROM service_parts_usage
        WHERE work_order_id = NEW.work_order_id
    )
    WHERE id = NEW.work_order_id;
END//
DELIMITER ;
```

### Problem: Permission Denied
**Fix:**
- Verify logged in user has company_id set
- Check database_name in companies table
- Verify tenant database connection works

---

## 🎯 POST-DEPLOYMENT CHECKLIST

- [ ] Git pull executed on Hostico
- [ ] All 9 new files present on server
- [ ] Database backup created
- [ ] SQL migration executed on TENANT database
- [ ] 3 tables created (service_parts, service_parts_usage, service_parts_transactions)
- [ ] 3 triggers created (update_work_order_parts_cost*)
- [ ] 8 demo parts visible in database
- [ ] PHP cache cleared (opcache reset)
- [ ] Application restarted/refreshed
- [ ] `/service/parts` loads successfully
- [ ] Add part form works
- [ ] Edit part works
- [ ] Stock adjustment works
- [ ] View part details works
- [ ] Filters work (search, category, low stock)
- [ ] No errors in logs/app.log
- [ ] No errors in logs/error.log
- [ ] Sidebar link added (optional)
- [ ] Test piesa created and deleted
- [ ] Demo data kept/removed as desired

---

## 📊 DEPLOYMENT SUMMARY

**What was deployed:**
- ✅ Parts inventory management system
- ✅ Stock tracking with transactions
- ✅ Low stock alerts
- ✅ Integration with work orders
- ✅ Complete CRUD operations
- ✅ Filters and search
- ✅ Statistics dashboard
- ✅ 8 demo parts for testing

**Database changes:**
- ✅ 3 new tables (service_parts*)
- ✅ 3 triggers for auto-cost calculation
- ✅ Foreign keys to work_orders

**Code changes:**
- ✅ 1 Model (Part.php)
- ✅ 1 Controller (PartsController.php)
- ✅ 3 Views (index, form, view)
- ✅ 9 Routes added
- ✅ Complete documentation

**Files deployed:**
1. modules/service/models/Part.php
2. modules/service/controllers/PartsController.php
3. modules/service/views/parts/index.php
4. modules/service/views/parts/form.php
5. modules/service/views/parts/view.php
6. sql/migrations/010_service_parts_inventory.sql
7. modules/service/PARTS_INVENTORY_README.md
8. docs/DEPLOY_PARTS_INVENTORY.md
9. config/routes.php (updated)

---

## 📞 SUPPORT

**Documentation:**
- User Guide: `modules/service/PARTS_INVENTORY_README.md`
- Deployment Guide: `docs/DEPLOY_PARTS_INVENTORY.md`

**Logs Location:**
- `logs/app.log`
- `logs/error.log`
- cPanel → Metrics → Errors

**GitHub:**
- Repository: nnoldi-hub/fleetly
- Branch: main
- Commit: cf751bd

---

## ✅ DEPLOYMENT COMPLETED

**Date**: _____________  
**Deployed By**: _____________  
**Status**: ☐ Success ☐ Issues (detail below)  
**Notes**: 
```
_________________________________________________
_________________________________________________
_________________________________________________
```

**Tested Features:**
- [ ] List parts
- [ ] Add part
- [ ] Edit part
- [ ] View details
- [ ] Adjust stock
- [ ] Filters work
- [ ] No errors

**Sign-off**: ________________  Date: ________
