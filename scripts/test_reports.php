<?php
// Quick smoke tests for report generation endpoints
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/model.php';
require_once __DIR__ . '/../modules/reports/models/report.php';
require_once __DIR__ . '/../modules/vehicles/models/vehicle.php';
require_once __DIR__ . '/../modules/fuel/models/FuelConsumption.php';
require_once __DIR__ . '/../modules/maintenance/models/maintenance.php';

function out($label, $value) { echo str_pad($label, 35, ' ') . ": " . $value . PHP_EOL; }

echo "== Reports smoke test ==\n";
$db = Database::getInstance();
$report = new Report();
$vehicleModel = new Vehicle();

$from = date('Y-m-01', strtotime('-5 months'));
$to   = date('Y-m-t');

// Fleet
$fleet = $report->generateFleetReport($from, $to, '', 'summary');
out('Fleet vehicles counted', count($fleet['vehicles'] ?? []));
out('Fleet total fuel cost', number_format($fleet['summary']['total_fuel_cost'] ?? 0, 2));

// Cost analysis
$cost = $report->generateCostAnalysis($from, $to, '', 'monthly', 'all');
out('Monthly breakdown entries', count($cost['monthly'] ?? []));

// Maintenance
$maint = $report->generateMaintenanceReport($from, $to, '', '', '');
out('Maintenance records', count($maint['maintenance_records'] ?? []));

// Fuel
$fuel = $report->generateFuelReport($from, $to, '', 'consumption');
out('Fuel records', count($fuel['fuel_records'] ?? []));

echo "Done. If numbers look sane, reports are wired.\n";
