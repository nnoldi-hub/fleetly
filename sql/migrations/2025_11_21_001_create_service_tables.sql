-- ============================================
-- SERVICE AUTO MODULE - Database Schema
-- Created: 2025-11-21
-- Description: Tables for service management, appointments, history, and maintenance rules
-- ============================================

-- ============================================
-- Table: services (Service-uri partenere/interne)
-- ============================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(500),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    phone VARCHAR(50),
    email VARCHAR(255),
    contact_person VARCHAR(255),
    service_type ENUM('internal', 'partner') DEFAULT 'partner',
    specializations TEXT COMMENT 'JSON array: ["revizie", "reparatii", "tinichigerie", etc.]',
    working_hours VARCHAR(255) COMMENT 'Program: L-V 8-17, etc.',
    rating DECIMAL(3,2) DEFAULT 0.00,
    notes TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_id (company_id),
    INDEX idx_status (status),
    INDEX idx_service_type (service_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: service_appointments (Programări)
-- ============================================
CREATE TABLE IF NOT EXISTS service_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME,
    type VARCHAR(100) NOT NULL COMMENT 'revizie, reparatie, schimb_ulei, ITP, etc.',
    description TEXT,
    estimated_cost DECIMAL(10,2),
    estimated_duration INT COMMENT 'Duration in hours',
    status ENUM('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    assigned_to INT COMMENT 'User ID responsible for this appointment',
    reminder_sent BOOLEAN DEFAULT FALSE,
    odometer_reading INT COMMENT 'KM la momentul programarii',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_id (company_id),
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_service_id (service_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: service_history (Istoric intervenții)
-- ============================================
CREATE TABLE IF NOT EXISTS service_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    appointment_id INT COMMENT 'Link to original appointment if exists',
    service_id INT,
    service_date DATE NOT NULL,
    type VARCHAR(100) NOT NULL COMMENT 'revizie, reparatie, schimb_ulei, ITP, etc.',
    description TEXT,
    work_performed TEXT COMMENT 'Detailed work description',
    parts_replaced TEXT COMMENT 'JSON or comma-separated list',
    labor_cost DECIMAL(10,2) DEFAULT 0.00,
    parts_cost DECIMAL(10,2) DEFAULT 0.00,
    total_cost DECIMAL(10,2) GENERATED ALWAYS AS (labor_cost + parts_cost) STORED,
    odometer_reading INT COMMENT 'KM at service time',
    next_service_km INT COMMENT 'Recommended next service at KM',
    next_service_date DATE COMMENT 'Recommended next service date',
    invoice_number VARCHAR(100),
    invoice_file VARCHAR(255) COMMENT 'Path to uploaded invoice PDF',
    warranty_until DATE,
    performed_by VARCHAR(255) COMMENT 'Technician name',
    quality_rating INT COMMENT '1-5 stars',
    notes TEXT,
    status ENUM('completed', 'pending_payment', 'warranty_claim') DEFAULT 'completed',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_id (company_id),
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_service_id (service_id),
    INDEX idx_service_date (service_date),
    INDEX idx_appointment_id (appointment_id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (appointment_id) REFERENCES service_appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: maintenance_rules (Reguli mentenanță periodică)
-- ============================================
CREATE TABLE IF NOT EXISTS maintenance_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    vehicle_id INT,
    rule_name VARCHAR(255) NOT NULL COMMENT 'Ex: Revizie tehnica, Schimb ulei, etc.',
    maintenance_type VARCHAR(100) NOT NULL,
    interval_km INT COMMENT 'Interval in KM (ex: 10000 pentru revizie)',
    interval_months INT COMMENT 'Interval in luni (ex: 12 pentru revizie anuala)',
    last_service_date DATE COMMENT 'Ultima efectuare',
    last_service_km INT COMMENT 'KM ultima efectuare',
    next_due_date DATE COMMENT 'Data urmatoare scadenta',
    next_due_km INT COMMENT 'KM urmatoare scadenta',
    notification_advance_days INT DEFAULT 30 COMMENT 'Cu cate zile inainte sa notifice',
    notification_advance_km INT DEFAULT 1000 COMMENT 'Cu cati KM inainte sa notifice',
    auto_create_appointment BOOLEAN DEFAULT FALSE COMMENT 'Creaza automat programare la scadenta',
    preferred_service_id INT COMMENT 'Service preferat pentru aceasta mentenanta',
    estimated_cost DECIMAL(10,2),
    is_mandatory BOOLEAN DEFAULT FALSE COMMENT 'Mentenanta obligatorie (ITP, asigurare)',
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_id (company_id),
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_next_due_date (next_due_date),
    INDEX idx_status (status),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (preferred_service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: service_reminders (Reminder-uri trimise)
-- ============================================
CREATE TABLE IF NOT EXISTS service_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    maintenance_rule_id INT,
    appointment_id INT,
    vehicle_id INT NOT NULL,
    reminder_type ENUM('maintenance_due', 'appointment_upcoming', 'appointment_today') NOT NULL,
    sent_date DATETIME NOT NULL,
    sent_to VARCHAR(500) COMMENT 'Email addresses or user IDs',
    status ENUM('sent', 'failed', 'acknowledged') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company_id (company_id),
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_maintenance_rule_id (maintenance_rule_id),
    INDEX idx_appointment_id (appointment_id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (maintenance_rule_id) REFERENCES maintenance_rules(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES service_appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert default maintenance rules (optional)
-- ============================================
-- These can be used as templates for common maintenance types
INSERT INTO maintenance_rules (company_id, vehicle_id, rule_name, maintenance_type, interval_km, interval_months, notification_advance_days, notification_advance_km, is_mandatory, notes)
VALUES 
(0, NULL, 'Revizie tehnică anuală (ITP)', 'ITP', NULL, 12, 30, NULL, TRUE, 'Template global pentru ITP - se va copia la nivel de vehicul'),
(0, NULL, 'Schimb ulei motor', 'schimb_ulei', 10000, 12, 14, 1000, FALSE, 'Template global pentru schimb ulei'),
(0, NULL, 'Revizie tehnică 15.000 km', 'revizie', 15000, NULL, 7, 1000, FALSE, 'Template global pentru revizie periodică'),
(0, NULL, 'Verificare sisteme siguranță', 'revizie', 30000, 24, 14, 2000, FALSE, 'Verificare frâne, airbag, ABS');

-- ============================================
-- Verification Queries
-- ============================================
-- Uncomment to verify installation:
-- SHOW TABLES LIKE 'service%';
-- DESCRIBE services;
-- DESCRIBE service_appointments;
-- DESCRIBE service_history;
-- DESCRIBE maintenance_rules;
-- DESCRIBE service_reminders;

-- ============================================
-- END OF SERVICE MODULE SCHEMA
-- ============================================
