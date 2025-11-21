-- ============================================================================
-- SERVICE MODULE SCHEMA - PDO COMPATIBLE VERSION (No DELIMITER commands)
-- For use with run-migration.php script
-- ============================================================================

-- 1. Tabelul services (servicii auto - parteneri și atelier intern)
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    type ENUM('partner', 'internal') DEFAULT 'partner',
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    specializations TEXT,
    is_active TINYINT(1) DEFAULT 1,
    rating DECIMAL(3,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_type (type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabelul service_appointments (programări service)
CREATE TABLE IF NOT EXISTS service_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_id INT,
    appointment_date DATETIME NOT NULL,
    status ENUM('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    service_type VARCHAR(100),
    description TEXT,
    estimated_cost DECIMAL(10,2),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_service (service_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabelul service_history (istoric intervenții)
CREATE TABLE IF NOT EXISTS service_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_id INT,
    service_date DATE NOT NULL,
    odometer INT,
    service_type VARCHAR(100) NOT NULL,
    description TEXT,
    cost DECIMAL(10,2) DEFAULT 0,
    invoice_number VARCHAR(50),
    next_service_date DATE,
    next_service_odometer INT,
    document_path VARCHAR(255),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_service (service_id),
    INDEX idx_date (service_date),
    INDEX idx_next_service (next_service_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabelul maintenance_rules (reguli întreținere preventivă)
CREATE TABLE IF NOT EXISTS maintenance_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_type_id INT,
    service_type VARCHAR(100) NOT NULL,
    interval_months INT,
    interval_km INT,
    description TEXT,
    estimated_cost DECIMAL(10,2),
    is_active TINYINT(1) DEFAULT 1,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle_type (vehicle_type_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabelul work_orders (ordine de lucru - atelier intern)
CREATE TABLE IF NOT EXISTS work_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    work_order_number VARCHAR(50) UNIQUE NOT NULL,
    vehicle_id INT NOT NULL,
    service_id INT NOT NULL,
    status ENUM('pending', 'in_progress', 'waiting_parts', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    description TEXT NOT NULL,
    diagnosis TEXT,
    odometer INT,
    estimated_hours DECIMAL(5,2),
    actual_hours DECIMAL(5,2) DEFAULT 0,
    parts_cost DECIMAL(10,2) DEFAULT 0,
    labor_cost DECIMAL(10,2) DEFAULT 0,
    total_cost DECIMAL(10,2) DEFAULT 0,
    start_date DATETIME,
    completion_date DATETIME,
    notes TEXT,
    created_by INT,
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_service (service_id),
    INDEX idx_status (status),
    INDEX idx_assigned (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabelul service_mechanics (mecanici atelier)
CREATE TABLE IF NOT EXISTS service_mechanics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    service_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(200),
    hourly_rate DECIMAL(8,2),
    phone VARCHAR(20),
    email VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_service (service_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabelul work_order_parts (piese utilizate în ordine de lucru)
CREATE TABLE IF NOT EXISTS work_order_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    work_order_id INT NOT NULL,
    part_name VARCHAR(200) NOT NULL,
    part_number VARCHAR(100),
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    supplier VARCHAR(200),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_work_order (work_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabelul work_order_labor (manoperă în ordine de lucru)
CREATE TABLE IF NOT EXISTS work_order_labor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    work_order_id INT NOT NULL,
    mechanic_id INT,
    task_description VARCHAR(200) NOT NULL,
    hours_worked DECIMAL(5,2) NOT NULL,
    hourly_rate DECIMAL(8,2) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    start_time DATETIME,
    end_time DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_work_order (work_order_id),
    INDEX idx_mechanic (mechanic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabelul work_order_checklist (checklist verificări)
CREATE TABLE IF NOT EXISTS work_order_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    work_order_id INT NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    category VARCHAR(100),
    is_checked TINYINT(1) DEFAULT 0,
    status ENUM('ok', 'needs_attention', 'critical', 'replaced') DEFAULT 'ok',
    notes TEXT,
    checked_by INT,
    checked_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_work_order (work_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Tabelul service_notifications (notificări service)
CREATE TABLE IF NOT EXISTS service_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    notification_type ENUM('service_due', 'service_overdue', 'maintenance_reminder', 'appointment_reminder') NOT NULL,
    message TEXT NOT NULL,
    due_date DATE,
    is_sent TINYINT(1) DEFAULT 0,
    sent_at DATETIME,
    is_dismissed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_type (notification_type),
    INDEX idx_sent (is_sent),
    INDEX idx_dismissed (is_dismissed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. View pentru întreținere scadentă
CREATE OR REPLACE VIEW v_maintenance_due AS
SELECT 
    v.id AS vehicle_id,
    v.tenant_id,
    v.make,
    v.model,
    v.registration_number,
    v.current_odometer,
    sh.service_type,
    sh.next_service_date,
    sh.next_service_odometer,
    DATEDIFF(sh.next_service_date, CURDATE()) AS days_until_service,
    (sh.next_service_odometer - v.current_odometer) AS km_until_service
FROM vehicles v
LEFT JOIN service_history sh ON v.id = sh.vehicle_id
WHERE sh.next_service_date IS NOT NULL OR sh.next_service_odometer IS NOT NULL;

-- 12. View pentru ordine de lucru active
CREATE OR REPLACE VIEW v_active_work_orders AS
SELECT 
    wo.id,
    wo.tenant_id,
    wo.work_order_number,
    wo.status,
    wo.priority,
    v.registration_number,
    v.make,
    v.model,
    s.name AS service_name,
    m.name AS mechanic_name,
    wo.total_cost,
    wo.start_date,
    wo.completion_date,
    DATEDIFF(CURDATE(), wo.start_date) AS days_in_progress
FROM work_orders wo
INNER JOIN vehicles v ON wo.vehicle_id = v.id
INNER JOIN services s ON wo.service_id = s.id
LEFT JOIN service_mechanics m ON wo.assigned_to = m.id
WHERE wo.status IN ('pending', 'in_progress', 'waiting_parts');
