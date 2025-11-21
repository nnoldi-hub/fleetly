# Workflow Service Intern - Ghid Detaliat

## Versiune: 1.0
## Data: 21 Noiembrie 2025

---

## 1. Prezentare Generală

Acest document descrie în detaliu fluxul de lucru pentru companiile care gestionează propriul service auto intern. Include scenarii concrete, exemple de utilizare și best practices.

---

## 2. Configurare Inițială Service Intern

### Pasul 1: Activare Modul Service Intern

**Acțiuni Admin:**
1. Accesare `Setări > Service > Service Intern`
2. Bifează "Activare Service Intern"
3. Completează detalii:
   - Nume atelier (ex: "Atelier Intern FlotaSRL")
   - Adresă completă
   - Program de lucru (ex: L-V: 08:00-17:00)
   - Număr posturi de lucru disponibile (ex: 4)
   - Tarif standard manoperă/oră (ex: 150 RON)

### Pasul 2: Adăugare Mecanici

**Acțiuni Admin:**
1. Accesare `Service > Mecanici > Adaugă Mecanic`
2. Pentru fiecare mecanic:
   ```
   Nume: Ion Popescu
   Specializare: Mecanic Motor
   Tarif/oră: 180 RON
   Telefon: 0722123456
   Email: ion.popescu@company.com
   Data angajării: 01/01/2020
   ```

**Exemplu listă mecanici:**
- Ion Popescu - Mecanic Motor - 180 RON/oră
- Maria Ionescu - Electrician Auto - 170 RON/oră
- Vasile Dumitrescu - Mecanic Caroserie - 160 RON/oră
- Alexandru Radu - Mecanic Universal - 150 RON/oră

---

## 3. Fluxul Complet al unui Vehicul în Service

### Scenariul 1: Revizie Tehnică Programată

#### Etapa 1: Programare Inițială
**Actor: Admin sau Dispatcher**

```
Data programării: 25 Noiembrie 2025, 09:00
Vehicul: Dacia Duster (B-123-ABC)
Tip intervenție: Revizie tehnică 15.000 km
Service: Atelier Intern
Observații: Vehiculul are și un zgomot la frână stângă față
```

#### Etapa 2: Intrare Vehicul în Atelier
**Actor: Admin sau Mecanic șef**

1. Vehiculul sosește la atelier
2. Creare **Ordine de Lucru #WO-2025-001**:

```
═══════════════════════════════════════════
    ORDIN DE LUCRU #WO-2025-001
═══════════════════════════════════════════

Vehicul: Dacia Duster (B-123-ABC)
Data intrare: 25/11/2025 09:15
Kilometraj: 15.234 km
Mecanic alocat: Ion Popescu

LUCRĂRI DE EFECTUAT:
✓ Revizie tehnică 15.000 km
✓ Verificare sistem frânare
✓ Schimb ulei motor + filtru
✓ Verificare toate lichide
✓ Rotație anvelope

Prioritate: NORMALĂ
Termen estimat: 25/11/2025 14:00
Ore estimate: 3h

STATUS: ÎN AȘTEPTARE
═══════════════════════════════════════════
```

#### Etapa 3: Checklist Diagnoză Inițială
**Actor: Mecanic (Ion Popescu)**

Mecanic completează checklist:

```
CHECKLIST DIAGNOZĂ - WO-2025-001
══════════════════════════════════

☑ Verificare nivel ulei motor - OK
☑ Verificare nivel lichid frână - ATENȚIE (sub minim)
☑ Verificare nivel antigel - OK
☑ Verificare uzură plăcuțe frână - CRITICAL (față: 2mm)
☑ Verificare discuri frână - Zgârieturi ușoare
☑ Verificare presiune anvelope - Ajustare necesară
☑ Verificare lumini - OK
☑ Verificare curele transmisie - OK
☑ Test funcțional climatizare - OK

OBSERVAȚII:
- Plăcuțe frână față uzate CRITIC → Schimb urgent
- Discuri prezintă zgârieturi → Recomand schimb
- Lichid frână sub nivel → Completare + verificare scurgeri
```

#### Etapa 4: Comunicare cu Clientul (Intern)
**Actor: Admin**

Admin (sau mecanic) adaugă note în ordine:

```
LUCRU SUPLIMENTAR NECESAR:

Descoperit: Plăcuțe frână față uzate critic (2mm)
Recomandare: Schimb urgent plăcuțe + discuri
Cost estimat piese: 450 RON
Cost estimat manoperă: 2h x 180 RON = 360 RON
TOTAL ESTIMAT SUPLIMENTAR: 810 RON

Status: APROBAT de șef flotă (25/11/2025 10:30)
```

#### Etapa 5: Începere Lucrări
**Actor: Mecanic (Ion Popescu)**

Mecanic pornește cronometrul pentru tracking timp:

```
TRACKING MANOPERĂ - WO-2025-001
════════════════════════════════════

Task #1: Revizie + schimb ulei
Început: 25/11/2025 09:30
Sfârșit: 25/11/2025 11:00
Ore lucrate: 1.5h
Tarif: 180 RON/h
Cost: 270 RON
Descriere: Revizie completă, schimb ulei Castrol 5W30, filtru ulei

Task #2: Schimb plăcuțe și discuri frână față
Început: 25/11/2025 11:15
Sfârșit: 25/11/2025 13:30
Ore lucrate: 2.25h
Tarif: 180 RON/h
Cost: 405 RON
Descriere: Demontare anvelope față, schimb plăcuțe Bosch, 
          schimb discuri ATE, test frânare
```

#### Etapa 6: Adăugare Piese Consumate
**Actor: Mecanic sau Admin**

```
PIESE UTILIZATE - WO-2025-001
═══════════════════════════════════════

1. Ulei motor Castrol 5W30
   Cod: CAST-5W30-5L
   Cantitate: 4.5L
   Preț unitar: 45 RON/L
   Total: 202.50 RON
   Furnizor: AutoParts SRL

2. Filtru ulei
   Cod: MANN-W712
   Cantitate: 1
   Preț: 35 RON
   Furnizor: AutoParts SRL

3. Set plăcuțe frână față Bosch
   Cod: BOSCH-0986424
   Cantitate: 1 set
   Preț: 280 RON
   Furnizor: BoschAuto

4. Discuri frână față ATE (2 buc)
   Cod: ATE-24012
   Cantitate: 2
   Preț unitar: 170 RON
   Total: 340 RON
   Furnizor: BoschAuto

5. Lichid frână DOT4
   Cod: MOTUL-DOT4
   Cantitate: 0.5L
   Preț: 25 RON
   Furnizor: AutoParts SRL

────────────────────────────────────────
TOTAL PIESE: 882.50 RON
```

#### Etapa 7: Finalizare și Raport
**Actor: Mecanic**

```
RAPORT FINALIZARE - WO-2025-001
════════════════════════════════════════════

Vehicul: Dacia Duster (B-123-ABC)
Data finalizare: 25/11/2025 13:45

LUCRĂRI EFECTUATE:
✓ Revizie tehnică 15.000 km - COMPLETĂ
✓ Schimb ulei motor + filtru ulei
✓ Schimb plăcuțe frână față (ambele)
✓ Schimb discuri frână față (ambele)
✓ Completare lichid frână + verificare sistem
✓ Rotație anvelope
✓ Verificare și setare presiune anvelope
✓ Test frânare - REZULTAT OPTIM

OBSERVAȚII FINALE:
- Sistem frânare 100% funcțional
- Toate lichidele la nivel optim
- Anvelope: uzură uniformă, presiune corectă
- Recomandare: Următoarea revizie la 30.000 km

CALCUL COSTURI:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Manoperă: 3.75h x 180 RON/h = 675.00 RON
Piese:                         882.50 RON
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTAL:                       1,557.50 RON
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Următorul service recomandat:
- Data: 25 Mai 2026 SAU
- Kilometraj: 30.000 km
(ce vine mai întâi)

Mecanic responsabil: Ion Popescu
Semnătură: _______________
```

#### Etapa 8: Livrare Vehicul
**Actor: Admin sau Mecanic șef**

```
STATUS FINAL: LIVRAT
Data livrare: 25/11/2025 14:00
Livrat către: Andrei Marinescu (șofer)

Notificare automată trimisă:
✓ Email către șofer
✓ Notificare in-app
✓ SMS (opțional)

"Vehiculul Dacia Duster (B-123-ABC) este gata 
și poate fi ridicat din atelier. Total cost: 1,557.50 RON"
```

---

## 4. Scenarii Speciale

### Scenariul 2: Pană în Teren - Intervenție Urgentă

```
ORDIN DE LUCRU #WO-2025-002 (URGENT)
════════════════════════════════════════

Vehicul: Ford Transit (B-456-DEF)
Raportare: 26/11/2025 07:30 (șofer pe teren)
Problemă: "Mașina nu mai pornește, baterie descărcată"
Locație: Șos. București-Ploiești km 15

ACȚIUNI IMEDIATE:
1. ☑ Trimis mecanic cu echipament mobil (08:00)
2. ☑ Încărcare baterie + diagnoză (08:30)
3. ☑ Constatat: Alternator defect
4. ☑ Tractare la atelier (09:15)

INTRARE ATELIER: 09:30
PRIORITATE: URGENTĂ (vehicul necesar azi)
MECANIC: Alexandru Radu + Maria Ionescu (electrician)

LUCRĂRI:
- Schimb alternator (2h)
- Test sistem electric complet
- Verificare și încărcare baterie

ESTIMAT FINALIZARE: 26/11/2025 14:00
```

### Scenariul 3: Service Planificat cu Lipsă Piese

```
ORDIN DE LUCRU #WO-2025-003
════════════════════════════════════════

Vehicul: Mercedes Sprinter (B-789-GHI)
Data intrare: 27/11/2025 10:00
Lucrare: Schimb amortizoare spate

STATUS INIȚIAL: ÎN LUCRU (10:15)

PROBLEMĂ IDENTIFICATĂ (11:30):
❌ Piese comandate greșite - nu se potrivesc
❌ Piese corecte indisponibile la furnizor local

ACȚIUNI:
1. ☑ Comandă urgentă piese corecte (27/11 12:00)
2. ☑ Confirmare livrare: 28/11 dimineața
3. ☑ Vehicul mutat în parcare așteptare

STATUS ACTUALIZAT: ÎN AȘTEPTARE PIESE
Notificare automată către șef flotă:
"Ordinul #WO-2025-003 este în așteptare piese. 
Estimare finalizare: 28/11/2025"

RELUARE LUCRĂRI: 28/11/2025 08:30
FINALIZARE: 28/11/2025 11:00
```

---

## 5. Dashboard Atelier - Vizualizare Zilnică

### Exemplu Dashboard (26 Noiembrie 2025, 10:00)

```
╔════════════════════════════════════════════════════════════════╗
║         DASHBOARD ATELIER - 26 Noiembrie 2025                  ║
╚════════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────────┐
│  VEHICULE ÎN ATELIER ACUM: 3/4 posturi ocupate                  │
└─────────────────────────────────────────────────────────────────┘

🔧 POST 1 - OCUPAT
   Vehicul: Ford Transit (B-456-DEF)
   Ordine: #WO-2025-002
   Mecanic: Alexandru Radu
   Status: ÎN LUCRU (50% completat)
   Estimat finalizare: 14:00
   ⏱️ Timp lucrat: 1.5h / 3h estimate

🔧 POST 2 - OCUPAT
   Vehicul: VW Caddy (B-321-JKL)
   Ordine: #WO-2025-004
   Mecanic: Maria Ionescu
   Status: ÎN LUCRU (70% completat)
   Estimat finalizare: 11:30
   ⏱️ Timp lucrat: 2h / 2.5h estimate

🔧 POST 3 - OCUPAT
   Vehicul: Renault Master (B-654-MNO)
   Ordine: #WO-2025-005
   Mecanic: Vasile Dumitrescu
   Status: DIAGNOZĂ
   Estimat finalizare: 16:00
   ⏱️ Timp lucrat: 0.5h / 4h estimate

🔧 POST 4 - LIBER

┌─────────────────────────────────────────────────────────────────┐
│  COADĂ AȘTEPTARE: 2 vehicule                                    │
└─────────────────────────────────────────────────────────────────┘

⏳ Opel Vivaro (B-987-PQR) - Revizie - NORMAL - Est. 2h
⏳ Fiat Ducato (B-147-STU) - Reparație suspensie - URGENTĂ - Est. 3h

┌─────────────────────────────────────────────────────────────────┐
│  STATISTICI AZI                                                 │
└─────────────────────────────────────────────────────────────────┘

✓ Ordine finalizate: 1
⏱️ Timp total lucrat: 4h
💰 Venit generat: 1,557.50 RON
👷 Mecanici activi: 3/4

┌─────────────────────────────────────────────────────────────────┐
│  ALERTE                                                         │
└─────────────────────────────────────────────────────────────────┘

⚠️ WO-2025-003 - În așteptare piese de 1 zi
⚠️ WO-2025-005 - Diagnosticare prelungită (posibile costuri extra)
```

---

## 6. Rapoarte Specifice Service Intern

### Raport Eficiență Atelier - Săptămânal

```
╔════════════════════════════════════════════════════════════════╗
║    RAPORT EFICIENȚĂ ATELIER                                    ║
║    Perioada: 18-24 Noiembrie 2025                              ║
╚════════════════════════════════════════════════════════════════╝

INDICATORI CHEIE:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Ordine de lucru finalizate: 23
Vehicule procesate: 23
Timp mediu per ordine: 3.2 ore
Utilizare capacitate atelier: 78% (bună)

Ore disponibile (4 posturi x 8h x 5 zile): 160h
Ore lucrate efectiv: 125h
Ore facturabile: 118h
Eficiență facturare: 94.4% (excelent)

DEFALCARE PE TIPURI LUCRĂRI:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Revizii periodice:      12 ordine (52%)
Reparații mecanice:      6 ordine (26%)
Reparații electrice:     3 ordine (13%)
Caroserie:               2 ordine (9%)

VENIT GENERAT:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Manoperă totală:     21,240 RON (118h x 180 RON/h mediu)
Piese totale:        18,450 RON
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTAL VENIT:         39,690 RON
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

COMPARAȚIE:
Săptămâna anterioară: 35,200 RON (+12.8% ↑)
```

### Raport Productivitate Mecanic - Lunar

```
╔════════════════════════════════════════════════════════════════╗
║    RAPORT PRODUCTIVITATE MECANICI                              ║
║    Noiembrie 2025                                              ║
╚════════════════════════════════════════════════════════════════╝

┌──────────────────────────────────────────────────────────────┐
│  Ion Popescu - Mecanic Motor                                 │
└──────────────────────────────────────────────────────────────┘

Ordine finalizate: 34
Ore lucrate: 142h
Ore facturabile: 138h
Eficiență: 97.2% ⭐⭐⭐⭐⭐
Venit generat: 24,840 RON (138h x 180 RON/h)
Rating mediu lucrări: 4.9/5

Top specializare: Revizii + Reparații motor

┌──────────────────────────────────────────────────────────────┐
│  Maria Ionescu - Electrician Auto                            │
└──────────────────────────────────────────────────────────────┘

Ordine finalizate: 28
Ore lucrate: 118h
Ore facturabile: 112h
Eficiență: 94.9% ⭐⭐⭐⭐
Venit generat: 19,040 RON (112h x 170 RON/h)
Rating mediu lucrări: 5.0/5

Top specializare: Diagnosticare electrică, instalații

┌──────────────────────────────────────────────────────────────┐
│  Vasile Dumitrescu - Mecanic Caroserie                       │
└──────────────────────────────────────────────────────────────┘

Ordine finalizate: 18
Ore lucrate: 128h
Ore facturabile: 120h
Eficiență: 93.8% ⭐⭐⭐⭐
Venit generat: 19,200 RON (120h x 160 RON/h)
Rating mediu lucrări: 4.7/5

Top specializare: Caroserie, vopsitorie

┌──────────────────────────────────────────────────────────────┐
│  Alexandru Radu - Mecanic Universal                          │
└──────────────────────────────────────────────────────────────┘

Ordine finalizate: 41
Ore lucrate: 156h
Ore facturabile: 148h
Eficiență: 94.9% ⭐⭐⭐⭐
Venit generat: 22,200 RON (148h x 150 RON/h)
Rating mediu lucrări: 4.8/5

Top specializare: Suspensii, frâne, revizii generale

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTAL ECHIPĂ:
Ordine: 121 | Ore: 544h facturabile | Venit: 85,280 RON
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## 7. Best Practices

### 7.1 Organizare Zilnică

**Dimineața (08:00-08:30):**
- Briefing echipă: Ordinele de azi
- Verificare piese disponibile
- Alocare mecanici pe ordine
- Prioritizare urgențe

**În timpul zilei:**
- Update status ordine la fiecare 2h
- Comunicare imediată probleme descoperite
- Tracking timp real pentru fiecare task
- Fotografii înainte/după (obligatoriu pentru daune)

**Sfârșitul zilei (17:00-17:30):**
- Finalizare ordine zilnice
- Pregătire ordine pentru mâine
- Verificare stoc piese necesare mâine
- Raport zilnic scurt

### 7.2 Comunicare

**Cu șoferii/managerii de flotă:**
- Notificare automată la fiecare schimbare status
- Update costuri dacă apar lucrări suplimentare (IMEDIAT)
- Confirmare înainte de lucrări peste buget
- Notificare când vehicul e gata

**Între mecanici:**
- Notes clare în sistem pentru fiecare descoperire
- Fotografii pentru probleme complexe
- Checklist completat complet
- Transmitere cunoștințe (ex: "Atenție la X la modelul Y")

### 7.3 Tracking Costuri

**Pentru fiecare ordine de lucru:**
- Estimate inițial clar
- Track piese în timp real
- Track manoperă pe task-uri
- Comparație estimat vs. real la final
- Analiză devieri peste 20%

### 7.4 Calitate

**Standarde minime:**
- Checklist diagnoză completat 100%
- Fotografii pentru orice problemă vizuală
- Test drive după lucrări majore (obligatoriu)
- Verificare finală de către mecanic șef
- Feedback șofer după 3 zile de utilizare

---

## 8. Integrare cu Module Existente

### Link cu Modulul Vehicles

```php
// Exemplu: Obținere kilometraj curent automat
$vehicle = Vehicle::getById($vehicle_id);
$current_km = $vehicle->getCurrentKilometrage(); // din ultima înregistrare fuel

// Setare în ordine de lucru automat
$work_order->odometer_reading = $current_km;
```

### Link cu Modulul Fuel

```php
// După finalizare service, update next_service_km în vehicule
$vehicle->next_service_km = $current_km + 15000; // peste 15.000 km
$vehicle->next_service_date = date('Y-m-d', strtotime('+6 months'));
```

### Link cu Modulul Notifications

```php
// Notificare automată când vehicul e gata
Notification::create([
    'user_id' => $driver_id,
    'type' => 'vehicle_ready',
    'message' => "Vehiculul $plate_number este gata și poate fi ridicat.",
    'priority' => 'normal'
]);
```

---

## 9. Avantaje Service Intern vs. Extern

### Service Intern (Atelier Propriu)

**✅ Avantaje:**
- Control total asupra calității
- Disponibilitate imediată
- Costuri transparente
- Prioritizare flota proprie
- Knowledge despre fiecare vehicul
- Flexibilitate program
- Training mecanici pe nevoile specifice

**❌ Dezavantaje:**
- Investiție inițială (echipament, spațiu)
- Costuri fixe (salarii mecanici)
- Limitare la capacitate proprie
- Necesită management activ

### Service Extern (Parteneri)

**✅ Avantaje:**
- Fără costuri fixe
- Acces la specializări diverse
- Scalabil ușor
- Fără responsabilitate angajați

**❌ Dezavantaje:**
- Control limitat calitate
- Disponibilitate limitată
- Prețuri mai mari
- Timp de așteptare
- Lipsa prioritizării

### Combinație Optimă

**Strategie Hibridă Recomandată:**
- Service intern: Mentenanță de rutină, service planificat, urgențe
- Service extern: Reparații specializate (ex: cutie automată, climatizare complexă)

```
Exemplu split 70/30:
- 70% lucrări în atelier propriu (revizii, frâne, suspensii, electric standard)
- 30% externalizate (reparații majore motor, caroserie complexă, vopsitorie)

Rezultat: Cost redus cu 35% față de 100% extern
```

---

## 10. Checklist Implementare

### Faza Pregătitoare (Săptămâna 1-2)

- [ ] Amenajare spațiu fizic atelier
- [ ] Achiziție echipamente de bază (elevator, scule, diagnostic)
- [ ] Recrutare mecanici (minim 2)
- [ ] Obținere autorizații necesare
- [ ] Contracte furnizori piese
- [ ] Setup sistem software

### Faza Pilot (Săptămâna 3-6)

- [ ] Start cu 1-2 mecanici
- [ ] Procesare doar revizii simple inițial
- [ ] Training intensiv pe sistem
- [ ] Optimizare procese
- [ ] Colectare feedback

### Faza Scalare (Luna 2-3)

- [ ] Extindere la toate tipurile de lucrări
- [ ] Angajare mecanici suplimentari
- [ ] Achiziție echipamente specializate
- [ ] Creștere stoc piese
- [ ] Rafinare procese

### Faza Maturitate (Luna 4+)

- [ ] Operare la capacitate optimă
- [ ] Proceduri standardizate
- [ ] KPI-uri monitorizate constant
- [ ] Îmbunătățire continuă
- [ ] Extindere capacitate (dacă necesar)

---

**Document menținut de echipa Fleet Management System**
**Ultima actualizare: 21 Noiembrie 2025**
