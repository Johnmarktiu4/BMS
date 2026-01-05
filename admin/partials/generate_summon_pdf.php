<?php
// admin/partials/generate_resident_pdf.php
ob_clean();
ob_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

$conn = getDBConnection();
if (!$conn) die('Database connection failed.');

// ===================================================================
// DETERMINE MODE: SINGLE OR ALL RESIDENTS
// ===================================================================
$case_id = $_GET['id'] ?? null;
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
}
closeDBConnection($conn);

// ===================================================================
// CUSTOM TCPDF CLASS WITH FULL OFFICIAL HEADER
// ===================================================================
class ResidentPDF extends TCPDF {
    public function Header() {
        $this->SetY(8);

        // Logos (relative path as requested)
        $logo_left  = '../image/Logo/Brgy3_logo-removebg-preview.png';
        $logo_right = '../image/Logo/cavite-city-new-removebg-preview.png';
        $logo_w = 25;
        $logo_h = 25;

        if (file_exists($logo_left)) {
            $this->Image($logo_left, 15, 8, $logo_w, $logo_h, 'PNG');
        }
        if (file_exists($logo_right)) {
            $this->Image($logo_right, 170, 8, $logo_w, $logo_h, 'PNG');
        }

        // Official Government Header
        $this->SetFont('helvetica', 'B', 14);
        $this->SetY(12);
        $this->Cell(0, 10, 'REPUBLIC OF THE PHILIPPINES', 0, 1, 'C');
        $this->Cell(0, 8, 'PROVINCE OF CAVITE', 0, 1, 'C');
        $this->Cell(0, 8, 'CITY OF CAVITE', 0, 1, 'C');
 
        // Separator Line
        $this->SetLineWidth(0.5);
        $this->Line(12, $this->GetY(), 285, $this->GetY());  // Landscape width
        $this->Ln(8);
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
$pdf = new ResidentPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Barangay Information System');
$pdf->SetAuthor('Punong Barangay / Secretary');
$pdf->SetTitle('Summon');
$pdf->SetMargins(15, 55, 15);
$pdf->SetAutoPageBreak(true, 35);
$pdf->AddPage();

// ===================================================================
// MAIN TITLE
// ===================================================================
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 12, 'SUMMON', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, "(Paanyaya para sa inirereklamo)", 0, 1, 'C');
$pdf->Ln(10);

// ===================================================================
// CONTENT
// ===================================================================
foreach ($blotters as $b) {
    $r = $residents[0];
    $profileImg = !empty($r['profile_picture']) ? $r['profile_picture'] : '';

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
    $html = '<p style="text-align:right;"><u>'. date('Y-m-d') .'</u><br>';
    $html .= 'Petsa</p>';
    $html .= '
    <p>Paanyaya para kay : <u>' . $defText . '</u></p>';

    $html .= '<p> &nbsp;&nbsp;&nbsp;&nbsp;Inaanyayahan po naming kayo sa aming tanggapan ng Barangay 3 Gen. Emilio Aguinaldo<br>upang sagutin ang ilang katanungan hinggil sa inihaing reklamo ni <u>'. $compText .'</u> kayo po<br>ay pormal nakakausapin ng aming On-duty Kagawad/Punong Barangay. </p>';

    $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ang inyong paghaharap ay gaganapin ngayong ika - <u>'. $b['date_filed'] .'</u>.';

    $pdf->writeHTML($html, true, false, true, false, '');

}
// === ADDED: Prepared By (appears after all table data) ===
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Prepared By : ' . $official_fullname, 0, 1, 'R');
// ===================================================================
// OUTPUT
// ===================================================================
ob_end_clean();
$filename = 'Test.pdf';

$pdf->Output($filename, 'I');
exit;
?>