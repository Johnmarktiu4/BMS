<?php
ob_clean();
ob_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

$conn = getDBConnection();
if (!$conn) die('Database connection failed.');

// === FETCH INVENTORY WITH PROPER VALUE CALCULATION ===
$sql = "
    SELECT
        i.item_name,
        COALESCE(i.description, '') AS description,
        i.qty_on_hand,
        i.qty_received,
        i.qty_lost,
        i.qty_damaged,
        i.qty_replaced,
        i.current_stock,
        COALESCE(i.remarks, '') AS remarks,
        COALESCE(m.value, '0') AS unit_value
    FROM inventory i
    LEFT JOIN item_meta m ON i.id = m.inventory_id AND m.meta_key = 'declared_value'
    WHERE i.archived = 0
    ORDER BY i.item_name
";
$result = $conn->query($sql);
$items = [];
$total_items_on_hand = 0;
$total_declared_value = 0.0;

while ($row = $result->fetch_assoc()) {
    $qty = (int)$row['qty_on_hand'];
    $unit_value = (float)$row['unit_value'];
    $total_value_for_item = $qty * $unit_value;

    $row['total_value'] = $total_value_for_item;
    $row['unit_value_display'] = $unit_value > 0 ? 'PHP ' . number_format($unit_value, 2) : '-';
    $total_items_on_hand += $qty;
    $total_declared_value += $total_value_for_item;
    $items[] = $row;
}
closeDBConnection($conn);

// ===================================================================
// CUSTOM TCPDF CLASS WITH FULL OFFICIAL BARANGAY HEADER
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
            $this->Image($logo_right, 255, 8, $logo_w, $logo_h, 'PNG');
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
// CREATE PDF (LANDSCAPE)
// ===================================================================
$pdf = new BarangayPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Barangay Information System');
$pdf->SetAuthor('Punong Barangay / Secretary');
$pdf->SetTitle('Barangay Inventory Summary Report');
$pdf->SetMargins(12, 55, 12);           // Increased top margin for full header
$pdf->SetAutoPageBreak(true, 30);
$pdf->AddPage();

// ===================================================================
// MAIN TITLE BELOW HEADER
// ===================================================================
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Cell(0, 12, 'INVENTORY STOCK MONITORING REPORT', 0, 1, 'C');
$pdf->Ln(8);

// ===================================================================
// TABLE HEADER
// ===================================================================
$header = ['Item Name', 'Description', 'On Hand', 'Received', 'Lost', 'Damaged', 'Replaced', 'Current Stock', 'Unit Value', 'Total Value', 'Remarks'];
$widths = [40, 50, 18, 18, 16, 16, 16, 20, 25, 30, 40];

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(41, 128, 185);     // Professional blue
$pdf->SetTextColor(255, 255, 255);

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 9, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// ===================================================================
// TABLE ROWS
// ===================================================================
$pdf->SetFont('helvetica', '', 8.5);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(245, 245, 245);

foreach ($items as $index => $item) {
    $fill = $index % 2 == 0;
    $desc = mb_strlen($item['description']) > 45 ? mb_substr($item['description'], 0, 42) . '...' : $item['description'];
    $remarks = mb_strlen($item['remarks']) > 38 ? mb_substr($item['remarks'], 0, 35) . '...' : $item['remarks'];
    $total_val_display = $item['total_value'] > 0 ? 'PHP ' . number_format($item['total_value'], 2) : '-';

    $pdf->Cell($widths[0], 8, $item['item_name'], 1, 0, 'L', $fill);
    $pdf->Cell($widths[1], 8, $desc, 1, 0, 'L', $fill);
    $pdf->Cell($widths[2], 8, $item['qty_on_hand'], 1, 0, 'C', $fill);
    $pdf->Cell($widths[3], 8, $item['qty_received'], 1, 0, 'C', $fill);
    $pdf->Cell($widths[4], 8, $item['qty_lost'], 1, 0, 'C', $fill);
    $pdf->Cell($widths[5], 8, $item['qty_damaged'], 1, 0, 'C', $fill);
    $pdf->Cell($widths[6], 8, $item['qty_replaced'], 1, 0, 'C', $fill);
    $pdf->Cell($widths[7], 8, $item['current_stock'], 1, 0, 'C', $fill);
    $pdf->Cell($widths[8], 8, $item['unit_value_display'], 1, 0, 'R', $fill);
    $pdf->Cell($widths[9], 8, $total_val_display, 1, 0, 'R', $fill);
    $pdf->Cell($widths[10], 8, $remarks, 1, 0, 'L', $fill);
    $pdf->Ln();
}

// ===================================================================
// SUMMARY BOX
// ===================================================================
$pdf->Ln(12);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetFillColor(46, 204, 113);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 11, 'INVENTORY SUMMARY', 1, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(230, 255, 230);
$pdf->Cell(150, 10, 'Total Number of Items On Hand', 1, 0, 'L', true);
$pdf->Cell(125, 10, number_format($total_items_on_hand) . ' pcs', 1, 1, 'C', true);

$pdf->SetFillColor(255, 255, 200);
$pdf->Cell(150, 12, 'Total Declared Value of All Items', 1, 0, 'L', true);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(125, 12, 'PHP ' . number_format($total_declared_value, 2), 1, 1, 'C', true);

// Note
$pdf->Ln(8);
$pdf->SetFont('helvetica', 'I', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 8, 'Note: Total Declared Value = Sum of (Quantity On Hand × Unit Declared Value) for all items', 0, 1, 'C');
// === ADDED: Prepared By (appears after all table data) ===
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Prepared By : MARIO MOJICA', 0, 1, 'R');
// ===================================================================
// OUTPUT PDF
// ===================================================================
ob_end_clean();
$filename = 'Barangay_Inventory_Report_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'I');
exit;
?>