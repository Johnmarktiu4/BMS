<?php
require_once 'db_conn.php';
header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request'];
$conn = getDBConnection();
if (!$conn) {
    $response['message'] = 'Database connection failed';
    echo json_encode($response);
    exit;
}

$profileDir = dirname(__DIR__, 2) . '/image/profiles/';
if (!is_dir($profileDir)) mkdir($profileDir, 0755, true);
if (!is_writable($profileDir)) {
    $response['message'] = 'Profiles directory is not writable';
    echo json_encode($response); closeDBConnection($conn); exit;
}

if (!isset($_POST['action'])) {
    echo json_encode($response); closeDBConnection($conn); exit;
}
$action = $_POST['action'];

/* FETCH */
if ($action === 'fetch') {
    $page = (int)($_POST['page'] ?? 1);
    $limit = (int)($_POST['limit'] ?? 10);
    $offset = ($page - 1) * $limit;
    $search = $conn->real_escape_string($_POST['search'] ?? '');
    $position = $conn->real_escape_string($_POST['position'] ?? '');
    $status = $conn->real_escape_string($_POST['status'] ?? '');
    $where = "WHERE archived = 0";
    if ($search) $where .= " AND (full_name LIKE '%$search%' OR contact LIKE '%$search%')";
    if ($position) $where .= " AND position = '$position'";
    if ($status) $where .= " AND status = '$status'";

    $sql = "SELECT id, full_name, position, term_start_date, term_end_date, contact, status, profile_picture 
            FROM officials $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $result = $conn->query($sql);
    $officials = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['profile_picture']) {
            $row['profile_picture'] = 'image/profiles/' . basename($row['profile_picture']);
        }
        $officials[] = $row;
    }
    $countResult = $conn->query("SELECT COUNT(*) as total FROM officials $where");
    $total = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);

    $response = [
        'status' => 'success',
        'data' => [
            'officials' => $officials,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total' => (int)$total,
                'limit' => $limit
            ]
        ]
    ];
}

/* ADD */
elseif ($action === 'add') {
    $full_name = htmlspecialchars($_POST['full_name']);
    $position = $conn->real_escape_string($_POST['position']);
    $term = $conn->real_escape_string($_POST['term']);
    $term = trim($term);
    $start = substr($term, 0, 4);
    $end = substr($term, -4);
    $contact = $conn->real_escape_string($_POST['contact']);
    $status = $conn->real_escape_string($_POST['status']);
    $profile_picture = '';

    if (isset($_POST['profile_picture']) && strpos($_POST['profile_picture'], 'data:image') === 0) {
        $imageData = $_POST['profile_picture'];
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $type = strtolower($type[1]);
            if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                $response['message'] = 'Invalid image format'; echo json_encode($response); closeDBConnection($conn); exit;
            }
            $imageData = base64_decode($imageData);
            $filename = 'official_' . time() . '_' . uniqid() . '.' . $type;
            $filepath = $profileDir . $filename;
            if (file_put_contents($filepath, $imageData)) {
                $profile_picture = 'image/profiles/' . $filename;
            } else {
                $response['message'] = 'Failed to save image'; echo json_encode($response); closeDBConnection($conn); exit;
            }
        }
    }

    $check = "SELECT position FROM officials WHERE position='$position' AND position!='Kagawad' AND term_start_date='$start' AND term_end_date='$end' AND archived=0";

    if ($result = $conn->query($check)) {
        if ($result->num_rows > 0) {
             echo json_encode(['status' => 'failed', 'message' => 'An official with the same position and term already exists.']); closeDBConnection($conn); exit;
        }
    }

    $checkPerson = "SELECT id FROM officials WHERE full_name='$full_name' AND term_start_date='$start' AND term_end_date='$end' AND archived=0";

    if ($result = $conn->query($checkPerson)) {
        if ($result->num_rows > 0) {
             echo json_encode(['status' => 'failed', 'message' => 'This person is already listed as an official for the specified term.']); closeDBConnection($conn); exit;
        }
    }

    $sql = "INSERT INTO officials (full_name, position, term_start_date, term_end_date, contact, status, profile_picture)
            VALUES ('$full_name', '$position', '$start', '$end', '$contact', '$status', " . ($profile_picture ? "'$profile_picture'" : 'NULL') . ")";
    if ($conn->query($sql)) {
        $response = ['status' => 'success', 'message' => 'Official added successfully'];
    } else {
        $response['message'] = 'Failed to add: ' . $conn->error;
    }
}

/* UPDATE */
elseif ($action === 'update') {
    $id = (int)$_POST['id'];
    $full_name = htmlspecialchars($_POST['full_name']);
    $position = $conn->real_escape_string($_POST['position']);
    $term_start = $conn->real_escape_string($_POST['term_start_date']);
    $term_end = !empty($_POST['term_end_date']) ? $conn->real_escape_string($_POST['term_end_date']) : 'NULL';
    $contact = $conn->real_escape_string($_POST['contact']);
    $status = $conn->real_escape_string($_POST['status']);
    $sql = "UPDATE officials SET full_name='$full_name', position='$position', term_start_date='$term_start', term_end_date=" . ($term_end === 'NULL' ? 'NULL' : "'$term_end'") . ", contact='$contact', status='$status'";

    if (isset($_POST['profile_picture']) && strpos($_POST['profile_picture'], 'data:image') === 0) {
        $imageData = $_POST['profile_picture'];
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $type = strtolower($type[1]);
            if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                $response['message'] = 'Invalid image format'; echo json_encode($response); closeDBConnection($conn); exit;
            }
            $imageData = base64_decode($imageData);
            $filename = 'official_' . time() . '_' . uniqid() . '.' . $type;
            $filepath = $profileDir . $filename;
            if (file_put_contents($filepath, $imageData)) {
                $sql .= ", profile_picture='image/profiles/$filename'";
            }
        }
    } elseif (isset($_POST['profile_picture']) && !empty($_POST['profile_picture'])) {
        $sql .= ", profile_picture='" . $conn->real_escape_string($_POST['profile_picture']) . "'";
    } else {
        $sql .= ", profile_picture=NULL";
    }

    $sql .= " WHERE id=$id AND archived=0";
    if ($conn->query($sql)) {
        $response = ['status' => 'success', 'message' => 'Official updated successfully'];
    } else {
        $response['message'] = 'Failed to update: ' . $conn->error;
    }
}

/* GET */
elseif ($action === 'get') {
    $id = (int)$_POST['id'];
    $sql = "SELECT * FROM officials WHERE id=$id AND archived=0";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        if ($row['profile_picture']) {
            $row['profile_picture'] = 'image/profiles/' . basename($row['profile_picture']);
        }
        $response = ['status' => 'success', 'data' => $row];
    } else {
        $response['message'] = 'Official not found';
    }
}

/* ARCHIVE */
elseif ($action === 'archive') {
    $id = (int)$_POST['id'];
    $sql = "UPDATE officials SET archived=1, status='Inactive' WHERE id=$id";
    if ($conn->query($sql)) {
        $response = ['status' => 'success', 'message' => 'Official archived'];
    } else {
        $response['message'] = 'Failed to archive';
    }
}

echo json_encode($response);
closeDBConnection($conn);
?>