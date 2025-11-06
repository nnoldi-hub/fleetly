<?php

class Company extends Model {
    protected $table = 'companies';
    private $conn;
    
    public function __construct() {
        parent::__construct();
        $this->conn = Database::getInstance()->getConnection();
    }
    
    /**
     * Reset the admin account for a company (change password and optionally username/email)
     * Returns ['success'=>true, 'username'=>..., 'email'=>..., 'password'=>...] on success
     */
    public function resetAdminAccount(int $companyId, ?string $newUsername = null, ?string $newEmail = null, ?string $newPassword = null): array {
        try {
            $pdo = $this->conn; // core DB

            // Discover schema capabilities
            $hasRoleCol = false; $hasRoleIdCol = false; $hasRolesTable = false;
            try {
                $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
                if ($col && $col->fetch()) { $hasRoleCol = true; }
            } catch (Throwable $e) { /* ignore */ }
            try {
                $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'role_id'");
                if ($col && $col->fetch()) { $hasRoleIdCol = true; }
            } catch (Throwable $e) { /* ignore */ }
            try {
                $tbl = $pdo->query("SHOW TABLES LIKE 'roles'");
                if ($tbl && $tbl->fetch()) { $hasRolesTable = true; }
            } catch (Throwable $e) { /* ignore */ }

            // Attempt to locate an admin user
            $user = null;
            // Prefer roles join if available (role_id + roles table)
            if ($hasRoleIdCol && $hasRolesTable) {
                try {
                    $sql = "SELECT u.*
                            FROM users u
                            LEFT JOIN roles r ON u.role_id = r.id
                            WHERE u.company_id = :cid AND (r.slug = 'admin' OR LOWER(r.name) = 'admin')
                            ORDER BY u.id ASC
                            LIMIT 1";
                    $stmt2 = $pdo->prepare($sql);
                    $stmt2->execute(['cid' => $companyId]);
                    $user = $stmt2->fetch(PDO::FETCH_ASSOC);
                } catch (Throwable $inner) {
                    error_log('[Company::resetAdminAccount] roles join lookup failed: ' . $inner->getMessage());
                }
            }

            // If not found and schema has a plain 'role' column, try enum/text role
            if (!$user && $hasRoleCol) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE company_id = :cid AND role = 'admin' ORDER BY id ASC LIMIT 1");
                    $stmt->execute(['cid' => $companyId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (Throwable $e) {
                    error_log('[Company::resetAdminAccount] enum role lookup failed: ' . $e->getMessage());
                }
            }

            if (!$user) {
                // No admin found: create one as a fallback
                // Choose password: provided or generated (do early to reuse later)
                if ($newPassword !== null && $newPassword !== '') {
                    if (strlen($newPassword) < 6) {
                        return ['success' => false, 'message' => 'Parola trebuie să aibă minim 6 caractere.'];
                    }
                    $plain = $newPassword;
                } else {
                    $plain = bin2hex(random_bytes(5)); // 10 hex chars
                }
                $hash = password_hash($plain, PASSWORD_BCRYPT);

                $username = $newUsername && $newUsername !== '' ? $newUsername : ('admin' . $companyId);
                $email = $newEmail && $newEmail !== '' ? $newEmail : ('admin+' . $companyId . '@example.com');

                try {
                    if ($hasRoleIdCol) {
                        // Try to fetch admin role id if roles table exists
                        $roleId = null;
                        if ($hasRolesTable) {
                            $r = $pdo->query("SELECT id FROM roles WHERE slug = 'admin' OR LOWER(name) = 'admin' LIMIT 1");
                            $row = $r ? $r->fetch(PDO::FETCH_ASSOC) : null;
                            if ($row && isset($row['id'])) { $roleId = (int)$row['id']; }
                        }
                        $stmtIns = $pdo->prepare("INSERT INTO users (company_id, role_id, username, email, password_hash, first_name, last_name, status) VALUES (:cid, :rid, :un, :em, :ph, 'Administrator', 'Companie', 'active')");
                        $stmtIns->execute([
                            'cid' => $companyId,
                            'rid' => $roleId,
                            'un'  => $username,
                            'em'  => $email,
                            'ph'  => $hash,
                        ]);
                    } else {
                        // Fallback schema: if role column exists, set it, otherwise omit
                        if ($hasRoleCol) {
                            $stmtIns = $pdo->prepare("INSERT INTO users (company_id, role, username, email, password_hash, first_name, last_name, status) VALUES (:cid, 'admin', :un, :em, :ph, 'Administrator', 'Companie', 'active')");
                        } else {
                            $stmtIns = $pdo->prepare("INSERT INTO users (company_id, username, email, password_hash, first_name, last_name, status) VALUES (:cid, :un, :em, :ph, 'Administrator', 'Companie', 'active')");
                        }
                        $stmtIns->execute([
                            'cid' => $companyId,
                            'un'  => $username,
                            'em'  => $email,
                            'ph'  => $hash,
                        ]);
                    }

                    $newId = (int)$pdo->lastInsertId();
                    $stmtFetch = $pdo->prepare("SELECT * FROM users WHERE id = :id");
                    $stmtFetch->execute(['id' => $newId]);
                    $user = $stmtFetch->fetch(PDO::FETCH_ASSOC);

                    // Audit creation
                    Auth::getInstance()->logAudit(
                        Auth::getInstance()->user()->id ?? null,
                        $companyId,
                        'create',
                        'user',
                        $newId,
                        null,
                        ['auto_created_admin' => true]
                    );

                    // Return immediately with the created credentials
                    return [
                        'success' => true,
                        'username' => $user['username'] ?? $username,
                        'email' => $user['email'] ?? $email,
                        'password' => $plain,
                    ];
                } catch (Throwable $ce) {
                    error_log('[Company::resetAdminAccount] failed to auto-create admin: ' . $ce->getMessage());
                    return ['success' => false, 'message' => 'Nu a fost găsit contul admin și nu s-a putut crea automat.'];
                }
            }

            // Build updates
            $updates = [];
            $params = [];
            if ($newUsername !== null && $newUsername !== '') {
                $updates[] = 'username = :username';
                $params['username'] = $newUsername;
            }
            if ($newEmail !== null && $newEmail !== '') {
                $updates[] = 'email = :email';
                $params['email'] = $newEmail;
            }

            // Choose password: provided or generated
            if ($newPassword !== null && $newPassword !== '') {
                if (strlen($newPassword) < 6) {
                    return ['success' => false, 'message' => 'Parola trebuie să aibă minim 6 caractere.'];
                }
                $plain = $newPassword;
            } else {
                // Generate a new strong password (human-friendly length)
                $plain = bin2hex(random_bytes(5)); // 10 hex chars
            }
            $hash = password_hash($plain, PASSWORD_BCRYPT);
            $updates[] = 'password_hash = :ph';
            $params['ph'] = $hash;

            // Ensure status active
            $updates[] = "status = 'active'";

            if (empty($updates)) {
                return ['success' => false, 'message' => 'Nicio modificare de aplicat.'];
            }

            $params['id'] = (int)$user['id'];
            $sqlU = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $pdo->prepare($sqlU)->execute($params);

            // Audit
            Auth::getInstance()->logAudit(
                Auth::getInstance()->user()->id ?? null,
                $companyId,
                'update',
                'user',
                (int)$user['id'],
                null,
                ['reset_admin_account' => true]
            );

            return [
                'success' => true,
                'username' => $newUsername ?: $user['username'] ?? null,
                'email' => $newEmail ?: $user['email'] ?? null,
                'password' => $plain,
            ];
        } catch (Throwable $e) {
            error_log('[Company::resetAdminAccount] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Eroare la resetarea contului admin.'];
        }
    }

    /**
     * Get all companies (SuperAdmin only)
     */
    public function getAll($filters = []) {
    // Pentru compatibilitate, nu facem join cu vehicles (în unele instalări nu există company_id pe vehicles)
    $sql = "SELECT c.*, 
        COUNT(DISTINCT u.id) as users_count,
        0 as vehicles_count
        FROM companies c
        LEFT JOIN users u ON c.id = u.company_id AND u.status = 'active'
        WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['subscription_type'])) {
            $sql .= " AND c.subscription_type = ?";
            $params[] = $filters['subscription_type'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.registration_number LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            error_log('[Company::getAll] ' . $e->getMessage());
            return [];
        }
    }

    // not used currently: hasColumn helper removed for simplicity/compatibility
    
    /**
     * Get company by ID
     */
    public function getById($id) {
        try {
            // Fără join cu vehicles pentru compatibilitate cu scheme vechi
            $stmt = $this->conn->prepare(
                "SELECT c.*, creator.username as created_by_username
                 FROM companies c
                 LEFT JOIN users creator ON c.created_by = creator.id
                 WHERE c.id = ?"
            );
            $stmt->execute([$id]);
            $company = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$company) return null;

            // Adăugăm număr utilizatori activi
            $uc = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE company_id = ? AND status = 'active'");
            $uc->execute([$id]);
            $company->users_count = (int)$uc->fetchColumn();

            // Fără relație vehicles->company în schema veche
            $company->vehicles_count = 0;
            return $company;
        } catch (Throwable $e) {
            error_log('[Company::getById] ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new company
     */
    public function create($data) {
        try {
            $this->conn->beginTransaction();
            
            // Create company
            $stmt = $this->conn->prepare("
                INSERT INTO companies 
                (name, registration_number, email, phone, address, city, country, 
                 subscription_type, max_users, max_vehicles, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['registration_number'] ?? null,
                $data['email'],
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['country'] ?? 'România',
                $data['subscription_type'] ?? 'basic',
                $data['max_users'] ?? 5,
                $data['max_vehicles'] ?? 10,
                $data['created_by'] ?? null
            ]);
            
            $companyId = $this->conn->lastInsertId();
            
            // Create admin user for company
            if (!empty($data['admin_email'])) {
                $adminPassword = $data['admin_password'] ?? bin2hex(random_bytes(8));
                
                // Get admin role ID
                $roleStmt = $this->conn->prepare("SELECT id FROM roles WHERE slug = 'admin' AND is_system = 1");
                $roleStmt->execute();
                $adminRole = $roleStmt->fetch(PDO::FETCH_OBJ);
                
                $userStmt = $this->conn->prepare("
                    INSERT INTO users 
                    (company_id, role_id, username, email, password_hash, first_name, last_name, status, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)
                ");
                
                $userStmt->execute([
                    $companyId,
                    $adminRole->id,
                    $data['admin_username'] ?? explode('@', $data['admin_email'])[0],
                    $data['admin_email'],
                    password_hash($adminPassword, PASSWORD_BCRYPT),
                    $data['admin_first_name'] ?? 'Administrator',
                    $data['admin_last_name'] ?? $data['name'],
                    $data['created_by'] ?? null
                ]);
                
                $data['admin_generated_password'] = $adminPassword;
            }
            
            $this->conn->commit();
            
            // Ensure tenant database is created for this company (with schema and defaults)
            try {
                Database::getInstance()->setTenantDatabaseByCompanyId($companyId);
            } catch (Throwable $e) {
                error_log('[Company::create] Failed to initialize tenant DB for company ' . $companyId . ' | ' . $e->getMessage());
            }

            // Log audit
            Auth::getInstance()->logAudit(
                $data['created_by'] ?? null,
                $companyId,
                'create',
                'company',
                $companyId,
                null,
                $data
            );
            
            return [
                'success' => true, 
                'company_id' => $companyId,
                'admin_password' => $data['admin_generated_password'] ?? null
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Company creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Eroare la crearea companiei'];
        }
    }
    
    /**
     * Update company
     */
    public function update($id, $data) {
        try {
            // Get old data for audit
            $oldData = $this->getById($id);
            
            $stmt = $this->conn->prepare("
                UPDATE companies 
                SET name = ?, registration_number = ?, email = ?, phone = ?, 
                    address = ?, city = ?, country = ?, status = ?,
                    subscription_type = ?, max_users = ?, max_vehicles = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['name'],
                $data['registration_number'] ?? null,
                $data['email'],
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['country'] ?? 'România',
                $data['status'] ?? 'active',
                $data['subscription_type'] ?? 'basic',
                $data['max_users'] ?? 5,
                $data['max_vehicles'] ?? 10,
                $id
            ]);
            
            // Log audit
            Auth::getInstance()->logAudit(
                Auth::getInstance()->user()->id ?? null,
                $id,
                'update',
                'company',
                $id,
                (array)$oldData,
                $data
            );
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log("Company update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Eroare la actualizarea companiei'];
        }
    }
    
    /**
     * Delete company
     */
    public function delete($id) {
        try {
            $company = $this->getById($id);
            
            $stmt = $this->conn->prepare("DELETE FROM companies WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log audit
            Auth::getInstance()->logAudit(
                Auth::getInstance()->user()->id ?? null,
                null,
                'delete',
                'company',
                $id,
                (array)$company,
                null
            );
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log("Company deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Eroare la È™tergerea companiei'];
        }
    }
    
    /**
     * Check subscription status
     */
    public function checkSubscription($companyId) {
        $company = $this->getById($companyId);
        
        if (!$company) {
            return ['active' => false, 'reason' => 'company_not_found'];
        }
        
        if ($company->status !== 'active') {
            return ['active' => false, 'reason' => 'company_suspended'];
        }
        
        if ($company->subscription_expires_at && strtotime($company->subscription_expires_at) < time()) {
            return ['active' => false, 'reason' => 'subscription_expired'];
        }
        
        return ['active' => true, 'company' => $company];
    }
    
    /**
     * Get company statistics
     */
    public function getStats($companyId) {
        $stmt = $this->conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM users WHERE company_id = ? AND status = 'active') as active_users,
                (SELECT COUNT(*) FROM vehicles WHERE company_id = ?) as total_vehicles,
                (SELECT COUNT(*) FROM vehicles WHERE company_id = ? AND status = 'active') as active_vehicles,
                (SELECT COUNT(*) FROM drivers WHERE company_id = ? AND status = 'active') as active_drivers,
                (SELECT COUNT(*) FROM maintenance WHERE company_id = ? AND status = 'pending') as pending_maintenance,
                (SELECT COUNT(*) FROM documents WHERE company_id = ? AND expiry_date < DATE_ADD(NOW(), INTERVAL 30 DAY)) as expiring_documents
        ");
        
        $stmt->execute([$companyId, $companyId, $companyId, $companyId, $companyId, $companyId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}
