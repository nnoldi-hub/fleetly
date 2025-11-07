<?php

class ImportController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // Verificare autentificare
        if (!Auth::check()) {
            $this->redirect('/login');
        }
    }

    /**
     * Pagina principala import
     */
    public function index()
    {
        $data = [
            'title' => 'Import Date CSV',
            'pageTitle' => 'Import Masiv Date',
            'breadcrumbs' => [
                ['name' => 'Dashboard', 'url' => '/dashboard'],
                ['name' => 'Import Date', 'url' => '']
            ]
        ];
        
        $this->render('import/index', $data);
    }

    /**
     * Download template CSV pentru vehicule
     */
    public function downloadVehiclesTemplate()
    {
        $headers = [
            'registration_number',
            'vin_number',
            'brand',
            'model',
            'year',
            'vehicle_type_id',
            'status',
            'purchase_date',
            'purchase_price',
            'current_mileage',
            'engine_capacity',
            'fuel_type',
            'color',
            'notes'
        ];

        $example = [
            'B-123-ABC',
            'UU1LSDA12ABC123456',
            'Dacia',
            'Logan',
            '2020',
            '1',
            'active',
            '2020-01-15',
            '45000',
            '50000',
            '1500',
            'petrol',
            'Alb',
            'Vehicul in stare buna'
        ];

        $this->generateCSV('template_vehicule.csv', $headers, [$example]);
    }

    /**
     * Download template CSV pentru documente
     */
    public function downloadDocumentsTemplate()
    {
        $headers = [
            'vehicle_registration_number',
            'document_type',
            'document_number',
            'issue_date',
            'expiry_date',
            'issuer',
            'notes'
        ];

        $example = [
            'B-123-ABC',
            'ITP',
            'ITP-2024-12345',
            '2024-01-15',
            '2025-01-15',
            'RAR Bucuresti',
            'ITP valabil 1 an'
        ];

        $this->generateCSV('template_documente.csv', $headers, [$example]);
    }

    /**
     * Download template CSV pentru soferi
     */
    public function downloadDriversTemplate()
    {
        $headers = [
            'name',
            'license_number',
            'license_category',
            'license_issue_date',
            'license_expiry_date',
            'phone',
            'email',
            'address',
            'date_of_birth',
            'hire_date',
            'status',
            'notes'
        ];

        $example = [
            'Popescu Ion',
            'AB123456',
            'B,C,D',
            '2015-03-15',
            '2025-03-15',
            '0721234567',
            'ion.popescu@email.ro',
            'Str. Exemplu nr. 10, Bucuresti',
            '1985-01-01',
            '2020-06-01',
            'active',
            'Experienta 10 ani'
        ];

        $this->generateCSV('template_soferi.csv', $headers, [$example]);
    }

    /**
     * Upload si procesare CSV vehicule
     */
    public function uploadVehicles()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Metoda invalida';
            $this->redirect('/import');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Eroare la incarcarea fisierului';
            $this->redirect('/import');
        }

        $file = $_FILES['csv_file'];
        $filePath = $file['tmp_name'];

        // Verificare extensie
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            $_SESSION['error'] = 'Fisierul trebuie sa fie CSV';
            $this->redirect('/import');
        }

        try {
            $results = $this->processVehiclesCSV($filePath);
            
            $_SESSION['success'] = sprintf(
                'Import finalizat: %d vehicule adaugate, %d erori',
                $results['success'],
                $results['errors']
            );
            
            if (!empty($results['error_details'])) {
                $_SESSION['import_errors'] = $results['error_details'];
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Eroare la procesarea fisierului: ' . $e->getMessage();
        }

        $this->redirect('/import');
    }

    /**
     * Upload si procesare CSV documente
     */
    public function uploadDocuments()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Metoda invalida';
            $this->redirect('/import');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Eroare la incarcarea fisierului';
            $this->redirect('/import');
        }

        try {
            $results = $this->processDocumentsCSV($_FILES['csv_file']['tmp_name']);
            
            $_SESSION['success'] = sprintf(
                'Import finalizat: %d documente adaugate, %d erori',
                $results['success'],
                $results['errors']
            );
            
            if (!empty($results['error_details'])) {
                $_SESSION['import_errors'] = $results['error_details'];
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Eroare la procesarea fisierului: ' . $e->getMessage();
        }

        $this->redirect('/import');
    }

    /**
     * Upload si procesare CSV soferi
     */
    public function uploadDrivers()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Metoda invalida';
            $this->redirect('/import');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Eroare la incarcarea fisierului';
            $this->redirect('/import');
        }

        try {
            $results = $this->processDriversCSV($_FILES['csv_file']['tmp_name']);
            
            $_SESSION['success'] = sprintf(
                'Import finalizat: %d soferi adaugati, %d erori',
                $results['success'],
                $results['errors']
            );
            
            if (!empty($results['error_details'])) {
                $_SESSION['import_errors'] = $results['error_details'];
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Eroare la procesarea fisierului: ' . $e->getMessage();
        }

        $this->redirect('/import');
    }

    /**
     * Procesare CSV vehicule
     */
    private function processVehiclesCSV($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception('Nu se poate deschide fisierul CSV');
        }

        // Citeste header
        $headers = fgetcsv($handle, 1000, ',');
        if (!$headers) {
            throw new Exception('Fisier CSV invalid');
        }

        $success = 0;
        $errors = 0;
        $errorDetails = [];
        $lineNumber = 1;

        require_once 'modules/vehicles/models/Vehicle.php';
        $vehicleModel = new Vehicle();

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $lineNumber++;
            
            try {
                // Map data to associative array
                $row = array_combine($headers, $data);
                
                // Validare date obligatorii
                if (empty($row['brand']) || empty($row['model']) || empty($row['registration_number'])) {
                    throw new Exception('Campuri obligatorii lipsa (brand, model, registration_number)');
                }

                // Validate year
                if (empty($row['year']) || !is_numeric($row['year'])) {
                    throw new Exception('An fabricatie invalid');
                }

                // Validate vehicle_type_id
                if (empty($row['vehicle_type_id']) || !is_numeric($row['vehicle_type_id'])) {
                    throw new Exception('ID tip vehicul invalid (trebuie sa fie numar: 1-7)');
                }

                // Prepare data for insert
                $vehicleData = [
                    'registration_number' => $row['registration_number'],
                    'vin_number' => $row['vin_number'] ?? null,
                    'brand' => $row['brand'],
                    'model' => $row['model'],
                    'year' => (int)$row['year'],
                    'vehicle_type_id' => (int)$row['vehicle_type_id'],
                    'status' => !empty($row['status']) ? $row['status'] : 'active',
                    'purchase_date' => !empty($row['purchase_date']) ? $row['purchase_date'] : null,
                    'purchase_price' => !empty($row['purchase_price']) ? (float)$row['purchase_price'] : null,
                    'current_mileage' => !empty($row['current_mileage']) ? (int)$row['current_mileage'] : 0,
                    'engine_capacity' => $row['engine_capacity'] ?? null,
                    'fuel_type' => !empty($row['fuel_type']) ? $row['fuel_type'] : 'diesel',
                    'color' => $row['color'] ?? null,
                    'notes' => $row['notes'] ?? null
                ];

                $vehicleModel->create($vehicleData);
                $success++;
                
            } catch (Exception $e) {
                $errors++;
                $errorDetails[] = "Linia {$lineNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        return [
            'success' => $success,
            'errors' => $errors,
            'error_details' => $errorDetails
        ];
    }

    /**
     * Procesare CSV documente
     */
    private function processDocumentsCSV($filePath)
    {
        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle, 1000, ',');
        
        $success = 0;
        $errors = 0;
        $errorDetails = [];
        $lineNumber = 1;

        require_once 'modules/documents/models/Document.php';
        require_once 'modules/vehicles/models/Vehicle.php';
        
        $documentModel = new Document();
        $vehicleModel = new Vehicle();

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $lineNumber++;
            
            try {
                $row = array_combine($headers, $data);
                
                // Gaseste vehiculul dupa numar inmatriculare
                $vehicle = $vehicleModel->findBy(['registration_number' => $row['vehicle_registration_number']]);
                if (!$vehicle || empty($vehicle)) {
                    throw new Exception('Vehicul negasit: ' . $row['vehicle_registration_number']);
                }
                $vehicleId = is_array($vehicle) ? $vehicle[0]['id'] : $vehicle['id'];

                $documentData = [
                    'vehicle_id' => $vehicleId,
                    'document_type' => $row['document_type'],
                    'document_number' => $row['document_number'] ?? null,
                    'issue_date' => !empty($row['issue_date']) ? $row['issue_date'] : null,
                    'expiry_date' => $row['expiry_date'],
                    'issuer' => $row['issuer'] ?? null,
                    'notes' => $row['notes'] ?? null
                ];

                $documentModel->create($documentData);
                $success++;
                
            } catch (Exception $e) {
                $errors++;
                $errorDetails[] = "Linia {$lineNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        return [
            'success' => $success,
            'errors' => $errors,
            'error_details' => $errorDetails
        ];
    }

    /**
     * Procesare CSV soferi
     */
    private function processDriversCSV($filePath)
    {
        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle, 1000, ',');
        
        $success = 0;
        $errors = 0;
        $errorDetails = [];
        $lineNumber = 1;

        require_once 'modules/drivers/models/Driver.php';
        $driverModel = new Driver();

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $lineNumber++;
            
            try {
                $row = array_combine($headers, $data);
                
                if (empty($row['name']) || empty($row['license_number'])) {
                    throw new Exception('Nume si numar permis sunt obligatorii');
                }

                $driverData = [
                    'name' => $row['name'],
                    'license_number' => $row['license_number'],
                    'license_category' => $row['license_category'] ?? null,
                    'license_issue_date' => !empty($row['license_issue_date']) ? $row['license_issue_date'] : null,
                    'license_expiry_date' => !empty($row['license_expiry_date']) ? $row['license_expiry_date'] : null,
                    'phone' => $row['phone'] ?? null,
                    'email' => $row['email'] ?? null,
                    'address' => $row['address'] ?? null,
                    'date_of_birth' => !empty($row['date_of_birth']) ? $row['date_of_birth'] : null,
                    'hire_date' => !empty($row['hire_date']) ? $row['hire_date'] : null,
                    'status' => !empty($row['status']) ? $row['status'] : 'active',
                    'notes' => $row['notes'] ?? null
                ];

                $driverModel->create($driverData);
                $success++;
                
            } catch (Exception $e) {
                $errors++;
                $errorDetails[] = "Linia {$lineNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        return [
            'success' => $success,
            'errors' => $errors,
            'error_details' => $errorDetails
        ];
    }

    /**
     * Genereaza si trimite fisier CSV
     */
    private function generateCSV($filename, $headers, $data)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // BOM pentru UTF-8 (Excel compatibility)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header
        fputcsv($output, $headers);
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
