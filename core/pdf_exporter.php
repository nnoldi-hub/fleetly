<?php
// Minimal PDF generator (no external deps) to produce simple text/tabular PDFs.
// Not fancy, but outputs valid PDF. For full features, replace with TCPDF/FPDF later.

class PdfExporter {
    private function esc($s) {
        if ($s === null) return '';
        $s = (string)$s;
        // Replace Romanian diacritics -> ASCII
        $map = [
            'ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t',
            'Ă'=>'A','Â'=>'A','Î'=>'I','Ș'=>'S','Ş'=>'S','Ț'=>'T','Ţ'=>'T'
        ];
        $s = strtr($s, $map);
        // Strip other non-ASCII bytes to avoid breaking PDF literal strings
        $s = preg_replace('/[^\x20-\x7E]/', '', $s);
        // Escape PDF special chars
        $s = str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $s);
        // Replace newlines
        return preg_replace("/[\r\n]+/", ' ', $s);
    }

    private function emitSimplePdf(array $lines, string $filename, string $orientation = 'P') {
        // Page size A4 in points
        $w = 595; $h = 842; // portrait
        if (strtoupper($orientation) === 'L') { [$w,$h] = [$h,$w]; }

    // Build text stream: Helvetica 12pt, 18pt leading, start at 0.5" from left, 1" from top
    $content = "BT\n/F1 12 Tf\n18 TL\n";
    $startX = 36; // 0.5 inch
    $startY = $h - 72; // 1 inch margin from top
    // Use Tm to set absolute text matrix to avoid viewer differences
    $content .= sprintf('1 0 0 1 %d %d Tm\n', $startX, $startY);
        $first = true;
        foreach ($lines as $ln) {
            if (!$first) { $content .= "T* "; } else { $first = false; }
            $content .= '(' . $this->esc($ln) . ") Tj\n";
        }
        $content .= "ET\n";

        $objects = [];
        $add = function($obj) use (&$objects) { $objects[] = $obj; return count($objects); };

        $catalogId = $add("<</Type /Catalog /Pages 2 0 R>>");
        $pagesId   = $add("<</Type /Pages /Kids [3 0 R] /Count 1>>");
        $pageId    = $add("<</Type /Page /Parent 2 0 R /MediaBox [0 0 $w $h] /Resources <</Font <</F1 5 0 R>>>> /Contents 4 0 R>>");
    $streamObj = "<</Length ".strlen($content)." >>\nstream\n$content"."endstream\n";
        $contentId = $add($streamObj);
        $fontId    = $add("<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>");

        // Build PDF
        $out = "%PDF-1.4\n";
        $offsets = [0];
        $offsets[1] = strlen($out);
        $out .= "1 0 obj\n".$objects[0]."\nendobj\n";
        for ($i=2; $i<=count($objects); $i++) {
            $offsets[$i] = strlen($out);
            $out .= $i." 0 obj\n".$objects[$i-1]."\nendobj\n";
        }
        $xrefPos = strlen($out);
        $count = count($objects) + 1;
        $out .= "xref\n0 $count\n0000000000 65535 f \n";
        for ($i=1; $i<=$count-1; $i++) {
            $out .= sprintf('%010d 00000 n ', $offsets[$i])."\n";
        }
        $out .= "trailer\n<</Size $count /Root 1 0 R>>\nstartxref\n$xrefPos\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $out;
    }

    public function outputFleetReport(array $data, string $dateFrom, string $dateTo, string $filename) {
        $lines = [];
    $lines[] = 'Raport flota';
        $lines[] = "Perioada: $dateFrom - $dateTo";
        $lines[] = '';
    $lines[] = 'Vehicul | Tip | Combustibil(L) | Cost Comb.(RON) | Cost Ment.(RON) | Cost Asig.(RON) | Consum(L/100) | Cost Total(RON)';
        if (!empty($data['vehicles'])) {
            foreach ($data['vehicles'] as $v) {
                $veh = trim(($v['license_plate'] ?? ($v['registration_number'] ?? '')) . ' - ' . (($v['make'] ?? ($v['brand'] ?? '')) . ' ' . ($v['model'] ?? '')));
                $row = [
                    $veh,
                    $v['vehicle_type'] ?? 'N/A',
                    number_format($v['fuel_consumed'] ?? 0, 2, ',', '.'),
                    number_format($v['fuel_cost'] ?? 0, 2, ',', '.'),
                    number_format($v['maintenance_cost'] ?? 0, 2, ',', '.'),
                    number_format($v['insurance_cost'] ?? 0, 2, ',', '.'),
                    number_format($v['avg_consumption'] ?? 0, 2, ',', '.'),
                    number_format($v['total_cost'] ?? 0, 2, ',', '.'),
                ];
                $lines[] = implode(' | ', $row);
            }
        } else {
            $lines[] = 'Nu există date pentru perioada selectată';
        }
        $this->emitSimplePdf($lines, $filename, 'L');
        exit;
    }

    // Simple stubs for other report types
    public function outputVehicleReport(array $data, string $dateFrom, string $dateTo, string $filename) {
        $this->emitSimplePdf(['Raport vehicul', "Perioada: $dateFrom - $dateTo", '', 'Utilizați exportul CSV pentru detalii.'], $filename);
        exit;
    }
    public function outputFuelReport(array $data, string $dateFrom, string $dateTo, string $filename) { $this->outputVehicleReport($data,$dateFrom,$dateTo,$filename); }
    public function outputMaintenanceReport(array $data, string $dateFrom, string $dateTo, string $filename) { $this->outputVehicleReport($data,$dateFrom,$dateTo,$filename); }
    public function outputCustomReport(array $data, string $dateFrom, string $dateTo, string $filename) { $this->outputVehicleReport($data,$dateFrom,$dateTo,$filename); }
}
