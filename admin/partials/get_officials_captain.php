<?php
// admin/partials/get_officials.php
require_once 'db_conn.php';
header('Content-Type: application/json');

$conn = getDBConnection();
if (!$conn) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, full_name, position from officials WHERE status = 'Active' AND archived = 0 AND position= 'Barangay Captain'";
$result = $conn->query($sql);
$residents = [];
while ($row = $result->fetch_assoc()) {
    $residents[] = $row;
}
echo json_encode($residents);
closeDBConnection($conn);
?>