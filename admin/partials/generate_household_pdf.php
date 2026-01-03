<?php
ob_clean();
ob_start();

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

$conn = getDBConnection();
if (!$conn) die('Database connection failed.');

// ===================================================================
// SINGLE RESIDENT OR WHOLE HOUSEHOLD OR ALL HOUSEHOLDS
// ===================================================================
$head_id = $_GET['head_id'] ?? null;
$resident_id = $_GET['id'] ?? null;
$official_fullname = $_GET['full_name'] ?? null;
$is_single_resident = $resident_id && is_numeric($resident_id);
$is_single_household = $head_id && is_numeric($head_id);

if ($is_single_resident) {
    $stmt = $conn->prepare("SELECT * FROM residents WHERE id = ? AND archived = 0");
    $stmt->bind_param("i", $resident_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resident = $result->fetch_assoc();
    $stmt->close();
    $residents = $resident ? [$resident] : [];
    $title = 'INDIVIDUAL RESIDENT PROFILE';
    $subtitle = $resident['full_name'] ?? 'Unknown';
} elseif ($is_single_household) {
    // Get head + all members
    $sql = "SELECT * FROM residents WHERE (id = ? OR head_of_family_id = ?) AND archived = 0 ORDER BY is_head_of_family DESC, full_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $head_id, $head_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $residents = [];
    while ($row = $result->fetch_assoc()) $residents[] = $row;
    $stmt->close();

    $head = array_filter($residents, fn($r) => $r['is_head_of_family'] == 1)[0] ?? $residents[0];
    $title = 'HOUSEHOLD RESIDENT REPORT';
    $subtitle = 'Head: ' . ($head['full_name'] ?? 'N/A') . ' | Total Members: ' . count($residents);
} else {
    // All households (group by head)
    $sql = "SELECT * FROM residents WHERE archived = 0 ORDER BY head_of_family_id, is_head_of_family DESC, full_name ASC";
    $result = $conn->query($sql);
    $all = [];
    while ($row = $result->fetch_assoc()) $all[] = $row;

    // Group by household
    $households = [];
    foreach ($all as $r) {
        $hid = $r['is_head_of_family'] == 1 ? $r['id'] : $r['head_of_family_id'];
        if (!$hid) $hid = 'orphan_' . $r['id'];
        $households[$hid][] = $r;
    }
    $residents = $households;
    $title = 'MASTER LIST OF ALL HOUSEHOLDS';
    $subtitle = 'Total Households: ' . count($households);
}

closeDBConnection($conn);

// ===================================================================
// CUSTOM TCPDF CLASS
// ===================================================================
class HouseholdPDF extends TCPDF {
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
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new HouseholdPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Barangay System');
$pdf->SetTitle('Household Report');
$pdf->SetMargins(15, 40, 15);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, $title, 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, $subtitle, 0, 1, 'C');
$pdf->Ln(8);

$pdf->SetFont('helvetica', '', 10);

// ===================================================================
// CONTENT
// ===================================================================
if ($is_single_resident) {
    $r = $residents[0];
    $html = generateResidentHTML($r, true);
    $pdf->writeHTML($html, true, false, true, false, '');
} elseif ($is_single_household) {
    foreach ($residents as $i => $r) {
        $is_head = $r['is_head_of_family'] == 1;
        $html = '<h4 style="background-color:#f0f0f0;padding:5px;">' . 
                ($is_head ? 'HEAD OF FAMILY' : 'Member ' . ($i)) . 
                ': ' . htmlspecialchars($r['full_name']) . '</h4>';
        $html .= generateResidentHTML($r, false);
        $html .= '<br><br>';
        $pdf->writeHTML($html, true, false, true, false, '');
    }
} else {
    // All households
    foreach ($residents as $hid => $members) {
        $head = array_values(array_filter($members, fn($m) => $m['is_head_of_family'] == 1))[0] ?? $members[0];
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Household: ' . htmlspecialchars($head['full_name']) . ' | Members: ' . count($members), 1, 1, 'L', true);
        $pdf->Ln(3);

        foreach ($members as $m) {
            $is_head = $m['is_head_of_family'] == 1;
            $html = '<strong style="color:' . ($is_head ? '#d4a017' : '#333') . ';">' . 
                    htmlspecialchars($m['full_name']) . 
                    ($is_head ? ' (Head of Family)' : '') . '</strong><br>';
            $html .= generateResidentHTML($m, false);
            $html .= '<hr>';
            $pdf->writeHTML($html, true, false, true, false, '');
        }
        $pdf->AddPage(); // New page per household in master list
    }
}

function generateResidentHTML($r, $full = false) {
    $profile = $r['profile_picture'] ? 
        '<img src="' . htmlspecialchars($r['profile_picture']) . '" width="80" height="80" style="float:right;margin-left:10px;">' : 
        '<div style="float:right;width:80px;height:80px;background:#ddd;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;">' . substr($r['full_name'],0,1) . '</div>';

    return '
    <table border="1" cellpadding="6" cellspacing="0" style="width:100%;">
        <tr><td colspan="2" style="background-color:#f8f9fa;"><strong>Personal Information</strong> ' . $profile . '</td></tr>
        <tr><td width="35%"><strong>Full Name</strong></td><td>' . htmlspecialchars($r['full_name']) . '</td></tr>
        <tr><td><strong>Age / Sex</strong></td><td>' . $r['age'] . ' years / ' . $r['sex'] . '</td></tr>
        <tr><td><strong>Civil Status</strong></td><td>' . $r['civil_status'] . '</td></tr>
        <tr><td><strong>Date of Birth</strong></td><td>' . $r['date_of_birth'] . '</td></tr>
        <tr><td><strong>Address</strong></td><td>' . htmlspecialchars($r['address']) . '</td></tr>
        <tr><td><strong>Contact</strong></td><td>' . htmlspecialchars($r['contact_number'] ?? '—') . '</td></tr>
        <tr><td><strong>PWD</strong></td><td>' . ($r['pwd'] === 'Yes' ? 'Yes (' . htmlspecialchars($r['disability_type'] ?? '') . ')' : 'No') . '</td></tr>
        <tr><td><strong>Senior Citizen</strong></td><td>' . $r['senior'] . '</td></tr>
        <tr><td><strong>Solo Parent</strong></td><td>' . $r['solo_parent'] . '</td></tr>
        ' . ($full ? '<tr><td><strong>Relationship to Head</strong></td><td>' . ($r['relationship_to_head'] ?? 'Head of Family') . '</td></tr>' : '') . '
    </table>';
}
// === ADDED: PREPARED BY (appears after all table data) ===
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'PREPARED BY : ' . $official_fullname, 0, 1, 'R');
// ===================================================================
// OUTPUT
// ===================================================================
ob_end_clean();

if ($is_single_resident) {
    $filename = 'Resident_' . preg_replace('/[^a-zA-Z0-9]/', '_', $residents[0]['full_name']) . '.pdf';
} elseif ($is_single_household) {
    $hname = $head['full_name'] ?? 'Household';
    $filename = 'Household_' . preg_replace('/[^a-zA-Z0-9]/', '_', $hname) . '.pdf';
} else {
    $filename = 'All_Households_' . date('Y-m-d') . '.pdf';
}

$pdf->Output($filename, 'I');
exit;