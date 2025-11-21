<?php
/**
 * WorkOrderController
 * Controller pentru gestionare ordine de lucru (service intern)
 * 
 * @package FleetManagement
 * @subpackage Service
 * @version 1.0
 */

// Dependencies are already loaded by index.php
// require_once __DIR__ . '/../../../core/Controller.php';
// require_once __DIR__ . '/../../../core/Auth.php';
// require_once __DIR__ . '/../models/WorkOrder.php';
// require_once __DIR__ . '/../models/Service.php';

class WorkOrderController extends Controller {
    private $workOrderModel;
    private $serviceModel;
    private $auth;
    
    public function __construct() {
        parent::__construct();
        $this->workOrderModel = new WorkOrder();
        $this->serviceModel = new Service();
        $this->auth = new Auth();
        
        if (!$this->auth->isLoggedIn()) {
            $this->redirect('/auth/login');
        }
    }
    
    protected function getModuleName() {
        return 'service';
    }
    
    /**
     * Dashboard atelier - Listă ordine de lucru
     */
    public function index() {
        $tenantId = $this->auth->getTenantId();
        
        // Verificăm dacă există service intern
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        if (!$internalService) {
            $_SESSION['error_message'] = 'Nu aveți configurat un service intern. Activați mai întâi modulul de service intern.';
            $this->redirect('/service/services/internal-setup');
        }
        
        // Filtre
        $filters = [
            'status' => $_GET['status'] ?? null,
            'priority' => $_GET['priority'] ?? null,
            'mechanic_id' => $_GET['mechanic_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];
        
        // Eliminăm filtrele goale
        $filters = array_filter($filters);
        
        // Obținere ordine de lucru
        $workOrders = $this->workOrderModel->getAllByTenant($tenantId, $filters, 50);
        
        // Statistici dashboard
        $stats = $this->workOrderModel->getWorkshopStats($tenantId, $internalService['id']);
        
        // Obținere mecanici pentru filtru
        $sql = "SELECT * FROM service_mechanics 
                WHERE tenant_id = ? AND service_id = ? AND is_active = 1 
                ORDER BY name";
        $mechanics = $this->db->fetchAllOn('service_mechanics', $sql, [$tenantId, $internalService['id']]);
        
        $this->render('workshop/dashboard', [
            'pageTitle' => 'Dashboard Atelier',
            'workOrders' => $workOrders,
            'stats' => $stats,
            'mechanics' => $mechanics,
            'filters' => $filters,
            'service' => $internalService
        ]);
    }
    
    /**
     * Formular creare ordine de lucru nouă
     */
    public function add() {
        if (!$this->auth->isAdmin()) {
            $this->redirect('/service/workshop');
        }
        
        $tenantId = $this->auth->getTenantId();
        $userId = $this->auth->getUserId();
        
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        if (!$internalService) {
            $_SESSION['error_message'] = 'Service intern nu este configurat!';
            $this->redirect('/service/services/internal-setup');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'tenant_id' => $tenantId,
                    'vehicle_id' => $_POST['vehicle_id'] ?? null,
                    'service_id' => $internalService['id'],
                    'entry_date' => $_POST['entry_date'] ?? date('Y-m-d H:i:s'),
                    'odometer_reading' => $_POST['odometer_reading'] ?? null,
                    'assigned_mechanic_id' => $_POST['assigned_mechanic_id'] ?? null,
                    'priority' => $_POST['priority'] ?? 'normal',
                    'work_description' => $_POST['work_description'] ?? '',
                    'customer_notes' => $_POST['customer_notes'] ?? '',
                    'estimated_hours' => $_POST['estimated_hours'] ?? null,
                    'estimated_completion' => $_POST['estimated_completion'] ?? null,
                    'created_by' => $userId
                ];
                
                if (!$data['vehicle_id']) {
                    throw new Exception('Trebuie să selectați un vehicul');
                }
                
                $workOrderId = $this->workOrderModel->createWorkOrder($data);
                
                // Adăugare checklist implicit dacă există
                if (isset($_POST['checklist_items']) && is_array($_POST['checklist_items'])) {
                    foreach ($_POST['checklist_items'] as $item) {
                        if (!empty($item)) {
                            $this->workOrderModel->addChecklistItem($workOrderId, [
                                'item' => $item,
                                'is_checked' => 0,
                                'status' => 'ok'
                            ]);
                        }
                    }
                }
                
                $_SESSION['success_message'] = 'Ordine de lucru creată cu succes!';
                $this->redirect('/service/workshop/view/' . $workOrderId);
                
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
            }
        }
        
        // Obținere vehicule pentru dropdown
        $sql = "SELECT id, plate_number, make, model FROM vehicles 
                WHERE tenant_id = ? AND is_active = 1 
                ORDER BY plate_number";
        $vehicles = $this->db->fetchAllOn('vehicles', $sql, [$tenantId]);
        
        // Obținere mecanici
        $sql = "SELECT * FROM service_mechanics 
                WHERE tenant_id = ? AND service_id = ? AND is_active = 1 
                ORDER BY name";
        $mechanics = $this->db->fetchAllOn('service_mechanics', $sql, [$tenantId, $internalService['id']]);
        
        $this->render('workshop/work_order_add', [
            'pageTitle' => 'Ordine de Lucru Nouă',
            'vehicles' => $vehicles,
            'mechanics' => $mechanics,
            'service' => $internalService,
            'defaultChecklist' => $this->getDefaultChecklist()
        ]);
    }
    
    /**
     * Vizualizare detalii ordine de lucru
     */
    public function view($id) {
        $tenantId = $this->auth->getTenantId();
        
        $workOrder = $this->workOrderModel->getWorkOrderDetails($id, $tenantId);
        
        if (!$workOrder) {
            $this->error404('Ordine de lucru nu a fost găsită');
        }
        
        // Obținere mecanici pentru alocare
        $sql = "SELECT * FROM service_mechanics 
                WHERE tenant_id = ? AND is_active = 1 
                ORDER BY name";
        $mechanics = $this->db->fetchAllOn('service_mechanics', $sql, [$tenantId]);
        
        $this->render('workshop/work_order_view', [
            'pageTitle' => 'Ordine de Lucru ' . $workOrder['work_order_number'],
            'workOrder' => $workOrder,
            'mechanics' => $mechanics,
            'userRole' => $this->auth->getUserRole()
        ]);
    }
    
    /**
     * Editare ordine de lucru
     */
    public function edit($id) {
        if (!$this->auth->isAdmin()) {
            $this->redirect('/service/workshop/view/' . $id);
        }
        
        $tenantId = $this->auth->getTenantId();
        
        $workOrder = $this->workOrderModel->getWorkOrderDetails($id, $tenantId);
        
        if (!$workOrder) {
            $this->error404('Ordine de lucru nu a fost găsită');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $updateData = [
                    'assigned_mechanic_id' => $_POST['assigned_mechanic_id'] ?? null,
                    'priority' => $_POST['priority'] ?? $workOrder['priority'],
                    'status' => $_POST['status'] ?? $workOrder['status'],
                    'work_description' => $_POST['work_description'] ?? '',
                    'diagnosis' => $_POST['diagnosis'] ?? '',
                    'internal_notes' => $_POST['internal_notes'] ?? '',
                    'estimated_hours' => $_POST['estimated_hours'] ?? null,
                    'estimated_completion' => $_POST['estimated_completion'] ?? null
                ];
                
                $columns = [];
                $values = [];
                foreach ($updateData as $key => $value) {
                    $columns[] = "$key = ?";
                    $values[] = $value;
                }
                $values[] = $id;
                
                $sql = "UPDATE work_orders SET " . implode(', ', $columns) . " WHERE id = ?";
                $this->db->queryOn('work_orders', $sql, $values);
                
                $_SESSION['success_message'] = 'Ordine actualizată cu succes!';
                $this->redirect('/service/workshop/view/' . $id);
                
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
            }
        }
        
        // Obținere mecanici
        $sql = "SELECT * FROM service_mechanics 
                WHERE tenant_id = ? AND is_active = 1 
                ORDER BY name";
        $mechanics = $this->db->fetchAllOn('service_mechanics', $sql, [$tenantId]);
        
        $this->render('workshop/work_order_edit', [
            'pageTitle' => 'Editare Ordine ' . $workOrder['work_order_number'],
            'workOrder' => $workOrder,
            'mechanics' => $mechanics
        ]);
    }
    
    /**
     * Actualizare status ordine (AJAX)
     */
    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metodă invalidă'], 405);
        }
        
        $tenantId = $this->auth->getTenantId();
        $id = $_POST['work_order_id'] ?? null;
        $status = $_POST['status'] ?? null;
        
        if (!$id || !$status) {
            $this->json(['success' => false, 'message' => 'Date incomplete'], 400);
        }
        
        try {
            $this->workOrderModel->updateStatus($id, $status, $tenantId);
            $this->json(['success' => true, 'message' => 'Status actualizat']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Alocare mecanic (AJAX)
     */
    public function assignMechanic() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metodă invalidă'], 405);
        }
        
        $tenantId = $this->auth->getTenantId();
        $workOrderId = $_POST['work_order_id'] ?? null;
        $mechanicId = $_POST['mechanic_id'] ?? null;
        
        if (!$workOrderId || !$mechanicId) {
            $this->json(['success' => false, 'message' => 'Date incomplete'], 400);
        }
        
        try {
            $this->workOrderModel->assignMechanic($workOrderId, $mechanicId, $tenantId);
            $this->json(['success' => true, 'message' => 'Mecanic alocat']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Adăugare piesă (AJAX)
     */
    public function addPart() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metodă invalidă'], 405);
        }
        
        $workOrderId = $_POST['work_order_id'] ?? null;
        
        if (!$workOrderId) {
            $this->json(['success' => false, 'message' => 'ID ordine lipsă'], 400);
        }
        
        try {
            $partData = [
                'part_name' => $_POST['part_name'] ?? '',
                'part_number' => $_POST['part_number'] ?? '',
                'quantity' => $_POST['quantity'] ?? 1,
                'unit_price' => $_POST['unit_price'] ?? 0,
                'supplier' => $_POST['supplier'] ?? '',
                'notes' => $_POST['notes'] ?? ''
            ];
            
            $partId = $this->workOrderModel->addPart($workOrderId, $partData);
            
            $this->json([
                'success' => true,
                'message' => 'Piesă adăugată',
                'part_id' => $partId
            ]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Start task manoperă (AJAX)
     */
    public function startLabor() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metodă invalidă'], 405);
        }
        
        $workOrderId = $_POST['work_order_id'] ?? null;
        $mechanicId = $_POST['mechanic_id'] ?? null;
        
        if (!$workOrderId || !$mechanicId) {
            $this->json(['success' => false, 'message' => 'Date incomplete'], 400);
        }
        
        try {
            $taskData = [
                'task_description' => $_POST['task_description'] ?? ''
            ];
            
            $laborId = $this->workOrderModel->startLaborTask($workOrderId, $mechanicId, $taskData);
            
            $this->json([
                'success' => true,
                'message' => 'Cronometru pornit',
                'labor_id' => $laborId,
                'start_time' => date('H:i:s')
            ]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Stop task manoperă (AJAX)
     */
    public function endLabor() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metodă invalidă'], 405);
        }
        
        $laborId = $_POST['labor_id'] ?? null;
        
        if (!$laborId) {
            $this->json(['success' => false, 'message' => 'ID labor lipsă'], 400);
        }
        
        try {
            $this->workOrderModel->endLaborTask($laborId);
            
            // Obținem datele actualizate
            $sql = "SELECT * FROM work_order_labor WHERE id = ?";
            $labor = $this->db->fetchOn('work_order_labor', $sql, [$laborId]);
            
            $this->json([
                'success' => true,
                'message' => 'Cronometru oprit',
                'hours_worked' => $labor['hours_worked'],
                'labor_cost' => $labor['labor_cost']
            ]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Actualizare item checklist (AJAX)
     */
    public function updateChecklist() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metodă invalidă'], 405);
        }
        
        $itemId = $_POST['item_id'] ?? null;
        
        if (!$itemId) {
            $this->json(['success' => false, 'message' => 'ID item lipsă'], 400);
        }
        
        try {
            $data = [
                'is_checked' => isset($_POST['is_checked']) ? (int)$_POST['is_checked'] : 0,
                'status' => $_POST['status'] ?? 'ok',
                'notes' => $_POST['notes'] ?? ''
            ];
            
            $this->workOrderModel->updateChecklistItem($itemId, $data);
            
            $this->json(['success' => true, 'message' => 'Checklist actualizat']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Vehicule în atelier (vizualizare rapidă)
     */
    public function vehiclesInService() {
        $tenantId = $this->auth->getTenantId();
        
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        if (!$internalService) {
            $this->redirect('/service/services/internal-setup');
        }
        
        $workOrders = $this->workOrderModel->getActiveWorkOrders($tenantId, $internalService['id']);
        
        $this->render('workshop/vehicles_in_service', [
            'pageTitle' => 'Vehicule în Atelier',
            'workOrders' => $workOrders,
            'service' => $internalService
        ]);
    }
    
    /**
     * Helper: Checklist implicit
     */
    private function getDefaultChecklist() {
        return [
            'Verificare nivel ulei motor',
            'Verificare nivel lichid frână',
            'Verificare nivel antigel',
            'Verificare uzură plăcuțe frână',
            'Verificare discuri frână',
            'Verificare presiune anvelope',
            'Verificare lumini (toate)',
            'Verificare curele transmisie',
            'Test funcțional climatizare',
            'Verificare baterie',
            'Verificare lichid servo',
            'Verificare sistem evacuare'
        ];
    }
}
