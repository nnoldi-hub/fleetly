-- ============================================================================
-- MODUL SERVICE AUTO - SCHEMA BAZA DE DATE
-- Versiune: 1.0
-- Data: 21 Noiembrie 2025
-- ============================================================================

-- Tabel 1: Services (Service-uri Partenere și Interne)
-- ============================================================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    service_type ENUM('internal', 'external') DEFAULT 'external' COMMENT 'Service intern sau partener extern',
    address TEXT,
    contact_phone VARCHAR(50),
    contact_email VARCHAR(100),
    contact_person VARCHAR(100),
    service_types TEXT COMMENT 'JSON cu tipuri de lucrări (ex: ["revizie", "reparatie", "schimb_ulei"])',
    working_hours VARCHAR(255) COMMENT 'Program de lucru (ex: "L-V: 08:00-17:00")',
    capacity INT DEFAULT NULL COMMENT 'Număr posturi de lucru (doar pentru service intern)',
    hourly_rate DECIMAL(10,2) DEFAULT NULL COMMENT 'Tarif manoperă/oră (doar pentru service intern)',
    rating DECIMAL(3,2) DEFAULT NULL COMMENT 'Rating 1-5',
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_type (service_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 2: Service Appointments (Programări Service)
-- ============================================================================
CREATE TABLE IF NOT EXISTS service_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME,
    type VARCHAR(100) NOT NULL COMMENT 'Tip intervenție: revizie, reparatie, schimb_ulei, etc.',
    description TEXT COMMENT 'Descriere lucrări',
    status ENUM('programat', 'confirmat', 'in_lucru', 'efectuat', 'anulat') DEFAULT 'programat',
    estimated_cost DECIMAL(10,2) COMMENT 'Cost estimat',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_service (service_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 3: Service History (Istoric Intervenții)
-- ============================================================================
CREATE TABLE IF NOT EXISTS service_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_id INT COMMENT 'Service unde s-a efectuat',
    appointment_id INT COMMENT 'Link cu programarea dacă există',
    service_date DATE NOT NULL,
    service_type VARCHAR(100) NOT NULL COMMENT 'Tip intervenție',
    description TEXT,
    odometer_reading INT COMMENT 'Kilometraj la momentul service-ului',
    cost_total DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost_parts DECIMAL(10,2) DEFAULT 0 COMMENT 'Cost piese',
    cost_labor DECIMAL(10,2) DEFAULT 0 COMMENT 'Cost manoperă',
    cost_other DECIMAL(10,2) DEFAULT 0 COMMENT 'Alte costuri',
    invoice_number VARCHAR(100),
    invoice_file VARCHAR(255) COMMENT 'Path către fișier factură',
    parts_replaced TEXT COMMENT 'JSON cu lista pieselor schimbate',
    notes TEXT,
    next_service_km INT COMMENT 'Sugestie pentru următorul service (km)',
    next_service_date DATE COMMENT 'Sugestie pentru următorul service (dată)',
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
    INDEX idx_service (service_id),
    INDEX idx_date (service_date),
    INDEX idx_type (service_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 4: Maintenance Rules (Reguli Mentenanță Periodică)
-- ============================================================================
CREATE TABLE IF NOT EXISTS maintenance_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT COMMENT 'NULL = regulă globală pentru toate vehiculele',
    rule_name VARCHAR(255) NOT NULL COMMENT 'Ex: "Revizie tehnică", "Schimb ulei"',
    service_type VARCHAR(100) NOT NULL,
    interval_km INT COMMENT 'Interval în kilometri (ex: 15000)',
    interval_months INT COMMENT 'Interval în luni (ex: 12)',
    warning_km INT DEFAULT 500 COMMENT 'Avertizare cu X km înainte',
    warning_days INT DEFAULT 7 COMMENT 'Avertizare cu X zile înainte',
    last_service_date DATE COMMENT 'Data ultimului service efectuat',
    last_service_km INT COMMENT 'Kilometrajul ultimului service',
    next_due_date DATE COMMENT 'Data calculată pentru următorul service',
    next_due_km INT COMMENT 'Kilometrajul calculat pentru următorul service',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_due_date (next_due_date),
    INDEX idx_due_km (next_due_km),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABELE PENTRU SERVICE INTERN (ATELIER PROPRIU)
-- ============================================================================

-- Tabel 5: Service Mechanics (Personal Atelier)
-- ============================================================================
CREATE TABLE IF NOT EXISTS service_mechanics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    service_id INT NOT NULL COMMENT 'Service-ul intern unde lucrează',
    user_id INT COMMENT 'Link cu user din sistem (opțional)',
    name VARCHAR(255) NOT NULL,
    specialization VARCHAR(255) COMMENT 'Ex: Motor, Caroserie, Electric, etc.',
    hourly_rate DECIMAL(10,2) NOT NULL COMMENT 'Tarif per oră',
    phone VARCHAR(50),
    email VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    hire_date DATE COMMENT 'Data angajării',
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

-- Tabel 6: Work Orders (Ordine de Lucru - Service Intern)
-- ============================================================================
CREATE TABLE IF NOT EXISTS work_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_id INT NOT NULL COMMENT 'Service-ul intern',
    appointment_id INT COMMENT 'Link cu programarea inițială',
    work_order_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Număr unic ordine (ex: WO-2025-001)',
    entry_date DATETIME NOT NULL COMMENT 'Data intrării în service',
    estimated_completion DATETIME COMMENT 'Data estimată finalizare',
    actual_completion DATETIME COMMENT 'Data reală finalizare',
    odometer_reading INT COMMENT 'Kilometraj la intrare',
    assigned_mechanic_id INT COMMENT 'Mecanic alocat principal',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'in_progress', 'waiting_parts', 'completed', 'delivered') DEFAULT 'pending',
    diagnosis TEXT COMMENT 'Diagnoză inițială',
    work_description TEXT COMMENT 'Descriere lucrări de efectuat',
    customer_notes TEXT COMMENT 'Observații client/șofer',
    internal_notes TEXT COMMENT 'Note interne atelier',
    estimated_hours DECIMAL(5,2) COMMENT 'Ore estimate',
    actual_hours DECIMAL(5,2) COMMENT 'Ore efectiv lucrate',
    labor_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'Cost total manoperă',
    parts_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'Cost total piese',
    total_cost DECIMAL(10,2) DEFAULT 0 COMMENT 'Cost total (manoperă + piese)',
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
    INDEX idx_service (service_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_mechanic (assigned_mechanic_id),
    INDEX idx_entry_date (entry_date),
    INDEX idx_work_order_number (work_order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 7: Work Order Parts (Piese Utilizate în Ordine de Lucru)
-- ============================================================================
CREATE TABLE IF NOT EXISTS work_order_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    part_name VARCHAR(255) NOT NULL,
    part_number VARCHAR(100) COMMENT 'Cod/număr piesă',
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL COMMENT 'quantity * unit_price',
    supplier VARCHAR(255) COMMENT 'Furnizor piese',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    INDEX idx_work_order (work_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 8: Work Order Labor (Manoperă Detaliată per Task)
-- ============================================================================
CREATE TABLE IF NOT EXISTS work_order_labor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    mechanic_id INT NOT NULL,
    start_time DATETIME NOT NULL COMMENT 'Începere lucru pe task',
    end_time DATETIME COMMENT 'Finalizare lucru pe task',
    hours_worked DECIMAL(5,2) COMMENT 'Ore lucrate calculate automat',
    hourly_rate DECIMAL(10,2) NOT NULL COMMENT 'Tarif/oră mecanic',
    labor_cost DECIMAL(10,2) COMMENT 'Cost manoperă = hours_worked * hourly_rate',
    task_description TEXT COMMENT 'Descriere task specific',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (mechanic_id) REFERENCES service_mechanics(id) ON DELETE CASCADE,
    INDEX idx_work_order (work_order_id),
    INDEX idx_mechanic (mechanic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 9: Work Order Checklist (Checklist Diagnoză/Verificări)
-- ============================================================================
CREATE TABLE IF NOT EXISTS work_order_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    item VARCHAR(255) NOT NULL COMMENT 'Element de verificat',
    is_checked TINYINT(1) DEFAULT 0,
    status ENUM('ok', 'attention', 'critical') DEFAULT 'ok' COMMENT 'Status verificare',
    notes TEXT COMMENT 'Observații pentru acest item',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    INDEX idx_work_order (work_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 10: Service Notifications (Notificări Service)
-- ============================================================================
CREATE TABLE IF NOT EXISTS service_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    maintenance_rule_id INT COMMENT 'Regulă care a generat notificarea',
    work_order_id INT COMMENT 'Ordine de lucru asociată',
    notification_type ENUM('upcoming', 'overdue', 'reminder', 'work_order_status', 'parts_needed') NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (maintenance_rule_id) REFERENCES maintenance_rules(id) ON DELETE CASCADE,
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_read (is_read),
    INDEX idx_type (notification_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DATE INIȚIALE / EXEMPLE
-- ============================================================================

-- Inserare date exemplu pentru testare (Opțional - comentat by default)
/*
-- Exemplu service intern pentru tenant_id = 1
INSERT INTO services (tenant_id, name, service_type, address, contact_phone, capacity, hourly_rate, is_active) 
VALUES (1, 'Atelier Intern FlotaSRL', 'internal', 'Str. Industriei nr. 10, București', '0722123456', 4, 150.00, 1);

-- Exemplu service extern
INSERT INTO services (tenant_id, name, service_type, address, contact_phone, contact_email, service_types, is_active) 
VALUES (1, 'Bosch Car Service - București Nord', 'external', 'Șos. București-Ploiești km 5', '0213456789', 'contact@boschservice.ro', 
'["revizie", "reparatie", "diagnoza", "climatizare"]', 1);

-- Exemplu mecanici pentru atelier intern (service_id = 1)
INSERT INTO service_mechanics (tenant_id, service_id, name, specialization, hourly_rate, phone, hire_date, is_active) 
VALUES 
(1, 1, 'Ion Popescu', 'Mecanic Motor', 180.00, '0722111222', '2020-01-15', 1),
(1, 1, 'Maria Ionescu', 'Electrician Auto', 170.00, '0722222333', '2021-03-20', 1),
(1, 1, 'Vasile Dumitrescu', 'Mecanic Caroserie', 160.00, '0722333444', '2019-06-10', 1);
*/

-- ============================================================================
-- TRIGGERS PENTRU CALCULE AUTOMATE
-- ============================================================================

-- Trigger 1: Calcul automat total_price în work_order_parts
DELIMITER //
CREATE TRIGGER IF NOT EXISTS before_insert_work_order_parts
BEFORE INSERT ON work_order_parts
FOR EACH ROW
BEGIN
    SET NEW.total_price = NEW.quantity * NEW.unit_price;
END;//
DELIMITER ;

DELIMITER //
CREATE TRIGGER IF NOT EXISTS before_update_work_order_parts
BEFORE UPDATE ON work_order_parts
FOR EACH ROW
BEGIN
    SET NEW.total_price = NEW.quantity * NEW.unit_price;
END;//
DELIMITER ;

-- Trigger 2: Calcul automat hours_worked și labor_cost în work_order_labor
DELIMITER //
CREATE TRIGGER IF NOT EXISTS before_insert_work_order_labor
BEFORE INSERT ON work_order_labor
FOR EACH ROW
BEGIN
    IF NEW.end_time IS NOT NULL AND NEW.start_time IS NOT NULL THEN
        SET NEW.hours_worked = TIMESTAMPDIFF(MINUTE, NEW.start_time, NEW.end_time) / 60.0;
        SET NEW.labor_cost = NEW.hours_worked * NEW.hourly_rate;
    END IF;
END;//
DELIMITER ;

DELIMITER //
CREATE TRIGGER IF NOT EXISTS before_update_work_order_labor
BEFORE UPDATE ON work_order_labor
FOR EACH ROW
BEGIN
    IF NEW.end_time IS NOT NULL AND NEW.start_time IS NOT NULL THEN
        SET NEW.hours_worked = TIMESTAMPDIFF(MINUTE, NEW.start_time, NEW.end_time) / 60.0;
        SET NEW.labor_cost = NEW.hours_worked * NEW.hourly_rate;
    END IF;
END;//
DELIMITER ;

-- Trigger 3: Actualizare automate costs în work_orders la adăugare/modificare piese
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_insert_work_order_parts_update_costs
AFTER INSERT ON work_order_parts
FOR EACH ROW
BEGIN
    UPDATE work_orders 
    SET parts_cost = (
        SELECT COALESCE(SUM(total_price), 0) 
        FROM work_order_parts 
        WHERE work_order_id = NEW.work_order_id
    ),
    total_cost = labor_cost + parts_cost
    WHERE id = NEW.work_order_id;
END;//
DELIMITER ;

DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_update_work_order_parts_update_costs
AFTER UPDATE ON work_order_parts
FOR EACH ROW
BEGIN
    UPDATE work_orders 
    SET parts_cost = (
        SELECT COALESCE(SUM(total_price), 0) 
        FROM work_order_parts 
        WHERE work_order_id = NEW.work_order_id
    ),
    total_cost = labor_cost + parts_cost
    WHERE id = NEW.work_order_id;
END;//
DELIMITER ;

-- Trigger 4: Actualizare automate costs în work_orders la adăugare/modificare manoperă
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_insert_work_order_labor_update_costs
AFTER INSERT ON work_order_labor
FOR EACH ROW
BEGIN
    UPDATE work_orders 
    SET labor_cost = (
        SELECT COALESCE(SUM(labor_cost), 0) 
        FROM work_order_labor 
        WHERE work_order_id = NEW.work_order_id
    ),
    actual_hours = (
        SELECT COALESCE(SUM(hours_worked), 0) 
        FROM work_order_labor 
        WHERE work_order_id = NEW.work_order_id
    ),
    total_cost = labor_cost + parts_cost
    WHERE id = NEW.work_order_id;
END;//
DELIMITER ;

DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_update_work_order_labor_update_costs
AFTER UPDATE ON work_order_labor
FOR EACH ROW
BEGIN
    UPDATE work_orders 
    SET labor_cost = (
        SELECT COALESCE(SUM(labor_cost), 0) 
        FROM work_order_labor 
        WHERE work_order_id = NEW.work_order_id
    ),
    actual_hours = (
        SELECT COALESCE(SUM(hours_worked), 0) 
        FROM work_order_labor 
        WHERE work_order_id = NEW.work_order_id
    ),
    total_cost = labor_cost + parts_cost
    WHERE id = NEW.work_order_id;
END;//
DELIMITER ;

-- ============================================================================
-- VIEWS PENTRU RAPORTARE
-- ============================================================================

-- View 1: Service-uri scadente per vehicul
CREATE OR REPLACE VIEW v_maintenance_due AS
SELECT 
    mr.id as rule_id,
    mr.tenant_id,
    mr.vehicle_id,
    v.plate_number,
    v.make,
    v.model,
    mr.rule_name,
    mr.service_type,
    mr.next_due_date,
    mr.next_due_km,
    CASE 
        WHEN mr.next_due_date < CURDATE() THEN 'overdue'
        WHEN mr.next_due_date <= DATE_ADD(CURDATE(), INTERVAL mr.warning_days DAY) THEN 'upcoming'
        ELSE 'ok'
    END as status_by_date,
    DATEDIFF(mr.next_due_date, CURDATE()) as days_remaining
FROM maintenance_rules mr
JOIN vehicles v ON mr.vehicle_id = v.id
WHERE mr.is_active = 1;

-- View 2: Ordine de lucru active în atelier
CREATE OR REPLACE VIEW v_active_work_orders AS
SELECT 
    wo.id,
    wo.work_order_number,
    wo.tenant_id,
    v.plate_number,
    v.make,
    v.model,
    wo.status,
    wo.priority,
    wo.entry_date,
    wo.estimated_completion,
    m.name as mechanic_name,
    wo.estimated_hours,
    wo.actual_hours,
    wo.total_cost
FROM work_orders wo
JOIN vehicles v ON wo.vehicle_id = v.id
LEFT JOIN service_mechanics m ON wo.assigned_mechanic_id = m.id
WHERE wo.status IN ('pending', 'in_progress', 'waiting_parts');

-- ============================================================================
-- FIN SCRIPT
-- ============================================================================
-- Pentru a rula acest script:
-- mysql -u [username] -p [database_name] < service_module_schema.sql
-- sau din phpMyAdmin: Import > Alege fișierul > Execute
-- ============================================================================
