CREATE DATABASE IF NOT EXISTS fleet_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fleet_management;

-- Tabela pentru tipuri de vehicule
CREATE TABLE vehicle_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category ENUM('vehicle', 'equipment') NOT NULL DEFAULT 'vehicle',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
);

-- Tabela principală pentru vehicule
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(20) NOT NULL,
    vin_number VARCHAR(50),
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    vehicle_type_id INT NOT NULL,
    status ENUM('active', 'inactive', 'maintenance', 'deleted') DEFAULT 'active',
    purchase_date DATE,
    purchase_price DECIMAL(10,2),
    current_mileage INT DEFAULT 0,
    engine_capacity VARCHAR(20),
    fuel_type ENUM('petrol', 'diesel', 'electric', 'hybrid', 'gas') DEFAULT 'diesel',
    color VARCHAR(30),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id),
    UNIQUE KEY unique_registration (registration_number),
    INDEX idx_status (status),
    INDEX idx_brand_model (brand, model),
    INDEX idx_vehicle_type (vehicle_type_id)
);

-- Tabela pentru șoferi
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    license_number VARCHAR(50) NOT NULL,
    license_category VARCHAR(20),
    license_issue_date DATE,
    license_expiry_date DATE,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    date_of_birth DATE,
    hire_date DATE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    assigned_vehicle_id INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    UNIQUE KEY unique_license (license_number),
    INDEX idx_status (status),
    INDEX idx_expiry_date (license_expiry_date)
);

-- Tabela pentru documente (asigurări, ITP, roviniete, etc.)
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    document_type ENUM('insurance_rca', 'insurance_casco', 'itp', 'vignette', 'registration', 'authorization', 'other') NOT NULL,
    document_number VARCHAR(100),
    issue_date DATE,
    expiry_date DATE,
    provider VARCHAR(100),
    cost DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'RON',
    file_path VARCHAR(255),
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    reminder_days INT DEFAULT 30,
    auto_renew BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    INDEX idx_vehicle_document (vehicle_id, document_type),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_status (status)
);

-- Tabela pentru întreținere și reparații
CREATE TABLE maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    driver_id INT NULL,
    maintenance_type ENUM('preventive', 'corrective', 'inspection', 'repair', 'service') NOT NULL,
    description TEXT NOT NULL,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'RON',
    mileage_at_service INT,
    service_date DATE NOT NULL,
    next_service_date DATE,
    next_service_mileage INT,
    provider VARCHAR(100),
    invoice_number VARCHAR(50),
    work_order_number VARCHAR(50),
    warranty_expiry_date DATE,
    parts_replaced TEXT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'completed',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    file_path VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    INDEX idx_vehicle_date (vehicle_id, service_date),
    INDEX idx_next_service (next_service_date),
    INDEX idx_maintenance_type (maintenance_type),
    INDEX idx_status (status)
);

-- Tabela pentru consum combustibil
CREATE TABLE fuel_consumption (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    driver_id INT NULL,
    fuel_date DATE NOT NULL,
    liters DECIMAL(8,3) NOT NULL,
    cost_per_liter DECIMAL(6,3) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'RON',
    mileage INT,
    fuel_type ENUM('petrol', 'diesel', 'electric', 'gas') NOT NULL,
    station VARCHAR(100),
    receipt_number VARCHAR(50),
    payment_method ENUM('cash', 'card', 'voucher', 'corporate_card') DEFAULT 'card',
    is_full_tank BOOLEAN DEFAULT TRUE,
    trip_purpose ENUM('business', 'personal', 'maintenance') DEFAULT 'business',
    location VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    INDEX idx_vehicle_date (vehicle_id, fuel_date),
    INDEX idx_fuel_date (fuel_date),
    INDEX idx_mileage (mileage)
);

-- Tabela pentru notificări și alerte
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('document_expiry', 'maintenance_due', 'inspection_due', 'license_expiry', 'mileage_alert', 'cost_alert', 'general') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    vehicle_id INT NULL,
    driver_id INT NULL,
    related_id INT NULL, -- ID-ul înregistrării relevante (document, maintenance, etc.)
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    target_date DATE,
    due_date DATE,
    status ENUM('pending', 'sent', 'acknowledged', 'dismissed', 'expired') DEFAULT 'pending',
    notification_methods JSON, -- ['email', 'sms', 'in_app']
    recipients JSON, -- emails/phones pentru notificări
    sent_at TIMESTAMP NULL,
    acknowledged_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    INDEX idx_status_date (status, due_date),
    INDEX idx_vehicle_notifications (vehicle_id),
    INDEX idx_type_priority (type, priority)
);

-- Tabela pentru utilizatori și autentificare
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role ENUM('admin', 'manager', 'operator', 'viewer') DEFAULT 'operator',
    permissions JSON, -- permisiuni specifice pentru module
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_username (username),
    UNIQUE KEY unique_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Tabela pentru audit trail (istoric modificări)
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('create', 'update', 'delete') NOT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_user_action (user_id, action),
    INDEX idx_created_at (created_at)
);

-- Tabela pentru configurări sistem
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_system BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting_key (setting_key)
);

-- Tabela pentru backup configurații
CREATE TABLE backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_type ENUM('manual', 'scheduled', 'auto') NOT NULL,
    file_path VARCHAR(255),
    file_size BIGINT,
    status ENUM('started', 'completed', 'failed') NOT NULL,
    error_message TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status_date (status, created_at)
);

-- View pentru rapoarte - vehicule cu informații complete
CREATE VIEW vehicles_complete AS
SELECT 
    v.*,
    vt.name as vehicle_type_name,
    vt.category as vehicle_category,
    d_driver.name as assigned_driver_name,
    d_driver.phone as driver_phone,
    COUNT(DISTINCT doc.id) as total_documents,
    COUNT(DISTINCT CASE WHEN doc.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND doc.status = 'active' THEN doc.id END) as expiring_documents,
    COUNT(DISTINCT m.id) as total_maintenance_records,
    SUM(DISTINCT m.cost) as total_maintenance_cost,
    COUNT(DISTINCT fc.id) as total_fuel_records,
    SUM(DISTINCT fc.total_cost) as total_fuel_cost,
    AVG(fc.liters) as avg_fuel_consumption,
    MAX(fc.fuel_date) as last_refuel_date
FROM vehicles v
LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
LEFT JOIN drivers d_driver ON v.id = d_driver.assigned_vehicle_id
LEFT JOIN documents doc ON v.id = doc.vehicle_id
LEFT JOIN maintenance m ON v.id = m.vehicle_id
LEFT JOIN fuel_consumption fc ON v.id = fc.vehicle_id
WHERE v.status != 'deleted'
GROUP BY v.id;

-- View pentru notificări active
CREATE VIEW active_notifications AS
SELECT 
    n.*,
    v.registration_number as vehicle_registration,
    v.brand as vehicle_brand,
    v.model as vehicle_model,
    d.name as driver_name,
    DATEDIFF(n.due_date, CURDATE()) as days_until_due
FROM notifications n
LEFT JOIN vehicles v ON n.vehicle_id = v.id
LEFT JOIN drivers d ON n.driver_id = d.id
WHERE n.status IN ('pending', 'sent')
AND (n.due_date IS NULL OR n.due_date >= CURDATE())
ORDER BY n.priority DESC, n.due_date ASC;

-- Inserarea datelor inițiale

-- Tipuri de vehicule predefinite
INSERT INTO vehicle_types (name, category, description) VALUES
('Autoturism Personal', 'vehicle', 'Vehicule pentru transportul personalului'),
('Autoutilitară Mică', 'vehicle', 'Vehicule pentru transport marfă până la 3.5t'),
('Camion', 'vehicle', 'Vehicule pentru transport marfă peste 3.5t'),
('Autobus/Microbuz', 'vehicle', 'Vehicule pentru transportul pasagerilor'),
('Motostivuitor', 'equipment', 'Echipament pentru manevrarea mărfurilor'),
('Excavator', 'equipment', 'Echipament pentru construcții'),
('Buldozer', 'equipment', 'Echipament pentru construcții'),
('Trailer/Remorcă', 'equipment', 'Remorci pentru transport'),
('Utilaj Agricol', 'equipment', 'Tractoare și utilaje agricole'),
('Generator/Compresor', 'equipment', 'Echipamente auxiliare');

-- Utilizator admin implicit
INSERT INTO users (username, email, password_hash, first_name, last_name, role, permissions) VALUES
('admin', 'admin@fleet.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'System', 'admin', '["all"]'),
('manager', 'manager@fleet.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'Fleet', 'manager', '["vehicles", "drivers", "documents", "maintenance", "fuel", "reports"]'),
('operator', 'operator@fleet.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator', 'Fleet', 'operator', '["vehicles", "fuel", "maintenance"]');

-- Configurări sistem predefinite
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_system) VALUES
('app_name', 'Fleet Management System', 'string', 'Numele aplicației', FALSE),
('default_currency', 'RON', 'string', 'Moneda implicită', FALSE),
('items_per_page', '20', 'number', 'Numărul de elemente pe pagină', FALSE),
('backup_retention_days', '30', 'number', 'Câte zile să păstreze backup-urile', FALSE),
('notification_reminder_days', '30', 'number', 'Cu câte zile înainte să trimită notificări', FALSE),
('auto_backup_enabled', 'true', 'boolean', 'Backup automat activat', TRUE),
('maintenance_mileage_threshold', '10000', 'number', 'Pragul de kilometraj pentru întreținere preventivă', FALSE),
('fuel_efficiency_alert_threshold', '15', 'number', 'Pragul de consum pentru alertă (L/100km)', FALSE),
('email_notifications_enabled', 'true', 'boolean', 'Notificări email activate', FALSE),
('sms_notifications_enabled', 'false', 'boolean', 'Notificări SMS activate', FALSE);

-- Triggere pentru audit trail
DELIMITER $

CREATE TRIGGER vehicles_audit_insert AFTER INSERT ON vehicles
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (table_name, record_id, action, new_values)
    VALUES ('vehicles', NEW.id, 'create', JSON_OBJECT(
        'registration_number', NEW.registration_number,
        'brand', NEW.brand,
        'model', NEW.model,
        'year', NEW.year,
        'status', NEW.status
    ));
END$

CREATE TRIGGER vehicles_audit_update AFTER UPDATE ON vehicles
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (table_name, record_id, action, old_values, new_values)
    VALUES ('vehicles', NEW.id, 'update', 
        JSON_OBJECT(
            'registration_number', OLD.registration_number,
            'brand', OLD.brand,
            'model', OLD.model,
            'current_mileage', OLD.current_mileage,
            'status', OLD.status
        ),
        JSON_OBJECT(
            'registration_number', NEW.registration_number,
            'brand', NEW.brand,
            'model', NEW.model,
            'current_mileage', NEW.current_mileage,
            'status', NEW.status
        )
    );
END$

-- Trigger pentru notificări automate la expirarea documentelor
CREATE TRIGGER documents_expiry_notification AFTER INSERT ON documents
FOR EACH ROW
BEGIN
    IF NEW.expiry_date IS NOT NULL THEN
        INSERT INTO notifications (
            type, vehicle_id, related_id, title, message, target_date, due_date, priority
        ) VALUES (
            'document_expiry',
            NEW.vehicle_id,
            NEW.id,
            CONCAT('Document ', NEW.document_type, ' expiră'),
            CONCAT('Documentul ', NEW.document_type, ' cu numărul ', COALESCE(NEW.document_number, 'N/A'), ' va expira pe ', NEW.expiry_date),
            NEW.expiry_date,
            DATE_SUB(NEW.expiry_date, INTERVAL COALESCE(NEW.reminder_days, 30) DAY),
            'high'
        );
    END IF;
END$

DELIMITER ;

-- Indexuri pentru optimizare
CREATE INDEX idx_vehicles_mileage ON vehicles(current_mileage);
CREATE INDEX idx_fuel_consumption_date_vehicle ON fuel_consumption(fuel_date, vehicle_id);
CREATE INDEX idx_maintenance_date_vehicle ON maintenance(service_date, vehicle_id);
CREATE INDEX idx_documents_expiry_status ON documents(expiry_date, status);

-- Proceduri stocate pentru rapoarte
DELIMITER $

-- Procedură pentru statistici lunare
CREATE PROCEDURE GetMonthlyStats(IN target_month INT, IN target_year INT)
BEGIN
    SELECT 
        'fuel_consumption' as metric,
        SUM(total_cost) as total_amount,
        COUNT(*) as total_records,
        AVG(total_cost) as average_amount
    FROM fuel_consumption 
    WHERE MONTH(fuel_date) = target_month AND YEAR(fuel_date) = target_year
    
    UNION ALL
    
    SELECT 
        'maintenance_costs' as metric,
        SUM(cost) as total_amount,
        COUNT(*) as total_records,
        AVG(cost) as average_amount
    FROM maintenance 
    WHERE MONTH(service_date) = target_month AND YEAR(service_date) = target_year;
END$

-- Procedură pentru raport vehicul
CREATE PROCEDURE GetVehicleReport(IN vehicle_id INT, IN start_date DATE, IN end_date DATE)
BEGIN
    -- Informații de bază
    SELECT * FROM vehicles_complete WHERE id = vehicle_id;
    
    -- Consumul de combustibil în perioada
    SELECT 
        fuel_date,
        liters,
        total_cost,
        mileage,
        station
    FROM fuel_consumption 
    WHERE vehicle_id = vehicle_id 
    AND fuel_date BETWEEN start_date AND end_date
    ORDER BY fuel_date DESC;
    
    -- Întreținerea în perioada
    SELECT 
        service_date,
        maintenance_type,
        description,
        cost,
        provider
    FROM maintenance 
    WHERE vehicle_id = vehicle_id 
    AND service_date BETWEEN start_date AND end_date
    ORDER BY service_date DESC;
END$

DELIMITER ;