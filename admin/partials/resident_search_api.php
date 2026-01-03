<?php
// partials/resident_search_api.php
require_once 'db_conn.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request'];

$conn = getDBConnection();
if (!$conn) {
    $response['message'] = 'Database connection failed';
    echo json_encode($response);
    exit;
}

if (!isset($_GET['q'])) {
    echo json_encode($response);
    closeDBConnection($conn);
    exit;
}

$search = $conn->real_escape_string($_GET['q']);
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

$sql = "SELECT id, full_name, house_number, street 
        FROM residents 
        WHERE archived = 0 
          AND full_name LIKE '%$search%' 
        ORDER BY full_name ASC 
        LIMIT $limit";

$result = $conn->query($sql);
$residents = [];

while ($row = $result->fetch_assoc()) {
    $residents[] = [
        'id' => (int)$row['id'],
        'text' => $row['full_name'],
        'address' => trim("{$row['house_number']} {$row['street']}")
    ];
}

$response = [
    'status' => 'success',
    'results' => $residents,
    'pagination' => ['more' => false]
];

echo json_encode($response);
closeDBConnection($conn);
?>