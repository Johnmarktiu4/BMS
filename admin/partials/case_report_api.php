<?php
require_once 'db_conn.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request'];

// Initialize database connection
$conn = getDBConnection();
if (!$conn) {
    $response['message'] = 'Database connection failed';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['action'])) {
    echo json_encode($response);
    closeDBConnection($conn);
    exit;
}

$action = $_POST['action'];

if ($action === 'fetch') {
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';
    $case_type = isset($_POST['case_type']) ? $conn->real_escape_string($_POST['case_type']) : '';
    $status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : '';

    $where = "WHERE archived = 0";
    if ($search) {
        $where .= " AND (case_number LIKE '%$search%' OR complainant LIKE '%$search%' OR respondent LIKE '%$search%')";
    }
    if ($case_type) {
        $where .= " AND case_type = '$case_type'";
    }
    if ($status) {
        $where .= " AND status = '$status'";
    }

    $sql = "SELECT id, case_number, complainant, respondent, case_type, date_filed, status, description FROM case_reports $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $result = $conn->query($sql);
    $cases = [];
    while ($row = $result->fetch_assoc()) {
        $cases[] = $row;
    }

    $countSql = "SELECT COUNT(*) as total FROM case_reports $where";
    $countResult = $conn->query($countSql);
    $total = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);

    $response = [
        'status' => 'success',
        'data' => [
            'cases' => $cases,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total' => (int)$total,
                'limit' => $limit
            ]
        ]
    ];
} elseif ($action === 'add') {
    $case_number = $conn->real_escape_string($_POST['case_number']);
    $complainant = $conn->real_escape_string($_POST['complainant']);
    $respondent = $conn->real_escape_string($_POST['respondent']);
    $case_type = $conn->real_escape_string($_POST['case_type']);
    $date_filed = $conn->real_escape_string($_POST['date_filed']);
    $status = $conn->real_escape_string($_POST['status']);
    $description = $conn->real_escape_string($_POST['description']);

    if (!preg_match('/^CR-\d{4}-\d+$/', $case_number)) {
        $response['message'] = 'Invalid case number format. Use CR-YYYY-NNN (e.g., CR-2025-001).';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    $sql = "INSERT INTO case_reports (case_number, complainant, respondent, case_type, date_filed, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssss', $case_number, $complainant, $respondent, $case_type, $date_filed, $status, $description);
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Case added successfully'];
    } else {
        $response['message'] = 'Failed to add case: ' . $conn->error;
    }
    $stmt->close();
} elseif ($action === 'update') {
    $id = (int)$_POST['id'];
    $case_number = $conn->real_escape_string($_POST['case_number']);
    $complainant = $conn->real_escape_string($_POST['complainant']);
    $respondent = $conn->real_escape_string($_POST['respondent']);
    $case_type = $conn->real_escape_string($_POST['case_type']);
    $date_filed = $conn->real_escape_string($_POST['date_filed']);
    $status = $conn->real_escape_string($_POST['status']);
    $description = $conn->real_escape_string($_POST['description']);

    if (!preg_match('/^CR-\d{4}-\d+$/', $case_number)) {
        $response['message'] = 'Invalid case number format. Use CR-YYYY-NNN (e.g., CR-2025-001).';
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }

    // Check if case_number is unique (excluding the current record)
    $sql = "SELECT id FROM case_reports WHERE case_number = ? AND id != ? AND archived = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $case_number, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $response['message'] = 'Case number already exists.';
        $stmt->close();
        echo json_encode($response);
        closeDBConnection($conn);
        exit;
    }
    $stmt->close();

    $sql = "UPDATE case_reports SET case_number=?, complainant=?, respondent=?, case_type=?, date_filed=?, status=?, description=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssssi', $case_number, $complainant, $respondent, $case_type, $date_filed, $status, $description, $id);
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Case updated successfully'];
    } else {
        $response['message'] = 'Failed to update case: ' . $conn->error;
    }
    $stmt->close();
} elseif ($action === 'get') {
    $id = (int)$_POST['id'];
    $sql = "SELECT id, case_number, complainant, respondent, case_type, date_filed, status, description FROM case_reports WHERE id=? AND archived=0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $response = ['status' => 'success', 'data' => $row];
    } else {
        $response['message'] = 'Case not found';
    }
    $stmt->close();
} elseif ($action === 'archive') {
    $id = (int)$_POST['id'];
    $sql = "UPDATE case_reports SET archived=1 WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Case archived successfully'];
    } else {
        $response['message'] = 'Failed to archive case: ' . $conn->error;
    }
    $stmt->close();
}

echo json_encode($response);
closeDBConnection($conn);
?>