<?php
// modules/service/controllers/PartsController.php

require_once __DIR__ . '/../../../core/Controller.php';
require_once __DIR__ . '/../models/Part.php';

class PartsController extends Controller {
    private $partModel;
    
    public function __construct() {
        parent::__construct();
        $this->checkAuth();
        $this->partModel = new Part();
    }
    
    /**
     * Display parts list
     */
    public function index() {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'category' => $_GET['category'] ?? '',
            'low_stock' => isset($_GET['low_stock'])
        ];
        
        $parts = $this->partModel->getAllParts($filters);
        $categories = $this->partModel->getCategories();
        $statistics = $this->partModel->getStatistics();
        $lowStockParts = $this->partModel->getLowStockParts();
        
        $this->renderView('service/parts/index', [
            'parts' => $parts,
            'categories' => $categories,
            'statistics' => $statistics,
            'lowStockParts' => $lowStockParts,
            'filters' => $filters,
            'pageTitle' => 'Gestiune Piese'
        ]);
    }
    
    /**
     * Display add part form
     */
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'part_number' => $_POST['part_number'],
                    'name' => $_POST['name'],
                    'description' => $_POST['description'] ?? null,
                    'category' => $_POST['category'],
                    'manufacturer' => $_POST['manufacturer'] ?? null,
                    'unit_price' => floatval($_POST['unit_price']),
                    'sale_price' => floatval($_POST['sale_price'] ?? $_POST['unit_price']),
                    'quantity_in_stock' => intval($_POST['quantity_in_stock'] ?? 0),
                    'minimum_quantity' => intval($_POST['minimum_quantity'] ?? 0),
                    'unit_of_measure' => $_POST['unit_of_measure'] ?? 'buc',
                    'location' => $_POST['location'] ?? null,
                    'supplier' => $_POST['supplier'] ?? null,
                    'supplier_part_number' => $_POST['supplier_part_number'] ?? null,
                    'notes' => $_POST['notes'] ?? null
                ];
                
                $partId = $this->partModel->createPart($data);
                
                // Log initial stock if any
                if ($data['quantity_in_stock'] > 0) {
                    $this->partModel->addStock($partId, $data['quantity_in_stock'], 'Stoc initial');
                }
                
                $_SESSION['success_message'] = 'Piesa a fost adaugata cu succes';
                header('Location: /service/parts');
                exit;
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
            }
        }
        
        $categories = $this->partModel->getCategories();
        
        $this->renderView('service/parts/form', [
            'part' => null,
            'categories' => $categories,
            'pageTitle' => 'Adauga Piesa'
        ]);
    }
    
    /**
     * Display edit part form
     */
    public function edit($id) {
        $part = $this->partModel->getPartById($id);
        
        if (!$part) {
            $_SESSION['error_message'] = 'Piesa nu a fost gasita';
            header('Location: /service/parts');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'part_number' => $_POST['part_number'],
                    'name' => $_POST['name'],
                    'description' => $_POST['description'] ?? null,
                    'category' => $_POST['category'],
                    'manufacturer' => $_POST['manufacturer'] ?? null,
                    'unit_price' => floatval($_POST['unit_price']),
                    'sale_price' => floatval($_POST['sale_price'] ?? $_POST['unit_price']),
                    'quantity_in_stock' => intval($_POST['quantity_in_stock']),
                    'minimum_quantity' => intval($_POST['minimum_quantity'] ?? 0),
                    'unit_of_measure' => $_POST['unit_of_measure'] ?? 'buc',
                    'location' => $_POST['location'] ?? null,
                    'supplier' => $_POST['supplier'] ?? null,
                    'supplier_part_number' => $_POST['supplier_part_number'] ?? null,
                    'notes' => $_POST['notes'] ?? null
                ];
                
                $this->partModel->updatePart($id, $data);
                
                $_SESSION['success_message'] = 'Piesa a fost actualizata cu succes';
                header('Location: /service/parts');
                exit;
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
            }
        }
        
        $categories = $this->partModel->getCategories();
        
        $this->renderView('service/parts/form', [
            'part' => $part,
            'categories' => $categories,
            'pageTitle' => 'Editeaza Piesa'
        ]);
    }
    
    /**
     * Delete part
     */
    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->partModel->deletePart($id);
            
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
        }
        
        header('Location: /service/parts');
        exit;
    }
    
    /**
     * View part details
     */
    public function view($id) {
        $part = $this->partModel->getPartById($id);
        
        if (!$part) {
            $_SESSION['error_message'] = 'Piesa nu a fost gasita';
            header('Location: /service/parts');
            exit;
        }
        
        $usageHistory = $this->partModel->getPartUsageHistory($id);
        $transactions = $this->partModel->getStockTransactions($id);
        
        $this->renderView('service/parts/view', [
            'part' => $part,
            'usageHistory' => $usageHistory,
            'transactions' => $transactions,
            'pageTitle' => 'Detalii Piesa'
        ]);
    }
    
    /**
     * Adjust stock
     */
    public function adjustStock($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type']; // 'in' or 'out'
            $quantity = intval($_POST['quantity']);
            $notes = $_POST['notes'] ?? null;
            
            try {
                if ($type === 'in') {
                    $this->partModel->addStock($id, $quantity, $notes);
                    $_SESSION['success_message'] = 'Stoc adaugat cu succes';
                } else {
                    $result = $this->partModel->removeStock($id, $quantity, $notes);
                    if ($result['success']) {
                        $_SESSION['success_message'] = 'Stoc scazut cu succes';
                    } else {
                        $_SESSION['error_message'] = $result['message'];
                    }
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Eroare: ' . $e->getMessage();
            }
        }
        
        header('Location: /service/parts/view/' . $id);
        exit;
    }
    
    /**
     * API: Get parts for work order
     */
    public function apiGetParts() {
        header('Content-Type: application/json');
        
        $search = $_GET['search'] ?? '';
        $parts = $this->partModel->getAllParts(['search' => $search]);
        
        echo json_encode(['success' => true, 'data' => $parts]);
        exit;
    }
}
