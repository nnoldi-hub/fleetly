# Configurare Notificări Broadcast pentru Companie

## Prezentare generală
Sistemul de notificări suportă acum **broadcast către toți utilizatorii unei companii**. Când un admin/manager activează această opțiune, notificările automate (asigurări/mentenanță/documente în expirare) sunt trimise la toți utilizatorii activi din companie, nu doar la admin.

## Cum funcționează

### 1. Schema bazei de date
- Tabela `notifications` are acum coloana `company_id` (INT NULL)
- Coloana `user_id` poate fi NULL pentru notificări în modul broadcast
- Când `company_id` este setat și `user_id` este NULL, sistemul creează automat câte o notificare pentru fiecare utilizator activ din companie

### 2. Preferințe utilizator
- Administratorii și managerii pot activa opțiunea **"Broadcast notificări către toți utilizatorii companiei"** în `/notifications/settings`
- Preferința este salvată în `system_settings` cu cheia `notifications_prefs_user_{id}` în câmpul JSON `broadcastToCompany`
- Doar rolurile `admin` și `manager` pot activa această opțiune

### 3. Fluxul de creare notificări
1. Notificările automate (cron, generare manuală) apelează metodele statice din `Notification`:
   - `createInsuranceExpiryNotification($insuranceId, $licensePlate, $type, $endDate, $priority, $companyId)`
   - `createMaintenanceNotification($vehicleId, $licensePlate, $type, $companyId)`
   - `createDocumentExpiryNotification($documentId, $licensePlate, $type, $daysUntilExpiry, $companyId)`

2. Aceste metode citesc preferința de broadcast a adminului companiei prin `getAdminBroadcastPreference($companyId)`

3. Dacă `broadcastToCompany` este `true`:
   - Se setează `company_id` în loc de `user_id`
   - Metoda `Notification::create()` detectează acest mod și:
     - Găsește toți utilizatorii activi ai companiei
     - Creează câte o notificare individuală pentru fiecare (`user_id` setat)
     - Fiecare utilizator primește notificarea în lista sa personală

4. Dacă `broadcastToCompany` este `false` (sau lipsește):
   - Se setează `user_id = 1` (admin)
   - Notificarea este creată doar pentru un singur utilizator

## Pași de instalare pe producție

### Pas 1: Aplicare migrare SQL
Rulează următorul SQL pe baza de date **TENANT** (exemplu: `wclsgzyf_fm_tenant_1`):

```sql
-- Adaugă coloană company_id pentru broadcast
ALTER TABLE notifications 
ADD COLUMN company_id INT(11) NULL AFTER user_id,
ADD INDEX idx_company_id (company_id);

-- Permite user_id NULL pentru notificări broadcast
ALTER TABLE notifications 
MODIFY COLUMN user_id INT(11) NULL;

-- Backfill company_id pentru notificările existente
UPDATE notifications n
INNER JOIN users u ON n.user_id = u.id
SET n.company_id = u.company_id
WHERE n.company_id IS NULL AND u.company_id IS NOT NULL;
```

**Notă**: Poți rula manual în phpMyAdmin sau prin scriptul:
```bash
php scripts/run_migration.php sql/migrations/2025_11_10_001_add_company_id_to_notifications.sql
```

### Pas 2: Commit și deploy cod
Toate modificările sunt deja implementate în cod:
- ✅ `modules/notifications/models/Notification.php` - logica broadcast în `create()`, helper `getAdminBroadcastPreference()`, metode statice actualizate
- ✅ `modules/notifications/controllers/NotificationController.php` - salvare preferință broadcast, `generateSystemNotifications()` actualizat
- ✅ `modules/notifications/views/settings.php` - checkbox UI pentru broadcast
- ✅ `sql/migrations/2025_11_10_001_add_company_id_to_notifications.sql` - migrare SQL

Commitează toate fișierele și push pe repository pentru a declanșa deployment-ul automat pe Hostico.

### Pas 3: Verificare și activare
1. Autentifică-te ca **admin** al companiei pe https://fleetly.ro/index.php/notifications/settings
2. Secțiunea **"Preferințe notificări"** va afișa:
   - ☑️ **"Broadcast notificări către toți utilizatorii companiei"** (checkbox nou)
   - Text explicativ: *"Când este activat, notificările automate (asigurări, mentenanță, documente) vor fi trimise la toți utilizatorii activi ai companiei, nu doar la tine."*
3. Bifează checkbox-ul și apasă **"Salvează preferințele"**

### Pas 4: Testare
1. **Generare manuală**: Accesează `/notifications` și apasă butonul **"Generează notificări automate"**
2. **Verificare**: 
   - Autentifică-te cu un alt utilizator din aceeași companie (rol `operator` sau `manager`)
   - Accesează `/notifications` - ar trebui să vezi aceleași notificări generate
3. **Cron automat**: Dacă ai configurat `scripts/process_notifications.php` în cron, notificările vor fi create automat zilnic

## Scenarii de utilizare

### Scenariu 1: Companie cu 5 utilizatori, broadcast activat
- Admin activează broadcast în settings
- Se generează o notificare pentru asigurare care expiră
- Sistemul creează **5 notificări** (una pentru fiecare utilizator: admin, 2 manageri, 2 operatori)
- Toți utilizatorii văd notificarea în lista lor personală

### Scenariu 2: Companie cu 3 utilizatori, broadcast dezactivat
- Admin NU activează broadcast (sau îl dezactivează)
- Se generează aceeași notificare pentru asigurare
- Sistemul creează **1 notificare** doar pentru admin (user_id = 1)
- Ceilalți utilizatori NU văd notificarea

### Scenariu 3: Notificări manuale (nu automate)
- Dacă un utilizator creează o notificare manual (prin API sau cod custom), poate alege explicit:
  - `user_id` setat → notificare individuală
  - `company_id` setat + `user_id` NULL → broadcast automat la toți utilizatorii companiei

## Arhitectura tehnică

### Fluxul de date

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Cron/Manual trigger                                          │
│    → generateSystemNotifications()                              │
│    → createInsuranceExpiryNotification($id, ..., $companyId)   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. Citire preferințe admin                                     │
│    → getAdminBroadcastPreference($companyId)                   │
│    → SELECT FROM system_settings WHERE key = 'notifications_   │
│       prefs_user_{admin_id}'                                    │
│    → JSON decode 'broadcastToCompany'                          │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. Decizie: broadcast sau individual?                          │
│    → IF broadcastToCompany == true:                            │
│         $data['company_id'] = $companyId                       │
│         $data['user_id'] = null                                │
│    → ELSE:                                                      │
│         $data['user_id'] = 1                                   │
│         $data['company_id'] = null                             │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. Notification::create($data)                                 │
│    → IF company_id IS SET AND user_id IS NULL:                │
│         SELECT id FROM users WHERE company_id = ? AND status   │
│         = 'active'                                              │
│         FOREACH user: createSingle($data + ['user_id' => id])  │
│    → ELSE:                                                      │
│         createSingle($data)                                     │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. Rezultat final în DB                                        │
│    Broadcast: 5 rânduri în notifications (câte unul per user)  │
│    Individual: 1 rând în notifications (doar admin)            │
└─────────────────────────────────────────────────────────────────┘
```

### Exemplu SQL după broadcast

```sql
-- Înainte de broadcast (user_id = 1, admin):
INSERT INTO notifications (user_id, company_id, type, title, message, ...)
VALUES (1, NULL, 'insurance_expiry', 'Asigurare în expirare', '...', ...);

-- După activare broadcast (company_id = 10, companie cu 3 utilizatori: id 1, 5, 8):
INSERT INTO notifications (user_id, company_id, type, title, message, ...)
VALUES
  (1, 10, 'insurance_expiry', 'Asigurare în expirare', '...', ...),
  (5, 10, 'insurance_expiry', 'Asigurare în expirare', '...', ...),
  (8, 10, 'insurance_expiry', 'Asigurare în expirare', '...', ...);
```

## Întrebări frecvente

**Î: Ce se întâmplă cu notificările existente dacă activez broadcast?**
R: Notificările existente rămân neschimbate. Broadcast-ul afectează doar notificările create DUPĂ activare.

**Î: Pot dezactiva broadcast-ul după ce l-am activat?**
R: Da, debifează checkbox-ul în settings. Notificările viitoare vor fi create doar pentru admin.

**Î: Dacă un utilizator este dezactivat (status != 'active'), primește notificări broadcast?**
R: Nu, doar utilizatorii cu `status = 'active'` primesc notificări broadcast.

**Î: Pot alege care utilizatori primesc notificări?**
R: Nu în versiunea actuală. Broadcast-ul trimite la TOȚI utilizatorii activi ai companiei. Pentru notificări selective, poți crea manual cu `user_id` specific.

**Î: Notificările broadcast consumă mai mult spațiu în DB?**
R: Da, dacă ai 10 utilizatori, o notificare broadcast creează 10 rânduri în loc de 1. Pentru companii mari (50+ utilizatori), monitorizează dimensiunea tabelei `notifications` și curăță notificările vechi periodic.

**Î: Cum testez local?**
R: 
1. Aplică migrarea pe baza locală tenant
2. Creează mai mulți utilizatori în aceeași companie
3. Activează broadcast în settings
4. Rulează `generateSystemNotifications()` sau creează manual o notificare cu `company_id` setat
5. Verifică că toți utilizatorii văd notificarea în `/notifications`

## Suport
Pentru probleme sau întrebări:
- Verifică logs în `logs/` (erori DB, SQL queries)
- Testează pe local înainte de a aplica pe producție
- Contactează echipa de dezvoltare pentru asistență
