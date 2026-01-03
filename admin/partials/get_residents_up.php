<?php
// admin/partials/get_residents.php
require_once 'db_conn.php';
header('Content-Type: application/json');

$conn = getDBConnection();
if (!$conn) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, full_name, first_name, last_name, age, sex, date_of_birth, civil_status, house_number, street, province, municipality, zip_code FROM residents WHERE archived = 0  AND date_of_birth <= DATE_SUB(CURDATE(), INTERVAL 18 YEAR) ORDER BY full_name";
$result = $conn->query($sql);
$residents = [];
while ($row = $result->fetch_assoc()) {
    $residents[] = $row;
}
echo json_encode($residents);
closeDBConnection($conn);
?>