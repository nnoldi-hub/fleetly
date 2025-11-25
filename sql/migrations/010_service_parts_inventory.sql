-- Migration: Service Parts Inventory System
-- Description: Add parts inventory management to internal workshop
-- Date: 2025-01-XX

-- Table for parts inventory
CREATE TABLE IF NOT EXISTS service_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_number VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    manufacturer VARCHAR(100),
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    sale_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantity_in_stock INT NOT NULL DEFAULT 0,
    minimum_quantity INT NOT NULL DEFAULT 0,
    unit_of_measure VARCHAR(20) DEFAULT 'buc',
    location VARCHAR(100),
    supplier VARCHAR(255),
    supplier_part_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_part_number (part_number),
    INDEX idx_category (category),
    INDEX idx_low_stock (quantity_in_stock, minimum_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for parts usage in work orders
CREATE TABLE IF NOT EXISTS service_parts_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    part_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (work_order_id) REFERENCES service_work_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (part_id) REFERENCES service_parts(id) ON DELETE RESTRICT,
    INDEX idx_work_order (work_order_id),
    INDEX idx_part (part_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for stock transactions (purchases, adjustments, returns)
CREATE TABLE IF NOT EXISTS service_parts_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_id INT NOT NULL,
    transaction_type ENUM('in', 'out', 'adjustment', 'return') NOT NULL,
    quantity INT NOT NULL,
    reference_number VARCHAR(100),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES service_parts(id) ON DELETE CASCADE,
    INDEX idx_part_date (part_id, created_at),
    INDEX idx_type (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data for testing
INSERT INTO service_parts (part_number, name, category, manufacturer, unit_price, sale_price, quantity_in_stock, minimum_quantity, unit_of_measure, location) VALUES
('OIL-5W30-5L', 'Ulei motor 5W30', 'Lubrifianti', 'Castrol', 45.00, 60.00, 20, 5, 'buc', 'Depozit A1'),
('FILTER-OIL-001', 'Filtru ulei', 'Filtre', 'Mann Filter', 25.00, 35.00, 15, 5, 'buc', 'Depozit A2'),
('FILTER-AIR-001', 'Filtru aer', 'Filtre', 'Mann Filter', 30.00, 40.00, 10, 5, 'buc', 'Depozit A2'),
('BRAKE-PAD-F', 'Placute frana fata', 'Frane', 'Brembo', 120.00, 150.00, 8, 4, 'set', 'Depozit B1'),
('BRAKE-PAD-R', 'Placute frana spate', 'Frane', 'Brembo', 100.00, 130.00, 6, 4, 'set', 'Depozit B1'),
('COOLANT-5L', 'Antigel concentrat', 'Lubrifianti', 'Motul', 35.00, 50.00, 12, 3, 'buc', 'Depozit A1'),
('WIPER-BLADE', 'Lamela stergator', 'Accesorii', 'Bosch', 15.00, 25.00, 20, 10, 'buc', 'Depozit C1'),
('BATTERY-12V', 'Baterie 12V 70Ah', 'Electrica', 'Varta', 350.00, 450.00, 3, 2, 'buc', 'Depozit D1');

-- Add trigger to update work order labor_cost when parts are added
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

CREATE TRIGGER update_work_order_parts_cost_update AFTER UPDATE ON service_parts_usage
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

CREATE TRIGGER update_work_order_parts_cost_delete AFTER DELETE ON service_parts_usage
FOR EACH ROW
BEGIN
    UPDATE service_work_orders
    SET parts_cost = (
        SELECT COALESCE(SUM(total_price), 0)
        FROM service_parts_usage
        WHERE work_order_id = OLD.work_order_id
    )
    WHERE id = OLD.work_order_id;
END//
DELIMITER ;
