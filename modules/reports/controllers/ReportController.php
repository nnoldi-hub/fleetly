<?php
// modules/reports/controllers/ReportController.php

class ReportController extends Controller {
    private $reportModel;
    private $vehicleModel;
    private $fuelModel;
    private $maintenanceModel;
    private $insuranceModel;
    
    public function __construct() {
        parent::__construct();
        $this->reportModel = new Report();
        $this->vehicleModel = new Vehicle();
        $this->fuelModel = new FuelConsumption();
        $this->maintenanceModel = new Maintenance();
        $this->insuranceModel = new Insurance();
    }
    
    public function index() {
        // Dashboard cu rezumat + link-uri către rapoarte
        $safeCount = function(callable $fn) {
            try { return (int)$fn(); } catch (\Throwable $e) { return 0; }
        };

        $data = [
            'totalVehicles' => $safeCount(fn() => $this->vehicleModel->getTotalCount()),
            'totalFuelRecords' => $safeCount(fn() => $this->fuelModel->getTotalCount()),
            'totalMaintenanceRecords' => $safeCount(fn() => $this->maintenanceModel->getTotalCount()),
            // Poate lipsi tabela insurance în unele instalații; tratăm sigur
            'totalInsuranceRecords' => $safeCount(fn() => $this->insuranceModel->getTotalCount())
        ];

        $this->render('index', $data);
    }
    
    public function fleetReport() {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // Prima zi a lunii curente
        $dateTo = $_GET['date_to'] ?? date('Y-m-t'); // Ultima zi a lunii curente
        $vehicleId = $_GET['vehicle_id'] ?? '';
        $reportType = $_GET['report_type'] ?? 'summary';
        
        // Obținem lista vehiculelor pentru filtru
        $vehicles = $this->vehicleModel->getActiveVehicles();
        
        // Generăm raportul
        $reportData = $this->reportModel->generateFleetReport($dateFrom, $dateTo, $vehicleId, $reportType);
        
        // Dacă este cerere de export
        if (isset($_GET['export'])) {
            $this->exportFleetReport($reportData, $_GET['export'], $dateFrom, $dateTo);
            return;
        }
        
        $data = [
            'reportData' => $reportData,
            'vehicles' => $vehicles,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'vehicle_id' => $vehicleId,
                'report_type' => $reportType
            ]
        ];
        $this->render('fleet_report', $data);
    }
    
    public function vehicleReport() {
        $vehicleId = $_GET['vehicle_id'] ?? '';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');
        $reportType = $_GET['report_type'] ?? 'detailed';
        
        if (!$vehicleId) {
            $_SESSION['errors'] = ['Vă rugăm să selectați un vehicul pentru raport'];
            $this->redirect(BASE_URL . 'reports');
            return;
        }
        
        // Obținem datele vehiculului
        $vehicle = $this->vehicleModel->getById($vehicleId);
        if (!$vehicle) {
            $_SESSION['errors'] = ['Vehiculul selectat nu a fost găsit'];
            $this->redirect(BASE_URL . 'reports');
            return;
        }
        // Normalizeaza campurile pentru view-ul de raport vehicul
        $vehicle['license_plate'] = $vehicle['registration_number'] ?? ($vehicle['license_plate'] ?? '');
        $vehicle['make'] = $vehicle['brand'] ?? ($vehicle['make'] ?? '');
        $vehicle['odometer'] = $vehicle['current_mileage'] ?? ($vehicle['odometer'] ?? 0);
        
        // Obținem lista vehiculelor pentru filtru
        $vehicles = $this->vehicleModel->getActiveVehicles();
        
        // Generăm raportul specific vehiculului
        $reportData = $this->reportModel->generateVehicleReport($vehicleId, $dateFrom, $dateTo, $reportType);
        
        // Dacă este cerere de export
        if (isset($_GET['export'])) {
            $this->exportVehicleReport($reportData, $_GET['export'], $vehicle, $dateFrom, $dateTo);
            return;
        }
        
        $data = [
            'vehicle' => $vehicle,
            'reportData' => $reportData,
            'vehicles' => $vehicles,
            'filters' => [
                'vehicle_id' => $vehicleId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'report_type' => $reportType
            ]
        ];
        $this->render('vehicle_report', $data);
    }
    
    public function costAnalysis() {
        $dateFrom = $_GET['date_from'] ?? date('Y-01-01'); // Prima zi a anului curent
        $dateTo = $_GET['date_to'] ?? date('Y-12-31'); // Ultima zi a anului curent
        $vehicleId = $_GET['vehicle_id'] ?? '';
        $analysisType = $_GET['analysis_type'] ?? 'monthly';
        $costType = $_GET['cost_type'] ?? 'all';
        
        // Obținem lista vehiculelor pentru filtru
        $vehicles = $this->vehicleModel->getActiveVehicles();
        
        // Generăm analiza costurilor
        $analysisData = $this->reportModel->generateCostAnalysis($dateFrom, $dateTo, $vehicleId, $analysisType, $costType);
        
        // Dacă este cerere de export
        if (isset($_GET['export'])) {
            $this->exportCostAnalysis($analysisData, $_GET['export'], $dateFrom, $dateTo);
            return;
        }
        
        $data = [
            'analysisData' => $analysisData,
            'vehicles' => $vehicles,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'vehicle_id' => $vehicleId,
                'analysis_type' => $analysisType,
                'cost_type' => $costType
            ]
        ];
        $this->render('cost_analysis', $data);
    }
    
    public function maintenanceReport() {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');
        $vehicleId = $_GET['vehicle_id'] ?? '';
        $maintenanceType = $_GET['maintenance_type'] ?? '';
        $status = $_GET['status'] ?? '';
        
        // Obținem lista vehiculelor pentru filtru
        $vehicles = $this->vehicleModel->getActiveVehicles();
        
        // Generăm raportul de mentenanță
        $reportData = $this->reportModel->generateMaintenanceReport($dateFrom, $dateTo, $vehicleId, $maintenanceType, $status);
        
        // Dacă este cerere de export
        if (isset($_GET['export'])) {
            $this->exportMaintenanceReport($reportData, $_GET['export'], $dateFrom, $dateTo);
            return;
        }
        
        $data = [
            // view expects $maintenanceData
            'maintenanceData' => $reportData,
            'vehicles' => $vehicles,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'vehicle_id' => $vehicleId,
                'maintenance_type' => $maintenanceType,
                'status' => $status
            ]
        ];
        // ensure variables are available inside the included template
        extract($data);
        include 'modules/reports/views/maintenance_report.php';
    }
    
    public function fuelReport() {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');
        $vehicleId = $_GET['vehicle_id'] ?? '';
        $reportType = $_GET['report_type'] ?? 'consumption';
        
        // Obținem lista vehiculelor pentru filtru
        $vehicles = $this->vehicleModel->getActiveVehicles();
        
        // Generăm raportul de combustibil
        $reportData = $this->reportModel->generateFuelReport($dateFrom, $dateTo, $vehicleId, $reportType);
        
        // Dacă este cerere de export
        if (isset($_GET['export'])) {
            $this->exportFuelReport($reportData, $_GET['export'], $dateFrom, $dateTo);
            return;
        }
        
        $data = [
            // view expects $fuelData
            'fuelData' => $reportData,
            'vehicles' => $vehicles,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'vehicle_id' => $vehicleId,
                'report_type' => $reportType
            ]
        ];
        extract($data);
        include 'modules/reports/views/fuel_report.php';
    }
    
    public function customReport() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->generateCustomReport();
        } else {
            // Afișăm formularul pentru raport personalizat
            $vehicles = $this->vehicleModel->getActiveVehicles();
            $data = ['vehicles' => $vehicles];
            extract($data);
            include 'modules/reports/views/custom_report.php';
        }
    }
    
    private function generateCustomReport() {
        $reportConfig = [
            'title' => $_POST['title'] ?? 'Raport Personalizat',
            'date_from' => $_POST['date_from'] ?? date('Y-m-01'),
            'date_to' => $_POST['date_to'] ?? date('Y-m-t'),
            'vehicle_ids' => $_POST['vehicle_ids'] ?? [],
            'include_fuel' => isset($_POST['include_fuel']),
            'include_maintenance' => isset($_POST['include_maintenance']),
            'include_insurance' => isset($_POST['include_insurance']),
            'include_costs' => isset($_POST['include_costs']),
            'grouping' => $_POST['grouping'] ?? 'vehicle'
        ];
        
        $reportData = $this->reportModel->generateCustomReport($reportConfig);
        
        // Dacă este cerere de export
        if (isset($_POST['export'])) {
            $this->exportCustomReport($reportData, $_POST['export'], $reportConfig);
            return;
        }
        
        $data = [
            'reportData' => $reportData,
            'reportConfig' => $reportConfig
        ];
        extract($data);
        include 'modules/reports/views/custom_report_result.php';
    }
    
    // Metode de export
    private function exportFleetReport($data, $format, $dateFrom, $dateTo) {
        $filename = "raport_flota_{$dateFrom}_{$dateTo}";
        
        if ($format === 'csv') {
            $this->exportToCSV($data, $filename, 'fleet');
        } elseif ($format === 'pdf') {
            $this->exportToPDF($data, $filename, 'fleet', $dateFrom, $dateTo);
        }
    }
    
    private function exportVehicleReport($data, $format, $vehicle, $dateFrom, $dateTo) {
        $plate = $vehicle['license_plate'] ?? ($vehicle['registration_number'] ?? 'vehicul');
        $filename = "raport_vehicul_{$plate}_{$dateFrom}_{$dateTo}";
        
        if ($format === 'csv') {
            $this->exportToCSV($data, $filename, 'vehicle');
        } elseif ($format === 'pdf') {
            $this->exportToPDF($data, $filename, 'vehicle', $dateFrom, $dateTo);
        }
    }
    
    private function exportCostAnalysis($data, $format, $dateFrom, $dateTo) {
        $filename = "analiza_costuri_{$dateFrom}_{$dateTo}";
        
        if ($format === 'csv') {
            $this->exportToCSV($data, $filename, 'cost');
        } elseif ($format === 'pdf') {
            $this->exportToPDF($data, $filename, 'cost', $dateFrom, $dateTo);
        }
    }
    
    private function exportMaintenanceReport($data, $format, $dateFrom, $dateTo) {
        $filename = "raport_mentenanta_{$dateFrom}_{$dateTo}";
        
        if ($format === 'csv') {
            $this->exportToCSV($data, $filename, 'maintenance');
        } elseif ($format === 'pdf') {
            $this->exportToPDF($data, $filename, 'maintenance', $dateFrom, $dateTo);
        }
    }
    
    private function exportFuelReport($data, $format, $dateFrom, $dateTo) {
        $filename = "raport_combustibil_{$dateFrom}_{$dateTo}";
        
        if ($format === 'csv') {
            $this->exportToCSV($data, $filename, 'fuel');
        } elseif ($format === 'pdf') {
            $this->exportToPDF($data, $filename, 'fuel', $dateFrom, $dateTo);
        }
    }
    
    private function exportCustomReport($data, $format, $config) {
        $filename = "raport_personalizat_" . date('Y-m-d');
        
        if ($format === 'csv') {
            $this->exportToCSV($data, $filename, 'custom');
        } elseif ($format === 'pdf') {
            $this->exportToPDF($data, $filename, 'custom', $config['date_from'], $config['date_to']);
        }
    }
    
    private function exportToCSV($data, $filename, $type) {
        // Write to temp as UTF-8 CSV first
        $tmp = fopen('php://temp', 'w+');
        switch ($type) {
            case 'fleet':
                $this->writeFleetCSV($tmp, $data);
                break;
            case 'vehicle':
                $this->writeVehicleCSV($tmp, $data);
                break;
            case 'cost':
                $this->writeCostCSV($tmp, $data);
                break;
            case 'maintenance':
                $this->writeMaintenanceCSV($tmp, $data);
                break;
            case 'fuel':
                $this->writeFuelCSV($tmp, $data);
                break;
            case 'custom':
                $this->writeCustomCSV($tmp, $data);
                break;
        }
        rewind($tmp);
        $csvUtf8 = stream_get_contents($tmp);
        fclose($tmp);

        // Excel-friendly: UTF-16LE with BOM and tab separator hint
        $excelData = mb_convert_encoding($csvUtf8, 'UTF-16LE', 'UTF-8');
        header('Content-Type: application/vnd.ms-excel; charset=UTF-16LE');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        // UTF-16LE BOM
        echo "\xFF\xFE";
        echo $excelData;
        exit;
    }
    
    private function exportToPDF($data, $filename, $type, $dateFrom, $dateTo) {
        // Prefer TCPDF for high-quality output; fallback to minimal generator, then CSV
        $wantInline = isset($_REQUEST['inline']) && (int)$_REQUEST['inline'] === 1; // inline view instead of download
        $wantPrint  = isset($_REQUEST['print']) && (int)$_REQUEST['print'] === 1;  // auto open print dialog
        
        // 1) Try TCPDF exporter
        try {
            require_once __DIR__ . '/../../../core/pdf_exporter_tcpdf.php';
            if (class_exists('TcpdfExporter')) {
                $tcpdf = new \TcpdfExporter();
                if ($type === 'fleet') {
                    $tcpdf->outputFleetReport($data, $dateFrom, $dateTo, $filename, $wantInline, $wantPrint);
                } elseif ($type === 'vehicle') {
                    $tcpdf->outputVehicleReport($data, $dateFrom, $dateTo, $filename, $wantInline, $wantPrint);
                } elseif ($type === 'fuel') {
                    $tcpdf->outputFuelReport($data, $dateFrom, $dateTo, $filename, $wantInline, $wantPrint);
                } elseif ($type === 'maintenance') {
                    $tcpdf->outputMaintenanceReport($data, $dateFrom, $dateTo, $filename, $wantInline, $wantPrint);
                } elseif ($type === 'cost') {
                    if (method_exists($tcpdf, 'outputCostAnalysisReport')) {
                        $tcpdf->outputCostAnalysisReport($data, $dateFrom, $dateTo, $filename, $wantInline, $wantPrint);
                    } else {
                        // fallback to next PDF generator since this method doesn't exist
                        throw new \Exception('Method not available');
                    }
                } elseif ($type === 'custom') {
                    if (method_exists($tcpdf, 'outputCustomReport')) {
                        $tcpdf->outputCustomReport($data, $dateFrom, $dateTo, $filename, $wantInline, $wantPrint);
                    } else {
                        // fallback to next PDF generator since this method doesn't exist
                        throw new \Exception('Method not available');
                    }
                }
                return; // already output
            }
        } catch (\Throwable $e) {
            // continue to fallback
        }

        // 2) Minimal internal PDF generator
        try {
            require_once __DIR__ . '/../../../core/pdf_exporter.php';
            if (class_exists('PdfExporter')) {
                $pdf = new \PdfExporter();
                if ($type === 'fleet') { $pdf->outputFleetReport($data, $dateFrom, $dateTo, $filename); return; }
                if ($type === 'vehicle') { $pdf->outputVehicleReport($data, $dateFrom, $dateTo, $filename); return; }
                if ($type === 'fuel') { $pdf->outputFuelReport($data, $dateFrom, $dateTo, $filename); return; }
                if ($type === 'maintenance') { $pdf->outputMaintenanceReport($data, $dateFrom, $dateTo, $filename); return; }
                if ($type === 'custom') { $pdf->outputCustomReport($data, $dateFrom, $dateTo, $filename); return; }
            }
        } catch (\Throwable $e) {
            // continue to CSV fallback
        }

        // 3) Fallback: export CSV so user still gets data
        $this->exportToCSV($data, $filename, $type);
    }
    
    private function writeFleetCSV($output, $data) {
        // Header CSV pentru raportul flotei
        fputcsv($output, [
            'Vehicul', 'Tip Vehicul', 'An Fabricație', 'Kilometraj',
            'Combustibil Consumat (L)', 'Cost Combustibil (RON)', 'Consum Mediu (L/100km)',
            'Cost Mentenanță (RON)', 'Cost Asigurări (RON)', 'Cost Total (RON)'
        ]);
        
        if (!empty($data['vehicles'])) {
            foreach ($data['vehicles'] as $vehicle) {
                fputcsv($output, [
                    $vehicle['license_plate'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model'],
                    $vehicle['vehicle_type'] ?? 'N/A',
                    $vehicle['year'] ?? 'N/A',
                    number_format($vehicle['odometer'] ?? 0, 0, ',', '.'),
                    number_format($vehicle['fuel_consumed'] ?? 0, 2, ',', '.'),
                    number_format($vehicle['fuel_cost'] ?? 0, 2, ',', '.'),
                    number_format($vehicle['avg_consumption'] ?? 0, 2, ',', '.'),
                    number_format($vehicle['maintenance_cost'] ?? 0, 2, ',', '.'),
                    number_format($vehicle['insurance_cost'] ?? 0, 2, ',', '.'),
                    number_format($vehicle['total_cost'] ?? 0, 2, ',', '.')
                ]);
            }
        }
    }
    
    private function writeVehicleCSV($output, $data) {
        // Header CSV pentru raportul vehiculului
        fputcsv($output, [
            'Data', 'Tip Operațiune', 'Descriere', 'Cost (RON)', 'Kilometraj', 'Observații'
        ]);
        
        if (!empty($data['timeline'])) {
            foreach ($data['timeline'] as $entry) {
                fputcsv($output, [
                    $entry['date'],
                    $entry['type'],
                    $entry['description'],
                    number_format($entry['cost'] ?? 0, 2, ',', '.'),
                    number_format($entry['odometer'] ?? 0, 0, ',', '.'),
                    $entry['notes'] ?? ''
                ]);
            }
        }
    }
    
    private function writeCostCSV($output, $data) {
        // Header CSV pentru analiza costurilor
        fputcsv($output, [
            'Perioada', 'Vehicul', 'Cost Combustibil (RON)', 'Cost Mentenanță (RON)', 
            'Cost Asigurări (RON)', 'Alte Costuri (RON)', 'Total (RON)'
        ]);
        
        if (!empty($data['breakdown'])) {
            foreach ($data['breakdown'] as $entry) {
                fputcsv($output, [
                    $entry['period'],
                    $entry['vehicle'] ?? 'Toate vehiculele',
                    number_format($entry['fuel_cost'] ?? 0, 2, ',', '.'),
                    number_format($entry['maintenance_cost'] ?? 0, 2, ',', '.'),
                    number_format($entry['insurance_cost'] ?? 0, 2, ',', '.'),
                    number_format($entry['other_cost'] ?? 0, 2, ',', '.'),
                    number_format($entry['total_cost'] ?? 0, 2, ',', '.')
                ]);
            }
        }
    }
    
    private function writeMaintenanceCSV($output, $data) {
        // Header CSV pentru raportul de mentenanță
        fputcsv($output, [
            'Vehicul', 'Data Programată', 'Data Completării', 'Tip Mentenanță', 
            'Descriere', 'Furnizor', 'Cost (RON)', 'Status'
        ]);
        
        if (!empty($data['maintenance_records'])) {
            foreach ($data['maintenance_records'] as $record) {
                fputcsv($output, [
                    $record['vehicle_info'],
                    $record['scheduled_date'],
                    $record['completed_date'] ?? 'Necompletat',
                    $record['maintenance_type'],
                    $record['description'],
                    $record['service_provider'] ?? 'N/A',
                    number_format($record['cost'] ?? 0, 2, ',', '.'),
                    $record['status']
                ]);
            }
        }
    }
    
    private function writeFuelCSV($output, $data) {
        // Header CSV pentru raportul de combustibil
        fputcsv($output, [
            'Vehicul', 'Data', 'Cantitate (L)', 'Cost Total (RON)', 'Cost/L (RON)', 
            'Kilometraj', 'Stația', 'Consum (L/100km)'
        ]);
        
        if (!empty($data['fuel_records'])) {
            foreach ($data['fuel_records'] as $record) {
                fputcsv($output, [
                    $record['vehicle_info'],
                    $record['fuel_date'],
                    number_format($record['liters'] ?? 0, 2, ',', '.'),
                    number_format(($record['total_cost'] ?? ($record['cost'] ?? 0)), 2, ',', '.'),
                    number_format($record['cost_per_liter'] ?? 0, 2, ',', '.'),
                    number_format(($record['mileage'] ?? ($record['odometer'] ?? 0)), 0, ',', '.'),
                    $record['station'] ?? 'N/A',
                    number_format($record['consumption'] ?? 0, 2, ',', '.')
                ]);
            }
        }
    }
    
    private function writeCustomCSV($output, $data) {
        // Header generic pentru raport personalizat
        if (!empty($data['headers'])) {
            fputcsv($output, $data['headers']);
        }
        
        if (!empty($data['rows'])) {
            foreach ($data['rows'] as $row) {
                fputcsv($output, $row);
            }
        }
    }
    
    public function generatePeriodicalReports() {
        // Generează rapoarte periodice automate (lunar, săptămânal, etc.)
        $reportType = $_GET['type'] ?? 'monthly';
        
        switch ($reportType) {
            case 'weekly':
                $this->generateWeeklyReports();
                break;
            case 'monthly':
                $this->generateMonthlyReports();
                break;
            case 'quarterly':
                $this->generateQuarterlyReports();
                break;
            case 'yearly':
                $this->generateYearlyReports();
                break;
        }
        
        if (isset($_GET['ajax'])) {
            $this->json(['success' => true, 'message' => 'Rapoartele au fost generate cu succes']);
        } else {
            $_SESSION['success'] = 'Rapoartele periodice au fost generate cu succes!';
            $this->redirect('/modules/reports/');
        }
    }
    
    private function generateWeeklyReports() {
        $startDate = date('Y-m-d', strtotime('last monday'));
        $endDate = date('Y-m-d', strtotime('last sunday'));
        
        // Generăm raportul săptămânal
        $data = $this->reportModel->generateFleetReport($startDate, $endDate, '', 'summary');
        
        // Salvăm raportul în baza de date
        $this->reportModel->saveGeneratedReport([
            'type' => 'weekly',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data' => json_encode($data),
            'generated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function generateMonthlyReports() {
        $startDate = date('Y-m-01', strtotime('last month'));
        $endDate = date('Y-m-t', strtotime('last month'));
        
        // Generăm raportul lunar
        $data = $this->reportModel->generateFleetReport($startDate, $endDate, '', 'detailed');
        
        // Salvăm raportul în baza de date
        $this->reportModel->saveGeneratedReport([
            'type' => 'monthly',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data' => json_encode($data),
            'generated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function generateQuarterlyReports() {
        // Calculăm trimestrul anterior
        $currentQuarter = ceil(date('n') / 3);
        $year = date('Y');
        
        if ($currentQuarter == 1) {
            $quarter = 4;
            $year--;
        } else {
            $quarter = $currentQuarter - 1;
        }
        
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;
        
        $startDate = $year . '-' . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . '-01';
        $endDate = date('Y-m-t', strtotime($year . '-' . str_pad($endMonth, 2, '0', STR_PAD_LEFT) . '-01'));
        
        // Generăm raportul trimestrial
        $data = $this->reportModel->generateFleetReport($startDate, $endDate, '', 'detailed');
        
        // Salvăm raportul în baza de date
        $this->reportModel->saveGeneratedReport([
            'type' => 'quarterly',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data' => json_encode($data),
            'generated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function generateYearlyReports() {
        $year = date('Y') - 1; // Anul anterior
        $startDate = $year . '-01-01';
        $endDate = $year . '-12-31';
        
        // Generăm raportul anual
        $data = $this->reportModel->generateFleetReport($startDate, $endDate, '', 'detailed');
        
        // Salvăm raportul în baza de date
        $this->reportModel->saveGeneratedReport([
            'type' => 'yearly',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data' => json_encode($data),
            'generated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // --- AJAX/Data endpoints for charts and generator ---

    public function fleetOverviewData() {
        // Current month summary split by cost categories
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-t');
        $analysis = $this->reportModel->generateCostAnalysis($dateFrom, $dateTo, '', 'monthly', 'all');
        $summary = $analysis['summary'] ?? ['fuel_cost'=>0,'maintenance_cost'=>0,'insurance_cost'=>0,'other_cost'=>0];
        $this->json([
            'labels' => ['Combustibil', 'Întreținere', 'Asigurări', 'Altele'],
            'values' => [
                (float)($summary['fuel_cost'] ?? 0),
                (float)($summary['maintenance_cost'] ?? 0),
                (float)($summary['insurance_cost'] ?? 0),
                (float)($summary['other_cost'] ?? 0)
            ]
        ]);
    }

    public function costData() {
        // Last 6 months stacked costs
        $dateTo = date('Y-m-t');
        $dateFrom = date('Y-m-01', strtotime('-5 months'));
        $analysis = $this->reportModel->generateCostAnalysis($dateFrom, $dateTo, '', 'monthly', 'all');
        $monthly = $analysis['monthly'] ?? [];
        usort($monthly, function($a,$b){
            return ($a['year'].$a['month']) <=> ($b['year'].$b['month']);
        });
        $labels = [];$fuel=[];$maint=[];$other=[];
        foreach ($monthly as $m) {
            $labels[] = sprintf('%04d-%02d', $m['year'], $m['month']);
            $fuel[] = round($m['fuel_cost'] ?? 0, 2);
            $maint[] = round($m['maintenance_cost'] ?? 0, 2);
            $other[] = round(($m['insurance_cost'] ?? 0) + ($m['other_cost'] ?? 0), 2);
        }
        $this->json([
            'labels' => $labels,
            'fuel' => $fuel,
            'maintenance' => $maint,
            'other' => $other,
        ]);
    }

    public function maintenanceData() {
        // Last 6 months: planned vs completed counts
        $dateTo = date('Y-m-t');
        $dateFrom = date('Y-m-01', strtotime('-5 months'));
        $sql = "SELECT DATE_FORMAT(service_date,'%Y-%m') AS period, status, COUNT(*) AS cnt
                FROM maintenance
                WHERE service_date BETWEEN ? AND ?
                GROUP BY period, status";
        $rows = $this->db->fetchAll($sql, [$dateFrom, $dateTo]);
        // init months
        $labels = [];$planned=[];$completed=[];
        $months = [];
        $cursor = new DateTime($dateFrom);
        $end = new DateTime($dateTo);
        $end->modify('first day of next month');
        while ($cursor < $end) { $k=$cursor->format('Y-m'); $months[$k]=['planned'=>0,'completed'=>0]; $cursor->modify('+1 month'); }
        foreach ($rows as $r) {
            $k = $r['period'];
            if (!isset($months[$k])) $months[$k] = ['planned'=>0,'completed'=>0];
            if (in_array($r['status'], ['scheduled','in_progress'])) { $months[$k]['planned'] += (int)$r['cnt']; }
            if ($r['status'] === 'completed') { $months[$k]['completed'] += (int)$r['cnt']; }
        }
        foreach ($months as $k=>$v) { $labels[]=$k; $planned[]=$v['planned']; $completed[]=$v['completed']; }
        $this->json(['labels'=>$labels,'planned'=>$planned,'completed'=>$completed]);
    }

    public function fuelConsumptionData() {
        // Last 6 months average consumption per top 3 vehicles
        $dateTo = date('Y-m-t');
        $labels = [];
        for ($i=5; $i>=0; $i--) { $labels[] = date('Y-m', strtotime("-{$i} months", strtotime($dateTo))); }
        $vehicles = array_slice($this->vehicleModel->getActiveVehicles(), 0, 3);
        $series = [];
        foreach ($vehicles as $v) {
            $cons = [];
            foreach ($labels as $ym) {
                [$y,$m] = explode('-', $ym);
                $from = date('Y-m-01', strtotime($ym.'-01'));
                $to = date('Y-m-t', strtotime($ym.'-01'));
                $cons[] = (float)$this->reportModel->generateFuelReport($from, $to, $v['id'])['statistics']['avg_consumption'] ?? 0.0;
            }
            $series[] = [
                'name' => ($v['registration_number'] ?? ($v['license_plate'] ?? 'Vehicul')).' - '.(($v['brand'] ?? $v['make'] ?? '').' '.($v['model'] ?? '')),
                'consumption' => $cons,
            ];
        }
        $this->json(['labels'=>$labels,'vehicles'=>$series]);
    }

    public function generateAjax() {
        // Generate a report snippet based on filters
        $input = file_get_contents('php://input');
        $payload = json_decode($input, true) ?: $_POST;
        $type = $payload['report_type'] ?? 'fleet';
        $dateFrom = $payload['date_from'] ?? date('Y-m-01');
        $dateTo = $payload['date_to'] ?? date('Y-m-t');
        $vehicleId = $payload['vehicle_id'] ?? '';

        try {
            $html = '';
            if ($type === 'fleet') {
                $data = $this->reportModel->generateFleetReport($dateFrom, $dateTo, $vehicleId, 'summary');
                $summary = $data['summary'];
                $html .= '<div class="row g-3">';
                $card = function($title,$value,$icon,$class) {
                    return "<div class='col-md-3'><div class='card text-white bg-{$class}'><div class='card-body'><div class='d-flex justify-content-between'><div><div class='h4 mb-0'>".
                    number_format((float)$value, 2, ',', '.')."</div><div>{$title}</div></div><div class='align-self-center'><i class='fas fa-{$icon} fa-2x'></i></div></div></div></div></div>";
                };
                $html .= $card('Cost combustibil (RON)', $summary['total_fuel_cost'] ?? 0, 'gas-pump', 'success');
                $html .= $card('Cost mentenanță (RON)', $summary['total_maintenance_cost'] ?? 0, 'wrench', 'warning');
                $html .= $card('Vehicule active', $summary['total_vehicles'] ?? 0, 'car', 'primary');
                $html .= $card('Preț mediu combustibil (RON/L)', $summary['avg_fuel_price'] ?? 0, 'coins', 'info');
                $html .= '</div>';
            } elseif ($type === 'vehicle' && !empty($vehicleId)) {
                $data = $this->reportModel->generateVehicleReport($vehicleId, $dateFrom, $dateTo, 'detailed');
                $c = $data['costs'];
                $html .= '<ul class="list-group">';
                $html .= '<li class="list-group-item d-flex justify-content-between"><span>Combustibil</span><strong>'.number_format($c['fuel_cost'] ?? 0,2,',','.').' RON</strong></li>';
                $html .= '<li class="list-group-item d-flex justify-content-between"><span>Mentenanță</span><strong>'.number_format($c['maintenance_cost'] ?? 0,2,',','.').' RON</strong></li>';
                $html .= '<li class="list-group-item d-flex justify-content-between"><span>Total</span><strong>'.number_format($c['total_cost'] ?? 0,2,',','.').' RON</strong></li>';
                $html .= '</ul>';
            } elseif ($type === 'costs') {
                $data = $this->reportModel->generateCostAnalysis($dateFrom, $dateTo, $vehicleId, 'monthly', 'all');
                $html .= '<div class="table-responsive"><table class="table table-striped table-sm"><thead><tr><th>Luna</th><th>Combustibil</th><th>Mentenanță</th><th>Asigurări</th><th>Total</th></tr></thead><tbody>';
                foreach (($data['breakdown'] ?? []) as $row) {
                    $html .= '<tr>'
                        .'<td>'.htmlspecialchars($row['period']).'</td>'
                        .'<td>'.number_format($row['fuel_cost'] ?? 0,2,',','.').'</td>'
                        .'<td>'.number_format($row['maintenance_cost'] ?? 0,2,',','.').'</td>'
                        .'<td>'.number_format($row['insurance_cost'] ?? 0,2,',','.').'</td>'
                        .'<td>'.number_format($row['total_cost'] ?? 0,2,',','.').'</td>'
                        .'</tr>';
                }
                $html .= '</tbody></table></div>';
            } else {
                return $this->json(['success'=>false,'message'=>'Tip de raport neacceptat sau lipsesc parametrii necesari'], 400);
            }
            return $this->json(['success'=>true,'report'=>['html'=>$html]]);
        } catch (\Throwable $e) {
            return $this->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function exportAjax() {
        $payload = $_POST;
        $type = $payload['report_type'] ?? 'fleet';
        $format = strtolower($payload['export_format'] ?? 'csv');
        $dateFrom = $payload['date_from'] ?? date('Y-m-01');
        $dateTo = $payload['date_to'] ?? date('Y-m-t');
        $vehicleId = $payload['vehicle_id'] ?? '';

        if ($type === 'fleet') {
            $data = $this->reportModel->generateFleetReport($dateFrom, $dateTo, $vehicleId, 'summary');
            return $this->exportFleetReport($data, $format, $dateFrom, $dateTo);
        } elseif ($type === 'vehicle' && !empty($vehicleId)) {
            $veh = $this->vehicleModel->getById($vehicleId) ?: ['registration_number'=>'vehicul'];
            // normalize
            $veh['license_plate'] = $veh['registration_number'] ?? ($veh['license_plate'] ?? 'vehicul');
            $data = $this->reportModel->generateVehicleReport($vehicleId, $dateFrom, $dateTo, 'detailed');
            return $this->exportVehicleReport($data, $format, $veh, $dateFrom, $dateTo);
        } elseif ($type === 'costs') {
            $data = $this->reportModel->generateCostAnalysis($dateFrom, $dateTo, $vehicleId, 'monthly', 'all');
            return $this->exportCostAnalysis($data, $format, $dateFrom, $dateTo);
        } elseif ($type === 'maintenance') {
            $data = $this->reportModel->generateMaintenanceReport($dateFrom, $dateTo, $vehicleId);
            return $this->exportMaintenanceReport($data, $format, $dateFrom, $dateTo);
        } elseif ($type === 'fuel') {
            $data = $this->reportModel->generateFuelReport($dateFrom, $dateTo, $vehicleId);
            return $this->exportFuelReport($data, $format, $dateFrom, $dateTo);
        }
        return $this->json(['success'=>false,'message'=>'Tip raport necunoscut'], 400);
    }
}
?>
