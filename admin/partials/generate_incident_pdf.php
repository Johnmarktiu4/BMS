<?php
// admin/partials/generate_incident_pdf.php
ob_clean();
ob_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

$conn = getDBConnection();
if (!$conn) die('Database connection failed.');

// ===================================================================
// SINGLE OR ALL INCIDENTS
// ===================================================================
$official_fullname = $_GET['full_name'] ?? null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("
        SELECT i.case_id, i.status, i.nature_of_incident, i.persons_involved, 
               i.date_reported, o.full_name AS official_name
        FROM incidents i
        LEFT JOIN officials o ON i.barangay_official_id = o.id
        WHERE i.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $incident = $result->fetch_assoc();
    $stmt->close();
    $incidents = $incident ? [$incident] : [];
    $is_single = true;
} else {
    $sql = "
        SELECT i.case_id, i.status, i.nature_of_incident, i.persons_involved, 
               i.date_reported, o.full_name AS official_name
        FROM incidents i
        LEFT JOIN officials o ON i.barangay_official_id = o.id
        ORDER BY i.date_reported DESC
    ";
    $result = $conn->query($sql);
    $incidents = [];
    while ($row = $result->fetch_assoc()) {
        $incidents[] = $row;
    }
    $is_single = false;
}
closeDBConnection($conn);

// ===================================================================
// CUSTOM TCPDF WITH BARANGAY HEADER
// ===================================================================
class BarangayPDF extends TCPDF {
    public function Header() {
        $this->SetY(10);
        $logo_left  = '../image/Logo/Brgy3_logo-removebg-preview.png';
        $logo_right = '../image/Logo/cavite-city-new-removebg-preview.png';

        if (file_exists($logo_left))  $this->Image($logo_left, 15, 8, 28, 28, 'PNG');
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
// CREATE PDF
// ===================================================================
$pdf = new BarangayPDF('L', 'mm', 'A4', true, 'UTF-8', false); // Landscape for better table
$pdf->SetCreator('Barangay System');
$pdf->SetAuthor('Barangay Secretary');
$pdf->SetTitle($is_single ? 'Incident Report' : 'Incident Reports Summary');
$pdf->SetMargins(15, 55, 15);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();

// ===================================================================
// MAIN TITLE
// ===================================================================
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 15, 'INCIDENT REPORT SUMMARY', 0, 1, 'C');
$pdf->Ln(51);

// ===================================================================
// TABLE HEADER
// ===================================================================
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(0, 128, 0); // Green
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(30, 10, 'Case ID', 1, 0, 'C', true);
$pdf->Cell(28, 10, 'Status', 1, 0, 'C', true);
$pdf->Cell(70, 10, 'Nature of Incident', 1, 0, 'C', true);
$pdf->Cell(70, 10, 'Persons Involved', 1, 0, 'C', true);
$pdf->Cell(45, 10, 'Barangay Incharge', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Date Reported', 1, 1, 'C', true);

// ===================================================================
// TABLE ROWS
// ===================================================================
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);

foreach ($incidents as $i) {
    // Status Color
    $status = strtoupper($i['status'] ?? 'PENDING');
    $statusColor = match(true) {
        in_array($status, ['RESOLVED', 'SETTLED']) => '<span style="color:green;font-weight:bold;">RESOLVED</span>',
        in_array($status, ['PENDING', 'ONGOING']) => '<span style="color:orange;font-weight:bold;">PENDING</span>',
        in_array($status, ['ESCALATED', 'FORWARDED']) => '<span style="color:red;font-weight:bold;">ESCALATED</span>',
        default => htmlspecialchars($status)
    };

    // Persons Involved (clean list)
    $persons = json_decode($i['persons_involved'] ?? '[]', true);
    $personText = '';
    if (is_array($persons) && !empty($persons)) {
        $names = array_map(function($p) {
            $name = $p['name'] ?? 'Unknown';
            $age = !empty($p['age']) ? ', ' . $p['age'] . 'yo' : '';
            return trim($name . $age);
        }, $persons);
        $personText = implode("\n", $names);
    } else {
        $personText = '—';
    }

    // Nature (shortened for table)
    $nature = htmlspecialchars($i['nature_of_incident'] ?? '—');
    $nature = strlen($nature) > 80 ? substr($nature, 0, 77) . '...' : $nature;

    // Official
    $official = htmlspecialchars($i['official_name'] ?? 'Not Assigned');

    // Date
    $date = date('M j, Y', strtotime($i['date_reported']));

    // Row height calculation
    $maxLines = max(
        substr_count($personText, "\n") + 1,
        2
    );
    $rowHeight = $maxLines * 6;

    // Output row
    $pdf->SetFillColor(240, 248, 240);
    $pdf->Cell(30, $rowHeight, htmlspecialchars($i['case_id']), 1, 0, 'C', true);
    $pdf->Cell(28, $rowHeight, $status, 1, 0, 'C', true);
    $pdf->Cell(70, $rowHeight, $nature, 1, 0, 'C', true);
    $pdf->MultiCell(70, $rowHeight, $personText, 1, 'C', true, 0);
    $pdf->MultiCell(45, $rowHeight, $official, 1, 'C', true, 0);
    $pdf->Cell(35, $rowHeight, $date, 1, 1, 'C', true);
}
// === ADDED: Prepared By (appears after all table data) ===
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Prepared By : '. $official_fullname, 0, 1, 'R');
// ===================================================================
// OUTPUT PDF
// ===================================================================
ob_end_clean();
$filename = $is_single 
    ? 'Incident_' . preg_replace('/[^a-zA-Z0-9]/', '_', $incidents[0]['case_id'] ?? 'Report') . '.pdf'
    : 'All_Incident_Reports_' . date('Y-m-d') . '.pdf';

$pdf->Output($filename, 'I');
exit;
?>