-- ============================================
-- Fleet Management System - Authentication & Authorization Schema
-- Multi-tenancy with Role-Based Access Control (RBAC)
-- ============================================

-- Companies table (Multi-tenancy)
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    registration_number VARCHAR(50) UNIQUE,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100) DEFAULT 'RomÃ¢nia',
    logo VARCHAR(255),
    database_name VARCHAR(100) UNIQUE COMMENT 'Dedicated DB name for complete isolation',
    status ENUM('active', 'suspended', 'trial', 'expired') DEFAULT 'active',
    subscription_type ENUM('basic', 'standard', 'premium', 'enterprise') DEFAULT 'basic',
    subscription_expires_at DATETIME,
    max_users INT DEFAULT 5,
    max_vehicles INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT 'SuperAdmin who created this company',
    INDEX idx_status (status),
    INDEX idx_subscription (subscription_type, subscription_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    level INT NOT NULL COMMENT '1=SuperAdmin, 2=Admin, 3=Manager, 4=User',
    is_system BOOLEAN DEFAULT FALSE COMMENT 'System roles cannot be deleted',
    company_id INT NULL COMMENT 'NULL for system roles, company-specific otherwise',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_per_company (slug, company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system roles
INSERT INTO roles (name, slug, description, level, is_system, company_id) VALUES
('Super Administrator', 'superadmin', 'Acces complet la toate companiile È™i configurÄƒri sistem', 1, TRUE, NULL),
('Administrator FirmÄƒ', 'admin', 'Administrator companie cu acces complet la datele firmei', 2, TRUE, NULL),
('Manager FlotÄƒ', 'fleet_manager', 'Gestionare vehicule, È™oferi, mentenanÈ›Äƒ', 3, TRUE, NULL),
('Operator FlotÄƒ', 'fleet_operator', 'Monitorizare È™i introducere date', 4, TRUE, NULL),
('È˜ofer', 'driver', 'Acces limitat - doar rapoarte proprii', 4, TRUE, NULL);

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NULL COMMENT 'NULL for SuperAdmin users',
    role_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    avatar VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    password_reset_token VARCHAR(255),
    password_reset_expires DATETIME,
    last_login_at DATETIME,
    last_login_ip VARCHAR(45),
    login_attempts INT DEFAULT 0,
    locked_until DATETIME,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255),
    preferences JSON COMMENT 'User settings, language, theme, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    -- FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_email (email),
    UNIQUE KEY unique_username_per_company (username, company_id),
    INDEX idx_status (status),
    INDEX idx_company (company_id),
    INDEX idx_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50) NOT NULL COMMENT 'vehicles, drivers, fuel, maintenance, etc.',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default permissions
INSERT INTO permissions (name, slug, module, description) VALUES
-- Vehicles
('Vizualizare Vehicule', 'vehicles.view', 'vehicles', 'Poate vizualiza lista de vehicule'),
('Creare Vehicule', 'vehicles.create', 'vehicles', 'Poate adÄƒuga vehicule noi'),
('Editare Vehicule', 'vehicles.edit', 'vehicles', 'Poate modifica vehicule existente'),
('È˜tergere Vehicule', 'vehicles.delete', 'vehicles', 'Poate È™terge vehicule'),
-- Drivers
('Vizualizare È˜oferi', 'drivers.view', 'drivers', 'Poate vizualiza lista de È™oferi'),
('Creare È˜oferi', 'drivers.create', 'drivers', 'Poate adÄƒuga È™oferi noi'),
('Editare È˜oferi', 'drivers.edit', 'drivers', 'Poate modifica È™oferi existenÈ›i'),
('È˜tergere È˜oferi', 'drivers.delete', 'drivers', 'Poate È™terge È™oferi'),
-- Fuel
('Vizualizare Combustibil', 'fuel.view', 'fuel', 'Poate vizualiza Ã®nregistrÄƒri combustibil'),
('Creare Combustibil', 'fuel.create', 'fuel', 'Poate adÄƒuga Ã®nregistrÄƒri combustibil'),
('Editare Combustibil', 'fuel.edit', 'fuel', 'Poate modifica Ã®nregistrÄƒri combustibil'),
('È˜tergere Combustibil', 'fuel.delete', 'fuel', 'Poate È™terge Ã®nregistrÄƒri combustibil'),
('Rapoarte Combustibil', 'fuel.reports', 'fuel', 'Poate vizualiza rapoarte combustibil'),
-- Maintenance
('Vizualizare MentenanÈ›Äƒ', 'maintenance.view', 'maintenance', 'Poate vizualiza mentenanÈ›Äƒ'),
('Creare MentenanÈ›Äƒ', 'maintenance.create', 'maintenance', 'Poate programa mentenanÈ›Äƒ'),
('Editare MentenanÈ›Äƒ', 'maintenance.edit', 'maintenance', 'Poate modifica mentenanÈ›Äƒ'),
('È˜tergere MentenanÈ›Äƒ', 'maintenance.delete', 'maintenance', 'Poate È™terge mentenanÈ›Äƒ'),
('Rapoarte MentenanÈ›Äƒ', 'maintenance.reports', 'maintenance', 'Poate vizualiza rapoarte mentenanÈ›Äƒ'),
-- Users
('Vizualizare Utilizatori', 'users.view', 'users', 'Poate vizualiza utilizatori'),
('Creare Utilizatori', 'users.create', 'users', 'Poate crea utilizatori noi'),
('Editare Utilizatori', 'users.edit', 'users', 'Poate modifica utilizatori'),
('È˜tergere Utilizatori', 'users.delete', 'users', 'Poate È™terge utilizatori'),
-- Companies (SuperAdmin only)
('Vizualizare Companii', 'companies.view', 'companies', 'Poate vizualiza toate companiile'),
('Creare Companii', 'companies.create', 'companies', 'Poate crea companii noi'),
('Editare Companii', 'companies.edit', 'companies', 'Poate modifica companii'),
('È˜tergere Companii', 'companies.delete', 'companies', 'Poate È™terge companii'),
('IntervenÈ›ie Companii', 'companies.intervene', 'companies', 'Poate accesa orice companie ca SuperAdmin'),
-- Reports
('Rapoarte Complete', 'reports.full', 'reports', 'Acces complet la toate raportele'),
('Export Rapoarte', 'reports.export', 'reports', 'Poate exporta rapoarte'),
-- Settings
('Configurare Sistem', 'settings.system', 'settings', 'Poate modifica setÄƒri sistem'),
('Configurare Companie', 'settings.company', 'settings', 'Poate modifica setÄƒri companie');

-- Role Permissions (many-to-many)
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    granted_by INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grant all permissions to SuperAdmin
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Grant company management permissions to Admin
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions 
WHERE slug NOT LIKE 'companies.%' OR slug IN ('companies.view', 'companies.edit');

-- Sessions table (for session management)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log table
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    company_id INT,
    action VARCHAR(100) NOT NULL COMMENT 'create, update, delete, login, logout, etc.',
    entity_type VARCHAR(50) COMMENT 'vehicle, driver, user, etc.',
    entity_id INT,
    old_values JSON COMMENT 'Before change',
    new_values JSON COMMENT 'After change',
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_company (company_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SuperAdmin intervention requests
CREATE TABLE IF NOT EXISTS intervention_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    requested_by INT NOT NULL COMMENT 'Admin user requesting help',
    superadmin_id INT COMMENT 'Assigned SuperAdmin',
    reason TEXT NOT NULL,
    status ENUM('pending', 'accepted', 'in_progress', 'resolved', 'rejected') DEFAULT 'pending',
    notes TEXT,
    started_at DATETIME,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (superadmin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create default SuperAdmin user (password: Admin123!)
INSERT INTO users (company_id, role_id, username, email, password_hash, first_name, last_name, status, email_verified)
VALUES (
    NULL, 
    1, 
    'superadmin', 
    'admin@fleetmanagement.ro', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- bcrypt hash for 'Admin123!'
    'Super', 
    'Administrator', 
    'active', 
    TRUE
);

