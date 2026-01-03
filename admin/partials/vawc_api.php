<?php
// admin/partials/vawc_api.php
require_once 'db_conn.php';

header('Content-Type: application/json');

// Get database connection
$conn = getDBConnection();

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'fetch_residents':
        $stmt = $conn->prepare("SELECT id, full_name FROM residents WHERE archived = 0 ORDER BY full_name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $residents = [];
        while ($row = $result->fetch_assoc()) {
            $residents[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $residents]);
        break;

    case 'get_resident_details':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }
        $stmt = $conn->prepare("SELECT date_of_birth, age, address, contact_number FROM residents WHERE id = ? AND archived = 0");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Resident not found']);
        }
        $stmt->close();
        break;

    case 'add_report':
        $victim_name = $conn->real_escape_string($_POST['victim_name']);
        $victim_dob = $conn->real_escape_string($_POST['victim_dob']);
        $victim_age = intval($_POST['victim_age']);
        $victim_address = $conn->real_escape_string($_POST['victim_address']);
        $victim_contact = $conn->real_escape_string($_POST['victim_contact']);
        $relationship_to_abuser = $conn->real_escape_string($_POST['relationship_to_abuser']);
        $abuser_name = $conn->real_escape_string($_POST['abuser_name']);
        $abuser_is_resident = $conn->real_escape_string($_POST['abuser_is_resident']);
        $abuser_address = $conn->real_escape_string($_POST['abuser_address']);
        $incident_date = $conn->real_escape_string($_POST['incident_date']);
        $incident_time = $conn->real_escape_string($_POST['incident_time']);
        $incident_place = $conn->real_escape_string($_POST['incident_place']);
        $incident_description = $conn->real_escape_string($_POST['incident_description']);
        $witnesses_evidence = $conn->real_escape_string($_POST['witnesses_evidence']);
        $status = $conn->real_escape_string($_POST['status']);
        $id = isset($_POST['id']) ? intval($_POST['id']) : null;

        if ($id) {
            // Update existing report
            $stmt = $conn->prepare("UPDATE vawc_reports SET victim_name = ?, victim_dob = ?, victim_age = ?, victim_address = ?, victim_contact = ?, relationship_to_abuser = ?, abuser_name = ?, abuser_is_resident = ?, abuser_address = ?, incident_date = ?, incident_time = ?, incident_place = ?, incident_description = ?, witnesses_evidence = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sisssssssssssssi", $victim_name, $victim_dob, $victim_age, $victim_address, $victim_contact, $relationship_to_abuser, $abuser_name, $abuser_is_resident, $abuser_address, $incident_date, $incident_time, $incident_place, $incident_description, $witnesses_evidence, $status, $id);
        } else {
            // Add new report
            $stmt = $conn->prepare("INSERT INTO vawc_reports (victim_name, victim_dob, victim_age, victim_address, victim_contact, relationship_to_abuser, abuser_name, abuser_is_resident, abuser_address, incident_date, incident_time, incident_place, incident_description, witnesses_evidence, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisssssssssssss", $victim_name, $victim_dob, $victim_age, $victim_address, $victim_contact, $relationship_to_abuser, $abuser_name, $abuser_is_resident, $abuser_address, $incident_date, $incident_time, $incident_place, $incident_description, $witnesses_evidence, $status);
        }

        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode(['success' => true]);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Failed to save report']);
        }
        break;

    case 'fetch_reports':
        $query = "SELECT * FROM vawc_reports";
        $params = [];
        $types = '';
        
        if (isset($_POST['status']) && !empty($_POST['status'])) {
            $query .= " WHERE status = ?";
            $params[] = $_POST['status'];
            $types .= 's';
        }
        
        if (isset($_POST['incident_date']) && !empty($_POST['incident_date'])) {
            $query .= (empty($params) ? " WHERE" : " AND") . " incident_date = ?";
            $params[] = $_POST['incident_date'];
            $types .= 's';
        } elseif (isset($_POST['date_range']) && !empty($_POST['date_range'])) {
            $query .= (empty($params) ? " WHERE" : " AND") . " incident_date >= ?";
            $params[] = $_POST['date_range'];
            $types .= 's';
        }
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $reports]);
        break;

    case 'fetch_report':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }
        $stmt = $conn->prepare("SELECT * FROM vawc_reports WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Report not found']);
        }
        $stmt->close();
        break;

    case 'delete_report':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM vawc_reports WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Report deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete report']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Close database connection
closeDBConnection($conn);
?>