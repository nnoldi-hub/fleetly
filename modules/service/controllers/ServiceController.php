<?php
/**
 * ServiceController
 * Controller pentru gestionare service-uri (partenere și interne)
 * 
 * @package FleetManagement
 * @subpackage Service
 * @version 1.0
 */

// Dependencies are already loaded by index.php
// require_once __DIR__ . '/../../../core/Controller.php';
// require_once __DIR__ . '/../../../core/Auth.php';
// require_once __DIR__ . '/../models/Service.php';

class ServiceController extends Controller {
    private $serviceModel;
    private $auth;
    
    public function __construct() {
        parent::__construct();
        $this->serviceModel = new Service();
        $this->auth = new Auth();
        
        // Verificare autentificare
        if (!$this->auth->isLoggedIn()) {
            $this->redirect('/auth/login');
        }
    }
    
    protected function getModuleName() {
        return 'service';
    }
    
    /**
     * Pagina principală - listă service-uri
     */
    public function index() {
        $tenantId = $this->auth->getTenantId();
        $userRole = $this->auth->getUserRole();
        
        // Filtre din GET
        $type = $_GET['type'] ?? 'all'; // all, internal, external
        $search = $_GET['search'] ?? '';
        
        // Obținere service-uri
        if (!empty($search)) {
            $services = $this->serviceModel->searchServices($tenantId, $search, $type);
        } else {
            $services = $this->serviceModel->getAllByTenant($tenantId, $type, false);
        }
        
        // Verificare dacă există service intern
        $hasInternal = $this->serviceModel->hasInternalService($tenantId);
        
        $this->render('services/index', [
            'pageTitle' => 'Service-uri',
            'services' => $services,
            'hasInternal' => $hasInternal,
            'currentType' => $type,
            'searchTerm' => $search,
            'userRole' => $userRole
        ]);
    }
    
    /**
     * Formular adăugare service nou
     */
    public function add() {
        // Doar admin poate adăuga service-uri
        if (!$this->auth->isAdmin()) {
            $this->redirect('/service/services');
        }
        
        $tenantId = $this->auth->getTenantId();
        $hasInternal = $this->serviceModel->hasInternalService($tenantId);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'tenant_id' => $tenantId,
                    'name' => $_POST['name'] ?? '',
                    'service_type' => $_POST['service_type'] ?? 'external',
                    'address' => $_POST['address'] ?? '',
                    'contact_phone' => $_POST['contact_phone'] ?? '',
                    'contact_email' => $_POST['contact_email'] ?? '',
                    'contact_person' => $_POST['contact_person'] ?? '',
                    'working_hours' => $_POST['working_hours'] ?? '',
                    'notes' => $_POST['notes'] ?? '',
                    'is_active' => isset($_POST['is_active']) ? 1 : 1
                ];
                
                // Câmpuri specifice pentru service intern
                if ($data['service_type'] === 'internal') {
                    $data['capacity'] = $_POST['capacity'] ?? null;
                    $data['hourly_rate'] = $_POST['hourly_rate'] ?? null;
                }
                
                // Procesare service_types (checkbox-uri)
                if (isset($_POST['service_types']) && is_array($_POST['service_types'])) {
                    $data['service_types'] = $_POST['service_types'];
                }
                
                $serviceId = $this->serviceModel->createService($data);
                
                $_SESSION['success_message'] = 'Service adăugat cu succes!';
                $this->redirect('/service/services/view/' . $serviceId);
                
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
            }
        }
        
        $this->render('services/add', [
            'pageTitle' => 'Adaugă Service',
            'hasInternal' => $hasInternal,
            'serviceTypes' => $this->getServiceTypeOptions()
        ]);
    }
    
    /**
     * Formular editare service
     */
    public function edit($id) {
        if (!$this->auth->isAdmin()) {
            $this->redirect('/service/services');
        }
        
        $tenantId = $this->auth->getTenantId();
        
        // Verificare acces
        if (!$this->serviceModel->checkTenantAccess($id, $tenantId)) {
            $_SESSION['error_message'] = 'Acces interzis!';
            $this->redirect('/service/services');
        }
        
        $service = $this->serviceModel->find($id);
        
        if (!$service) {
            $this->error404('Service nu a fost găsit');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'name' => $_POST['name'] ?? $service['name'],
                    'address' => $_POST['address'] ?? '',
                    'contact_phone' => $_POST['contact_phone'] ?? '',
                    'contact_email' => $_POST['contact_email'] ?? '',
                    'contact_person' => $_POST['contact_person'] ?? '',
                    'working_hours' => $_POST['working_hours'] ?? '',
                    'notes' => $_POST['notes'] ?? '',
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];
                
                if ($service['service_type'] === 'internal') {
                    $data['capacity'] = $_POST['capacity'] ?? null;
                    $data['hourly_rate'] = $_POST['hourly_rate'] ?? null;
                }
                
                if (isset($_POST['service_types']) && is_array($_POST['service_types'])) {
                    $data['service_types'] = $_POST['service_types'];
                }
                
                $this->serviceModel->updateService($id, $data);
                
                $_SESSION['success_message'] = 'Service actualizat cu succes!';
                $this->redirect('/service/services/view/' . $id);
                
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
            }
        }
        
        $this->render('services/edit', [
            'pageTitle' => 'Editare Service',
            'service' => $service,
            'serviceTypes' => $this->getServiceTypeOptions()
        ]);
    }
    
    /**
     * Vizualizare detalii service
     */
    public function view($id) {
        $tenantId = $this->auth->getTenantId();
        
        if (!$this->serviceModel->checkTenantAccess($id, $tenantId)) {
            $_SESSION['error_message'] = 'Acces interzis!';
            $this->redirect('/service/services');
        }
        
        $service = $this->serviceModel->find($id);
        
        if (!$service) {
            $this->error404('Service nu a fost găsit');
        }
        
        // Obținere statistici
        $stats = $this->serviceModel->getServiceStats($id, 'year');
        
        // Obținere istoricul intervențiilor recente
        $sql = "SELECT sh.*, v.plate_number, v.make, v.model
                FROM service_history sh
                JOIN vehicles v ON sh.vehicle_id = v.id
                WHERE sh.service_id = ? AND sh.tenant_id = ?
                ORDER BY sh.service_date DESC
                LIMIT 10";
        
        $recentServices = $this->db->fetchAllOn('service_history', $sql, [$id, $tenantId]);
        
        $this->render('services/view', [
            'pageTitle' => $service['name'],
            'service' => $service,
            'stats' => $stats,
            'recentServices' => $recentServices,
            'userRole' => $this->auth->getUserRole()
        ]);
    }
    
    /**
     * Ștergere service (soft delete)
     */
    public function delete($id) {
        if (!$this->auth->isAdmin()) {
            $this->json(['success' => false, 'message' => 'Acces interzis'], 403);
        }
        
        $tenantId = $this->auth->getTenantId();
        
        if (!$this->serviceModel->checkTenantAccess($id, $tenantId)) {
            $this->json(['success' => false, 'message' => 'Acces interzis'], 403);
        }
        
        try {
            $this->serviceModel->deleteService($id);
            
            if (isset($_POST['ajax'])) {
                $this->json(['success' => true, 'message' => 'Service dezactivat']);
            } else {
                $_SESSION['success_message'] = 'Service dezactivat cu succes!';
                $this->redirect('/service/services');
            }
        } catch (Exception $e) {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 500);
            } else {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
                $this->redirect('/service/services');
            }
        }
    }
    
    /**
     * Activare service
     */
    public function activate($id) {
        if (!$this->auth->isAdmin()) {
            $this->json(['success' => false, 'message' => 'Acces interzis'], 403);
        }
        
        $tenantId = $this->auth->getTenantId();
        
        if (!$this->serviceModel->checkTenantAccess($id, $tenantId)) {
            $this->json(['success' => false, 'message' => 'Acces interzis'], 403);
        }
        
        try {
            $this->serviceModel->activateService($id);
            
            if (isset($_POST['ajax'])) {
                $this->json(['success' => true, 'message' => 'Service activat']);
            } else {
                $_SESSION['success_message'] = 'Service activat cu succes!';
                $this->redirect('/service/services');
            }
        } catch (Exception $e) {
            if (isset($_POST['ajax'])) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 500);
            } else {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
                $this->redirect('/service/services');
            }
        }
    }
    
    /**
     * Configurare service intern
     */
    public function internalSetup() {
        if (!$this->auth->isAdmin()) {
            $this->redirect('/service/services');
        }
        
        $tenantId = $this->auth->getTenantId();
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'tenant_id' => $tenantId,
                    'name' => $_POST['name'] ?? 'Atelier Intern',
                    'service_type' => 'internal',
                    'address' => $_POST['address'] ?? '',
                    'contact_phone' => $_POST['contact_phone'] ?? '',
                    'contact_email' => $_POST['contact_email'] ?? '',
                    'working_hours' => $_POST['working_hours'] ?? '',
                    'capacity' => $_POST['capacity'] ?? 4,
                    'hourly_rate' => $_POST['hourly_rate'] ?? 150.00,
                    'notes' => $_POST['notes'] ?? '',
                    'is_active' => 1
                ];
                
                if ($internalService) {
                    // Update existent
                    $this->serviceModel->updateService($internalService['id'], $data);
                    $_SESSION['success_message'] = 'Configurare actualizată cu succes!';
                } else {
                    // Creare nou
                    $serviceId = $this->serviceModel->createService($data);
                    $_SESSION['success_message'] = 'Service intern configurat cu succes!';
                    $this->redirect('/service/services/view/' . $serviceId);
                    return;
                }
                
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
            }
        }
        
        $this->render('services/internal_setup', [
            'pageTitle' => $internalService ? 'Configurare Service Intern' : 'Activare Service Intern',
            'service' => $internalService
        ]);
    }
    
    /**
     * API: Obține service-uri pentru dropdown-uri
     */
    public function apiGetServices() {
        $tenantId = $this->auth->getTenantId();
        $type = $_GET['type'] ?? 'all';
        
        $services = $this->serviceModel->getAllByTenant($tenantId, $type, true);
        
        $this->json([
            'success' => true,
            'services' => $services
        ]);
    }
    
    /**
     * Helper: Opțiuni tipuri de servicii disponibile
     */
    private function getServiceTypeOptions() {
        return [
            'revizie' => 'Revizie tehnică',
            'reparatie' => 'Reparații',
            'schimb_ulei' => 'Schimb ulei și filtre',
            'frane' => 'Frâne',
            'suspensie' => 'Suspensie',
            'electric' => 'Instalații electrice',
            'diagnoza' => 'Diagnoză electronică',
            'climatizare' => 'Climatizare',
            'caroserie' => 'Caroserie și vopsitorie',
            'anvelope' => 'Anvelope',
            'geometrie' => 'Geometrie roți',
            'alte' => 'Alte servicii'
        ];
    }
}
