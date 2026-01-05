<?php
include 'db_conn.php';
$conn = getDBConnection();
header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request'];
if (!isset($_REQUEST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_REQUEST['action'];

switch ($action) {
    /* --------------------------------------------------------------
       1. LIST RESIDENTS (search, filter, pagination)
       -------------------------------------------------------------- */
    case 'get_residents':
        $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
        $sex = isset($_GET['sex']) ? $conn->real_escape_string($_GET['sex']) : '';
        $status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
        $ageSearch = isset($_GET['ageSearch']) ? $conn->real_escape_string($_GET['ageSearch']) : '';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;

        $where = 'WHERE archived = 0';
        if ($search) {
            $where .= " AND (full_name LIKE '%$search%' OR address LIKE '%$search%' OR contact_number LIKE '%$search%')";
        }
        if ($sex) {
            $where .= " AND sex = '$sex'";
        }
        if ($status) {
            $registered = ($status == 'Yes') ? 1 : 0;
            $where .= " AND registered = $registered";
        }
        if ($ageSearch) {
            if ($ageSearch == 'Infant') {
                $where .= " AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) BETWEEN 0 AND 1";
            } elseif ($ageSearch == 'Toddler') {
                $where .= " AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) BETWEEN 1 AND 3";
            } elseif ($ageSearch == 'Minor') {
                $where .= " AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) BETWEEN 4 AND 12";
            } elseif ($ageSearch == 'Teen') {
                $where .= " AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) BETWEEN 13 AND 19";
            } elseif ($ageSearch == 'Adult') {
                $where .= " AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) BETWEEN 20 AND 59";
            } elseif ($ageSearch == 'Senior') {
                $where .= " AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) >= 60";
            }
        }

        $sql = "SELECT *, 
                (YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5)) AS age
                FROM residents $where 
                ORDER BY id DESC 
                LIMIT $offset, $limit";
        $result = $conn->query($sql);
        $residents = [];
        while ($row = $result->fetch_assoc()) {
            $residents[] = $row;
        }

        $count_sql = "SELECT COUNT(*) AS total FROM residents $where";
        $total = $conn->query($count_sql)->fetch_assoc()['total'];

        echo json_encode(['residents' => $residents, 'total' => $total]);
        break;

    /* --------------------------------------------------------------
       2. LIST HEADS
       -------------------------------------------------------------- */
    case 'get_heads':
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT *,
                (YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5)) AS age
                FROM residents
                WHERE is_head_of_family = 1 AND archived = 0
                ORDER BY full_name ASC
                LIMIT $offset, $limit";
        $result = $conn->query($sql);
        if (!$result) {
            echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
            exit;
        }

        $heads = [];
        while ($row = $result->fetch_assoc()) {
            $member_sql = "SELECT id, full_name, date_of_birth, sex
                           FROM residents
                           WHERE head_of_family_id = {$row['id']} AND archived = 0
                           ORDER BY full_name ASC";
            $member_res = $conn->query($member_sql);
            $members = [];
            while ($m = $member_res->fetch_assoc()) {
                $m['age'] = (new DateTime())->diff(new DateTime($m['date_of_birth']))->y;
                $members[] = $m;
            }
            $row['family_members'] = $members;
            $heads[] = $row;
        }

        $cnt_sql = "SELECT COUNT(*) AS total FROM residents WHERE is_head_of_family = 1 AND archived = 0";
        $total = $conn->query($cnt_sql)->fetch_assoc()['total'];

        echo json_encode(['heads' => $heads, 'total' => $total]);
        break;

    /* --------------------------------------------------------------
       3. GET HEAD ADDRESS
       -------------------------------------------------------------- */
    case 'get_head_address':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['house_number'=>'','street'=>'','address'=>'']);
            exit;
        }
        $sql = "SELECT house_number, street, address, lat, lng
                FROM residents
                WHERE id = $id AND is_head_of_family = 1 AND archived = 0";
        $res = $conn->query($sql);
        if ($row = $res->fetch_assoc()) {
            echo json_encode($row);
        } else {
            echo json_encode(['house_number'=>'','street'=>'','address'=>'']);
        }
        break;

    /* --------------------------------------------------------------
       4. GET ONE RESIDENT
       -------------------------------------------------------------- */
    case 'get_resident':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success'=>false,'message'=>'Invalid ID']);
            exit;
        }
        $sql = "SELECT * FROM residents WHERE id = $id AND archived = 0";
        $res = $conn->query($sql);
        if ($row = $res->fetch_assoc()) {
            $row['age'] = (new DateTime())->diff(new DateTime($row['date_of_birth']))->y;
            echo json_encode(['success'=>true,'resident'=>$row]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Resident not found']);
        }
        break;

    /* --------------------------------------------------------------
       5. ADD / UPDATE RESIDENT
       -------------------------------------------------------------- */
    case 'add_resident':
    case 'update_resident':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $is_update = ($action === 'update_resident' && $id > 0);

        $first_name = $conn->real_escape_string($_POST['first_name'] ?? '');
        $last_name = $conn->real_escape_string($_POST['last_name'] ?? '');
        $middle_name = $conn->real_escape_string($_POST['middle_name'] ?? '');
        $suffix = $conn->real_escape_string($_POST['suffix'] ?? '');
        $full_name = trim("$first_name " . ($middle_name ? "$middle_name " : '') . "$last_name " . ($suffix ? $suffix : ''));

        $civil_status = $conn->real_escape_string($_POST['civil_status'] ?? '');
        $sex = $conn->real_escape_string($_POST['sex'] ?? '');
        $date_of_birth = $conn->real_escape_string($_POST['date_of_birth'] ?? '');
        $place_of_birth = $conn->real_escape_string($_POST['place_of_birth'] ?? '');
        $religion = $conn->real_escape_string($_POST['religion'] ?? '');
        $nationality = $conn->real_escape_string($_POST['nationality'] ?? 'Filipino');

        $house_number = $conn->real_escape_string($_POST['house_number'] ?? '');
        $street = $conn->real_escape_string($_POST['street'] ?? '');
        $province = $conn->real_escape_string($_POST['province'] ?? 'Cavite');
        $municipality = $conn->real_escape_string($_POST['municipality'] ?? 'Cavite City');
        $zip_code = $conn->real_escape_string($_POST['zip_code'] ?? '4100');
        $address = "$house_number $street, Barangay 3, $municipality, $province $zip_code";

        $contact_number = $conn->real_escape_string($_POST['contact_number'] ?? '');
        $email_address = $conn->real_escape_string($_POST['email_address'] ?? '');

        $pwd = $conn->real_escape_string($_POST['pwd'] ?? 'No');
        $pwd_id = ($pwd === 'Yes') ? $conn->real_escape_string($_POST['pwd_id'] ?? '') : '';
        $disability_type = ($pwd === 'Yes') ? $conn->real_escape_string($_POST['disability_type'] ?? '') : '';

        $solo_parent = $conn->real_escape_string($_POST['solo_parent'] ?? 'No');

        $is_head_of_family = isset($_POST['head_of_family']) ? 1 : 0;
        $head_of_family_id = $is_head_of_family ? 'NULL' : (intval($_POST['selected_head_of_family'] ?? 0) ?: 'NULL');
        $relationship_to_head = $is_head_of_family ? 'Head' : $conn->real_escape_string($_POST['relationship_to_head'] ?? '');

        $emergency_name = $conn->real_escape_string($_POST['emergency_name'] ?? '');
        $emergency_relationship = $conn->real_escape_string($_POST['emergency_relationship'] ?? '');
        $emergency_contact = $conn->real_escape_string($_POST['emergency_contact'] ?? '');

        $lat = !empty($_POST['lat']) ? floatval($_POST['lat']) : 'NULL';
        $lng = !empty($_POST['lng']) ? floatval($_POST['lng']) : 'NULL';

        $is_voter = isset($_POST['is_voter']) ? 1 : 0;

        $employment_status = $conn->real_escape_string($_POST['employment_status'] ?? '');
        $year_of_residency = $conn->real_escape_string($_POST['year_of_residency'] ?? '');

        $registered = 1;
        $age = 0; $senior = 'No'; $senior_id = '';
        if (!empty($date_of_birth)) {
            $dob = new DateTime($date_of_birth);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            $senior = ($age >= 60) ? 'Yes' : 'No';
            $senior_id = ($senior === 'Yes') ? $conn->real_escape_string($_POST['senior_id'] ?? '') : '';
        }

        $profile_picture = '';
        if ($is_update) {
            $cur = $conn->query("SELECT profile_picture FROM residents WHERE id = $id");
            if ($cur && $cur->num_rows) $profile_picture = $cur->fetch_assoc()['profile_picture'] ?? '';
        }
        if (isset($_POST['profile_picture']) && preg_match('/^data:image\/jpeg;base64,/', $_POST['profile_picture'])) {
            $data = str_replace('data:image/jpeg;base64,', '', $_POST['profile_picture']);
            $data = str_replace(' ', '+', $data);
            $decoded = base64_decode($data);
            $upload_dir = '../image/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = time() . '_' . uniqid() . '.jpg';
            $path = $upload_dir . $filename;
            if (file_put_contents($path, $decoded)) {
                $profile_picture = 'image/profiles/' . $filename;
            }
        }

        $lat_sql = is_numeric($lat) ? $lat : 'NULL';
        $lng_sql = is_numeric($lng) ? $lng : 'NULL';

        if ($is_update) {
            $sql = "UPDATE residents SET
                first_name='$first_name', last_name='$last_name', middle_name='$middle_name', suffix='$suffix',
                full_name='$full_name', civil_status='$civil_status', sex='$sex', date_of_birth='$date_of_birth',
                age=$age, place_of_birth='$place_of_birth', religion='$religion', nationality='$nationality',
                house_number='$house_number', street='$street', province='$province', municipality='$municipality',
                zip_code='$zip_code', address='$address', contact_number='$contact_number',
                email_address='$email_address', pwd='$pwd', pwd_id='$pwd_id', disability_type='$disability_type',
                senior='$senior', senior_id='$senior_id', solo_parent='$solo_parent',
                is_head_of_family=$is_head_of_family, head_of_family_id=$head_of_family_id,
                relationship_to_head='$relationship_to_head',
                emergency_name='$emergency_name', emergency_relationship='$emergency_relationship',
                emergency_contact='$emergency_contact', registered=$registered,
                profile_picture='$profile_picture', lat=$lat_sql, lng=$lng_sql,
                is_voter=$is_voter, year_of_residency='$year_of_residency', employment_status='$employment_status'
                WHERE id=$id";
        } else {
            $sql = "INSERT INTO residents (
                first_name, last_name, middle_name, suffix, full_name, civil_status, sex, date_of_birth, age,
                place_of_birth, religion, nationality, house_number, street, province, municipality, zip_code,
                address, contact_number, email_address, pwd, pwd_id, disability_type, senior, senior_id,
                solo_parent, is_head_of_family, head_of_family_id, relationship_to_head,
                emergency_name, emergency_relationship, emergency_contact, registered,
                profile_picture, lat, lng, is_voter, year_of_residency, employment_status
            ) VALUES (
                '$first_name', '$last_name', '$middle_name', '$suffix', '$full_name',
                '$civil_status', '$sex', '$date_of_birth', $age, '$place_of_birth',
                '$religion', '$nationality', '$house_number', '$street', '$province',
                '$municipality', '$zip_code', '$address', '$contact_number',
                '$email_address', '$pwd', '$pwd_id', '$disability_type',
                '$senior', '$senior_id', '$solo_parent', $is_head_of_family,
                $head_of_family_id, '$relationship_to_head',
                '$emergency_name', '$emergency_relationship', '$emergency_contact',
                $registered, '$profile_picture', $lat_sql, $lng_sql, $is_voter,
                '$year_of_residency', '$employment_status'
            )";
        }

        if ($conn->query($sql) === TRUE) {
            $new_id = $is_update ? $id : $conn->insert_id;
            echo json_encode([
                'success' => true,
                'message' => $is_update ? 'Resident updated successfully' : 'Resident added successfully',
                'id' => $new_id
            ]);
        } else {
            echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
        }
        break;

    /* --------------------------------------------------------------
       6. ARCHIVE RESIDENT
       -------------------------------------------------------------- */
    case 'archive_resident':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $reason = $conn->real_escape_string($_POST['reason'] ?? '');
        if ($id <= 0) {
            echo json_encode(['success'=>false,'message'=>'Invalid ID']);
            exit;
        }
        if (!$reason) {
            echo json_encode(['success'=>false,'message'=>'Reason is required']);
            exit;
        }
        $sql = "UPDATE residents SET archived = 1, archive_reason = '$reason' WHERE id = $id";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success'=>true,'message'=>'Resident archived successfully']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Error: '.$conn->error]);
        }
        break;

    /* --------------------------------------------------------------
       7. GET FAMILY MEMBERS
       -------------------------------------------------------------- */
    case 'get_family_members':
        $head_id = isset($_GET['head_id']) ? intval($_GET['head_id']) : 0;
        if ($head_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid head ID']);
            exit;
        }
        $head_sql = "SELECT id, full_name,
                        (YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5)) AS age,
                        sex
                 FROM residents
                 WHERE id = $head_id AND is_head_of_family = 1 AND archived = 0";
        $head_res = $conn->query($head_sql);
        if (!$head_res || $head_res->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Head not found']);
            exit;
        }
        $head = $head_res->fetch_assoc();

        $member_sql = "SELECT id, full_name, relationship_to_head,
                              (YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5)) AS age,
                              sex
                       FROM residents
                       WHERE head_of_family_id = $head_id AND archived = 0
                       ORDER BY
                           FIELD(relationship_to_head, 'Wife', 'Husband', 'Son', 'Daughter', 'Father', 'Mother') DESC,
                           full_name ASC";
        $member_res = $conn->query($member_sql);
        $members = [];
        while ($m = $member_res->fetch_assoc()) {
            $members[] = $m;
        }
        echo json_encode([
            'success' => true,
            'head_full_name' => $head['full_name'],
            'head_age' => $head['age'],
            'head_sex' => $head['sex'],
            'members' => $members
        ]);
        break;

    /* --------------------------------------------------------------
       8. GET ARCHIVED RESIDENTS
       -------------------------------------------------------------- */
    case 'get_archived_residents':
        $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
        $sex = isset($_GET['sex']) ? $conn->real_escape_string($_GET['sex']) : '';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;

        $where = "WHERE archived = 1";
        if ($search) {
            $where .= " AND (full_name LIKE '%$search%' OR address LIKE '%$search%' OR contact_number LIKE '%$search%' OR archive_reason LIKE '%$search%')";
        }
        if ($sex) {
            $where .= " AND sex = '$sex'";
        }

        $sql = "SELECT *,
                       (YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5)) AS age
                FROM residents $where
                ORDER BY updated_at DESC
                LIMIT $offset, $limit";
        $result = $conn->query($sql);
        $residents = [];
        while ($row = $result->fetch_assoc()) {
            $residents[] = $row;
        }

        $count_sql = "SELECT COUNT(*) AS total FROM residents $where";
        $total = $conn->query($count_sql)->fetch_assoc()['total'];

        echo json_encode(['residents' => $residents, 'total' => $total]);
        break;

    /* --------------------------------------------------------------
       9. RESTORE RESIDENT
       -------------------------------------------------------------- */
    case 'restore_resident':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }
        $sql = "UPDATE residents SET archived = 0, archive_reason = NULL WHERE id = $id AND archived = 1";
        if ($conn->query($sql) === TRUE && $conn->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Resident restored successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to restore or already active']);
        }
        break;
    /* --------------------------------------------------------------
       10. CHECK THE ADDRESS IF ALREADY EXISTS
       -------------------------------------------------------------- */
       case 'check_mapping':
        $house_number = $conn->real_escape_string($_POST['house_number'] ?? '');
        $street = $conn->real_escape_string($_POST['street'] ?? '');

        $sql = "SELECT lat, lng FROM residents 
                WHERE house_number = '$house_number' AND street = '$street' AND archived = 0";
        $result = $conn->query($sql);
        $datas = [];
        while ($row = $result->fetch_assoc()) {
            $datas[] = $row;
        }
        if (count($datas) > 0) {
            $exists = true;
        } else {
            $exists = false;
        }
        echo json_encode(['success' => true, 'exists' => $exists, 'data' => $datas]);
        break;
      case 'get_resident_list':
        $type = $conn->real_escape_string($_POST['type'] ?? '');

        $where = 'WHERE archived = 0';
        if ($type === 'Male'){
            $where .= " AND sex= 'Male'";
        }
        if ($type === "Female"){
            $where .= " AND sex= 'Female'";
        }
        if ($type === "Voters"){
            $where .= " AND is_voter= '1'";
        }
        if ($type === "Non-Voters"){
            $where .= " AND is_voter= '0'";
        }
        if ($type === "Senior"){
            $where .= " AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) >= 60";
        }
        if ($type === "Adult"){
            $where .= " AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) BETWEEN 20 AND 59";
        }
        if ($type === "PWDMale"){
            $where .= " AND pwd= 'Yes' and sex= 'Male'";
        }
        if ($type === "PWDFemale"){
            $where .= " AND pwd= 'Yes' and sex= 'Female'";
        }
        if ($type === "SeniorMale"){
            $where .= " AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) >= 60 AND sex= 'Male'";
        }
        if ($type === "SeniorFemale"){
            $where .= " AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) >= 60 AND sex= 'Female'";
        }
        $sql = "SELECT id, full_name, sex, civil_status, address, contact_number FROM residents $where 
                ORDER BY id DESC";
        
        $result = $conn->query($sql);
        $residents = [];
        while ($row = $result->fetch_assoc()) {
            $residents[] = $row;
        }

        $count_sql = "SELECT COUNT(*) AS total FROM residents $where";
        $total = $conn->query($count_sql)->fetch_assoc()['total'];

        echo json_encode(['residents' => $residents, 'total' => $total]);
        break;
    default:
        echo json_encode(['success'=>false,'message'=>'Invalid action']);
        break;
}

$conn->close();
?>