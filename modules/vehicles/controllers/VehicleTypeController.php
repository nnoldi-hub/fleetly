<?php
// modules/vehicles/controllers/VehicleTypeController.php

class VehicleTypeController extends Controller {
    private $vehicleTypeModel;
    
    public function __construct() {
        parent::__construct();
        $this->vehicleTypeModel = new VehicleType();
    }
    
    public function index() {
        $vehicleTypes = $this->vehicleTypeModel->getAll();
        
        $this->render('types/list', [
            'vehicleTypes' => $vehicleTypes,
            'title' => 'Tipuri de Vehicule'
        ]);
    }
    
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'fuel_type' => $_POST['fuel_type'] ?? 'benzina',
                'capacity_min' => (int)($_POST['capacity_min'] ?? 0),
                'capacity_max' => (int)($_POST['capacity_max'] ?? 0),
                'maintenance_interval' => (int)($_POST['maintenance_interval'] ?? 10000)
            ];
            
            // Validare
            $errors = $this->validateVehicleType($data);
            
            if (empty($errors)) {
                $result = $this->vehicleTypeModel->create($data);
                
                if ($result) {
                    $_SESSION['success'] = 'Tipul de vehicul a fost adăugat cu succes!';
                    $this->redirect(BASE_URL . 'vehicle-types');
                } else {
                    $errors[] = 'Eroare la salvarea tipului de vehicul.';
                }
            }
            
            $this->render('types/add', [
                'errors' => $errors,
                'data' => $data,
                'title' => 'Adaugă Tip Vehicul'
            ]);
        } else {
            $this->render('types/add', [
                'title' => 'Adaugă Tip Vehicul'
            ]);
        }
    }
    
    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            $_SESSION['error'] = 'ID invalid pentru tipul de vehicul.';
            $this->redirect(BASE_URL . 'vehicle-types');
        }
        
        $vehicleType = $this->vehicleTypeModel->getById($id);
        
        if (!$vehicleType) {
            $_SESSION['error'] = 'Tipul de vehicul nu a fost găsit.';
            $this->redirect(BASE_URL . 'vehicle-types');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'fuel_type' => $_POST['fuel_type'] ?? 'benzina',
                'capacity_min' => (int)($_POST['capacity_min'] ?? 0),
                'capacity_max' => (int)($_POST['capacity_max'] ?? 0),
                'maintenance_interval' => (int)($_POST['maintenance_interval'] ?? 10000)
            ];
            
            // Validare
            $errors = $this->validateVehicleType($data, $id);
            
            if (empty($errors)) {
                $result = $this->vehicleTypeModel->update($id, $data);
                
                if ($result) {
                    $_SESSION['success'] = 'Tipul de vehicul a fost actualizat cu succes!';
                    $this->redirect(BASE_URL . 'vehicle-types');
                } else {
                    $errors[] = 'Eroare la actualizarea tipului de vehicul.';
                }
            }
            
            $this->render('types/edit', [
                'vehicleType' => $vehicleType,
                'errors' => $errors,
                'data' => $data,
                'title' => 'Editează Tip Vehicul'
            ]);
        } else {
            $this->render('types/edit', [
                'vehicleType' => $vehicleType,
                'title' => 'Editează Tip Vehicul'
            ]);
        }
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Metodă nepermisă'], 405);
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            $this->json(['error' => 'ID invalid']);
        }
        
        // Verifică dacă tipul de vehicul este folosit
        $vehiclesCount = $this->vehicleTypeModel->countVehiclesByType($id);
        
        if ($vehiclesCount > 0) {
            $this->json(['error' => "Nu se poate șterge tipul de vehicul deoarece este folosit de $vehiclesCount vehicul(e)."]);
        }
        
        $result = $this->vehicleTypeModel->delete($id);
        
        if ($result) {
            $this->json(['success' => true, 'message' => 'Tipul de vehicul a fost șters cu succes.']);
        } else {
            $this->json(['error' => 'Eroare la ștergerea tipului de vehicul.']);
        }
    }
    
    private function validateVehicleType($data, $id = null) {
        $errors = [];
        
        // Validare nume
        if (empty($data['name'])) {
            $errors[] = 'Numele tipului de vehicul este obligatoriu.';
        } elseif (strlen($data['name']) < 2) {
            $errors[] = 'Numele tipului de vehicul trebuie să aibă minim 2 caractere.';
        } else {
            // Verifică unicitatea numelui
            $existing = $this->vehicleTypeModel->getByName($data['name']);
            if ($existing && ($id === null || $existing['id'] != $id)) {
                $errors[] = 'Există deja un tip de vehicul cu acest nume.';
            }
        }
        
        // Validare tip combustibil
        $allowedFuelTypes = ['benzina', 'motorina', 'electric', 'hibrid', 'gpl'];
        if (!in_array($data['fuel_type'], $allowedFuelTypes)) {
            $errors[] = 'Tipul de combustibil selectat nu este valid.';
        }
        
        // Validare capacitate
        if ($data['capacity_min'] < 0) {
            $errors[] = 'Capacitatea minimă nu poate fi negativă.';
        }
        
        if ($data['capacity_max'] < 0) {
            $errors[] = 'Capacitatea maximă nu poate fi negativă.';
        }
        
        if ($data['capacity_max'] > 0 && $data['capacity_min'] > $data['capacity_max']) {
            $errors[] = 'Capacitatea minimă nu poate fi mai mare decât capacitatea maximă.';
        }
        
        // Validare interval întreținere
        if ($data['maintenance_interval'] <= 0) {
            $errors[] = 'Intervalul de întreținere trebuie să fie pozitiv.';
        }
        
        return $errors;
    }
}
