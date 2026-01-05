<?php
require_once 'db_conn.php';
header('Content-Type: application/json');
$conn = getDBConnection();
$action = $_POST['action'] ?? '';

function generateCaseId($conn) {
    $stmt = $conn->query("SELECT COALESCE(MAX(id), 0) AS max_id FROM blotters");
    $row = $stmt->fetch_assoc();
    $next = $row['max_id'] + 1;
    return 'CMP-' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

switch ($action) {
    case 'fetch_residents':
        $stmt = $conn->prepare("
            SELECT id,
                   CONCAT(first_name, ' ', COALESCE(middle_name,''), ' ', last_name, COALESCE(CONCAT(' ', suffix),'')) AS full_name,
                   first_name, last_name, sex, age,
                   CONCAT(house_number, ' ', street, ', Barangay 3 Gen. Emilio Aguinaldo, Dalahican, Cavite City') AS address,
                   contact_number AS contact
            FROM residents WHERE archived = 0 ORDER BY full_name
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
        $complainant_ids = $_POST['complainant_ids'] ?? '[]';
        $defendant_ids = $_POST['defendant_ids'] ?? '[]';
        $nature = $_POST['nature_of_complaint'] ?? '';
        $details = $_POST['details'] ?? '';
        $date_filed = $_POST['date_filed'] ?? date('Y-m-d');
        $incident_time = $_POST['incident_time'] ?? null;
        $location = $_POST['location'] ?? null;
        $barangay_incharge_id = !empty($_POST['barangay_incharge_id']) ? (int)$_POST['barangay_incharge_id'] : null;
        $status = $_POST['status'] ?? 'Unresolved';
        $date_schedule = $_POST['date_schedule'] ?? date('Y-m-d');
        $time_schedule = $_POST['time_schedule'] ?? null;

        if ($id) {
            $stmt = $conn->prepare("UPDATE blotters SET 
                case_id = ?, complainant_ids = ?, defendant_ids = ?, nature_of_complaint = ?, 
                details = ?, date_filed = ?, incident_time = ?, location = ?, 
                barangay_incharge_id = ?, status = ? 
                WHERE id = ?");
            $stmt->bind_param("ssssssssisi", $case_id, $complainant_ids, $defendant_ids, $nature, $details, $date_filed, $incident_time, $location, $barangay_incharge_id, $status, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO blotters (
                case_id, complainant_ids, defendant_ids, nature_of_complaint, details, 
                date_filed, incident_time, location, barangay_incharge_id, status, hearing_count
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssssssis", $case_id, $complainant_ids, $defendant_ids, $nature, $details, $date_filed, $incident_time, $location, $barangay_incharge_id, $status);
        }

        $success = $stmt->execute();
        $stmt->close();       

        $sql = "Select id, hearing_count FROM blotters WHERE case_id = '$case_id'";
        $res = $conn->query($sql);
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $idB = $row["id"];
            $count = $row["hearing_count"];
            
            if ($count == 1) {
                $stmt2 = $conn->prepare("INSERT INTO blotter_hearings 
                    (blotter_id, hearing_number, hearing_date, hearing_time, barangay_incharge_id) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("iissi", $idB, $count, $date_schedule, $time_schedule, $barangay_incharge_id);
                $stmt2->execute();
                $stmt2->close();

                $conn->commit();
            }
        }

        echo json_encode(['success' => $success]);
        break;

    case 'fetch_blotters':
        $search = $_POST['search'] ?? '';
        $status_filter = $_POST['status'] ?? '';
        $date = $_POST['date'] ?? '';

        $sql = "SELECT b.*, 
                       (SELECT MAX(hearing_number) FROM blotter_hearings h WHERE h.blotter_id = b.id AND h.discussion_summary IS NOT NULL) AS last_hearing_recorded,
                       COALESCE(o.full_name, '—') AS incharge_name
                FROM blotters b
                LEFT JOIN officials o ON b.barangay_incharge_id = o.id
                WHERE 1=1";
        $params = []; 
        $types = '';

        if ($search) {
            $like = "%$search%";
            $sql .= " AND (b.case_id LIKE ? OR b.nature_of_complaint LIKE ? OR b.complainant_ids LIKE ? OR b.defendant_ids LIKE ? OR b.location LIKE ?)";
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
            $types .= 'sssss';
        }
        if ($status_filter) {
            $sql .= " AND b.status = ?";
            $params[] = $status_filter;
            $types .= 's';
        }
        if ($date) {
            $sql .= " AND b.date_filed = ?";
            $params[] = $date;
            $types .= 's';
        }

        $sql .= " ORDER BY b.id DESC";

        $stmt = $conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'get':
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("SELECT b.*, COALESCE(o.full_name, '—') AS incharge_name FROM blotters b LEFT JOIN officials o ON b.barangay_incharge_id = o.id WHERE b.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => !!$row, 'data' => $row]);
        break;

    case 'get_blotter_with_hearings':
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("SELECT b.*, COALESCE(o.full_name, '—') AS incharge_name FROM blotters b LEFT JOIN officials o ON b.barangay_incharge_id = o.id WHERE b.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $blotter = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("SELECT h.*, COALESCE(o.full_name, '—') AS official_name 
                                FROM blotter_hearings h 
                                LEFT JOIN officials o ON h.barangay_incharge_id = o.id 
                                WHERE h.blotter_id = ? 
                                ORDER BY h.hearing_number");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $hearings = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $blotter, 'hearings' => $hearings]);
        break;

    case 'schedule_hearing':
        $blotter_id = (int)$_POST['blotter_id'];
        $hearing_number = (int)$_POST['hearing_number'];
        $hearing_date = $_POST['hearing_date'] ?? null;
        $hearing_time = $_POST['hearing_time'] ?? null;
        $barangay_incharge_id = !empty($_POST['barangay_incharge_id']) ? (int)$_POST['barangay_incharge_id'] : null;

        $conn->begin_transaction();
        try {
            $conn->query("UPDATE blotters SET hearing_count = hearing_count + 1 WHERE id = $blotter_id");

            $stmt = $conn->prepare("INSERT INTO blotter_hearings 
                (blotter_id, hearing_number, hearing_date, hearing_time, barangay_incharge_id) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iissi", $blotter_id, $hearing_number, $hearing_date, $hearing_time, $barangay_incharge_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'record_hearing':
        date_default_timezone_set('Asia/Manila');
        $hearing_id = !empty($_POST['hearing_id']) ? (int)$_POST['hearing_id'] : null;
        $blotter_id = (int)$_POST['blotter_id'];
        $attendees = json_encode($_POST['attendees'] ?? []);
        $summary = $_POST['summary'] ?? '';
        $outcome = $_POST['outcome'] ?? 'Unresolved';
        $nexthearingSchedule = $_POST['nexthearingSchedule'] ?? null;
        $nexthearingTimeSchedule = $_POST['nexthearingTimeSchedule'] ?? null;
        $incharge = $_POST['barangay_incharge_id'] ?? 0;
        $todayy = date('Y-m-d');
        $currentTime = date("H:i");

        $conn->begin_transaction();
        try {
            if ($hearing_id) {
                $stmt = $conn->prepare("UPDATE blotter_hearings SET attendees = ?, discussion_summary = ?, outcome = ?, hearing_date = ?, hearing_time = ?  WHERE id = ?");
                $stmt->bind_param("sssssi", $attendees, $summary, $outcome, $todayy, $currentTime, $hearing_id);
            } 
            $stmt->execute();
            $stmt->close();
            
            if ($outcome !== "Resolved" || $outcome !== "Forwarded to Police")
            $conn->query("UPDATE blotters SET hearing_count = hearing_count + 1 WHERE id = $blotter_id");
            $conn->commit();

            $stmt_count = $conn->prepare("SELECT hearing_count FROM blotters WHERE id = ?");
                $stmt_count->bind_param("i", $blotter_id);
                $stmt_count->execute();
                $hearing_number = $stmt_count->get_result()->fetch_assoc()['hearing_count'];
                $stmt_count->close();
            if ($hearing_number <= 3) {
                $stmt2 = $conn->prepare("INSERT INTO blotter_hearings 
                    (blotter_id, hearing_number, hearing_date, hearing_time, barangay_incharge_id) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("iissi", $blotter_id, $hearing_number, $hearing_date, $hearing_time, $incharge);
                $stmt2->execute();
                $stmt2->close();
            }


            $conn->query("UPDATE blotters SET status = '$outcome' WHERE id = $blotter_id");

            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_hearings':
        $id = (int)$_POST['blotter_id'];
        $stmt = $conn->prepare("SELECT h.*, COALESCE(o.full_name, '—') AS official_name 
                                FROM blotter_hearings h 
                                LEFT JOIN officials o ON h.barangay_incharge_id = o.id 
                                WHERE h.blotter_id = ? 
                                ORDER BY h.hearing_number");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

closeDBConnection($conn);
?>