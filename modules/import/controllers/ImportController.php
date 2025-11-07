<?php

class ImportController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // Verificare autentificare
        Auth::getInstance()->requireAuth();
    }

    /**
     * Pagina principala import
     */
    public function index()
    {
        $title = 'Import Date CSV';
        $pageTitle = 'Import Masiv Date';
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => '/dashboard'],
            ['name' => 'Import Date', 'url' => '']
        ];
        
        // Include view direct
        include 'modules/import/views/index.php';
    }

    /**
     * Download template CSV pentru vehicule
     */
    public function downloadVehiclesTemplate()
    {
        $headers = [
            'numar_inmatriculare',
            'cod_vin',
            'marca',
            'model',
            'an',
            'tip_vehicul_id',
            'status',
            'data_achizitie',
            'pret_achizitie',
            'kilometraj_curent',
            'capacitate_motor',
            'tip_combustibil',
            'culoare',
            'observatii'
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
            'tip_vehicul_id: 1=Autoturism, 2=Autoutilitara, 3=Camion, 4=Autobus, 5=Motostivuitor, 6=Excavator, 7=Buldozer, 8=Trailer, 9=Utilaj Agricol, 10=Generator'
        ];

        $this->generateCSV('template_vehicule.csv', $headers, [$example]);
    }

    /**
     * Download template CSV pentru documente
     */
    public function downloadDocumentsTemplate()
    {
        $headers = [
            'numar_inmatriculare_vehicul',
            'tip_document',
            'numar_document',
            'data_emitere',
            'data_expirare',
            'emitent',
            'observatii'
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
            'nume_complet',
            'numar_permis',
            'categorii_permis',
            'data_emitere_permis',
            'data_expirare_permis',
            'telefon',
            'email',
            'adresa',
            'data_nastere',
            'data_angajare',
            'status',
            'observatii'
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
                if (empty($row['marca']) || empty($row['model']) || empty($row['numar_inmatriculare'])) {
                    throw new Exception('Campuri obligatorii lipsa (marca, model, numar_inmatriculare)');
                }

                // Validate year
                if (empty($row['an']) || !is_numeric($row['an'])) {
                    throw new Exception('An fabricatie invalid');
                }

                // Validate vehicle_type_id
                if (empty($row['tip_vehicul_id']) || !is_numeric($row['tip_vehicul_id'])) {
                    throw new Exception('ID tip vehicul invalid (trebuie sa fie numar: 1-7)');
                }

                // Prepare data for insert - mapping romana -> engleza
                $vehicleData = [
                    'registration_number' => $row['numar_inmatriculare'],
                    'vin_number' => $row['cod_vin'] ?? null,
                    'brand' => $row['marca'],
                    'model' => $row['model'],
                    'year' => (int)$row['an'],
                    'vehicle_type_id' => (int)$row['tip_vehicul_id'],
                    'status' => !empty($row['status']) ? $row['status'] : 'active',
                    'purchase_date' => !empty($row['data_achizitie']) ? $row['data_achizitie'] : null,
                    'purchase_price' => !empty($row['pret_achizitie']) ? (float)$row['pret_achizitie'] : null,
                    'current_mileage' => !empty($row['kilometraj_curent']) ? (int)$row['kilometraj_curent'] : 0,
                    'engine_capacity' => $row['capacitate_motor'] ?? null,
                    'fuel_type' => !empty($row['tip_combustibil']) ? $row['tip_combustibil'] : 'diesel',
                    'color' => $row['culoare'] ?? null,
                    'notes' => $row['observatii'] ?? null
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
                
                // Gaseste vehiculul dupa numar inmatriculare - mapping romana -> engleza
                $vehicles = $vehicleModel->findAll(['registration_number' => $row['numar_inmatriculare_vehicul']]);
                if (!$vehicles || empty($vehicles)) {
                    throw new Exception('Vehicul negasit: ' . $row['numar_inmatriculare_vehicul']);
                }
                $vehicleId = $vehicles[0]['id'];

                // Mapping romana -> engleza
                $documentData = [
                    'vehicle_id' => $vehicleId,
                    'document_type' => $row['tip_document'],
                    'document_number' => $row['numar_document'] ?? null,
                    'issue_date' => !empty($row['data_emitere']) ? $row['data_emitere'] : null,
                    'expiry_date' => $row['data_expirare'],
                    'issuer' => $row['emitent'] ?? null,
                    'notes' => $row['observatii'] ?? null
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
                
                if (empty($row['nume_complet']) || empty($row['numar_permis'])) {
                    throw new Exception('Nume complet si numar permis sunt obligatorii');
                }

                // Mapping romana -> engleza
                $driverData = [
                    'name' => $row['nume_complet'],
                    'license_number' => $row['numar_permis'],
                    'license_category' => $row['categorii_permis'] ?? null,
                    'license_issue_date' => !empty($row['data_emitere_permis']) ? $row['data_emitere_permis'] : null,
                    'license_expiry_date' => !empty($row['data_expirare_permis']) ? $row['data_expirare_permis'] : null,
                    'phone' => $row['telefon'] ?? null,
                    'email' => $row['email'] ?? null,
                    'address' => $row['adresa'] ?? null,
                    'date_of_birth' => !empty($row['data_nastere']) ? $row['data_nastere'] : null,
                    'hire_date' => !empty($row['data_angajare']) ? $row['data_angajare'] : null,
                    'status' => !empty($row['status']) ? $row['status'] : 'active',
                    'notes' => $row['observatii'] ?? null
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
