# 🧪 Ghid de Testare - Modul Service Auto

## 📋 Pregătire pentru Testare

### 1. Rulare Migrare SQL

**Opțiunea A: Prin phpMyAdmin**
1. Deschideți http://localhost/phpmyadmin
2. Selectați baza de date (ex: `fleet_management`)
3. Click pe tab **SQL**
4. Copiați conținutul fișierului `sql/migrations/service_module_schema.sql`
5. Click **Execute** / **Du-te**
6. Verificați că toate tabelele au fost create (10 tabele + 2 view-uri)

**Opțiunea B: Prin linia de comandă**
```bash
cd C:\wamp64\www\fleet-management
mysql -u root -p fleet_management < sql/migrations/service_module_schema.sql
```

### 2. Verificare Tabele Create

Rulați în SQL:
```sql
SHOW TABLES LIKE 'service%';
SHOW TABLES LIKE 'work_order%';
```

Ar trebui să vedeți:
- services
- service_appointments
- service_history
- service_mechanics
- service_notifications
- work_orders
- work_order_checklist
- work_order_labor
- work_order_parts
- maintenance_rules

### 3. Verificare Triggere

```sql
SHOW TRIGGERS WHERE `Table` LIKE 'work_order%';
```

Ar trebui să existe 8 triggere:
- `update_work_order_costs_after_part_insert`
- `update_work_order_costs_after_part_update`
- `update_work_order_costs_after_part_delete`
- `update_work_order_costs_after_labor_insert`
- `update_work_order_costs_after_labor_update`
- `update_work_order_costs_after_labor_delete`
- `calculate_labor_hours_on_end`
- `update_labor_cost_on_end`

## 🎯 Scenarii de Testare

### Test 1: Adăugare Service Extern

**Pași:**
1. Navigați la: `http://localhost/fleet-management/service/services`
2. Click **Service Nou**
3. Selectați **Service Extern**
4. Completați:
   - Nume: "Auto Expert Service SRL"
   - Email: "contact@autoexpert.ro"
   - Telefon: "0721234567"
   - Adresă: "Str. Industriei nr. 15"
   - Oraș: "București"
   - Județ: "București"
   - Specialități: "BMW, Mercedes, Audi"
   - Rating: 5 stele
   - Certificate: "RAR, ARR, ISO 9001"
5. Click **Salvează Service**

**Rezultat Așteptat:**
- ✅ Service creat cu succes
- ✅ Apare în lista de servicii
- ✅ Badge "Extern" vizibil
- ✅ Rating cu 5 stele afișat

### Test 2: Configurare Service Intern (Atelier)

**Pași:**
1. Click **Service Nou**
2. Selectați **Service Intern**
3. Completați:
   - Nume: "Atelier FlotaPro"
   - Capacitate: 6 posturi
   - Tarif orar: 180 RON/h
   - Program: "L-V 08:00-18:00"
   - Echipamente: "2x Elevator, Aparat diagnoza OBD, Geometrie roti, Banc probe"
4. Click **Salvează Service**

**Rezultat Așteptat:**
- ✅ Service intern creat
- ✅ Badge "Intern" vizibil
- ✅ Buton "Dashboard Atelier" apare
- ✅ Capacitate 6 posturi înregistrată

### Test 3: Adăugare Mecanic

**Pași:**
1. Rulați SQL manual (deocamdată nu există UI):
```sql
INSERT INTO service_mechanics (tenant_id, service_id, user_id, name, specialization, is_active) 
VALUES 
(1, (SELECT id FROM services WHERE service_type = 'internal' LIMIT 1), NULL, 'Ion Popescu', 'Mecanic Auto General', 1),
(1, (SELECT id FROM services WHERE service_type = 'internal' LIMIT 1), NULL, 'Vasile Ionescu', 'Specialist Electronica', 1);
```

**Notă:** Înlocuiți `tenant_id=1` cu ID-ul companiei dvs.

### Test 4: Creare Ordine de Lucru

**Pași:**
1. Click pe service-ul intern → **Dashboard Atelier**
2. Click **Ordine de Lucru Nouă**
3. Completați:
   - **Vehicul**: Selectați din listă (ex: B-123-ABC)
   - **Kilometraj Intrare**: 45000
   - **Data Intrare**: Astăzi, ora curentă
   - **Descriere Problemă**: "Zgomot suspensie față la viraje, frâne slabe"
   - **Lucrări Solicitate**: "Verificare suspensie, înlocuire plăcuțe frână"
   - **Prioritate**: Ridicată
   - **Estimare Ore**: 3
   - **Mecanic**: Ion Popescu
   - **Estimare Manoperă**: 540 RON (3h x 180 RON/h)
   - **Estimare Piese**: 800 RON
4. Click **Creează Ordine de Lucru**

**Rezultat Așteptat:**
- ✅ Ordine creată cu număr automat (ex: WO-2025-001)
- ✅ Status: "În Așteptare"
- ✅ Total estimat: 1340 RON
- ✅ Apare în dashboard

### Test 5: Lucru pe Ordine - Checklist

**Pași:**
1. Click pe ordinea creată → **Vizualizare Detalii**
2. În secțiunea **Checklist**, click **Generează Checklist Implicit**
3. Completați fiecare element:
   - Nivel ulei motor: **OK**
   - Lichid frână: **Atenție** (observații: "Nivel scăzut, necesită completare")
   - Placuțe frână față: **Critic** (observații: "Sub 2mm, înlocuire urgentă")
   - Suspensie față: **Atenție** (observații: "Joc în silent-block braț")
   - ... (continuați pentru toate cele 12 elemente)
4. Click **Salvează Checklist**

**Rezultat Așteptat:**
- ✅ Checklist salvat în baza de date
- ✅ Statusurile color-coded (verde/galben/roșu)
- ✅ Observațiile vizibile

### Test 6: Adăugare Piese

**Pași:**
1. În pagina ordinii, click **Adaugă Piesă**
2. Completați:
   - **Cod Piesă**: "BRK-PADS-F-001"
   - **Denumire**: "Plăcuțe frână față TRW"
   - **Cantitate**: 1
   - **Preț Unitar**: 350 RON
3. Click **Adaugă**
4. Repetați pentru:
   - Silent-block braț: "SUSP-SB-002", 2 buc x 125 RON
   - Lichid frână DOT4: "FLUID-BRK-01", 1L x 45 RON

**Rezultat Așteptat:**
- ✅ Piese adăugate în tabel
- ✅ Cost piese actualizat automat: 645 RON (350 + 250 + 45)
- ✅ Total ordine recalculat în sidebar

### Test 7: Cronometru Manoperă

**Pași:**
1. Click **Start Lucru**
2. Completați:
   - **Descriere**: "Înlocuire plăcuțe frână și silent-block-uri"
   - **Tarif Orar**: 180 RON/h
3. Click **Start**
4. Așteptați 1-2 minute (simulare lucru)
5. Click **Stop** pe linia de manoperă

**Rezultat Așteptat:**
- ✅ Manoperă apare în tabel cu start time
- ✅ La stop: ore calculate automat (ex: 0.03h pentru 2 minute)
- ✅ Cost manoperă calculat: ore × tarif
- ✅ Total ordine actualizat

**Test Avansat - Multiple sesiuni:**
6. Adăugați a doua sesiune:
   - Descriere: "Testare suspensie și frâne după reparație"
   - Tarif: 180 RON/h
7. Start → Așteptați → Stop
8. Verificați că ambele sesiuni sunt în tabel
9. Verificați că **Ore Lucrate** din header = suma tuturor sesiunilor

### Test 8: Schimbare Statusuri

**Pași:**
1. **Din "În Așteptare" → "În Lucru":**
   - Click buton **Începe Lucru** din sidebar
   - Confirmați
   - Verificați badge devine albastru "În Lucru"

2. **Din "În Lucru" → "Așteptare Piese":**
   - Click **Așteptare Piese**
   - Verificați badge galben "Waiting Parts"

3. **Din "Așteptare Piese" → "În Lucru":**
   - Click **Reia Lucru**
   - Badge revine la albastru

4. **Din "În Lucru" → "Finalizat":**
   - Click **Marchează Finalizat**
   - Verificați badge verde "Finalizat"
   - Verificați că se setează **Data Finalizare**

5. **Din "Finalizat" → "Livrat":**
   - Click **Marchează Livrat**
   - Badge devine albastru deschis "Livrat"
   - Verificați **Data Livrare** setată

**Rezultat Așteptat:**
- ✅ Toate tranzițiile funcționează
- ✅ Badge-urile își schimbă culoarea
- ✅ Datele sunt înregistrate corect
- ✅ Butoanele de acțiune se adaptează la status

### Test 9: Dashboard Statistici

**Pași:**
1. Navigați la **Dashboard Atelier**
2. Verificați cardurile de statistici:
   - **Posturi Ocupate**: Ar trebui să arate 1/6 (o ordine activă)
   - **Finalizate Astăzi**: Dacă ați finalizat ordinea, ar trebui 1
   - **În Lucru Acum**: Ordine cu status "in_progress"
   - **Venit Astăzi**: Suma costurilor ordine finalizate astăzi

**Test Filtre:**
3. Testați filtrele:
   - Status: "În Lucru" → Ar trebui să afișeze doar ordine active
   - Prioritate: "Ridicată" → Doar ordine cu prioritate high
   - Mecanic: "Ion Popescu" → Doar ordinele lui
   - Interval date: Ultima săptămână

**Rezultat Așteptat:**
- ✅ Statistici corecte și actualizate
- ✅ Filtrele funcționează
- ✅ Progress bar capacitate corect calculat
- ✅ Culori adaptative (verde/galben/roșu pentru capacitate)

### Test 10: Validare Costuri Automate

**Pași:**
1. Creați o ordine nouă
2. Adăugați 3 piese cu prețuri cunoscute (ex: 100 + 200 + 300 = 600 RON)
3. Adăugați 2 sesiuni manoperă:
   - Prima: 2h × 180 RON/h = 360 RON
   - A doua: 1.5h × 180 RON/h = 270 RON
   - Total manoperă: 630 RON
4. Verificați în **Sumar Costuri** din sidebar:
   - Piese: 600 RON
   - Manoperă: 630 RON
   - **TOTAL: 1230 RON**

5. Rulați SQL pentru verificare:
```sql
SELECT 
    work_order_number,
    parts_cost,
    labor_cost,
    total_cost,
    (parts_cost + labor_cost) AS calculated_total
FROM work_orders 
WHERE work_order_number = 'WO-2025-002'; -- înlocuiți cu numărul vostru
```

**Rezultat Așteptat:**
- ✅ `parts_cost` = 600.00
- ✅ `labor_cost` = 630.00
- ✅ `total_cost` = 1230.00
- ✅ `calculated_total` = `total_cost` (confirmare triggere funcționează)

## 🐛 Verificare Errori Comune

### 1. Tabelele nu se creează

**Verificare:**
```sql
SHOW ERRORS;
```

**Soluții:**
- Verificați că aveți permisiuni CREATE TABLE
- Verificați că tabelele `tenants`, `vehicles`, `users` există
- Verificați engine InnoDB activat

### 2. Vehiculele nu apar în dropdown

**Cauză:** Lipsă vehicule în baza de date sau tenant_id incorect

**Soluție:**
```sql
-- Verificare vehicule
SELECT id, plate_number, make, model, tenant_id 
FROM vehicles 
WHERE tenant_id = 1; -- înlocuiți cu tenant-ul vostru

-- Adăugare vehicul test (dacă lipsește)
INSERT INTO vehicles (tenant_id, plate_number, make, model, year, current_km) 
VALUES (1, 'B-TEST-123', 'Dacia', 'Logan', 2020, 50000);
```

### 3. Eroare "Service ID not found"

**Cauză:** Nu ați creat un service intern

**Soluție:** Urmați **Test 2** pentru creare service intern

### 4. Mecanicii nu apar

**Cauză:** Tabelul `service_mechanics` este gol

**Soluție:** Urmați **Test 3** pentru adăugare mecanici

### 5. Triggerele nu calculează corect

**Verificare:**
```sql
-- Verificare trigger există
SHOW TRIGGERS LIKE 'work_order%';

-- Test manual calcul
SELECT 
    wo.work_order_number,
    COALESCE(SUM(wop.total_price), 0) AS manual_parts_cost,
    wo.parts_cost AS trigger_parts_cost
FROM work_orders wo
LEFT JOIN work_order_parts wop ON wo.id = wop.work_order_id
GROUP BY wo.id;
```

**Soluție:** Dacă diferă, re-rulați crearea triggerelor din SQL migration

## ✅ Checklist Final Testare

Bifați după ce testați fiecare funcționalitate:

- [ ] Adăugare service extern
- [ ] Configurare service intern
- [ ] Adăugare mecanici
- [ ] Creare ordine de lucru (numerotare automată)
- [ ] Generare checklist implicit
- [ ] Completare checklist cu toate statusurile
- [ ] Adăugare minimum 3 piese diferite
- [ ] Start/stop manoperă (minimum 2 sesiuni)
- [ ] Schimbare toate statusurile (pending → delivered)
- [ ] Verificare calcul automat costuri
- [ ] Testare filtre în dashboard
- [ ] Verificare statistici dashboard
- [ ] Alocare mecanic pe ordine
- [ ] Editare service existent
- [ ] Vizualizare detalii service cu istoric
- [ ] Toggle activate/deactivate service

## 📊 Rezultate Așteptate

După testarea completă:
- ✅ Minimum 2 servicii create (1 extern, 1 intern)
- ✅ Minimum 2 mecanici în sistem
- ✅ Minimum 2 ordine de lucru complete
- ✅ Toate statusurile testate
- ✅ Costuri calculate automat corect
- ✅ Dashboard cu statistici reale
- ✅ Zero erori PHP/SQL în log

## 🚀 Următorii Pași

După testare cu succes:
1. Commit în Git
2. Push pe repository
3. Deploy pe Hostico
4. Testare pe server production
5. Training utilizatori finali

## 📝 Raportare Bug-uri

Dacă găsiți probleme în timpul testării:

1. **Notați:**
   - Pașii care au dus la eroare
   - Mesajul de eroare exact
   - Screenshot (dacă e posibil)
   - Browser și versiune

2. **Verificați:**
   - Log-uri PHP: `C:\wamp64\logs\php_error.log`
   - Log-uri Apache: `C:\wamp64\logs\apache_error.log`
   - Console browser (F12 → Console)

3. **Raportați** prin GitHub Issues sau document Word

---

**Baftă la testare! 🎉**
