<?php
/**
 * MechanicController
 * Controller pentru gestionare mecanici (personal atelier intern)
 * 
 * @package FleetManagement
 * @subpackage Service
 * @version 1.0
 */

class MechanicController extends Controller {
    private $auth;
    private $serviceModel;
    
    public function __construct() {
        parent::__construct();
        Auth::getInstance()->requireAuth();
        $this->auth = Auth::getInstance();
        $this->serviceModel = new Service();
    }
    
    protected function getModuleName() {
        return 'service';
    }
    
    /**
     * Listă mecanici
     */
    public function index() {
        $tenantId = $this->auth->getTenantId();
        
        // Verificăm dacă există service intern
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        if (!$internalService) {
            $_SESSION['error_message'] = 'Nu aveți configurat un service intern.';
            $this->redirect('/service/services');
        }
        
        // Obținere mecanici
        $sql = "SELECT sm.*, 
                       (SELECT COUNT(*) FROM work_orders wo 
                        WHERE wo.assigned_mechanic_id = sm.id 
                        AND wo.status IN ('pending', 'in_progress')) as active_work_orders
                FROM service_mechanics sm
                WHERE sm.tenant_id = ? AND sm.service_id = ?
                ORDER BY sm.name";
        
        $mechanics = $this->db->fetchAllOn('service_mechanics', $sql, [$tenantId, $internalService['id']]);
        
        $this->render('mechanics/index', [
            'pageTitle' => 'Mecanici Atelier',
            'mechanics' => $mechanics,
            'service' => $internalService
        ]);
    }
    
    /**
     * Formular adăugare mecanic
     */
    public function add() {
        if (!$this->auth->isAdmin()) {
            $this->redirect('/service/mechanics');
        }
        
        $tenantId = $this->auth->getTenantId();
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        if (!$internalService) {
            $_SESSION['error_message'] = 'Service intern nu este configurat!';
            $this->redirect('/service/services/internal-setup');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'tenant_id' => $tenantId,
                    'service_id' => $internalService['id'],
                    'name' => $_POST['name'] ?? '',
                    'specialization' => $_POST['specialization'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'hourly_rate' => $_POST['hourly_rate'] ?? 0,
                    'hire_date' => $_POST['hire_date'] ?? date('Y-m-d'),
                    'is_active' => isset($_POST['is_active']) ? 1 : 1
                ];
                
                if (empty($data['name'])) {
                    throw new Exception('Numele este obligatoriu');
                }
                
                $sql = "INSERT INTO service_mechanics 
                        (tenant_id, service_id, name, specialization, phone, email, hourly_rate, hire_date, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $this->db->queryOn('service_mechanics', $sql, [
                    $data['tenant_id'],
                    $data['service_id'],
                    $data['name'],
                    $data['specialization'],
                    $data['phone'],
                    $data['email'],
                    $data['hourly_rate'],
                    $data['hire_date'],
                    $data['is_active']
                ]);
                
                $_SESSION['success_message'] = 'Mecanic adăugat cu succes!';
                $this->redirect('/service/mechanics');
                
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
            }
        }
        
        $this->render('mechanics/add', [
            'pageTitle' => 'Adaugă Mecanic',
            'service' => $internalService
        ]);
    }
    
    /**
     * Formular editare mecanic
     */
    public function edit() {
        if (!$this->auth->isAdmin()) {
            $this->redirect('/service/mechanics');
        }
        
        $id = $_GET['id'] ?? 0;
        $tenantId = $this->auth->getTenantId();
        
        $sql = "SELECT * FROM service_mechanics WHERE id = ? AND tenant_id = ?";
        $mechanic = $this->db->fetchOn('service_mechanics', $sql, [$id, $tenantId]);
        
        if (!$mechanic) {
            $this->error404('Mecanic nu a fost găsit');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'name' => $_POST['name'] ?? $mechanic['name'],
                    'specialization' => $_POST['specialization'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'hourly_rate' => $_POST['hourly_rate'] ?? 0,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];
                
                $sql = "UPDATE service_mechanics 
                        SET name = ?, specialization = ?, phone = ?, email = ?, 
                            hourly_rate = ?, is_active = ?
                        WHERE id = ? AND tenant_id = ?";
                
                $this->db->queryOn('service_mechanics', $sql, [
                    $data['name'],
                    $data['specialization'],
                    $data['phone'],
                    $data['email'],
                    $data['hourly_rate'],
                    $data['is_active'],
                    $id,
                    $tenantId
                ]);
                
                $_SESSION['success_message'] = 'Mecanic actualizat cu succes!';
                $this->redirect('/service/mechanics');
                
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
            }
        }
        
        $this->render('mechanics/edit', [
            'pageTitle' => 'Editare Mecanic',
            'mechanic' => $mechanic
        ]);
    }
    
    /**
     * Vizualizare detalii mecanic
     */
    public function view() {
        $id = $_GET['id'] ?? 0;
        $tenantId = $this->auth->getTenantId();
        
        $sql = "SELECT sm.* FROM service_mechanics sm
                WHERE sm.id = ? AND sm.tenant_id = ?";
        $mechanic = $this->db->fetchOn('service_mechanics', $sql, [$id, $tenantId]);
        
        if (!$mechanic) {
            $this->error404('Mecanic nu a fost găsit');
        }
        
        // Obținere ordine active
        $sql = "SELECT wo.*, v.plate_number, v.make, v.model
                FROM work_orders wo
                JOIN vehicles v ON wo.vehicle_id = v.id
                WHERE wo.assigned_mechanic_id = ? AND wo.status IN ('pending', 'in_progress')
                ORDER BY wo.priority DESC, wo.entry_date ASC";
        $activeOrders = $this->db->fetchAllOn('work_orders', $sql, [$id]);
        
        // Statistici
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    AVG(CASE WHEN status = 'completed' THEN actual_hours ELSE NULL END) as avg_hours
                FROM work_orders
                WHERE assigned_mechanic_id = ?";
        $stats = $this->db->fetchOn('work_orders', $sql, [$id]);
        
        $this->render('mechanics/view', [
            'pageTitle' => $mechanic['name'],
            'mechanic' => $mechanic,
            'activeOrders' => $activeOrders,
            'stats' => $stats
        ]);
    }
    
    /**
     * Dezactivare mecanic
     */
    public function delete() {
        if (!$this->auth->isAdmin()) {
            $this->json(['success' => false, 'message' => 'Acces interzis'], 403);
        }
        
        $id = $_GET['id'] ?? 0;
        $tenantId = $this->auth->getTenantId();
        
        try {
            // Soft delete
            $sql = "UPDATE service_mechanics SET is_active = 0 WHERE id = ? AND tenant_id = ?";
            $this->db->queryOn('service_mechanics', $sql, [$id, $tenantId]);
            
            $_SESSION['success_message'] = 'Mecanic dezactivat cu succes!';
            $this->redirect('/service/mechanics');
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
            $this->redirect('/service/mechanics');
        }
    }
}
