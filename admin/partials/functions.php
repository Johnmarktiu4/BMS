<?php
require_once 'db_conn.php';

function getAllResidents($conn, $search = '', $sex = '', $status = '', $limit = 10, $offset = 0) {
    $query = "SELECT * FROM residents WHERE is_archived = 0";
    $params = [];
    $types = '';

    if ($search) {
        $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR CONCAT(house_number, ' ', street) LIKE ? OR contact_number LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $types .= 'ssss';
    }

    if ($sex) {
        $query .= " AND sex = ?";
        $params[] = $sex;
        $types .= 's';
    }

    if ($status) {
        $query .= " AND registered = ?";
        $params[] = $status;
        $types .= 's';
    }

    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($query);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

function getResidentById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM residents WHERE id = ? AND is_archived = 0");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function addResident($conn, $data) {
    $stmt = $conn->prepare("
        INSERT INTO residents (
            profile_picture, first_name, last_name, middle_name, suffix, civil_status, sex,
            date_of_birth, place_of_birth, religion, nationality, house_number, street,
            province, municipality, zip_code, contact_number, email_address, pwd_senior,
            pwd_senior_id, solo_parent, head_of_family, selected_head_of_family,
            emergency_name, emergency_relationship, emergency_contact, registered
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssssssssssssssssssiissss",
        $data['profile_picture'], $data['first_name'], $data['last_name'], $data['middle_name'],
        $data['suffix'], $data['civil_status'], $data['sex'], $data['date_of_birth'],
        $data['place_of_birth'], $data['religion'], $data['nationality'], $data['house_number'],
        $data['street'], $data['province'], $data['municipality'], $data['zip_code'],
        $data['contact_number'], $data['email_address'], $data['pwd_senior'],
        $data['pwd_senior_id'], $data['solo_parent'], $data['head_of_family'],
        $data['selected_head_of_family'], $data['emergency_name'], $data['emergency_relationship'],
        $data['emergency_contact'], $data['registered']
    );

    return $stmt->execute();
}

function updateResident($conn, $id, $data) {
    $stmt = $conn->prepare("
        UPDATE residents SET
            profile_picture = ?, first_name = ?, last_name = ?, middle_name = ?, suffix = ?,
            civil_status = ?, sex = ?, date_of_birth = ?, place_of_birth = ?, religion = ?,
            nationality = ?, house_number = ?, street = ?, province = ?, municipality = ?,
            zip_code = ?, contact_number = ?, email_address = ?, pwd_senior = ?,
            pwd_senior_id = ?, solo_parent = ?, head_of_family = ?, selected_head_of_family = ?,
            emergency_name = ?, emergency_relationship = ?, emergency_contact = ?, registered = ?
        WHERE id = ? AND is_archived = 0
    ");

    $stmt->bind_param(
        "sssssssssssssssssssssiisssi",
        $data['profile_picture'], $data['first_name'], $data['last_name'], $data['middle_name'],
        $data['suffix'], $data['civil_status'], $data['sex'], $data['date_of_birth'],
        $data['place_of_birth'], $data['religion'], $data['nationality'], $data['house_number'],
        $data['street'], $data['province'], $data['municipality'], $data['zip_code'],
        $data['contact_number'], $data['email_address'], $data['pwd_senior'],
        $data['pwd_senior_id'], $data['solo_parent'], $data['head_of_family'],
        $data['selected_head_of_family'], $data['emergency_name'], $data['emergency_relationship'],
        $data['emergency_contact'], $data['registered'], $id
    );

    return $stmt->execute();
}

function archiveResident($conn, $id) {
    $stmt = $conn->prepare("UPDATE residents SET is_archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function getHeadOfFamilyOptions($conn) {
    $stmt = $conn->prepare("SELECT id, CONCAT(first_name, ' ', last_name) AS full_name FROM residents WHERE head_of_family = 1 AND is_archived = 0");
    $stmt->execute();
    return $stmt->get_result();
}
?>