<?php
require_once 'db_conn.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request'];
$conn = getDBConnection();

if (!$conn) {
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? '';

/* 1. FETCH OFFICIALS */
if ($action === 'fetch_officials') {
    $sql = "SELECT id, full_name, position FROM officials WHERE archived = 0 ORDER BY full_name";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

/* 2. FETCH ACCOUNTS */
if ($action === 'fetch_accounts') {
    $sql = "SELECT ua.id, ua.username, ua.status,
                   o.full_name, o.position, o.id AS official_id,
                   ua.sec_a1, ua.sec_a2, ua.sec_a3
            FROM user_roles_official_accounts ua
            JOIN officials o ON ua.official_id = o.id
            WHERE o.archived = 0
            ORDER BY o.full_name";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

/* 3. ADD ACCOUNT - FIXED: 10 columns, 10 placeholders, 10 binds */
if ($action === 'add_account') {
    $official_id = (int)$_POST['official_id'];
    $username    = trim($_POST['username']);
    $password    = $_POST['password'];
    $status      = $_POST['status'] === 'Active' ? 'Active' : 'Inactive';
    $a1          = strtolower(trim($_POST['sec_a1']));
    $a2          = strtolower(trim($_POST['sec_a2']));
    $a3          = strtolower(trim($_POST['sec_a3']));

    if (empty($username) || empty($password) || empty($a1) || empty($a2) || empty($a3)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    // Check duplicate username
    $check = $conn->prepare("SELECT id FROM user_roles_official_accounts WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username already taken']);
        exit;
    }
    $check->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO user_roles_official_accounts 
        (official_id, username, password, status, sec_q1, sec_a1, sec_q2, sec_a2, sec_q3, sec_a3)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $q1 = "What is your mother's maiden name?";
    $q2 = "What was the name of your first pet?";
    $q3 = "In what city were you born?";

    $stmt->bind_param(
        "isssssssss", 
        $official_id, 
        $username, 
        $hash, 
        $status, 
        $q1, $a1, 
        $q2, $a2, 
        $q3, $a3
    );

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Account created successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

/* 4. UPDATE ACCOUNT */
if ($action === 'update_account') {
    $id     = (int)$_POST['id'];
    $status = $_POST['status'] === 'Active' ? 'Active' : 'Inactive';
    $a1     = strtolower(trim($_POST['sec_a1']));
    $a2     = strtolower(trim($_POST['sec_a2']));
    $a3     = strtolower(trim($_POST['sec_a3']));

    if (empty($a1) || empty($a2) || empty($a3)) {
        echo json_encode(['status' => 'error', 'message' => 'All security answers required']);
        exit;
    }

    if (!empty($_POST['password'])) {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user_roles_official_accounts 
            SET password = ?, status = ?, sec_a1 = ?, sec_a2 = ?, sec_a3 = ? 
            WHERE id = ?");
        $stmt->bind_param("sssssi", $hash, $status, $a1, $a2, $a3, $id);
    } else {
        $stmt = $conn->prepare("UPDATE user_roles_official_accounts 
            SET status = ?, sec_a1 = ?, sec_a2 = ?, sec_a3 = ? 
            WHERE id = ?");
        $stmt->bind_param("ssssi", $status, $a1, $a2, $a3, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Account updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed']);
    }
    $stmt->close();
    exit;
}

/* 5. DELETE ACCOUNT */
if ($action === 'delete_account') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM user_roles_official_accounts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    echo json_encode($success 
        ? ['status' => 'success', 'message' => 'Account deleted']
        : ['status' => 'error', 'message' => 'Delete failed']
    );
    exit;
}

echo json_encode($response);
$conn->close();
?>