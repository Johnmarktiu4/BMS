<?php
require_once 'db_conn.php';
header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false, 'message' => 'Invalid request'];

if (!isset($_POST['action']) || $_POST['action'] !== 'fetch') {
    echo json_encode($response);
    exit;
}

$today = date('Y-m-d');

/*
   NEW LOGIC:
   Show officials who are:
   1. Archived (archived = 1) → regardless of term_end_date
   OR
   2. Not archived BUT term has ended (term_end_date < today)
*/

$sql = "SELECT 
            id, full_name, position, term_start_date, term_end_date, 
            contact, profile_picture, status, archived
        FROM officials 
        WHERE (
            archived = 1 
            OR (
                archived = 0 
                AND term_end_date IS NOT NULL 
                AND term_end_date < ?
            )
        )
        ORDER BY 
            archived DESC,  -- Archived ones appear first
            term_end_date DESC, 
            position ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $today);
$stmt->execute();
$result = $stmt->get_result();

$officials = [];

while ($row = $result->fetch_assoc()) {
    // Fix profile picture path
    if ($row['profile_picture']) {
        $row['profile_picture'] = 'image/profiles/' . basename($row['profile_picture']);
    }

    // Add a reason label for clarity in frontend
    if ($row['archived'] == 1) {
        $row['reason'] = 'Manually Archived';
        $row['badge_class'] = 'bg-dark';
    } else {
        $row['reason'] = 'Term Ended';
        $row['badge_class'] = 'bg-danger';
    }

    $officials[] = $row;
}

$response = [
    'success' => true,
    'data'    => $officials
];

echo json_encode($response);
$stmt->close();
closeDBConnection($conn);
?>