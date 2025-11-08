<?php
// config/database.php
//
// Production-ready configuration with safe overrides:
// - Defaults are for local dev (WAMP). DO NOT commit secrets.
// - On server, you can either:
//   a) create config/database.override.php that returns an array with keys
//      host, db, user, pass, tenancy_mode
//   b) or set environment variables: FM_DB_HOST, FM_DB_NAME, FM_DB_USER, FM_DB_PASS, FM_TENANCY_MODE
//
class DatabaseConfig {
    // Local defaults (no secrets). These are used only if no overrides are provided.
    private const DEFAULT_HOST = 'localhost';
    private const DEFAULT_DB   = 'fleet_management';
    private const DEFAULT_USER = 'root';
    private const DEFAULT_PASS = '';
    // Multi-tenant mode: 'multi' (separate DB per company) or 'single' (one DB for all companies)
    private const DEFAULT_TENANCY_MODE = 'multi';

    private static $override = null; // cache override file content

    private static function loadOverride(): array {
        if (self::$override !== null) return self::$override;
        $file = __DIR__ . '/database.override.php';
        if (is_file($file)) {
            $data = include $file; // must return array
            if (is_array($data)) {
                return self::$override = $data;
            }
        }
        return self::$override = [];
    }

    public static function getHost(): string {
        $ovr = self::loadOverride();
        return $ovr['host'] ?? getenv('FM_DB_HOST') ?: self::DEFAULT_HOST;
    }
    public static function getDbName(): string {
        $ovr = self::loadOverride();
        return $ovr['db'] ?? getenv('FM_DB_NAME') ?: self::DEFAULT_DB;
    }
    public static function getUsername(): string {
        $ovr = self::loadOverride();
        return $ovr['user'] ?? getenv('FM_DB_USER') ?: self::DEFAULT_USER;
    }
    public static function getPassword(): string {
        $ovr = self::loadOverride();
        return $ovr['pass'] ?? getenv('FM_DB_PASS') ?: self::DEFAULT_PASS;
    }
    public static function getTenancyMode(): string {
        $ovr = self::loadOverride();
        $mode = $ovr['tenancy_mode'] ?? getenv('FM_TENANCY_MODE') ?: self::DEFAULT_TENANCY_MODE;
        return strtolower($mode) === 'single' ? 'single' : 'multi';
    }

    // Optional: cPanel-style DB name prefix (e.g., 'wclsgzyf_')
    public static function getTenantDbPrefix(): string {
        $ovr = self::loadOverride();
        $prefix = $ovr['tenant_db_prefix'] ?? getenv('FM_TENANT_DB_PREFIX') ?: '';
        // Ensure only safe chars
        return preg_replace('/[^a-zA-Z0-9_]/', '', $prefix);
    }

    public static function getConnection() {
        try {
            $dsn = 'mysql:host=' . self::getHost() . ';dbname=' . self::getDbName() . ';charset=utf8mb4';
            $pdo = new PDO(
                $dsn,
                self::getUsername(),
                self::getPassword(),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            die('Conexiune eșuată la baza de date: ' . $e->getMessage());
        }
    }
}