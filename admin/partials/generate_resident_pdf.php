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
$resident_id = $_GET['id'] ?? null;
$official_fullname = $_GET['full_name'] ?? null;
$is_single = $resident_id && is_numeric($resident_id);

if ($is_single) {
    $stmt = $conn->prepare("SELECT * FROM residents WHERE id = ? AND archived = 0");
    $stmt->bind_param("i", $resident_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resident = $result->fetch_assoc();
    $stmt->close();
    if (!$resident) die('Resident not found or archived.');
    $residents = [$resident];
    $title = 'RESIDENT PROFILE';
    $subtitle = strtoupper($resident['full_name']);
} else {
    $sql = "SELECT * FROM residents WHERE archived = 0 ORDER BY last_name, first_name";
    $result = $conn->query($sql);
    $residents = [];
    while ($row = $result->fetch_assoc()) {
        $residents[] = $row;
    }
    $title = 'MASTER LIST OF ALL REGISTERED RESIDENTS';
    $subtitle = 'Total Residents: ' . count($residents) . ' | Generated: ' . date('F j, Y \a\t g:i A');
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
$pdf->SetTitle($is_single ? 'Resident Profile' : 'All Residents');
$pdf->SetMargins(15, 55, 15);
$pdf->SetAutoPageBreak(true, 35);
$pdf->AddPage();

// ===================================================================
// MAIN TITLE
// ===================================================================
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 12, $title, 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, $subtitle, 0, 1, 'C');
$pdf->Ln(10);

// ===================================================================
// CONTENT
// ===================================================================
if ($is_single) {
    $r = $residents[0];
    $profileImg = !empty($r['profile_picture']) ? $r['profile_picture'] : '';
    $imgHtml = $profileImg ?
        '<img src="' . htmlspecialchars($profileImg) . '" width="100" height="100" style="border-radius:50%;border:4px solid #333;object-fit:cover;">' :
        '<div style="width:100px;height:100px;background:#e9ecef;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:50px;color:#6c757d;border:4px solid #333;">' . strtoupper(substr($r['full_name'], 0, 1)) . '</div>';

    $html = '
    <table border="0" cellpadding="12">
        <tr>
            <td width="72%" style="background:#f8f9fa;padding:20px;font-size:11pt;">
            <p><strong>Region : </strong><u>REGION IV-A</u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>City/Municipality : </strong><u>Cavite City</u></p>
            <p><strong>Province : </strong><u>CAVITE</u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Barangay : </strong><u>BARANGAY 3</u></p>
            </td>
        </tr>
    </table><br><br>';

    $html .= '
    <table border="1" cellpadding="10" cellspacing="0" style="font-size:10.5pt;">
        <tr><td style="text-align: center;"><p><center><strong>PERSONAL INFORMATION</strong></center></p></td></tr>
        <tr><td width="35%"><strong>Date of Birth</strong></td><td>' . date('F j, Y', strtotime($r['date_of_birth'])) . '</td></tr>
        <tr><td><strong>Place of Birth</strong></td><td>' . htmlspecialchars($r['place_of_birth'] ?? '—') . '</td></tr>
        <tr><td><strong>Contact Number</strong></td><td>' . ($r['contact_number'] ?: '—') . '</td></tr>
        <tr><td><strong>Email Address</strong></td><td>' . ($r['email_address'] ?: '—') . '</td></tr>
        <tr><td><strong>Religion</strong></td><td>' . ($r['religion'] ?: '—') . '</td></tr>
        <tr><td><strong>Nationality</strong></td><td>' . ($r['nationality'] ?: 'Filipino') . '</td></tr>
        <tr><td><strong>Registered Voter</strong></td><td>' . ($r['is_voter'] == 1 ? 'Yes' : 'No') . '</td></tr>
        <tr><td><strong>PWD</strong></td><td>' . ($r['pwd'] === 'Yes' ? 'Yes (' . ($r['disability_type'] ?: 'Not specified') . ')' : 'No') . '</td></tr>
        <tr><td><strong>Senior Citizen</strong></td><td>' . ($r['senior'] === 'Yes' ? 'Yes' : 'No') . '</td></tr>
        <tr><td><strong>Solo Parent</strong></td><td>' . ($r['solo_parent'] === 'Yes' ? 'Yes' : 'No') . '</td></tr>
        <tr><td><strong>Head of Family</strong></td><td>' . ($r['is_head_of_family'] == 1 ? 'Yes' : 'No') . '</td></tr>
        <tr><td><strong>Relationship to Head</strong></td><td>' . ($r['relationship_to_head'] ?: 'Head of Family') . '</td></tr>
        <tr><td><strong>Emergency Contact</strong></td><td>' . ($r['emergency_name'] ? htmlspecialchars($r['emergency_name']) . ' (' . ($r['emergency_relationship'] ?: '—') . ') - ' . ($r['emergency_contact'] ?: '—') : '—') . '</td></tr>
    </table>';

    $html .= '<p>I hereby certify that the above information is true and correct to the best of my knowledge. I understand<br>
    that for the Barangay to carry out its mandate pursuant to Section 394 (d)(6) of the Local Government<br>
    Code of 1991, they must necessarily process my personal information for easy identification of<br>
    inhabitants, as a tool in planning, and as an updated reference in the number of inhabitants of the<br>
    Barangay. Therefore, I grant my consent and recognize the authority of the Barangay to process my<br>
    personal information, subject to the provision of the Philippine Data Privacy Act of 2012.</p>';

    $pdf->writeHTML($html, true, false, true, false, '');

} else {
    // Master List – Fixed HTML table structure
    $html = '<table border="1" cellpadding="6" cellspacing="0" style="font-size:9pt;">
        <thead>
            <tr style="background-color:#2c3e50;color:white;">
                <th width="5%">#</th>
                <th width="25%">Full Name</th>
                <th width="8%">Age</th>
                <th width="8%">Sex</th>
                <th width="14%">Civil Status</th>
                <th width="25%">Address</th>
                <th width="15%">Contact</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($residents as $i => $r) {
        $rowColor = $i % 2 == 0 ? '#f8f9fa' : '#ffffff';
        $html .= '<tr style="background-color:' . $rowColor . ';">
            <td width="5%" align="center">' . ($i + 1) . '</td>
            <td width="25%"><strong>' . htmlspecialchars($r['full_name']) . '</strong></td>
            <td width="8%" align="center">' . $r['age'] . '</td>
            <td width="8%" align="center">' . $r['sex'] . '</td>
            <td width="14%" align="center">' . $r['civil_status'] . '</td>
            <td width="25%">' . htmlspecialchars($r['address']) . '</td>
            <td width="15%">' . ($r['contact_number'] ?: '—') . '</td>
        </tr>';
    }

    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');
}
// === ADDED: PREPARED BY (appears after all table data) ===
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'PREPARED BY : ' . $official_fullname, 0, 1, 'R');
// ===================================================================
// OUTPUT
// ===================================================================
ob_end_clean();
$filename = $is_single
    ? 'Resident_' . preg_replace('/[^a-zA-Z0-9]/', '_', $residents[0]['full_name']) . '.pdf'
    : 'All_Residents_' . date('Y-m-d') . '.pdf';

$pdf->Output($filename, 'I');
exit;
?>