<?php
ob_clean();
ob_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid request');
}
$id = (int)$_GET['id'];

$conn = getDBConnection();
$sql = "
    SELECT i.*, COALESCE(m.value, '0') AS unit_value
    FROM inventory i
    LEFT JOIN item_meta m ON i.id = m.inventory_id AND m.meta_key = 'declared_value'
    WHERE i.id = ? AND i.archived = 0
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();
closeDBConnection($conn);

if (!$item) die('Item not found');

$qty_on_hand = (int)$item['qty_on_hand'];
$unit_value  = (float)$item['unit_value'];
$total_value = $qty_on_hand * $unit_value;

// ===================================================================
// CUSTOM TCPDF CLASS WITH FULL BARANGAY HEADER (SAME AS BLOTTER)
// ===================================================================
class BarangayPDF extends TCPDF {
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
$pdf = new BarangayPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Barangay Information System');
$pdf->SetAuthor('Punong Barangay / Secretary');
$pdf->SetTitle('Inventory Item Report');
$pdf->SetMargins(15, 55, 15);        // Increased top margin for full header
$pdf->SetAutoPageBreak(true, 35);
$pdf->AddPage();

// Main title below header
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Cell(0, 10, 'ITEM DETAILS', 0, 1, 'C');
$pdf->Ln(10);

// Content Table
$html = '
<table border="1" cellpadding="12" cellspacing="0" style="width:100%; font-size:11pt;">
    <tr style="background-color:#f0f0f0;">
        <td width="40%"><strong>Item Name</strong></td>
        <td width="60%">' . htmlspecialchars($item['item_name']) . '</td>
    </tr>
    <tr>
        <td><strong>Description</strong></td>
        <td>' . nl2br(htmlspecialchars($item['description'] ?? '—')) . '</td>
    </tr>
    <tr>
        <td><strong>Quantity On Hand</strong></td>
        <td><strong>' . $item['qty_on_hand'] . ' pcs</strong></td>
    </tr>
    <tr>
        <td><strong>Current Stock</strong></td>
        <td>' . $item['current_stock'] . ' pcs</td>
    </tr>
    <tr>
        <td><strong>Unit Declared Value</strong></td>
        <td>' . ($unit_value > 0 ? 'PHP ' . number_format($unit_value, 2) : 'Not declared') . '</td>
    </tr>
    <tr style="background-color:#fffacd;">
        <td><strong>TOTAL VALUE</strong></td>
        <td><strong>PHP ' . number_format($total_value, 2) . '</strong></td>
    </tr>
    <tr>
        <td><strong>Received</strong></td>
        <td>' . $item['qty_received'] . ' pcs</td>
    </tr>
    <tr>
        <td><strong>Lost</strong></td>
        <td>' . $item['qty_lost'] . ' pcs</td>
    </tr>
    <tr>
        <td><strong>Damaged</strong></td>
        <td>' . $item['qty_damaged'] . ' pcs</td>
    </tr>
    <tr>
        <td><strong>Replaced</strong></td>
        <td>' . $item['qty_replaced'] . ' pcs</td>
    </tr>
    <tr>
        <td><strong>Remarks</strong></td>
        <td>' . nl2br(htmlspecialchars($item['remarks'] ?? '—')) . '</td>
    </tr>
</table>
<br>
<div style="text-align:center; font-style:italic; color:#555;">
    Total Value = Quantity On Hand × Unit Value<br>
    This is an official barangay inventory record.
</div>
';

$pdf->writeHTML($html, true, false, true, false, '');
// === ADDED: Prepared By (appears after all table data) ===
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Prepared By : MARIO MOJICA', 0, 1, 'R');
// ===================================================================
// OUTPUT PDF
// ===================================================================
ob_end_clean();
$filename = 'Inventory_' . preg_replace('/[^a-zA-Z0-9]/', '_', $item['item_name']) . '.pdf';
$pdf->Output($filename, 'I');
exit;
?>