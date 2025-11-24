-- ============================================================================
-- SERVICE MODULE - SIMPLE VERSION FOR PHPMYADMIN IMPORT
-- Import direct prin phpMyAdmin - fără DELIMITER, fără triggere, fără foreign keys
-- ============================================================================

-- 1. Services (Service-uri partenere și atelier intern)
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `service_type` enum('internal','external') DEFAULT 'external',
  `address` text,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `service_types` text DEFAULT NULL COMMENT 'JSON cu tipuri de lucrări',
  `working_hours` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL COMMENT 'Posturi de lucru (atelier intern)',
  `hourly_rate` decimal(10,2) DEFAULT NULL COMMENT 'Tarif manoperă/oră',
  `rating` decimal(3,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_type` (`service_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Service Appointments (Programări service)
CREATE TABLE IF NOT EXISTS `service_appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time DEFAULT NULL,
  `type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('programat','confirmat','in_lucru','efectuat','anulat') DEFAULT 'programat',
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_vehicle` (`vehicle_id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_date` (`appointment_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Service History (Istoric intervenții)
CREATE TABLE IF NOT EXISTS `service_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `service_date` date NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `odometer_reading` int(11) DEFAULT NULL,
  `cost_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_parts` decimal(10,2) DEFAULT 0.00,
  `cost_labor` decimal(10,2) DEFAULT 0.00,
  `cost_other` decimal(10,2) DEFAULT 0.00,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_file` varchar(255) DEFAULT NULL,
  `parts_replaced` text DEFAULT NULL COMMENT 'JSON cu piese schimbate',
  `notes` text DEFAULT NULL,
  `next_service_km` int(11) DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_vehicle` (`vehicle_id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_date` (`service_date`),
  KEY `idx_type` (`service_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Maintenance Rules (Reguli mentenanță periodică)
CREATE TABLE IF NOT EXISTS `maintenance_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL COMMENT 'NULL = regulă globală',
  `rule_name` varchar(255) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `interval_km` int(11) DEFAULT NULL,
  `interval_months` int(11) DEFAULT NULL,
  `warning_km` int(11) DEFAULT 500,
  `warning_days` int(11) DEFAULT 7,
  `last_service_date` date DEFAULT NULL,
  `last_service_km` int(11) DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `next_due_km` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_vehicle` (`vehicle_id`),
  KEY `idx_due_date` (`next_due_date`),
  KEY `idx_due_km` (`next_due_km`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Service Mechanics (Personal atelier)
CREATE TABLE IF NOT EXISTS `service_mechanics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `hire_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Work Orders (Ordine de lucru - atelier intern)
CREATE TABLE IF NOT EXISTS `work_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `work_order_number` varchar(50) NOT NULL,
  `entry_date` datetime NOT NULL,
  `estimated_completion` datetime DEFAULT NULL,
  `actual_completion` datetime DEFAULT NULL,
  `odometer_reading` int(11) DEFAULT NULL,
  `assigned_mechanic_id` int(11) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `status` enum('pending','in_progress','waiting_parts','completed','delivered') DEFAULT 'pending',
  `diagnosis` text DEFAULT NULL,
  `work_description` text DEFAULT NULL,
  `customer_notes` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `estimated_hours` decimal(5,2) DEFAULT NULL,
  `actual_hours` decimal(5,2) DEFAULT NULL,
  `labor_cost` decimal(10,2) DEFAULT 0.00,
  `parts_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_order_number` (`work_order_number`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_vehicle` (`vehicle_id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_mechanic` (`assigned_mechanic_id`),
  KEY `idx_entry_date` (`entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Work Order Parts (Piese utilizate)
CREATE TABLE IF NOT EXISTS `work_order_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_order_id` int(11) NOT NULL,
  `part_name` varchar(255) NOT NULL,
  `part_number` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_work_order` (`work_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Work Order Labor (Manoperă detaliată)
CREATE TABLE IF NOT EXISTS `work_order_labor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_order_id` int(11) NOT NULL,
  `mechanic_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `labor_cost` decimal(10,2) DEFAULT NULL,
  `task_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_work_order` (`work_order_id`),
  KEY `idx_mechanic` (`mechanic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Work Order Checklist (Checklist diagnoză/verificări)
CREATE TABLE IF NOT EXISTS `work_order_checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_order_id` int(11) NOT NULL,
  `item` varchar(255) NOT NULL,
  `is_checked` tinyint(1) DEFAULT 0,
  `status` enum('ok','attention','critical') DEFAULT 'ok',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_work_order` (`work_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Service Notifications (Notificări service)
CREATE TABLE IF NOT EXISTS `service_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `maintenance_rule_id` int(11) DEFAULT NULL,
  `work_order_id` int(11) DEFAULT NULL,
  `notification_type` enum('upcoming','overdue','reminder','work_order_status','parts_needed') NOT NULL,
  `message` text NOT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_vehicle` (`vehicle_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_type` (`notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. View pentru service-uri scadente (opțional - comentat pentru a evita erori)
-- Decomentează și adaptează după structura tabelului vehicles
/*
CREATE OR REPLACE VIEW `v_maintenance_due` AS
SELECT 
    mr.id as rule_id,
    mr.tenant_id,
    mr.vehicle_id,
    v.registration_number as plate_number,
    v.brand as make,
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
*/

-- 12. View pentru ordine de lucru active (opțional - comentat pentru a evita erori)
-- Decomentează și adaptează după structura tabelului vehicles
/*
CREATE OR REPLACE VIEW `v_active_work_orders` AS
SELECT 
    wo.id,
    wo.work_order_number,
    wo.tenant_id,
    v.registration_number as plate_number,
    v.brand as make,
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
*/

-- ============================================================================
-- FIN - GATA DE IMPORT
-- ============================================================================
