<?php
include 'db_conn.php';
$conn = getDBConnection();
header('Content-Type: application/json');

if (!isset($_REQUEST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_REQUEST['action'];

switch ($action) {
    /* --------------------------------------------------------------
       1. LIST Term of Office (search, filter, pagination)
       -------------------------------------------------------------- */
    case 'get_terms':
        $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
        $status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;

        $terms = [];

        $where = 'WHERE 1 = 1';
        if ($search) {
            $where .= " AND (start LIKE '%$search%' OR end LIKE '%$search%' OR term LIKE '%$search%' OR status LIKE '%$search%')";
        }
        
        if ($status) {
            $stat = ($status == 'Yes') ? 1 : 0;
            $where .= " AND status = $stat";
        }

        $sql = "SELECT id, start, end, term, status FROM term_of_office $where ORDER BY id DESC LIMIT $offset, $limit";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $terms[] = $row;
        }

        $count_sql = "SELECT COUNT(*) AS total FROM term_of_office $where";
        $total = $conn->query($count_sql)->fetch_assoc()['total'];

        echo json_encode(['terms' => $terms, 'total' => $total]);
        break;
    
    /* --------------------------------------------------------------
       Save Term of Office
       -------------------------------------------------------------- */
    case 'save_term':
    case 'update_term':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $is_update = ($action === 'update_term' && $id > 0);

        // Basic existence check
        if (!isset($_POST['start'])) {
            echo json_encode(['success' => false, 'message' => 'Start year is required']);
            break;
        }

        // Normalize + validate numeric-only year
        $start = trim($_POST['start']);
        if (!preg_match('/^\d{4}$/', $start)) {
            echo json_encode(['success' => false, 'message' => 'Start must be a 4-digit year']);
            break;
        }

        $startYear = (int)$start;
        // Optional range validation
        if ($startYear < 2000 || $startYear > 2100) {
            echo json_encode(['success' => false, 'message' => 'Year must be between 2000 and 2100']);
            break;
        }

        $endYear = $startYear + 4;
        $term = $startYear . ' - ' . $endYear;   // use dot for concatenation
        $status = 0;

        if ($is_update) {

            $sql = "UPDATE term_of_office SET start = ?, end = ?, term = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
                break;
            }
            // Bind: start(int), end(int), term(string), id(int)
            $stmt->bind_param('iiss', $startYear, $endYear, $term, $id);
            
        }
        else {
            // For new entries, default status to inactive (0)
            $sql = "INSERT INTO term_of_office (start, end, term, status)
                VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
                break;
            }

            // Bind: start(int), end(int), term(string), status(string)
            $stmt->bind_param('iiss', $startYear, $endYear, $term, $status);
        }

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Term of Office saved successfully'
                ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
        }

        $stmt->close();
        break;

    /* --------------------------------------------------------------
       4. GET ONE TERM
       -------------------------------------------------------------- */
    case 'get_term':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success'=>false,'message'=>'Invalid ID']);
            exit;
        }
        $sql = "SELECT * FROM term_of_office WHERE id = $id";
        $res = $conn->query($sql);
        if ($row = $res->fetch_assoc()) {
            echo json_encode(['success'=>true,'term'=>$row]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Term not found']);
        }
        break;
    case 'archive_term':
        $id = isset($_POST['id']) ? intval($_POST['id']) :0;
        if ($id <= 0) {
            echo json_encode(['success'=>false,'message'=>'Invalid ID']);
            exit;
        }
        $sql = 'UPDATE term_of_office SET status = 0';
        $res = $conn->query($sql);

        $sql = "UPDATE term_of_office SET status = 1 WHERE id = $id";
        $res = $conn->query($sql);
        if ($res) {
            echo json_encode(['success'=>true,'message'=>'Term set as active successfully']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Failed to set as active: ' . $conn->error]);
        }
}

$conn->close();
?>