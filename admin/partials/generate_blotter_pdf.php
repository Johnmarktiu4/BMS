<?php
// admin/partials/generate_blotter_pdf.php
ob_clean();
ob_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';
$conn = getDBConnection();
if (!$conn) die('Database connection failed.');
// ===================================================================
// SINGLE OR ALL BLOTTERS
// ===================================================================
$official_fullname = $_GET['full_name'] ?? null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("
        SELECT b.case_id, b.complainant_ids, b.defendant_ids, b.nature_of_complaint,
               b.status, b.hearing_count, b.date_filed, o.full_name AS official_name
        FROM blotters b
        LEFT JOIN officials o ON b.barangay_incharge_id = o.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $blotter = $result->fetch_assoc();
    $stmt->close();
    $blotters = $blotter ? [$blotter] : [];
    $is_single = true;
} else {
    $sql = "
        SELECT b.case_id, b.complainant_ids, b.defendant_ids, b.nature_of_complaint,
               b.status, b.hearing_count, b.date_filed, o.full_name AS official_name
        FROM blotters b
        LEFT JOIN officials o ON b.barangay_incharge_id = o.id
        ORDER BY b.date_filed DESC, b.case_id DESC
    ";
    $result = $conn->query($sql);
    $blotters = [];
    while ($row = $result->fetch_assoc()) {
        $blotters[] = $row;
    }
    $is_single = false;
}
closeDBConnection($conn);
// ===================================================================
// CUSTOM TCPDF WITH FULL BARANGAY HEADER
// ===================================================================
class BarangayPDF extends TCPDF {
    public function Header() {
        $this->SetY(10);
        $logo_left = '../image/Logo/Brgy3_logo-removebg-preview.png';
        $logo_right = '../image/Logo/cavite-city-new-removebg-preview.png';
        if (file_exists($logo_left)) $this->Image($logo_left, 15, 8, 28, 28, 'PNG');
        if (file_exists($logo_right)) $this->Image($logo_right, 255, 8, 28, 28, 'PNG');
        $this->SetFont('helvetica', 'B', 14);
        $this->SetY(12);
        $this->Cell(0, 10, 'REPUBLIC OF THE PHILIPPINES', 0, 1, 'C');
        $this->Cell(0, 8, 'PROVINCE OF CAVITE', 0, 1, 'C');
        $this->Cell(0, 8, 'CITY OF CAVITE', 0, 1, 'C');
        $this->SetLineWidth(0.6);
         $this->Ln(10);
    }
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}
// ===================================================================
// CREATE PDF (LANDSCAPE FOR WIDE TABLE)
// ===================================================================
$pdf = new BarangayPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Barangay Information System');
$pdf->SetAuthor('Barangay Secretary');
$pdf->SetTitle('Complaint Master List');
$pdf->SetMargins(12, 55, 12);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();
// ===================================================================
// MAIN TITLE
// ===================================================================
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 15, 'MASTER LIST OF BARANGAY COMPLAINT RECORDS', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Barangay 3 Gen. Emilio Aguinaldo, Dalahican, Cavite City', 0, 1, 'C');
$pdf->Ln(8);
// ===================================================================
// TABLE HEADER
// ===================================================================
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(0, 128, 0);
$pdf->SetTextColor(255, 255, 255);
// Column widths (total 273mm)
$col_case = 28;
$col_comp = 60;
$col_def = 60;
$col_nature = 55;
$col_status = 25;
$col_hear = 20;
$col_date = 25;
$pdf->Cell($col_case, 12, 'Case ID', 1, 0, 'C', true);
$pdf->Cell($col_comp, 12, 'Complainant(s)', 1, 0, 'C', true);
$pdf->Cell($col_def, 12, 'Defendant(s)', 1, 0, 'C', true);
$pdf->Cell($col_nature, 12, 'Nature', 1, 0, 'C', true);
$pdf->Cell($col_status, 12, 'Status', 1, 0, 'C', true);
$pdf->Cell($col_hear, 12, 'Hearings', 1, 0, 'C', true);
$pdf->Cell($col_date, 12, 'Date Filed', 1, 1, 'C', true);
// ===================================================================
// TABLE ROWS
// ===================================================================
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
foreach ($blotters as $b) {
    // Parse persons
    $complainants = json_decode($b['complainant_ids'] ?? '[]', true);
    $defendants = json_decode($b['defendant_ids'] ?? '[]', true);
    $compText = '';
    if (is_array($complainants)) {
        foreach ($complainants as $p) {
            $name = $p['name'] ?? 'Unknown';
            $age = $p['age'] ?? '';
            $compText .= $name . ($age ? ", {$age}yo" : '') . "\n";
        }
    }
    $compText = trim($compText) ?: '—';
    $defText = '';
    if (is_array($defendants)) {
        foreach ($defendants as $p) {
            $name = $p['name'] ?? 'Unknown';
            $age = $p['age'] ?? '';
            $defText .= $name . ($age ? ", {$age}yo" : '') . "\n";
        }
    }
    $defText = trim($defText) ?: '—';
    // Nature (shorten if too long)
    $nature = htmlspecialchars($b['nature_of_complaint'] ?? '—');
    $nature = strlen($nature) > 60 ? substr($nature, 0, 57) . '...' : $nature;
    // Status badge color
    $status = strtoupper($b['status'] ?? 'PENDING');
    $statusText = match($status) {
        'RESOLVED' => 'RESOLVED',
        'UNRESOLVED' => 'UNRESOLVED',
        'FORWARDED TO POLICE' => 'FORWARDED',
        default => 'PENDING'
    };
    $hearings = $b['hearing_count'] - 1 . '/3';
    $dateFiled = date('M j, Y', strtotime($b['date_filed']));
    // Dynamic row height
    $maxLines = max(
        substr_count($compText, "\n") + 1,
        substr_count($defText, "\n") + 1,
        2
    );
    $rowHeight = max(10, $maxLines * 6);
    // Row background
    $pdf->SetFillColor(245, 255, 245);
    $pdf->Cell($col_case, $rowHeight, $b['case_id'], 1, 0, 'C', true);
    $pdf->MultiCell($col_comp, $rowHeight, $compText, 1, 'C', true, 0);
    $pdf->MultiCell($col_def, $rowHeight, $defText, 1, 'C', true, 0);
    $pdf->Cell($col_nature, $rowHeight, $nature, 1, 0, 'C', true);
    $pdf->Cell($col_status, $rowHeight, $statusText, 1, 0, 'C', true);
    $pdf->Cell($col_hear, $rowHeight, $hearings, 1, 0, 'C', true);
    $pdf->Cell($col_date, $rowHeight, $dateFiled, 1, 1, 'C', true);
}

// === ADDED: Prepared By (appears after all table data) ===
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Prepared By : '. $blotters[0]['official_name']  , 0, 1, 'R');
// ===================================================================
// OUTPUT PDF
// ===================================================================
ob_end_clean();
$filename = $is_single && !empty($blotters)
    ? 'Complaint' . preg_replace('/[^a-zA-Z0-9]/', '_', $blotters[0]['case_id']) . '.pdf'
    : 'Complaint_Master_List_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'I');
exit;
?>