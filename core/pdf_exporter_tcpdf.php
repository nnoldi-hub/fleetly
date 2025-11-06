<?php
// TCPDF-based exporter. Requires TCPDF installed (composer or manual).

class TcpdfExporter {
    private function ensureTcpdfLoaded(): void {
        if (class_exists('TCPDF')) return;
        $candidates = [
            __DIR__ . '/vendor/tcpdf_min/tcpdf.php',
            __DIR__ . '/vendor/tcpdf/tcpdf.php',
            __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php',
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/tcpdf/tcpdf.php',
        ];
        foreach ($candidates as $p) {
            if (is_file($p)) {
                // If it's tcpdf.php, also check the companion autoconfig exists beside it
                if (substr($p, -9) === 'tcpdf.php') {
                    $auto = dirname($p) . DIRECTORY_SEPARATOR . 'tcpdf_autoconfig.php';
                    if (!is_file($auto)) {
                        // Incomplete copy; skip this candidate
                        continue;
                    }
                }
                require_once $p;
                break;
            }
        }
        if (!class_exists('TCPDF')) {
            throw new \RuntimeException('TCPDF library missing or incomplete. Recommended: composer require tecnickcom/tcpdf. Manual: copy entire tcpdf_min folder (including tcpdf_autoconfig.php, fonts/) into core/vendor/tcpdf_min/.');
        }
    }

    private function asciiRo(string $s): string {
        // Replace Romanian diacritics with ASCII equivalents
        $map = [
            'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t',
            'Ă'=>'A','Â'=>'A','Î'=>'I','Ș'=>'S','Ş'=>'S','Ț'=>'T','Ţ'=>'T',
        ];
        return strtr($s, $map);
    }

    private function out(string $filename, bool $inline, bool $autoPrint, \TCPDF $pdf): void {
        if ($autoPrint) { $pdf->IncludeJS('print(true);'); }
        // Clear any previous output to avoid "Some data has already been output" error
        while (ob_get_level() > 0) { @ob_end_clean(); }
        $mode = $inline ? 'I' : 'D'; // inline view or download
        $pdf->Output($filename . '.pdf', $mode);
        exit;
    }

    public function outputFleetReport(array $data, string $dateFrom, string $dateTo, string $filename, bool $inline = true, bool $autoPrint = false): void {
        $this->ensureTcpdfLoaded();
    $orientation = 'L'; // Fleet has many columns => landscape
    $pdf = new \TCPDF($orientation, 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Fleet Management');
        $pdf->SetAuthor('Fleet Management');
        $pdf->SetTitle($this->asciiRo('Raport flotă'));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(8, 10, 8);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 11);

        // Title
    $pdf->SetFont('helvetica','B',15);
        $pdf->Cell(0, 8, $this->asciiRo('Raport flotă'), 0, 1, 'L');
    $pdf->SetFont('helvetica','',11);
        $pdf->Cell(0, 6, $this->asciiRo("Perioada: {$dateFrom} - {$dateTo}"), 0, 1, 'L');
    // quick summary
    $vehCount = isset($data['vehicles']) ? count($data['vehicles']) : 0;
    $totalCost = 0.0; foreach (($data['vehicles'] ?? []) as $v) { $totalCost += (float)($v['total_cost'] ?? 0); }
    $pdf->SetFont('helvetica','',10);
    $pdf->Cell(0, 6, $this->asciiRo("Vehicule: {$vehCount} | Cost total: ".number_format($totalCost,2,',','.')." RON"), 0, 1, 'L');
        $pdf->Ln(2);

        // Table header
        $headers = [
            'Vehicul','Tip','An','Km','Comb(L)','Cost Comb','Consum','Cost Ment','Cost Asig','Total'
        ];
        // Column widths (sum <= 277mm printable area approx in landscape with margins)
    $w = [55, 24, 14, 24, 22, 28, 24, 28, 28, 32];
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.2);
        $pdf->SetFont('helvetica','B',9);
        foreach ($headers as $i => $h) {
            $pdf->Cell($w[$i], 8, $this->asciiRo($h), 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica','',10);

        if (!empty($data['vehicles'])) {
            foreach ($data['vehicles'] as $v) {
                $veh = trim(($v['license_plate'] ?? ($v['registration_number'] ?? '')) . ' - ' . (($v['make'] ?? ($v['brand'] ?? '')) . ' ' . ($v['model'] ?? '')));
                $row = [
                    $veh,
                    $v['vehicle_type'] ?? 'N/A',
                    (string)($v['year'] ?? ''),
                    number_format($v['odometer'] ?? 0, 0, ',', '.'),
                    number_format($v['fuel_consumed'] ?? 0, 2, ',', '.'),
                    number_format($v['fuel_cost'] ?? 0, 2, ',', '.'),
                    number_format($v['avg_consumption'] ?? 0, 2, ',', '.'),
                    number_format($v['maintenance_cost'] ?? 0, 2, ',', '.'),
                    number_format($v['insurance_cost'] ?? 0, 2, ',', '.'),
                    number_format($v['total_cost'] ?? 0, 2, ',', '.'),
                ];
                // Cells: text left, numbers right
                $align = ['L','L','C','R','R','R','R','R','R','R'];
                foreach ($row as $i => $val) {
                    $txt = $this->asciiRo((string)$val);
                    $pdf->Cell($w[$i], 8, $txt, 1, 0, $align[$i], false);
                }
                $pdf->Ln();
            }
        } else {
            $pdf->Cell(array_sum($w), 8, $this->asciiRo('Nu exista date pentru perioada selectata'), 1, 1, 'C');
        }

        // Totals row
        if (!empty($data['vehicles'])) {
            $sum = ['fuel'=>0,'maint'=>0,'ins'=>0,'total'=>0,'liters'=>0];
            foreach ($data['vehicles'] as $v) {
                $sum['fuel'] += (float)($v['fuel_cost'] ?? 0);
                $sum['maint'] += (float)($v['maintenance_cost'] ?? 0);
                $sum['ins'] += (float)($v['insurance_cost'] ?? 0);
                $sum['total'] += (float)($v['total_cost'] ?? 0);
                $sum['liters'] += (float)($v['fuel_consumed'] ?? 0);
            }
            $pdf->SetFont('helvetica','B',10);
            $pdf->Cell($w[0]+$w[1]+$w[2]+$w[3], 8, $this->asciiRo('Total'), 1, 0, 'R', true);
            $pdf->Cell($w[4], 8, $this->asciiRo(number_format($sum['liters'],2,',','.')), 1, 0, 'R');
            $pdf->Cell($w[5], 8, $this->asciiRo(number_format($sum['fuel'],2,',','.')), 1, 0, 'R');
            $pdf->Cell($w[6], 8, $this->asciiRo('-'), 1, 0, 'C');
            $pdf->Cell($w[7], 8, $this->asciiRo(number_format($sum['maint'],2,',','.')), 1, 0, 'R');
            $pdf->Cell($w[8], 8, $this->asciiRo(number_format($sum['ins'],2,',','.')), 1, 0, 'R');
            $pdf->Cell($w[9], 8, $this->asciiRo(number_format($sum['total'],2,',','.')), 1, 1, 'R');
        }

        $this->out($filename, $inline, $autoPrint, $pdf);
    }

    public function outputFuelReport(array $data, string $dateFrom, string $dateTo, string $filename, bool $inline = true, bool $autoPrint = false): void {
        $this->ensureTcpdfLoaded();
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false); $pdf->setPrintFooter(false); $pdf->SetMargins(8,10,8); $pdf->AddPage();
        $pdf->SetFont('helvetica','B',15); $pdf->Cell(0,8,$this->asciiRo('Raport combustibil'),0,1,'L');
        $pdf->SetFont('helvetica','',11); $pdf->Cell(0,6,$this->asciiRo("Perioada: {$dateFrom} - {$dateTo}"),0,1,'L'); $pdf->Ln(2);
        $headers=['Vehicul','Data','Litri','Cost Total','Cost/L','Km','Statie','Consum'];
        $w=[55,24,18,24,18,24,30,22];
        $pdf->SetFillColor(230,230,230); $pdf->SetDrawColor(180,180,180); $pdf->SetFont('helvetica','B',10);
        foreach($headers as $i=>$h){ $pdf->Cell($w[$i],8,$this->asciiRo($h),1,0,'C',true);} $pdf->Ln();
        $pdf->SetFont('helvetica','',10);
        $totL=0; $totC=0;
        foreach(($data['fuel_records']??[]) as $r){
            $row=[
                $r['vehicle_info'] ?? (($r['license_plate']??'').' - '.(($r['make']??'').' '.($r['model']??''))),
                $r['fuel_date'] ?? '',
                number_format($r['liters']??0,2,',','.'),
                number_format(($r['total_cost']??($r['cost']??0)),2,',','.'),
                number_format($r['cost_per_liter']??0,2,',','.'),
                number_format(($r['mileage']??($r['odometer']??0)),0,',','.'),
                $r['station'] ?? 'N/A',
                number_format($r['consumption']??0,2,',','.')
            ];
            $totL += (float)($r['liters']??0); $totC += (float)($r['total_cost']??($r['cost']??0));
            $align=['L','C','R','R','R','R','L','R'];
            foreach($row as $i=>$val){ $pdf->Cell($w[$i],8,$this->asciiRo((string)$val),1,0,$align[$i]); }
            $pdf->Ln();
        }
        if (($data['fuel_records']??[]) !== []){
            $pdf->SetFont('helvetica','B',10);
            $pdf->Cell($w[0]+$w[1],8,$this->asciiRo('Total'),1,0,'R',true);
            $pdf->Cell($w[2],8,$this->asciiRo(number_format($totL,2,',','.')),1,0,'R');
            $pdf->Cell($w[3],8,$this->asciiRo(number_format($totC,2,',','.')),1,0,'R');
            $pdf->Cell($w[4]+$w[5]+$w[6]+$w[7],8,$this->asciiRo(' '),1,1,'R');
        }
        $this->out($filename,$inline,$autoPrint,$pdf);
    }

    public function outputMaintenanceReport(array $data, string $dateFrom, string $dateTo, string $filename, bool $inline=true, bool $autoPrint=false): void {
        $this->ensureTcpdfLoaded();
        $pdf = new \TCPDF('L','mm','A4',true,'UTF-8',false);
        $pdf->setPrintHeader(false); $pdf->setPrintFooter(false); $pdf->SetMargins(8,10,8); $pdf->AddPage();
        $pdf->SetFont('helvetica','B',15); $pdf->Cell(0,8,$this->asciiRo('Raport mentenanta'),0,1,'L');
        $pdf->SetFont('helvetica','',11); $pdf->Cell(0,6,$this->asciiRo("Perioada: {$dateFrom} - {$dateTo}"),0,1,'L'); $pdf->Ln(2);
        $headers=['Vehicul','Data','Tip','Descriere','Furnizor','Cost','Status'];
        $w=[55,24,28,80,40,24,24];
        $pdf->SetFillColor(230,230,230); $pdf->SetDrawColor(180,180,180); $pdf->SetFont('helvetica','B',10);
        foreach($headers as $i=>$h){ $pdf->Cell($w[$i],8,$this->asciiRo($h),1,0,'C',true);} $pdf->Ln();
        $pdf->SetFont('helvetica','',10); $tot=0.0;
        foreach(($data['maintenance_records']??[]) as $r){
            $veh = $r['vehicle_info'] ?? ((($r['license_plate']??'').' - '.(($r['make']??'').' '.($r['model']??''))));
            $row=[ $veh, $r['scheduled_date']??'', $r['maintenance_type']??'', $r['description']??'', $r['service_provider']??'N/A', number_format($r['cost']??0,2,',','.'), $r['status']??'' ];
            $tot += (float)($r['cost']??0); $align=['L','C','L','L','L','R','C'];
            foreach($row as $i=>$val){ $pdf->Cell($w[$i],8,$this->asciiRo((string)$val),1,0,$align[$i]); }
            $pdf->Ln();
        }
        if (($data['maintenance_records']??[]) !== []){
            $pdf->SetFont('helvetica','B',10);
            $pdf->Cell($w[0]+$w[1]+$w[2]+$w[3]+$w[4],8,$this->asciiRo('Total'),1,0,'R',true);
            $pdf->Cell($w[5],8,$this->asciiRo(number_format($tot,2,',','.')),1,0,'R');
            $pdf->Cell($w[6],8,'',1,1,'R');
        }
        $this->out($filename,$inline,$autoPrint,$pdf);
    }

    public function outputCostAnalysisReport(array $data, string $dateFrom, string $dateTo, string $filename, bool $inline=true, bool $autoPrint=false): void {
        $this->ensureTcpdfLoaded();
        $pdf = new \TCPDF('P','mm','A4',true,'UTF-8',false);
        $pdf->setPrintHeader(false); $pdf->setPrintFooter(false); $pdf->SetMargins(8,10,8); $pdf->AddPage();
        $pdf->SetFont('helvetica','B',15); $pdf->Cell(0,8,$this->asciiRo('Analiza costuri'),0,1,'L');
        $pdf->SetFont('helvetica','',11); $pdf->Cell(0,6,$this->asciiRo("Perioada: {$dateFrom} - {$dateTo}"),0,1,'L'); $pdf->Ln(2);
        $headers=['Perioada','Comb','Ment','Asig','Total']; $w=[40,30,30,30,35];
        $pdf->SetFillColor(230,230,230); $pdf->SetDrawColor(180,180,180); $pdf->SetFont('helvetica','B',10);
        foreach($headers as $i=>$h){ $pdf->Cell($w[$i],8,$this->asciiRo($h),1,0,'C',true);} $pdf->Ln();
        $pdf->SetFont('helvetica','',10); $sum=['f'=>0,'m'=>0,'i'=>0,'t'=>0];
        foreach (($data['breakdown']??[]) as $row){
            $sum['f'] += (float)($row['fuel_cost']??0); $sum['m'] += (float)($row['maintenance_cost']??0); $sum['i'] += (float)($row['insurance_cost']??0); $sum['t'] += (float)($row['total_cost']??0);
            $cells=[ $row['period']??'', number_format($row['fuel_cost']??0,2,',','.'), number_format($row['maintenance_cost']??0,2,',','.'), number_format($row['insurance_cost']??0,2,',','.'), number_format($row['total_cost']??0,2,',','.') ];
            $align=['C','R','R','R','R'];
            foreach($cells as $i=>$v){ $pdf->Cell($w[$i],8,$this->asciiRo((string)$v),1,0,$align[$i]); }
            $pdf->Ln();
        }
        if (($data['breakdown']??[]) !== []){
            $pdf->SetFont('helvetica','B',10);
            $pdf->Cell($w[0],8,$this->asciiRo('Total'),1,0,'R',true);
            $pdf->Cell($w[1],8,$this->asciiRo(number_format($sum['f'],2,',','.')),1,0,'R');
            $pdf->Cell($w[2],8,$this->asciiRo(number_format($sum['m'],2,',','.')),1,0,'R');
            $pdf->Cell($w[3],8,$this->asciiRo(number_format($sum['i'],2,',','.')),1,0,'R');
            $pdf->Cell($w[4],8,$this->asciiRo(number_format($sum['t'],2,',','.')),1,1,'R');
        }
        $this->out($filename,$inline,$autoPrint,$pdf);
    }

    // Stubs for other reports; can be expanded similarly
    public function outputVehicleReport(array $data, string $dateFrom, string $dateTo, string $filename, bool $inline = true, bool $autoPrint = false): void {
        $this->ensureTcpdfLoaded();
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false); $pdf->setPrintFooter(false); $pdf->AddPage();
        $pdf->SetFont('helvetica','',12);
        $pdf->Write(6, $this->asciiRo('Raport vehicul - utilizati CSV pentru detalii'));
        $this->out($filename, $inline, $autoPrint, $pdf);
    }

    // Note: Specialized methods for Fuel/Maintenance/Cost are implemented above.
}

?>
