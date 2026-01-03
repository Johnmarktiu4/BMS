<?php
require_once 'db_conn.php';
header('Content-Type: application/json');
$conn = getDBConnection();
$action = $_POST['action'] ?? '';

function generateCaseId($conn) {
    $stmt = $conn->query("SELECT MAX(id) AS max_id FROM complaints");
    $row = $stmt->fetch_assoc();
    $next = ($row['max_id'] ?? 0) + 1;
    return 'B-' . str_pad($next, 5, '0', STR_PAD_LEFT);
}

switch ($action) {
    case 'fetch_residents':
        $stmt = $conn->prepare("
            SELECT 
                r.id,
                CONCAT(r.first_name, ' ', COALESCE(r.middle_name,''), ' ', r.last_name, COALESCE(CONCAT(' ', r.suffix),'')) AS full_name,
                r.sex, r.age,
                CONCAT(r.house_number, ' ', r.street, ', Barangay 3 Gen. Emilio Aguinaldo, Dalahican, Cavite City') AS address,
                r.contact_number AS contact
            FROM residents r 
            WHERE r.archived = 0 
            ORDER BY full_name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'fetch_officials':
        $stmt = $conn->prepare("SELECT id, full_name, position FROM officials WHERE status = 'Active' AND archived = 0 ORDER BY full_name");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'add':
    case 'update':
        $id = $_POST['id'] ?? null;
        $case_id = $id ? ($_POST['case_id'] ?? '') : generateCaseId($conn);
        $status = $_POST['status'] ?? 'New';
        $official_id = (int)($_POST['barangay_official_id'] ?? 0);
        $date_reported = $_POST['date_reported'] ?? date('Y-m-d');
        $date_incident = $_POST['date_incident'] ?? $date_reported;
        $details = $_POST['details'] ?? '';
        $reported_by_resident_id = null;
        $reported_by_name = 'N/A';

        $uploadDir = '../uploads/compliants/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $complainants = json_decode($_POST['complainants'] ?? '[]', true);
        $defendants = json_decode($_POST['defendants'] ?? '[]', true);

        $conn->begin_transaction();
        try {
            if ($id) {
                $stmt = $conn->prepare("UPDATE complaints SET case_id=?, status=?, barangay_official_id=?, date_reported=?, date_incident=?, details=?, reported_by_resident_id=?, reported_by_name=? WHERE id=?");
                $stmt->bind_param("ssisssisi", $case_id, $status, $official_id, $date_reported, $date_incident, $details, $reported_by_resident_id, $reported_by_name, $id);
                $stmt->execute();
                $stmt->close();

                $conn->query("DELETE FROM complaint_complainants WHERE complaint_id = $id");
                $conn->query("DELETE FROM complaint_defendants WHERE complaint_id = $id");
            } else {
                $stmt = $conn->prepare("INSERT INTO complaints (case_id, status, barangay_official_id, date_reported, date_incident, details, reported_by_resident_id, reported_by_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisssis", $case_id, $status, $official_id, $date_reported, $date_incident, $details, $reported_by_resident_id, $reported_by_name);
                $stmt->execute();
                $id = $conn->insert_id;
                $stmt->close();
            }

            // Save Complainants
            foreach ($complainants as $p) {
                $resident_id = $p['is_resident'] ? (int)$p['id'] : null;
                $name = $p['is_resident'] ? null : ($p['name'] ?? '');
                $sex = $p['sex'] ?? '';
                $age = (int)($p['age'] ?? 0);
                $address = $p['address'] ?? '';
                $contact = $p['contact'] ?? '';
                $doc = null;

                if (isset($_FILES['complainant_file']) && $_FILES['complainant_file']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['complainant_file'];
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'complainant_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $doc = $filename;
                    }
                }

                $stmt = $conn->prepare("INSERT INTO complaint_complainants (complaint_id, resident_id, name, sex, age, address, contact, supporting_doc_complainant) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iississs", $id, $resident_id, $name, $sex, $age, $address, $contact, $doc);
                $stmt->execute();
                $stmt->close();
            }

            // Save Defendants
            foreach ($defendants as $p) {
                $resident_id = $p['is_resident'] ? (int)$p['id'] : null;
                $name = $p['is_resident'] ? null : ($p['name'] ?? '');
                $sex = $p['sex'] ?? '';
                $age = (int)($p['age'] ?? 0);
                $address = $p['address'] ?? '';
                $contact = $p['contact'] ?? '';
                $doc = null;

                if (isset($_FILES['defendant_file']) && $_FILES['defendant_file']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['defendant_file'];
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'defendant_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $doc = $filename;
                    }
                }

                $stmt = $conn->prepare("INSERT INTO complaint_defendants (complaint_id, resident_id, name, sex, age, address, contact, supporting_doc_defendant) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iississs", $id, $resident_id, $name, $sex, $age, $address, $contact, $doc);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Complaint saved successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'fetch_compliants':
        $search = $_POST['search'] ?? '';
        $status = $_POST['status'] ?? '';
        $date = $_POST['date'] ?? '';

        $sql = "SELECT c.*, o.full_name AS official_name FROM complaints c LEFT JOIN officials o ON c.barangay_official_id = o.id WHERE 1=1";
        $params = []; $types = '';

        if ($search) {
            $like = "%$search%";
            $sql .= " AND (c.case_id LIKE ? OR c.details LIKE ?)";
            $params[] = $like; $params[] = $like; $types .= 'ss';
        }
        if ($status) { $sql .= " AND c.status = ?"; $params[] = $status; $types .= 's'; }
        if ($date) { $sql .= " AND c.date_reported = ?"; $params[] = $date; $types .= 's'; }

        $sql .= " ORDER BY c.id DESC";
        $stmt = $conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $compliants = [];
        while ($row = $result->fetch_assoc()) {
            $cid = $row['id'];

            // Fetch Complainants with Resident Name
            $comp = $conn->query("
                SELECT cc.*, 
                       IF(cc.resident_id IS NOT NULL, 
                          CONCAT(r.first_name, ' ', COALESCE(r.middle_name,''), ' ', r.last_name, COALESCE(CONCAT(' ', r.suffix),'')),
                          cc.name
                       ) AS display_name
                FROM complaint_complainants cc
                LEFT JOIN residents r ON cc.resident_id = r.id
                WHERE cc.complaint_id = $cid
            ")->fetch_all(MYSQLI_ASSOC);

            // Fetch Defendants with Resident Name
            $def = $conn->query("
                SELECT cd.*, 
                       IF(cd.resident_id IS NOT NULL, 
                          CONCAT(r.first_name, ' ', COALESCE(r.middle_name,''), ' ', r.last_name, COALESCE(CONCAT(' ', r.suffix),'')),
                          cd.name
                       ) AS display_name
                FROM complaint_defendants cd
                LEFT JOIN residents r ON cd.resident_id = r.id
                WHERE cd.complaint_id = $cid
            ")->fetch_all(MYSQLI_ASSOC);

            $row['complainants'] = array_map(function($p) {
                return [
                    'name' => $p['display_name'] ?? 'Unknown',
                    'sex' => $p['sex'] ?? '',
                    'age' => $p['age'] ?? 0,
                    'is_resident' => !empty($p['resident_id'])
                ];
            }, $comp);

            $row['defendants'] = array_map(function($p) {
                return [
                    'name' => $p['display_name'] ?? 'Unknown',
                    'sex' => $p['sex'] ?? '',
                    'age' => $p['age'] ?? 0,
                    'is_resident' => !empty($p['resident_id'])
                ];
            }, $def);

            $compliants[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $compliants]);
        break;

    case 'get':
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("SELECT c.*, o.full_name AS official_name FROM complaints c LEFT JOIN officials o ON c.barangay_official_id = o.id WHERE c.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $cid = $row['id'];
            $comp = $conn->query("SELECT cc.*, r.full_name AS resident_name FROM complaint_complainants cc LEFT JOIN residents r ON cc.resident_id = r.id WHERE cc.complaint_id = $cid")->fetch_all(MYSQLI_ASSOC);
            $def = $conn->query("SELECT cd.*, r.full_name AS resident_name FROM complaint_defendants cd LEFT JOIN residents r ON cd.resident_id = r.id WHERE cd.complaint_id = $cid")->fetch_all(MYSQLI_ASSOC);

            $row['complainants'] = array_map(fn($p) => [
                'id' => $p['resident_id'], 'name' => $p['resident_name'] ?? $p['name'], 'sex' => $p['sex'], 'age' => $p['age'],
                'address' => $p['address'], 'contact' => $p['contact'], 'is_resident' => !empty($p['resident_id'])
            ], $comp);

            $row['defendants'] = array_map(fn($p) => [
                'id' => $p['resident_id'], 'name' => $p['resident_name'] ?? $p['name'], 'sex' => $p['sex'], 'age' => $p['age'],
                'address' => $p['address'], 'contact' => $p['contact'], 'is_resident' => !empty($p['resident_id'])
            ], $def);
        }

        echo json_encode(['success' => !!$row, 'data' => $row]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

closeDBConnection($conn);
?>