<?php
require_once 'db_conn.php';
header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false, 'message' => 'Invalid request'];

if (!isset($_POST['action'])) {
    echo json_encode($response); exit;
}

$action = $_POST['action'];

// FETCH ALL ARCHIVED OFFICIALS
if ($action === 'fetch') {
    $sql = "SELECT id, full_name, position, contact, status, profile_picture, updated_at 
            FROM officials 
            WHERE archived = 1 
            ORDER BY updated_at DESC";

    $result = $conn->query($sql);
    $officials = [];

    while ($row = $result->fetch_assoc()) {
        if ($row['profile_picture']) {
            $row['profile_picture'] = 'image/profiles/' . basename($row['profile_picture']);
        }
        $officials[] = $row;
    }

    $response = ['success' => true, 'data' => $officials];
}

// RESTORE OFFICIAL
elseif ($action === 'restore') {
    $id = (int)$_POST['id'];

    $sql = "UPDATE officials SET archived = 0, updated_at = NOW() WHERE id = ? AND archived = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $response = ['success' => true, 'message' => 'Official restored successfully'];
    } else {
        $response = ['message' => 'Official not found or already restored'];
    }
    $stmt->close();
}

echo json_encode($response);
closeDBConnection($conn);
?>