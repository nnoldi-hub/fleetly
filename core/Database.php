<?php
class Database {
    private static $instance = null;
    // Core (central) connection: auth, companies, roles, etc.
    private $core;
    // Tenant connections pool by DB name
    private $tenants = [];
    private $currentTenantDb = null; // string db name

    // Tables that must stay in the core DB
    private $coreTables = [
        'companies','users','roles','permissions','role_permissions','user_sessions','audit_logs','system_settings','intervention_requests'
    ];

    private function __construct() {
        $this->core = DatabaseConfig::getConnection();
        // If a tenant db is stored in session (intervention mode), preload it
        if (!empty($_SESSION['acting_company']['db'])) {
            $this->setTenantDatabase($_SESSION['acting_company']['db']);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ---------- Generic CORE-only helpers (backward compatibility) ----------
    public function getConnection() { return $this->core; }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->core->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw $e;
        }
    }
    public function fetch($sql, $params = []) { return $this->query($sql, $params)->fetch(); }
    public function fetchAll($sql, $params = []) { return $this->query($sql, $params)->fetchAll(); }
    public function lastInsertId() { return $this->core->lastInsertId(); }
    public function beginTransaction() { return $this->core->beginTransaction(); }
    public function commit() { return $this->core->commit(); }
    public function rollback() { return $this->core->rollBack(); }

    // ---------- Multi-tenant helpers ----------
    public function setTenantDatabaseByCompanyId($companyId) {
        $prefix = method_exists('DatabaseConfig', 'getTenantDbPrefix') ? DatabaseConfig::getTenantDbPrefix() : '';
        $dbName = $prefix . 'fm_tenant_' . (int)$companyId;
        return $this->setTenantDatabase($dbName);
    }

    public function setTenantDatabase($dbName) {
        $dbName = preg_replace('/[^a-zA-Z0-9_]/','_', $dbName);

        // Single-DB tenancy mode (shared hosting friendly): reuse CORE DB
        if (method_exists('DatabaseConfig', 'getTenancyMode') && DatabaseConfig::getTenancyMode() === 'single') {
            // Use core connection as tenant connection as well
            $this->installTenantSchemaIfNeeded($this->core);
            $this->tenants[$dbName] = $this->core;
            $this->currentTenantDb = DatabaseConfig::getDbName();
            $_SESSION['acting_company']['db'] = $this->currentTenantDb;
            return $this->core;
        }

        if (isset($this->tenants[$dbName])) {
            $this->currentTenantDb = $dbName;
            $_SESSION['acting_company']['db'] = $dbName;
            return $this->tenants[$dbName];
        }
        // Ensure database exists and return PDO
        $pdo = $this->createPdoForDb($dbName);
        $this->installTenantSchemaIfNeeded($pdo);
        $this->tenants[$dbName] = $pdo;
        $this->currentTenantDb = $dbName;
        $_SESSION['acting_company']['db'] = $dbName;
        return $pdo;
    }

    public function getTenantPdo() {
        if ($this->currentTenantDb && isset($this->tenants[$this->currentTenantDb])) {
            return $this->tenants[$this->currentTenantDb];
        }
        // Try from session
        if (!empty($_SESSION['acting_company']['db'])) {
            return $this->setTenantDatabase($_SESSION['acting_company']['db']);
        }
        return null;
    }

    private function createPdoForDb($dbName) {
        // Connect to server and ensure DB exists
        $host = DatabaseConfig::getHost();
        $user = DatabaseConfig::getUsername();
        $pass = DatabaseConfig::getPassword();
        $charset = 'utf8mb4';
        $serverDsn = 'mysql:host=' . $host . ';charset=' . $charset;
        $pdo = new PDO($serverDsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        // Connect to that DB
        $dsn = 'mysql:host=' . $host . ';dbname=' . $dbName . ';charset=' . $charset;
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }

    private function installTenantSchemaIfNeeded(PDO $pdo) {
        // Create required fleet tables if missing (aligned with sql/schema.sql used by modules)
        $pdo->exec("CREATE TABLE IF NOT EXISTS vehicle_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category ENUM('vehicle', 'equipment') NOT NULL DEFAULT 'vehicle',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS vehicles (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS drivers (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Insurance table (lipsă în unele instalări tenant)
        $pdo->exec("CREATE TABLE IF NOT EXISTS insurance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_id INT NOT NULL,
            insurance_type VARCHAR(50) NOT NULL,
            policy_number VARCHAR(100),
            insurance_company VARCHAR(100),
            start_date DATE,
            expiry_date DATE,
            coverage_amount DECIMAL(12,2),
            premium_amount DECIMAL(12,2),
            deductible DECIMAL(12,2),
            payment_frequency VARCHAR(50),
            agent_name VARCHAR(100),
            agent_phone VARCHAR(30),
            agent_email VARCHAR(100),
            coverage_details TEXT,
            policy_file VARCHAR(255),
            status ENUM('active','inactive','cancelled','expired') DEFAULT 'active',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
            INDEX idx_expiry_date (expiry_date),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("CREATE TABLE IF NOT EXISTS maintenance (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS fuel_consumption (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            company_id INT NULL,
            type ENUM('document_expiry', 'maintenance_due', 'inspection_due', 'license_expiry', 'mileage_alert', 'cost_alert', 'general') NOT NULL,
            priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            vehicle_id INT NULL,
            driver_id INT NULL,
            related_id INT NULL,
            related_type VARCHAR(50) NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            target_date DATE,
            due_date DATE,
            status ENUM('pending', 'sent', 'acknowledged', 'dismissed', 'expired') DEFAULT 'pending',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            read_at TIMESTAMP NULL,
            action_url VARCHAR(255) NULL,
            notification_methods JSON,
            recipients JSON,
            sent_at TIMESTAMP NULL,
            acknowledged_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
            FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
            INDEX idx_status_date (status, due_date),
            INDEX idx_vehicle_notifications (vehicle_id),
            INDEX idx_type_priority (type, priority),
            INDEX idx_notifications_user_unread (user_id, is_read),
            INDEX idx_notifications_user_created (user_id, created_at),
            INDEX idx_notifications_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        // Seed default vehicle types if empty
        try {
            $count = (int)$pdo->query("SELECT COUNT(*) AS c FROM vehicle_types")->fetch()['c'];
            if ($count === 0) {
                $pdo->exec("INSERT INTO vehicle_types (name, category, description) VALUES
                ('Autoturism Personal', 'vehicle', 'Vehicule pentru transportul personalului'),
                ('Autoutilitară Mică', 'vehicle', 'Vehicule pentru transport marfă până la 3.5t'),
                ('Camion', 'vehicle', 'Vehicule pentru transport marfă peste 3.5t'),
                ('Autobus/Microbuz', 'vehicle', 'Vehicule pentru transportul pasagerilor'),
                ('Motostivuitor', 'equipment', 'Echipament pentru manevrarea mărfurilor'),
                ('Excavator', 'equipment', 'Echipament pentru construcții'),
                ('Buldozer', 'equipment', 'Echipament pentru construcții'),
                ('Trailer/Remorcă', 'equipment', 'Remorci pentru transport'),
                ('Utilaj Agricol', 'equipment', 'Tractoare și utilaje agricole'),
                ('Generator/Compresor', 'equipment', 'Echipamente auxiliare')");
            }
        } catch (Throwable $e) {
        }
    }

    // Fallback: dacă suntem pe modul multi-tenancy dar operăm pe baza core (ex. tenant DB nu există încă)
    // și lipsesc tabele flotă (vehicles, insurance etc), le creăm aici în baza core pentru a evita 1146.
    public function ensureFleetTablesOnCoreIfMissing() {
        $corePdo = $this->core;
        try {
            $exists = $corePdo->query("SHOW TABLES LIKE 'vehicles'")->fetch();
        } catch (Throwable $e) { $exists = false; }
        if ($exists) { return; } // deja create
        try {
            // Replicăm subsetul minim necesar (vehicle_types + vehicles + insurance + maintenance) pentru generator notificări
            $corePdo->exec("CREATE TABLE IF NOT EXISTS vehicle_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                category ENUM('vehicle', 'equipment') NOT NULL DEFAULT 'vehicle',
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $corePdo->exec("CREATE TABLE IF NOT EXISTS vehicles (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $corePdo->exec("CREATE TABLE IF NOT EXISTS insurance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                vehicle_id INT NOT NULL,
                insurance_type VARCHAR(50) NOT NULL,
                policy_number VARCHAR(100),
                insurance_company VARCHAR(100),
                start_date DATE,
                expiry_date DATE,
                coverage_amount DECIMAL(12,2),
                premium_amount DECIMAL(12,2),
                deductible DECIMAL(12,2),
                payment_frequency VARCHAR(50),
                agent_name VARCHAR(100),
                agent_phone VARCHAR(30),
                agent_email VARCHAR(100),
                coverage_details TEXT,
                policy_file VARCHAR(255),
                status ENUM('active','inactive','cancelled','expired') DEFAULT 'active',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
                INDEX idx_expiry_date (expiry_date),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $corePdo->exec("CREATE TABLE IF NOT EXISTS maintenance (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (Throwable $e) {
            // Dacă nu se poate crea, lăsăm generatorul să raporteze eroarea originală
        }
    }

    // Choose the proper PDO based on table location
    private function pdoForTable($table) {
        $t = strtolower($table);
        if (in_array($t, $this->coreTables, true)) { return $this->core; }
        // fleet data -> tenant DB
        $tenant = $this->getTenantPdo();
        return $tenant ?: $this->core; // fallback to core if no tenant selected
    }

    public function queryOn($table, $sql, $params = []) {
        $pdo = $this->pdoForTable($table);
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw $e;
        }
    }
    public function fetchOn($table, $sql, $params = []) { return $this->queryOn($table, $sql, $params)->fetch(); }
    public function fetchAllOn($table, $sql, $params = []) { return $this->queryOn($table, $sql, $params)->fetchAll(); }
    public function lastInsertIdOn($table) {
        $pdo = $this->pdoForTable($table);
        return $pdo->lastInsertId();
    }
}
?>
