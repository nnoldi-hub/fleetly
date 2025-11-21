# Modul Service Auto - Plan de Implementare

## Versiune: 1.0
## Data creării: 21 Noiembrie 2025
## Status: În planificare

---

## 1. Obiective Generale

Modulul Service Auto permite companiilor să gestioneze eficient mentenanța flotei de vehicule, inclusiv programări, istoric intervenții, costuri și notificări automate pentru service-uri scadente.

---

## 2. Funcționalități Principale

### 2.1 Programări Service
- [ ] **Adăugare programări**
  - Formular pentru programare nouă
  - Selectare vehicul din flotă
  - Alegere tip intervenție (revizie, reparație, schimb ulei, etc.)
  - Selectare service partener sau intern
  - Setare dată și oră
  - Adăugare observații/detalii
  
- [ ] **Gestionare programări**
  - Vizualizare calendar programări
  - Editare/anulare programări
  - Marcare programare ca efectuată
  - Filtrare după vehicul, dată, service, status
  
- [ ] **Notificări automate**
  - Reminder cu X zile înainte de programare
  - Notificare în ziua programării
  - Notificare dacă programarea nu este marcată ca efectuată

### 2.2 Istoric Intervenții
- [ ] **Jurnal complet per vehicul**
  - Vizualizare cronologică a tuturor intervențiilor
  - Detalii complete: tip, dată, cost, service, observații
  - Posibilitate de export (PDF/Excel)
  
- [ ] **Tipuri de intervenții**
  - Revizie tehnică periodică
  - Schimb ulei și filtre
  - Reparații mecanice
  - Reparații caroserie
  - Schimb anvelope
  - Alte service-uri
  
- [ ] **Documentare completă**
  - Atașare facturi (PDF)
  - Fotografii înainte/după
  - Note tehnice detaliate
  - Piese schimbate

### 2.3 Mentenanță Periodică
- [ ] **Setare reguli de mentenanță**
  - Definire intervale per vehicul (km/luni)
  - Configurare tipuri de mentenanță recurentă
  - Setare praguri de avertizare
  
- [ ] **Calcul automat scadențe**
  - Monitorizare km parcurși (integrare cu modul fuel/route)
  - Calculare data următorului service
  - Identificare service-uri scadente/depășite
  
- [ ] **Generare notificări automate**
  - Alertă când se apropie scadența (ex: cu 500km sau 7 zile înainte)
  - Alertă la depășirea scadenței
  - Sugestii de programare

### 2.4 Service-uri Partenere
- [ ] **Registru service-uri**
  - Adăugare service nou
  - Editare informații service
  - Dezactivare service
  - Marcare service ca "intern" sau "extern"
  
- [ ] **Profil service**
  - Nume service
  - Adresă completă
  - Contact (telefon, email, persoană de contact)
  - Tipuri de lucrări efectuate
  - Program de lucru
  - Rating/evaluări (opțional)
  - Note/observații
  - Tip service: Intern / Extern
  
- [ ] **Asociere cu programări**
  - Selectare rapidă din listă la programare
  - Istoric colaborări per service
  - Statistici costuri per service

### 2.5 Service Intern (Atelier Propriu)
- [ ] **Configurare atelier intern**
  - Activare/dezactivare modul service intern
  - Configurare detalii atelier (adresă, program, capacitate)
  - Setare tarife manoperă pe categorie lucrare
  - Configurare posturi de lucru disponibile
  
- [ ] **Ordine de lucru (Work Orders)**
  - Creare ordine de lucru la intrarea vehiculului în service
  - Alocare mecanic responsabil
  - Checklist diagnoză inițială
  - Status: În așteptare / În lucru / Finalizat / Livrat client
  - Timp estimat vs. timp real
  - Fotografii înainte/după
  
- [ ] **Tracking vehicule în atelier**
  - Vizualizare vehicule aflate în service
  - Status fiecare vehicul în timp real
  - Prioritate lucrări (urgentă, normală, când e timp)
  - Queue management - coadă vehicule în așteptare
  - Notificare client când vehicul este gata
  
- [ ] **Gestiune piese consumate**
  - Asociere piese utilizate cu ordinul de lucru
  - Tracking consum piese din stoc
  - Cost piese per intervenție
  - Link cu modul de inventar (dacă există)
  - Cerere piese care lipsesc
  
- [ ] **Timpi de lucru și manoperă**
  - Înregistrare timp început/sfârșit lucrare
  - Timp efectiv lucrat de fiecare mecanic
  - Calcul cost manoperă pe bază de tarif/oră
  - Raport productivitate mecanic
  - Ore lucrate vs. ore facturate
  
- [ ] **Mecanici și personal atelier**
  - Registru mecanici (nume, specializare, tarif oră)
  - Asociere mecanic cu ordinele de lucru
  - Istoric lucrări per mecanic
  - Performanță și statistici
  - Disponibilitate și planning

### 2.6 Costuri și Facturi
- [ ] **Înregistrare costuri**
  - Cost per intervenție
  - Defalcare: piese, manoperă, alte taxe
  - Valuță și curs de schimb (opțional)
  
- [ ] **Gestionare facturi**
  - Upload facturi PDF
  - Vizualizare facturi în aplicație
  - Link facturi cu intervențiile
  
- [ ] **Rapoarte și export**
  - Raport costuri per vehicul
  - Raport costuri per perioadă (lună, trimestru, an)
  - Raport costuri per tip intervenție
  - Raport costuri per service partener
  - Top vehicule cu cele mai mari costuri de mentenanță
  - Export Excel/PDF

---

## 3. Structură Bază de Date

### 3.1 Tabel: `services`
```sql
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    service_type ENUM('internal', 'external') DEFAULT 'external' COMMENT 'Service intern sau partener extern',
    address TEXT,
    contact_phone VARCHAR(50),
    contact_email VARCHAR(100),
    contact_person VARCHAR(100),
    service_types TEXT COMMENT 'JSON cu tipuri de lucrări',
    working_hours VARCHAR(255),
    capacity INT DEFAULT NULL COMMENT 'Număr posturi de lucru (doar pentru service intern)',
    hourly_rate DECIMAL(10,2) DEFAULT NULL COMMENT 'Tarif manoperă/oră (doar pentru service intern)',
    rating DECIMAL(3,2) DEFAULT NULL,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_type (service_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Tabel: `service_appointments`
```sql
CREATE TABLE service_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME,
    type VARCHAR(100) NOT NULL COMMENT 'revizie, reparatie, schimb_ulei, etc.',
    description TEXT,
    status ENUM('programat', 'confirmat', 'in_lucru', 'efectuat', 'anulat') DEFAULT 'programat',
    estimated_cost DECIMAL(10,2),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.3 Tabel: `service_history`
```sql
CREATE TABLE service_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_id INT,
    appointment_id INT COMMENT 'Link cu programarea dacă există',
    service_date DATE NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    description TEXT,
    odometer_reading INT COMMENT 'Kilometraj la momentul service-ului',
    cost_total DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost_parts DECIMAL(10,2) DEFAULT 0,
    cost_labor DECIMAL(10,2) DEFAULT 0,
    cost_other DECIMAL(10,2) DEFAULT 0,
    invoice_number VARCHAR(100),
    invoice_file VARCHAR(255),
    parts_replaced TEXT COMMENT 'JSON cu lista pieselor',
    notes TEXT,
    next_service_km INT COMMENT 'Sugestie pentru următorul service',
    next_service_date DATE COMMENT 'Sugestie pentru următorul service',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (appointment_id) REFERENCES service_appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_date (service_date),
    INDEX idx_type (service_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.4 Tabel: `maintenance_rules`
```sql
CREATE TABLE maintenance_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT,
    rule_name VARCHAR(255) NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    interval_km INT COMMENT 'Interval în kilometri',
    interval_months INT COMMENT 'Interval în luni',
    warning_km INT COMMENT 'Avertizare cu X km înainte',
    warning_days INT COMMENT 'Avertizare cu X zile înainte',
    last_service_date DATE,
    last_service_km INT,
    next_due_date DATE,
    next_due_km INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_due_date (next_due_date),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.5 Tabel: `work_orders` (Ordine de Lucru - Service Intern)
```sql
CREATE TABLE work_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_id INT NOT NULL COMMENT 'Service-ul intern',
    appointment_id INT COMMENT 'Link cu programarea inițială',
    work_order_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Număr unic ordine de lucru',
    entry_date DATETIME NOT NULL COMMENT 'Data intrării în service',
    estimated_completion DATETIME COMMENT 'Data estimată finalizare',
    actual_completion DATETIME COMMENT 'Data reală finalizare',
    odometer_reading INT COMMENT 'Kilometraj la intrare',
    assigned_mechanic_id INT COMMENT 'Mecanic alocat',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'in_progress', 'waiting_parts', 'completed', 'delivered') DEFAULT 'pending',
    diagnosis TEXT COMMENT 'Diagnoză inițială',
    work_description TEXT COMMENT 'Descriere lucrări de efectuat',
    customer_notes TEXT COMMENT 'Observații client',
    internal_notes TEXT COMMENT 'Note interne atelier',
    estimated_hours DECIMAL(5,2) COMMENT 'Ore estimate',
    actual_hours DECIMAL(5,2) COMMENT 'Ore efectiv lucrate',
    labor_cost DECIMAL(10,2) DEFAULT 0,
    parts_cost DECIMAL(10,2) DEFAULT 0,
    total_cost DECIMAL(10,2) DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES service_appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_mechanic_id) REFERENCES service_mechanics(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_status (status),
    INDEX idx_mechanic (assigned_mechanic_id),
    INDEX idx_entry_date (entry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.6 Tabel: `service_mechanics` (Personal Atelier)
```sql
CREATE TABLE service_mechanics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    service_id INT NOT NULL COMMENT 'Service-ul intern',
    user_id INT COMMENT 'Link cu user din sistem (opțional)',
    name VARCHAR(255) NOT NULL,
    specialization VARCHAR(255) COMMENT 'Motor, caroserie, electric, etc.',
    hourly_rate DECIMAL(10,2) NOT NULL COMMENT 'Tarif per oră',
    phone VARCHAR(50),
    email VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    hire_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_service (service_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.7 Tabel: `work_order_parts` (Piese Utilizate)
```sql
CREATE TABLE work_order_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    part_name VARCHAR(255) NOT NULL,
    part_number VARCHAR(100) COMMENT 'Cod/număr piesă',
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    supplier VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    INDEX idx_work_order (work_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.8 Tabel: `work_order_labor` (Manoperă Detaliată)
```sql
CREATE TABLE work_order_labor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    mechanic_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME,
    hours_worked DECIMAL(5,2),
    hourly_rate DECIMAL(10,2) NOT NULL,
    labor_cost DECIMAL(10,2),
    task_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (mechanic_id) REFERENCES service_mechanics(id) ON DELETE CASCADE,
    INDEX idx_work_order (work_order_id),
    INDEX idx_mechanic (mechanic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.9 Tabel: `work_order_checklist` (Checklist Diagnoză)
```sql
CREATE TABLE work_order_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    item VARCHAR(255) NOT NULL,
    is_checked TINYINT(1) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    INDEX idx_work_order (work_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.10 Tabel: `service_notifications`
```sql
CREATE TABLE service_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    maintenance_rule_id INT,
    notification_type ENUM('upcoming', 'overdue', 'reminder') NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (maintenance_rule_id) REFERENCES maintenance_rules(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. Structură Module

```
modules/service/
├── controllers/
│   ├── ServiceController.php         # CRUD service-uri partenere
│   ├── AppointmentController.php     # Gestionare programări
│   ├── HistoryController.php         # Istoric intervenții
│   ├── MaintenanceController.php     # Reguli mentenanță
│   ├── WorkOrderController.php       # Ordine de lucru (service intern)
│   ├── MechanicController.php        # Gestionare mecanici
│   └── ReportsController.php         # Rapoarte și statistici
├── models/
│   ├── Service.php                   # Model service
│   ├── ServiceAppointment.php        # Model programări
│   ├── ServiceHistory.php            # Model istoric
│   ├── MaintenanceRule.php           # Model reguli
│   ├── WorkOrder.php                 # Model ordine de lucru
│   ├── WorkOrderPart.php             # Model piese utilizate
│   ├── WorkOrderLabor.php            # Model manoperă
│   ├── ServiceMechanic.php           # Model mecanici
│   └── ServiceNotification.php       # Model notificări
├── views/
│   ├── services/
│   │   ├── index.php                 # Listă service-uri
│   │   ├── add.php                   # Adăugare service
│   │   ├── edit.php                  # Editare service
│   │   └── internal_setup.php        # Configurare service intern
│   ├── appointments/
│   │   ├── index.php                 # Calendar programări
│   │   ├── add.php                   # Programare nouă
│   │   └── view.php                  # Detalii programare
│   ├── workshop/                     # Secțiune atelier (service intern)
│   │   ├── dashboard.php             # Dashboard atelier
│   │   ├── work_orders.php           # Listă ordine de lucru
│   │   ├── work_order_view.php       # Detalii ordine de lucru
│   │   ├── work_order_add.php        # Creare ordine nouă
│   │   ├── work_order_edit.php       # Editare ordine
│   │   ├── vehicles_in_service.php   # Vehicule în atelier
│   │   ├── mechanics.php             # Gestionare mecanici
│   │   └── mechanic_performance.php  # Statistici mecanic
│   ├── history/
│   │   ├── index.php                 # Listă intervenții
│   │   ├── add.php                   # Adăugare intervenție
│   │   ├── view.php                  # Detalii intervenție
│   │   └── vehicle_history.php       # Istoric per vehicul
│   ├── maintenance/
│   │   ├── index.php                 # Listă reguli
│   │   ├── add.php                   # Adăugare regulă
│   │   ├── edit.php                  # Editare regulă
│   │   └── dashboard.php             # Dashboard scadențe
│   └── reports/
│       ├── costs.php                 # Raport costuri
│       ├── by_vehicle.php            # Raport per vehicul
│       ├── by_service.php            # Raport per service
│       ├── workshop_efficiency.php   # Eficiență atelier
│       └── mechanic_productivity.php # Productivitate mecanici
├── api/
│   ├── service_api.php               # API pentru AJAX
│   └── workshop_api.php              # API pentru atelier
└── README.md                         # Documentație modul
```

---

## 5. Interfață Utilizatori

### 5.1 Admin Firmă (Tenant Admin)
**Permisiuni:**
- Vizualizare toate programările companiei
- Adăugare/editare/anulare programări
- Vizualizare istoric intervenții
- Adăugare intervenții efectuate
- Gestionare service-uri partenere
- **Configurare și gestionare service intern**
- **Creare și gestionare ordine de lucru**
- **Gestionare mecanici și personal atelier**
- **Tracking vehicule în atelier în timp real**
- Configurare reguli mentenanță
- Primire notificări automate
- Generare rapoarte costuri
- Export date

**Dashboard Service:**
- Programări următoare 7 zile
- **Vehicule în atelier acum (service intern)**
- **Status ordine de lucru active**
- Service-uri scadente/depășite
- Top 5 vehicule cu cele mai mari costuri
- Grafic evoluție costuri ultimele 6 luni
- **Eficiență atelier (dacă service intern)**
- Alerte prioritare

### 5.2 Superadmin (Platform Admin)
**Permisiuni suplimentare:**
- Vizualizare toate service-urile din platformă
- Statistici cross-tenant
- Setare reguli globale de mentenanță
- Export rapoarte agregate
- Monitorizare utilizare modul
- Configurare notificări globale

**Dashboard Superadmin:**
- Număr total programări în platformă
- Top companii după număr service-uri
- Statistici adopție modul
- Rapoarte financiare agregate

### 5.3 Mecanic (Workshop User) - NOU
**Permisiuni:**
- Vizualizare ordine de lucru alocate
- Actualizare status ordine de lucru
- Înregistrare timp început/sfârșit lucrare
- Adăugare piese consumate
- Completare checklist diagnoză
- Adăugare note tehnice
- Marcare ordine ca finalizată
- Vizualizare istoric lucrări personale

**Dashboard Mecanic:**
- Ordinele mele active
- Ordine în așteptare de alocare
- Timpul lucrat astăzi
- Statistici personale

### 5.4 User Normal (Driver/Fleet User)
**Permisiuni:**
- Vizualizare istoric service pentru vehiculele atribuite
- Raportare probleme tehnice
- Vizualizare programări viitoare pentru vehiculele lor
- **Primire notificare când vehicul este gata (dacă e în service intern)**

---

## 6. Automatizări și Notificări

### 6.1 Script Zilnic: `scripts/cron_service_notifications.php`
**Funcții:**
- Verificare mentenanțe scadente în următoarele X zile
- Verificare mentenanțe depășite
- Generare notificări pentru admini
- Trimitere email-uri de reminder
- Actualizare status reguli mentenanță
- Log activitate

**Frecvență:** Rulare zilnică (ex: 08:00 AM)

**Logică:**
```php
// Pseudocod
1. Selectare toate regulile active de mentenanță
2. Pentru fiecare regulă:
   a. Calcul zile/km rămase până la scadență
   b. Dacă < prag avertizare → generare notificare
   c. Dacă depășit → notificare urgentă
3. Trimitere notificări către admini
4. Sugerare programări automate
5. Log rezultate
```

### 6.2 Tipuri de Notificări
1. **Upcoming Service** (Cu X zile înainte)
   - "Vehiculul [NUMĂR] necesită revizie în 7 zile"
   
2. **Overdue Service** (Scadență depășită)
   - "URGENT: Vehiculul [NUMĂR] a depășit scadența reviziei cu 3 zile"
   
3. **Appointment Reminder** (Reminder programare)
   - "Reminder: Programare service pentru [VEHICUL] mâine la ora 10:00"
   
4. **Cost Alert** (Depășire buget)
   - "Atenție: Costurile de service pentru [VEHICUL] au depășit bugetul lunar"

5. **Work Order Status** (Status ordine de lucru - service intern)
   - "Ordinul de lucru #[NR] pentru [VEHICUL] a fost finalizat"
   - "Vehiculul [NR] este gata de ridicat din atelier"

6. **Parts Needed** (Piese necesare - service intern)
   - "Ordinul #[NR] este în așteptare - piese necesare: [LISTA]"

### 6.3 Canale de Notificare
- [ ] Notificări in-app (dasboard)
- [ ] Email
- [ ] SMS (opțional, extensie viitoare)
- [ ] Push notifications (extensie viitoare)

---

## 7. Rapoarte și Statistici

### 7.1 Rapoarte Disponibile
1. **Raport Costuri per Vehicul**
   - Costuri totale per vehicul
   - Defalcare pe categorii (piese, manoperă)
   - Comparație cu media flotei
   - Trend evoluție

2. **Raport Costuri per Perioadă**
   - Lunar, trimestrial, anual
   - Comparație cu perioadele anterioare
   - Grafice evoluție

3. **Raport per Service Partener**
   - Total cheltuieli per service
   - Număr intervenții
   - Cost mediu per intervenție
   - Rating colaborare

4. **Raport per Tip Intervenție**
   - Distribuție costuri pe categorii
   - Frecvență intervenții
   - Cost mediu per tip

5. **Top Vehicule Costuri Mari**
   - Identificare vehicule problematice
   - Analiza cost/beneficiu
   - Sugestii înlocuire

6. **Raport Eficiență Atelier** (service intern)
   - Număr ordine de lucru finalizate
   - Timp mediu de execuție
   - Utilizare capacitate atelier
   - Vehicule în lucru vs. capacitate

7. **Raport Productivitate Mecanici** (service intern)
   - Ore lucrate per mecanic
   - Număr ordine finalizate
   - Timp mediu per ordine
   - Venit generat per mecanic
   - Comparație între mecanici

### 7.2 Export Formate
- [ ] PDF (cu logo companie)
- [ ] Excel (.xlsx)
- [ ] CSV

---

## 8. Integrări

### 8.1 Integrări Interne (în aplicație)
- [ ] **Modul Vehicles**
  - Import date vehicule pentru programări
  - Actualizare kilometraj
  - Link istoric service cu vehiculul
  
- [ ] **Modul Fuel**
  - Preluare kilometraj curent din înregistrări combustibil
  - Calcul automat km parcurși
  
- [ ] **Modul Notifications**
  - Utilizare sistem centralizat de notificări
  - Preferințe utilizator pentru tipuri notificări
  
- [ ] **Modul Reports**
  - Includere costuri service în rapoarte financiare generale

- [ ] **Modul Inventar** (dacă există)
  - Sincronizare piese consumate în atelier
  - Tracking stoc piese de schimb
  - Alertă stoc minim

- [ ] **Modul HR** (dacă există)
  - Link mecanici cu angajați
  - Ore lucrate pentru pontaj
  - Cost manoperă pentru contabilitate

### 8.2 Integrări Externe (extensii viitoare)
- [ ] **API Service-uri externe**
  - Bosch Car Service
  - Autonet Service
  - Programare online automată
  
- [ ] **Sisteme contabilitate**
  - Export facturi către software-uri contabile
  - Integrare Smart Bill / Oblio
  
- [ ] **GPS/Telematics**
  - Import kilometraj real-time
  - Monitorizare stil conducere → predicție necesități service

---

## 9. Securitate și Permisiuni

### 9.1 Nivel Acces Date
- **Tenant Isolation:** Fiecare tenant vede doar propriile date
- **Role-Based Access:** Permisiuni diferite pentru admin/user/superadmin
- **Audit Trail:** Log toate acțiunile importante

### 9.2 Validări
- [ ] Validare date input (SQL injection, XSS)
- [ ] Verificare permisiuni la fiecare acțiune
- [ ] Validare fișiere upload (tip, dimensiune)
- [ ] Sanitizare date export

### 9.3 Upload Fișiere
- Locație: `uploads/service/invoices/`
- Tipuri permise: PDF, JPG, PNG
- Dimensiune maximă: 5MB
- Nume fișier: `{tenant_id}_{vehicle_id}_{timestamp}_{original_name}`

---

## 10. Plan de Implementare

### Faza 1: Core Functionality (Sprint 1-2)
**Prioritate: ÎNALTĂ**
- [x] Planificare și documentare
- [ ] Creare tabele bază de date
- [ ] Model și Controller pentru Service-uri
- [ ] CRUD service-uri partenere
- [ ] Interfață adăugare/listare service-uri
- [ ] Teste funcționalitate de bază

**Estimare: 2 săptămâni**

### Faza 2: Programări (Sprint 3-4)
**Prioritate: ÎNALTĂ**
- [ ] Model și Controller programări
- [ ] Interfață calendar programări
- [ ] Formular adăugare programare
- [ ] Editare/anulare programări
- [ ] Marcare programare ca efectuată
- [ ] Filtre și căutare

**Estimare: 2 săptămâni**

### Faza 3: Istoric Intervenții (Sprint 5-6)
**Prioritate: ÎNALTĂ**
- [ ] Model și Controller istoric
- [ ] Interfață vizualizare istoric
- [ ] Formular adăugare intervenție
- [ ] Upload și gestionare facturi
- [ ] Vizualizare istoric per vehicul
- [ ] Link cu programările

**Estimare: 2 săptămâni**

### Faza 4: Mentenanță Periodică (Sprint 7-8)
**Prioritate: MEDIE-ÎNALTĂ**
- [ ] Model și Controller reguli mentenanță
- [ ] Interfață configurare reguli
- [ ] Algoritm calcul scadențe
- [ ] Dashboard scadențe
- [ ] Integrare cu modul vehicule pentru kilometraj
- [ ] Teste calcule

**Estimare: 2 săptămâni**

### Faza 5: Automatizări și Notificări (Sprint 9-10)
**Prioritate: MEDIE-ÎNALTĂ**
- [ ] Script cron notificări service
- [ ] Generare notificări automate
- [ ] Integrare cu sistem notificări existent
- [ ] Email templates pentru notificări service
- [ ] Configurare preferințe notificări
- [ ] Teste automatizări

**Estimare: 2 săptămâni**

### Faza 6: Service Intern - Ordine de Lucru (Sprint 11-13)
**Prioritate: MEDIE-ÎNALTĂ**
- [ ] Tabele bază de date pentru service intern
- [ ] Model și Controller ordine de lucru
- [ ] Interfață creare ordine de lucru
- [ ] Tracking vehicule în atelier
- [ ] Status și workflow ordine
- [ ] Dashboard atelier
- [ ] Gestionare mecanici
- [ ] Înregistrare piese și manoperă
- [ ] Calcul costuri automat
- [ ] Notificări status ordine

**Estimare: 3 săptămâni**

### Faza 7: Costuri și Rapoarte (Sprint 14-15)
**Prioritate: MEDIE**
- [ ] Modul rapoarte costuri
- [ ] Rapoarte eficiență atelier
- [ ] Rapoarte productivitate mecanici
- [ ] Grafice și statistici
- [ ] Export PDF/Excel
- [ ] Dashboard financiar service
- [ ] Comparații și analize
- [ ] Optimizare performanță

**Estimare: 2 săptămâni**

### Faza 8: Superadmin Features (Sprint 16)
**Prioritate: SCĂZUTĂ**
- [ ] Dashboard superadmin
- [ ] Statistici cross-tenant
- [ ] Rapoarte agregate
- [ ] Configurări globale

**Estimare: 1 săptămână**

### Faza 9: Polish și Documentare (Sprint 17)
**Prioritate: MEDIE**
- [ ] Documentație utilizare (inclusiv service intern)
- [ ] User guide pentru admini
- [ ] User guide pentru mecanici
- [ ] Video tutorial
- [ ] Bug fixes și îmbunătățiri UX
- [ ] Teste de acceptare

**Estimare: 1 săptămână**

**TOTAL ESTIMAT: 17 săptămâni (aproximativ 4.25 luni)**

---

## 11. Considerații Tehnice

### 11.1 Performanță
- Indexare corespunzătoare în baza de date
- Pagination pentru liste cu multe înregistrări
- Lazy loading pentru istoric intervenții
- Cache pentru rapoarte complexe
- Optimizare query-uri

### 11.2 UX/UI
- Design responsive (mobile-friendly)
- Calendar interactiv pentru programări
- Drag & drop pentru upload facturi
- Preview facturi în modal
- Filtre avansate cu AJAX
- Grafice interactive (Chart.js sau similar)
- Toast notifications pentru acțiuni

### 11.3 Dependențe
- jQuery (deja în aplicație)
- DataTables pentru liste (deja în aplicație)
- FullCalendar.js pentru calendar programări (NOU)
- Chart.js pentru grafice (NOU)
- PDFjs pentru preview facturi (OPȚIONAL)
- Kanban board library pentru tracking ordine de lucru (OPȚIONAL)
- Real-time updates (WebSocket/Server-Sent Events) pentru dashboard atelier (OPȚIONAL)

---

## 12. Extensii Viitoare

### V2.0 - Funcționalități Avansate
- [ ] **Integrare API service-uri externe**
  - Programare automată online
  - Verificare disponibilitate în timp real
  - Sincronizare status lucrări
  
- [ ] **Estimare costuri automată**
  - Bază de date prețuri indicative
  - Calcul estimativ pe baza tipului intervenției
  - Comparații prețuri între service-uri
  
- [ ] **Modul aprobare internă**
  - Workflow aprobare pentru cheltuieli mari
  - Configurare limite bugetare
  - Notificări către manageri pentru aprobare
  - Istoric aprobări/respingeri
  
- [ ] **Predicții și AI**
  - Predicție necesități service pe baza istoricului
  - Identificare pattern-uri probleme recurente
  - Sugestii optimizare costuri
  
- [ ] **Mobile App**
  - Aplicație mobilă pentru șoferi
  - **Aplicație mobilă pentru mecanici**
  - **Scanare coduri bare piese**
  - **Actualizare status ordine din telefon**
  - Raportare probleme din teren
  - Notificări push
  - Scanare QR pentru check-in la service

- [ ] **Service Intern - Advanced**
  - **Gestiune completă stoc piese**
  - **Comenzi automate piese la furnizori**
  - **Integrare cu furnizori piese auto**
  - **Scanner VIN pentru identificare automată vehicul**
  - **Portal client pentru tracking ordine online**
  - **Semnătură digitală la predare vehicul**
  - **Fotografii și video înainte/după reparații**
  - **Estimare costuri AI pe baza diagnostic**

### V2.1 - Integrări Avansate
- [ ] Integrare GPS/Telematics pentru kilometraj real-time
- [ ] Integrare Smart Bill / Oblio pentru facturare
- [ ] API public pentru integrări terțe
- [ ] Webhook-uri pentru evenimente importante

### V2.2 - Business Intelligence
- [ ] Dashboard executiv cu KPI-uri
- [ ] Predictive maintenance cu ML
- [ ] Analiză TCO (Total Cost of Ownership)
- [ ] Benchmarking între flote similare

---

## 13. Metrici de Succes

### KPI-uri Implementare
- [ ] Timp mediu adăugare programare < 2 minute
- [ ] Timp încărcare dashboard < 2 secunde
- [ ] Rate erori < 1%
- [ ] Adopție utilizatori > 80% în primele 2 luni

### KPI-uri Business
- [ ] Reducere costuri mentenanță cu 10-15% (prin planificare mai bună)
- [ ] Creștere disponibilitate flotă cu 5% (mai puține breakdowns)
- [ ] Reducere timp administrativ cu 30%
- [ ] Satisfacție utilizatori > 4.5/5

---

## 14. Riscos și Mitigări

| Risc | Probabilitate | Impact | Mitigare |
|------|---------------|---------|----------|
| Complexitate calcule mentenanță | Medie | Înalt | Testare extensivă, algoritm simplu inițial |
| Integrare cu module existente | Scăzută | Mediu | Arhitectură modulară, interfețe clare |
| Performanță rapoarte complexe | Medie | Mediu | Indexare, cache, optimizare query-uri |
| Adopție scăzută utilizatori | Medie | Înalt | Tutorial, documentație, suport activ |
| Probleme sincronizare date | Scăzută | Înalt | Transactions, validări, backup regulat |

---

## 15. Note și Decizii

### Decizii Arhitecturale
1. **Multi-tenancy:** Fiecare tabel include `tenant_id` pentru izolare date
2. **Soft delete:** Service-uri și programări nu se șterg fizic, ci se marchează ca inactive
3. **Audit trail:** Toate tabelele au `created_at`, `updated_at` și `created_by`
4. **Flexibilitate:** Câmpuri JSON pentru date care pot varia (ex: tipuri service, piese)

### Limitări Cunoscute
- Inițial nu va avea integrare cu service-uri externe
- Estimare costuri va fi manuală în prima versiune
- Fără modul de aprobare în V1.0
- Kilometraj va fi introdus manual dacă nu există în modulul fuel
- Service intern fără gestiune completă stoc în V1.0 (doar tracking consum)
- Fără portal client pentru tracking ordine în V1.0
- Fără aplicație mobilă pentru mecanici în V1.0

### Întrebări Deschise
- [ ] Ce nivel de granularitate dorim pentru tipurile de intervenții?
- [ ] Trebuie să suportăm multiple monede pentru costuri?
- [ ] Avem nevoie de istoric versiuni pentru regulile de mentenanță?
- [ ] Dorim notificări SMS din prima versiune?

---

## 16. Contact și Responsabilități

### Product Owner
- **Responsabil:** [DE COMPLETAT]
- **Rol:** Definire cerințe, prioritizare, acceptanță

### Tech Lead
- **Responsabil:** [DE COMPLETAT]
- **Rol:** Arhitectură, code review, decizii tehnice

### Developers
- **Backend:** [DE COMPLETAT]
- **Frontend:** [DE COMPLETAT]
- **QA:** [DE COMPLETAT]

---

## 17. Istoric Versiuni Document

| Versiune | Data | Autor | Modificări |
|----------|------|-------|------------|
| 1.0 | 21 Nov 2025 | GitHub Copilot | Creare inițială document plan |

---

## 18. Link-uri Utile

- [Documentație aplicație Fleet Management](../docs/)
- [Ghid arhitectură multi-tenancy](../docs/DEV_GUIDE_TENANCY_ROUTING.md)
- [Ghid notificări](../docs/NOTIFICATION_ARCHITECTURE.md)
- [Repository GitHub](https://github.com/nnoldi-hub/fleetly)

---

**Notă:** Acest document este un plan dinamic și va fi actualizat pe măsură ce implementarea progresează. Orice modificare majoră trebuie documentată în secțiunea "Istoric Versiuni Document".
