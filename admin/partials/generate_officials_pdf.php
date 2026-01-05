<?php
// admin/partials/generate_officials_pdf.php
ob_clean();
ob_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

$conn = getDBConnection();
if (!$conn) die('Database connection failed.');

// ===================================================================
// FETCH ALL ACTIVE OFFICIALS
// ===================================================================
$sql = "SELECT * FROM officials 
        WHERE status = 'Active' AND archived = 0
        ORDER BY 
            CASE position 
                WHEN 'Barangay Captain' THEN 1
                WHEN 'Kagawad' THEN 2  
                WHEN 'Secretary' THEN 3
                WHEN 'Treasurer' THEN 4
                ELSE 5 
            END, term_start_date DESC";

$result = $conn->query($sql);
if (!$result) die("Query failed: " . $conn->error);

$officials = [];
while ($row = $result->fetch_assoc()) {
    $officials[] = $row;
}
closeDBConnection($conn);

// ===================================================================
// CUSTOM TCPDF CLASS – SAME AS YOUR RESIDENT PDF
// ===================================================================
class OfficialsPDF extends TCPDF {
    public function Header() {
        $this->SetY(8);
        $logo_left  = '../image/Logo/Brgy3_logo-removebg-preview.png';
        $logo_right = '../image/Logo/cavite-city-new-removebg-preview.png';
        $logo_w = 25;
        $logo_h = 25;

        if (file_exists($logo_left))  $this->Image($logo_left, 15, 8, $logo_w, $logo_h, 'PNG');
        if (file_exists($logo_right)) $this->Image($logo_right, 170, 8, $logo_w, $logo_h, 'PNG');

        $this->SetFont('helvetica', 'B', 14);
        $this->SetY(12);
        $this->Cell(0, 10, 'REPUBLIC OF THE PHILIPPINES', 0, 1, 'C');
        $this->Cell(0, 8, 'PROVINCE OF CAVITE', 0, 1, 'C');
        $this->Cell(0, 8, 'CITY OF CAVITE', 0, 1, 'C');

        $this->SetLineWidth(0.5);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->Ln(8);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new OfficialsPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Barangay Information System');
$pdf->SetTitle('List of Barangay Officials');
$pdf->SetMargins(15, 40, 15);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();

// ===================================================================
// TITLE & SUBTITLE – EXACT SAME STYLE
// ===================================================================
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'LIST OF BARANGAY OFFICIALS', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, 'Current Term • Total: ' . count($officials) . ' Official(s)', 0, 1, 'C');
$pdf->Ln(8);

// ===================================================================
// TABLE – NO PHOTO, PERFECTLY ALIGNED COLUMNS
// ===================================================================
$html = '<table border="1" cellpadding="9" cellspacing="0" style="width:100%;font-size:10.5pt;">
    <thead>
        <tr style="background-color:#198754;color:white;font-weight:bold;">
            <th width="8%"  align="center">#</th>
            <th width="32%" align="left">FULL NAME</th>
            <th width="24%" align="center">POSITION</th>
            <th width="18%" align="center">TERM START</th>
            <th width="18%" align="center">TERM END</th>
        </tr>
    </thead>
    <tbody>';

foreach ($officials as $i => $o) {
    $bg = $i % 2 == 0 ? '#f8f9fa' : '#ffffff';

    $termEnd = !empty($o['term_end_date'])
        ? date('M j, Y', strtotime($o['term_end_date']))
        : 'Present';

    $html .= '<tr style="background-color:' . $bg . ';">
        <td width="8%" align="center">' . ($i + 1) . '</td>
        <td width="32%" style="padding-left:12px;"><strong>' . htmlspecialchars($o['full_name']) . '</strong></td>
        <td width="24%" align="center">' . htmlspecialchars($o['position']) . '</td>
        <td width="18%" align="center">' . date('M j, Y', strtotime($o['term_start_date'])) . '</td>
        <td width="18%" align="center">' . $termEnd . '</td>
    </tr>';
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

// ===================================================================
// Prepared By – RIGHT ALIGNED
// ===================================================================
$pdf->Ln(20);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Prepared By : ' . $officials[0]['full_name'], 0, 1, 'R');

// ===================================================================
// OUTPUT
// ===================================================================
ob_end_clean();
$filename = 'Barangay_Officials_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'I');
exit;
?>