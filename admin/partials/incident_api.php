<?php
require_once 'db_conn.php';
header('Content-Type: application/json');
$conn = getDBConnection();
$action = $_POST['action'] ?? '';

function generateCaseId($conn) {
    $stmt = $conn->query("SELECT MAX(id) AS max_id FROM incidents");
    $row = $stmt->fetch_assoc();
    $next = ($row['max_id'] ?? 0) + 1;
    return 'INC-' . str_pad($next, 5, '0', STR_PAD_LEFT);
}

switch ($action) {
    case 'fetch_residents':
        $stmt = $conn->prepare("
            SELECT id, full_name, contact_number,
                   house_number, street, province, municipality, zip_code
            FROM residents
            WHERE archived = 0
            ORDER BY full_name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $residents = [];
        while ($row = $result->fetch_assoc()) {
            $residents[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $residents]);
        break;

    case 'fetch_officials':
        $stmt = $conn->prepare("
            SELECT id, full_name
            FROM officials
            WHERE status = 'Active' AND archived = 0
            ORDER BY full_name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $officials = [];
        while ($row = $result->fetch_assoc()) {
            $officials[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $officials]);
        break;

    case 'add':
    case 'update':
        $id = $_POST['id'] ?? null;
        $case_id = $id ? ($_POST['case_id'] ?? '') : generateCaseId($conn);
        $status = $_POST['status'] ?? 'New';
        $nature_of_incident = $_POST['nature_of_incident'] ?? '';
        $persons = json_decode($_POST['persons_involved'] ?? '[]', true);
        $official_id = (int)($_POST['barangay_official_id'] ?? 0);
        $date_reported = $_POST['date_reported'] ?? date('Y-m-d');
        $date_incident = $_POST['date_incident'] ?? $date_reported;

        // Always set reported_by to N/A
        $reported_by_resident_id = null;
        $reported_by_name = 'N/A';

        // File upload
        $uploadDir = '../uploads/incidents/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $existingFiles = $id ? json_decode($conn->query("SELECT supporting_docs FROM incidents WHERE id=$id")->fetch_assoc()['supporting_docs'] ?? '[]', true) : [];
        $newFiles = [];
        if (!empty($_FILES['files']['name'][0])) {
            foreach ($_FILES['files']['name'] as $k => $name) {
                $tmp = $_FILES['files']['tmp_name'][$k];
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $filename = uniqid('doc_') . '.' . $ext;
                if (move_uploaded_file($tmp, $uploadDir . $filename)) {
                    $newFiles[] = $filename;
                }
            }
        }
        $allFiles = array_merge($existingFiles, $newFiles);
        $filesJson = json_encode($allFiles);
        $personsJson = json_encode($persons);

        $conn->begin_transaction();
        try {
            if ($id) {
                $stmt = $conn->prepare("
                    UPDATE incidents SET
                        case_id=?, status=?, nature_of_incident=?, persons_involved=?, barangay_official_id=?,
                        supporting_docs=?, reported_by_resident_id=?, reported_by_name=?,
                        date_reported=?, date_incident=?
                    WHERE id=?
                ");
                $stmt->bind_param("ssssisissii", $case_id, $status, $nature_of_incident, $personsJson, $official_id, $filesJson, $reported_by_resident_id, $reported_by_name, $date_reported, $date_incident, $id);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO incidents
                    (case_id, status, nature_of_incident, persons_involved, barangay_official_id, supporting_docs,
                     reported_by_resident_id, reported_by_name, date_reported, date_incident)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssssissss", $case_id, $status, $nature_of_incident, $personsJson, $official_id, $filesJson, $reported_by_resident_id, $reported_by_name, $date_reported, $date_incident);
            }
            $success = $stmt->execute();
            $stmt->close();
            $conn->commit();
            echo json_encode(['success' => $success, 'message' => $success ? 'Saved' : $conn->error]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'fetch_incidents':
        $search = $_POST['search'] ?? '';
        $status = $_POST['status'] ?? '';
        $date = $_POST['date'] ?? '';
        $sql = "
            SELECT i.*, o.full_name AS official_name
            FROM incidents i
            LEFT JOIN officials o ON i.barangay_official_id = o.id
            WHERE 1=1
        ";
        $params = []; $types = '';
        if ($search) {
            $like = "%$search%";
            $sql .= " AND (i.case_id LIKE ? OR i.nature_of_incident LIKE ?)";
            $params[] = $like; $params[] = $like; $types .= 'ss';
        }
        if ($status) {
            $sql .= " AND i.status = ?"; $params[] = $status; $types .= 's';
        }
        if ($date) {
            $sql .= " AND i.date_reported = ?"; $params[] = $date; $types .= 's';
        }
        $sql .= " ORDER BY i.id DESC";

        $stmt = $conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $incidents = [];
        while ($row = $result->fetch_assoc()) {
            $persons = json_decode($row['persons_involved'], true) ?: [];
            $row['persons_involved'] = $persons ? array_column($persons, 'name') : [];
            $row['reported_by_name'] = 'N/A'; // Always N/A
            $incidents[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $incidents]);
        break;

    case 'get':
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("SELECT * FROM incidents WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => !!$row, 'data' => $row]);
        break;

    case 'delete':
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM incidents WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $success]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

closeDBConnection($conn);
?>