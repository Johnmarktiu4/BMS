<?php
// admin/partials/generate_complaint_pdf.php
ob_clean();
ob_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

$conn = getDBConnection();
if (!$conn) die('Database connection failed.');

// ===================================================================
// SINGLE OR ALL COMPLAINTS
// ===================================================================
$official_fullname = $_GET['full_name'] ?? null;
$complaint_id = $_GET['id'] ?? null;
$is_single = $complaint_id && is_numeric($complaint_id);

if ($is_single) {
    $stmt = $conn->prepare("
        SELECT c.case_id, c.status, c.date_reported, c.date_incident, 
               o.full_name AS official_name
        FROM complaints c
        LEFT JOIN officials o ON c.barangay_official_id = o.id
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $complaint = $result->fetch_assoc();
    $stmt->close();
    if (!$complaint) die('Complaint not found.');
} else {
    $sql = "
        SELECT c.id, c.case_id, c.status, c.date_reported, c.date_incident, 
               o.full_name AS official_name
        FROM complaints c
        LEFT JOIN officials o ON c.barangay_official_id = o.id
        ORDER BY c.date_reported DESC
    ";
    $result = $conn->query($sql);
}

// ===================================================================
// FETCH COMPLAINANTS & DEFENDANTS (FROM resident_id → full_name)
// ===================================================================
function getPersons($conn, $complaint_id, $table) {
    $persons = [];
    $sql = "SELECT resident_id, name, age FROM $table WHERE complaint_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        if ($row['resident_id']) {
            // Use full_name from residents table
            $rid = $row['resident_id'];
            $r = $conn->query("SELECT full_name, age FROM residents WHERE id = $rid AND archived = 0 LIMIT 1")->fetch_assoc();
            $name = $r['full_name'] ?? 'Unknown Resident';
            $age  = $r['age'] ?? $row['age'];
        } else {
            // Non-resident → use saved name
            $name = $row['name'] ?? 'Unknown';
            $age  = $row['age'] ?? null;
        }
        $persons[] = $name . ($age ? ", {$age}yo" : '');
    }
    $stmt->close();
    return $persons ?: ['—'];
}

// ===================================================================
// BUILD COMPLAINTS ARRAY
// ===================================================================
$complaints = [];

if ($is_single) {
    $complainants = getPersons($conn, $complaint_id, 'complaint_complainants');
    $defendants   = getPersons($conn, $complaint_id, 'complaint_defendants');

    $complaints[] = [
        'case_id'       => $complaint['case_id'],
        'status'        => $complaint['status'],
        'date_reported' => $complaint['date_reported'],
        'date_incident' => $complaint['date_incident'],
        'official_name' => $complaint['official_name'] ?? 'Not Assigned',
        'complainants'  => $complainants,
        'defendants'    => $defendants
    ];
} else {
    while ($row = $result->fetch_assoc()) {
        $cid = $row['id'];
        $complainants = getPersons($conn, $cid, 'complaint_complainants');
        $defendants   = getPersons($conn, $cid, 'complaint_defendants');

        $complaints[] = [
            'case_id'       => $row['case_id'],
            'status'        => $row['status'],
            'date_reported' => $row['date_reported'],
            'date_incident' => $row['date_incident'],
            'official_name' => $row['official_name'] ?? 'Not Assigned',
            'complainants'  => $complainants,
            'defendants'    => $defendants
        ];
    }
}
closeDBConnection($conn);

// ===================================================================
// TCPDF + HEADER
// ===================================================================
class ComplaintPDF extends TCPDF {
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

$pdf = new ComplaintPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(10, 55, 10);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 15, 'MASTER LIST OF BARANGAY BLOTTER', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Barangay 3 Gen. Emilio Aguinaldo, Dalahican, Cavite City', 0, 1, 'C');
$pdf->Ln(8);

// ===================================================================
// TABLE HEADER
// ===================================================================
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(0, 128, 0);
$pdf->SetTextColor(255, 255, 255);

$w = [28, 25, 60, 60, 45, 30, 30];
$pdf->Cell($w[0], 12, 'Case ID', 1, 0, 'C', true);
$pdf->Cell($w[1], 12, 'Status', 1, 0, 'C', true);
$pdf->Cell($w[2], 12, 'Complainant(s)', 1, 0, 'C', true);
$pdf->Cell($w[3], 12, 'Defendant(s)', 1, 0, 'C', true);
$pdf->Cell($w[4], 12, 'Official', 1, 0, 'C', true);
$pdf->Cell($w[5], 12, 'Date Reported', 1, 0, 'C', true);
$pdf->Cell($w[6], 12, 'Date Incident', 1, 1, 'C', true);

// ===================================================================
// TABLE ROWS
// ===================================================================
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);

foreach ($complaints as $c) {
    $statusText = str_replace(' ', "\n", strtoupper($c['status'] ?? 'NEW'));

    $compText = implode("\n", $c['complainants']);
    $defText  = implode("\n", $c['defendants']);

    $maxLines = max(
        substr_count($compText, "\n"),
        substr_count($defText, "\n"),
        1
    ) + 1;
    $rowHeight = max(12, $maxLines * 6);

    $pdf->SetFillColor(245, 255, 245);

    $pdf->Cell($w[0], $rowHeight, $c['case_id'], 1, 0, 'C', true);
    $pdf->Cell($w[1], $rowHeight, $statusText, 1, 0, 'C', true);
    $pdf->MultiCell($w[2], $rowHeight, $compText, 1, 'C', true, 0);
    $pdf->MultiCell($w[3], $rowHeight, $defText, 1, 'C', true, 0);
    $pdf->Cell($w[4], $rowHeight, $c['official_name'], 1, 0, 'C', true);
    $pdf->Cell($w[5], $rowHeight, date('M j, Y', strtotime($c['date_reported'])), 1, 0, 'C', true);
    $pdf->Cell($w[6], $rowHeight, date('M j, Y', strtotime($c['date_incident'])), 1, 1, 'C', true);
}
// === ADDED: Prepared By (appears after all table data) ===
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Prepared By : ' . $official_fullname, 0, 1, 'R');
// ===================================================================
// OUTPUT
// ===================================================================
ob_end_clean();
$filename = $is_single
    ? 'Blotter_' . preg_replace('/[^a-zA-Z0-9]/', '_', $complaints[0]['case_id']) . '.pdf'
    : 'Blotter_Master_List_' . date('Y-m-d') . '.pdf';

$pdf->Output($filename, 'I');
exit;
?>