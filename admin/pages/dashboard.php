<?php
include 'partials/db_conn.php';
$conn = getDBConnection();
// === ALL YOUR EXISTING COUNTS (UNCHANGED) ===
$total_residents_query = $conn->query("SELECT COUNT(*) FROM residents WHERE archived = 0");
$total_residents = $total_residents_query ? $total_residents_query->fetch_row()[0] : 0;
$male_query = $conn->query("SELECT COUNT(*) FROM residents WHERE sex = 'Male' AND archived = 0");
$male = $male_query ? $male_query->fetch_row()[0] : 0;
$female_query = $conn->query("SELECT COUNT(*) FROM residents WHERE sex = 'Female' AND archived = 0");
$female = $female_query ? $female_query->fetch_row()[0] : 0;
$pwd_query = $conn->query("SELECT COUNT(*) FROM residents WHERE pwd = 'Yes' AND archived = 0");
$pwd = $pwd_query ? $pwd_query->fetch_row()[0] : 0;
$senior_query = $conn->query("SELECT COUNT(*) FROM residents WHERE senior = 'Yes' AND archived = 0");
$senior = $senior_query ? $senior_query->fetch_row()[0] : 0;

$infant_query = $conn->query("SELECT COUNT(*) FROM residents WHERE archived = 0  AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) BETWEEN 0 AND 1");
$infant = $infant_query ? $infant_query->fetch_row()[0] : 0;
$toddler_query = $conn->query("SELECT COUNT(*) FROM residents WHERE archived = 0  AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) BETWEEN 1 AND 3");
$toddler = $toddler_query ? $toddler_query->fetch_row()[0] : 0;
$minor_query = $conn->query("SELECT COUNT(*) FROM residents WHERE archived = 0  AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) BETWEEN 4 AND 12");
$minor = $minor_query ? $minor_query->fetch_row()[0] : 0;
$teen_query = $conn->query("SELECT COUNT(*) FROM residents WHERE archived = 0  AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) BETWEEN 13 AND 19");
$teen = $teen_query ? $teen_query->fetch_row()[0] : 0;
$adult_query = $conn->query("SELECT COUNT(*) FROM residents WHERE archived = 0  AND ((YEAR(CURDATE()) - YEAR(date_of_birth)) - (RIGHT(CURDATE(), 5) < RIGHT(date_of_birth, 5))) BETWEEN 20 AND 59");
$adult = $adult_query ? $adult_query->fetch_row()[0] : 0;

// === NEW: VOTERS COUNT ADDED HERE ===
$voters_query = $conn->query("SELECT COUNT(*) FROM residents WHERE is_voter = 1 AND archived = 0");
$voters = $voters_query ? $voters_query->fetch_row()[0] : 0;

$total_households_query = $conn->query("SELECT COUNT(*) FROM residents WHERE is_head_of_family = 1 AND archived = 0");
$total_households = $total_households_query ? $total_households_query->fetch_row()[0] : 0;
$households_with_pwd_query = $conn->query("
    SELECT COUNT(DISTINCT r1.id)
    FROM residents r1
    WHERE pwd = 'Yes' AND archived = 0
");
$households_with_pwd = $households_with_pwd_query ? $households_with_pwd_query->fetch_row()[0] : 0;
$male_with_pwd_query = $conn->query("
    SELECT COUNT(DISTINCT r1.id)
    FROM residents r1
    WHERE pwd = 'Yes' AND archived = 0 AND sex = 'Male'
");
$male_with_pwd = $male_with_pwd_query ? $male_with_pwd_query->fetch_row()[0] : 0;
$female_with_pwd_query = $conn->query("
    SELECT COUNT(DISTINCT r1.id)
    FROM residents r1
    WHERE pwd = 'Yes' AND archived = 0 AND sex = 'Female'
");
$female_with_pwd = $female_with_pwd_query ? $female_with_pwd_query->fetch_row()[0] : 0;
$households_with_senior_query = $conn->query("
    SELECT COUNT(DISTINCT r1.id)
    FROM residents r1
    WHERE senior = 'Yes' AND archived = 0
");
$male_with_senior_query = $conn->query("
    SELECT COUNT(DISTINCT r1.id)
    FROM residents r1
    WHERE pwd = 'Yes' AND archived = 0 AND sex = 'Male'
");
$male_with_senior = $male_with_senior_query ? $male_with_senior_query->fetch_row()[0] : 0;
$female_with_senior_query = $conn->query("
    SELECT COUNT(DISTINCT r1.id)
    FROM residents r1
    WHERE pwd = 'Yes' AND archived = 0 AND sex = 'Female'
");
$female_with_senior = $female_with_senior_query ? $female_with_senior_query->fetch_row()[0] : 0;
$households_with_senior = $households_with_senior_query ? $households_with_senior_query->fetch_row()[0] : 0;
$count_blotters = $conn->query("SELECT COUNT(*) FROM blotters")->fetch_row()[0];
$count_complaints = $conn->query("SELECT COUNT(*) FROM complaints")->fetch_row()[0];
$count_incidents = $conn->query("SELECT COUNT(*) FROM incidents")->fetch_row()[0];
$notifications = [];
$due_items_query = $conn->query("
    SELECT it.id, i.item_name, r.full_name AS borrower_name, it.return_date, DATEDIFF(it.return_date, CURDATE()) AS days_left
    FROM inventory_transactions it
    JOIN inventory i ON it.inventory_id = i.id
    LEFT JOIN residents r ON it.borrower_id = r.id
    WHERE it.action_type = 'Borrow'
      AND it.returned_date IS NULL
      AND it.return_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
      AND it.return_date >= CURDATE()
    ORDER BY it.return_date ASC
");
$employed = $conn->query("SELECT COUNT(*) FROM residents WHERE employment_status = 'Employed' AND archived = 0")->fetch_row()[0];
$due_count = $due_items_query->num_rows;
if ($due_count > 0) {
    while ($item = $due_items_query->fetch_assoc()) {
        $days = $item['days_left'];
        $status = $days == 0 ? "DUE TODAY" : ($days == 1 ? "DUE TOMORROW" : "DUE IN $days DAYS");
        $borrower_name = $item['borrower_name'] ? $item['borrower_name'] : 'Unknown';
        $notifications[] = [
            'id' => $item['id'],
            'message' => "<strong>{$item['item_name']}</strong> borrowed by <em>{$borrower_name}</em> — $status",
            'date' => $item['return_date']
        ];
    }
}
// === GEOCODING FUNCTION ===
function geocode($address) {
    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ph&q=' . urlencode($address);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'BarangaySystem/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        return [(float)$data[0]['lat'], (float)$data[0]['lon']];
    }
    return null;
}
// === FAMILY MAP DATA – FULL MEMBER DETAILS ===
$family_locations = [];
$plotted_count = 0;
$heads_query = "
    SELECT
        id,
        full_name,
        house_number,
        street,
        lat,
        lng
    FROM residents
    WHERE is_head_of_family = 1
      AND archived = 0
      AND (house_number != '' OR lat IS NOT NULL)
    ORDER BY full_name
";
$heads_result = $conn->query($heads_query);
if ($heads_result && $heads_result->num_rows > 0) {
    while ($head = $heads_result->fetch_assoc()) {
        $lat = null;
        $lng = null;
        if ($head['lat'] && $head['lng']) {
            $lat = (float)$head['lat'];
            $lng = (float)$head['lng'];
        } else {
            $search_address = trim($head['house_number'] . ' ' . $head['street'] . ', Dalahican, Cavite City, Cavite, Philippines');
            $coords = geocode($search_address);
            if ($coords) {
                $lat = $coords[0];
                $lng = $coords[1];
            }
        }
        if ($lat && $lng) {
            $members_query = "
                SELECT
                    full_name,
                    sex,
                    age,
                    civil_status,
                    pwd,
                    senior,
                    relationship_to_head,
                    CASE WHEN id = ? THEN 1 ELSE 0 END AS is_head
                FROM residents
                WHERE (head_of_family_id = ? OR id = ?) AND archived = 0
                ORDER BY is_head DESC, age DESC
            ";
            $stmt = $conn->prepare($members_query);
            $stmt->bind_param("iii", $head['id'], $head['id'], $head['id']);
            $stmt->execute();
            $members_result = $stmt->get_result();
            $members = [];
            while ($m = $members_result->fetch_assoc()) {
                $members[] = $m;
            }
            $stmt->close();
            $family_locations[] = [
                'lat' => $lat,
                'lng' => $lng,
                'address' => trim($head['house_number'] . ' ' . $head['street']),
                'head_name' => $head['full_name'],
                'members' => $members,
                'total' => count($members)
            ];
            $plotted_count++;
        }
    }
}
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Dashboard</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; margin: 0; padding: 0; }
        #dashboard-content { padding: 2rem; }
        .card { border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(16,185,129,0.15); transition: all 0.3s; }
        .card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(16,185,129,0.25); }
        .small-resident-card, .small-household-card, .chart-card { min-height: 300px; display: flex; flex-direction: column; justify-content: center; }
        .small-resident-card { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 1.8rem; text-align: center; }
        .small-resident-card i.main-icon { font-size: 3.5rem; opacity: 0.9; }
        .small-resident-card .big-number { font-size: 3.8rem; font-weight: 900; margin: 0.5rem 0; }
        .small-resident-card .title { font-size: 1.2rem; opacity: 0.95; }
        .small-resident-card .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-top: 1rem; }     
        .small-resident-card .stat-grid > .stat-item:last-child:nth-child(odd) {
            grid-column: 1 / -1;       /* span across both columns */
            justify-self: center;      /* center the item horizontally */
            width: -webkit-fill-available;
            /* optional: add max-width if your item can be very wide */
            /* max-width: 100%; */
        }
        .small-resident-card .stat-item { background: rgba(255,255,255,0.2); border-radius: 12px; padding: 0.6rem; }
        .small-resident-card .stat-item i { font-size: 1.4rem; display: block; margin-bottom: 0.3rem; }
        .small-resident-card .stat-item .label { font-size: 0.8rem; opacity: 0.9; }
        .small-resident-card .stat-item .value { font-size: 1.3rem; font-weight: 800; }
        .small-household-card { background: linear-gradient(135deg, #34d399, #10b981); color: white; padding: 1.8rem; text-align: center; }
        .small-household-card i.main-icon { font-size: 3.5rem; opacity: 0.9; }
        .small-household-card .big-number { font-size: 3.8rem; font-weight: 900; margin: 0.5rem 0; }
        .small-household-card .title { font-size: 1.2rem; opacity: 0.95; }
        .small-household-card .info-box { background: rgba(255,255,255,0.2); border-radius: 12px; padding: 0.8rem; margin-top: 1rem; }
        .small-household-card .info-row { display: flex; justify-content: space-between; padding: 0.4rem 0; }
        .small-household-card .info-row i { font-size: 1.4rem; }
        .small-household-card .info-row strong { font-size: 1.3rem; }
        .chart-card { background: white; padding: 2rem; border-radius: 20px; }
        #map { height: 580px; border-radius: 20px; margin-top: 2rem; }
        .map-title { background: linear-gradient(135deg, #059669, #10b981); color: white; padding: 1.2rem; font-size: 1.5rem; font-weight: 700; border-radius: 20px 20px 0 0; text-align: center; }
        .custom-tooltip {
            background: rgba(16,185,129,0.98);
            color: white;
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 0.94rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            max-width: 360px;
            line-height: 1.5;
        }
        .custom-tooltip strong { color: #fff; }
        .custom-tooltip .member-head { background: rgba(255,255,255,0.25); padding: 6px 10px; border-radius: 8px; margin: 6px 0; font-weight: bold; }
        .custom-tooltip .member { padding: 4px 0; font-size: 0.92rem; }
        .custom-tooltip .badge-pwd { background: #dc3545; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; margin-left: 6px; }
        .custom-tooltip .badge-senior { background: #f59e0b; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; margin-left: 6px; }
        @media (max-width: 768px) { #map { height: 450px; } #dashboard-content { padding: 1.5rem; } }
    </style>
</head>
<body>
<div id="dashboard-content">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h2 class="mb-0 text-success">Dashboard</h2>
            <div class="text-success fw-bold"><?php echo $plotted_count; ?> households plotted on map</div>
            <!-- NOTIFICATION BELL -->
            <div class="position-relative">
                <button class="btn btn-outline-success btn-lg rounded-pill shadow-sm d-flex align-items-center gap-2"
                        type="button" id="notificationBtn" data-bs-toggle="dropdown">
                    <i class="bi bi-bell-fill fs-4"></i>
                    <?php if ($due_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;">
                            <?php echo $due_count; ?>
                            <span class="visually-hidden">due items</span>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-0" style="width: 380px; max-height: 80vh; overflow: hidden;">
                    <div class="bg-success text-white px-4 py-3 d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold">Due for Return (<?php echo $due_count; ?>)</h6>
                        <?php if ($due_count > 0): ?>
                            <small class="opacity-90">Items returning soon</small>
                        <?php endif; ?>
                        <h6 class="mb-0 fw-bold">Hearing for today (<?php echo $due_count; ?>)</h6>
                        <?php if ($due_count > 0): ?>
                            <small class="opacity-90"></small>
                        <?php endif; ?>
                    </div>
                    <?php if ($due_count > 0): ?>
                        <div class="list-group list-group-flush" style="max-height: 60vh; overflow-y: auto;">
                            <?php foreach ($notifications as $notif): ?>
                                <a href="http://localhost/bms/admin/layout.php?page=borrowed" class="list-group-item list-group-item-action px-4 py-3 border-0">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="me-3">
                                            <i class="bi bi-box-seam text-warning fs-5"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold text-dark" style="font-size:0.95rem;">
                                                <?php echo $notif['message']; ?>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                <i class="bi bi-calendar-event me-1"></i>
                                                <?php echo date('M j, Y', strtotime($notif['date'])); ?>
                                            </small>
                                        </div>
                                        <?php
                                        $daysLeft = (strtotime($notif['date']) - time()) / 86400;
                                        if ($daysLeft == 0): ?>
                                            <span class="badge bg-danger">Today</span>
                                        <?php elseif ($daysLeft == 1): ?>
                                            <span class="badge bg-warning text-dark">Tomorrow</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo round($daysLeft); ?> days</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="p-3 border-top bg-light">
                            <a href="http://localhost/bms/admin/layout.php?page=borrowed" class="btn btn-success w-100">
                                Go to Inventory
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-check-circle-fill fs-1 text-success mb-3 d-block"></i>
                            <h6>No items due soon</h6>
                            <small>Everything is returned on time!</small>
                            <hr>
                            <i class="bi bi-check-circle-fill fs-1 text-success mb-3 d-block"></i>
                            <h6>No hearing for this day</h6>
                            <small>You have no hearings scheduled for today.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5 row-cols-1 row-cols-md-3">
        <div class="col">
            <div class="card small-resident-card">
                <i class="bi bi-people-fill main-icon"></i>
                <div class="title mt-3">Total Residents</div>
                <div class="big-number"><?php echo number_format($total_residents); ?></div>
                <div class="stat-grid">
                    <div class="stat-item"><a onclick="loadResidentList('Male')" data-bs-toggle="modal" data-bs-target="#residentModal"><i class="bi bi-gender-male"></i><div class="label">Male</div><div class="value"><?php echo number_format($male); ?></div></a></div>
                    <div class="stat-item"><a onclick="loadResidentList('Female')" data-bs-toggle="modal" data-bs-target="#residentModal"><i class="bi bi-gender-female"></i><div class="label">Female</div><div class="value"><?php echo number_format($female); ?></div></a></div>

                    <!-- VOTERS COUNT ADDED HERE, RIGHT NEXT TO MALE -->
                    <div class="stat-item"><a onclick="loadResidentList('Voters')" data-bs-toggle="modal" data-bs-target="#residentModal"><i class="bi bi-check2-circle"></i><div class="label">Registered Voter</div><div class="value"><?php echo number_format($voters); ?></div></a></div>
                    <div class="stat-item"><a onclick="loadResidentList('Non-Voters')" data-bs-toggle="modal" data-bs-target="#residentModal"><i class="bi bi-x-circle"></i><div class="label">Non-Registered</div><div class="value"><?php echo number_format($total_residents - $voters); ?></div></a></div>
                    
                   
                    <div class="stat-item"><a onclick="loadResidentList('Senior')" data-bs-toggle="modal" data-bs-target="#residentModal"><i class="bi bi-person-lines-fill"></i><div class="label">Senior</div><div class="value"><?php echo number_format($senior); ?></div></a></div>
                    <div class="stat-item"><a onclick="loadResidentList('Adult')" data-bs-toggle="modal" data-bs-target="#residentModal"><i class="bi bi-person-lines-fill"></i><div class="label">Adult</div><div class="value"><?php echo number_format($adult); ?></div></a></div>
                    <div class="stat-item"><i class="bi bi-person-lines-fill"></i><div class="label">Teen</div><div class="value"><?php echo number_format($teen); ?></div></div>
                    <div class="stat-item"><i class="bi bi-person-lines-fill"></i><div class="label">Minor</div><div class="value"><?php echo number_format($minor); ?></div></div>
                    <div class="stat-item"><i class="bi bi-person-standing"></i><div class="label">Toddler</div><div class="value"><?php echo number_format($toddler); ?></div></div>
                    <div class="stat-item"><i class="bi bi-person-standing"></i><div class="label">Infant</div><div class="value"><?php echo number_format($infant); ?></div></div>

                    <div class="stat-item"><i class="bi bi-briefcase"></i><div class="label">Employed</div><div class="value"><?php echo number_format($employed); ?></div></div>
                    <div class="stat-item"><i class="bi bi-person-x"></i><div class="label">Unemployed</div><div class="value"><?php echo number_format($total_residents - $employed); ?></div></div>

                    <div class="stat-item"><i class="bi bi-person-wheelchair"></i><div class="label">PWD</div><div class="value"><?php echo number_format($pwd); ?></div></div>
                    <div class="stat-item"><i class="bi bi-person-heart"></i><div class="label">Solo Parent</div><div class="value"><?php echo number_format($pwd); ?></div></div>

                </div>
            </div>
        </div>
        <div class="col">
            <div class="card small-household-card">
                <i class="bi bi-houses-fill main-icon"></i>
                <div class="title mt-3">Total Households</div>
                <div class="big-number"><?php echo number_format($total_households); ?></div>
                <div class="info-box">
                    <div class="info-row"><span><strong>With PWD</strong></span><strong><?php echo number_format($households_with_pwd); ?></strong></div>
                    <div class="info-row"><span>&nbsp;&nbsp;-PWD Male</span><strong><?php echo number_format($male_with_pwd); ?></strong></div>
                    <div class="info-row"><span>&nbsp;&nbsp;-PWD Female</span><strong><?php echo number_format($female_with_pwd); ?></strong></div>
                    <div class="info-row"><span><strong>With Senior</strong></span><strong><?php echo number_format($households_with_senior); ?></strong></div>
                    <div class="info-row"><span>&nbsp;&nbsp;-Senior Male</span><strong><?php echo number_format($male_with_senior); ?></strong></div>
                    <div class="info-row"><span>&nbsp;&nbsp;-Senior Female</span><strong><?php echo number_format($female_with_senior); ?></strong></div>
                </div>
            </div>
            <br>
            <div class="card small-household-card">
                <i class="bi bi-exclamation-triangle-fill main-icon"></i>
                <div class="title mt-3">Unsettled Cases<br>&<br>Unreturned Items</div>
                <div class="info-box">
                    <div class="info-row"><span>Unsettled Cases</span><strong><?php echo number_format($households_with_pwd); ?></strong></div>
                    <div class="info-row"><span>Unreturned Items</span><strong><?php echo number_format($households_with_senior); ?></strong></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card chart-card">
                <h5 class="text-success mb-4">System Overview</h5>
                <canvas id="overviewChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="map-title">
                    HOUSEHOLD MAPPING - DALAHICAN, CAVITE CITY
                    <small class="ms-3 opacity-75">(<?php echo $plotted_count; ?> out of <?php echo $total_households; ?> households shown)</small>
                </div>
                <div class="card-body p-0">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>

        <!-- Resident Modal -->
    <div class="modal fade" id="residentModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg">
                <div class="modal-header bg-success text-white border-0">
                    <h5><i class="fas fa-plus-circle me-2"></i>Resident List</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="residentModalTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Full Name</th>
                                    <th>Sex</th>
                                    <th>CivilStatus</th>
                                    <th>Address</th>
                                    <th>Contact</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div><small class="text-muted" id="paginationInfo"></small></div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}
    function loadResidentList(param) {
        $.ajax({
            url: 'partials/resident_management_api.php',
            type: 'POST',
            data: {
                action: 'get_resident_list',
                type: param
            },
            dataType: 'json',
            success: function(response) {
                if (response.residents) {
                    console.log(response.residents);
                    updateResidentListTable(response.residents);
                }
                else{
                    showAlert('danger', response.message || 'Failed to load resident list.');
                }
            }
        });
    }

    function updateResidentListTable(data) {
        let tbody = '';
        
        if (!data.length) {
            tbody += '<tr><td colspan="6" class="text-center py-5 text-muted">No resident found.</td></tr>';
            return;
        }
        console.log(data);
        data.forEach(item => {
            tbody += `
                <tr>
                    <td><strong>${item.id}</strong></td>
                    <td>${item.full_name || '—'}</td>
                    <td>${item.sex}</td>
                    <td>${item.civil_status}</td>
                    <td>${item.address}</td>
                    <td>${item.contact_number || '—'}</td>
                </tr>
            `;
        });
        $('#residentModalTable tbody').html(tbody);
    }

document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('overviewChart'), {
        type: 'bar',
        data: {
            labels: ['Residents', 'Blotters', 'Complaints', 'Incidents'],
            datasets: [{
                label: 'Count',
                data: [<?php echo $total_residents; ?>, <?php echo $count_blotters; ?>, <?php echo $count_complaints; ?>, <?php echo $count_incidents; ?>],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                borderRadius: 8
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    const map = L.map('map').setView([14.463331, 120.884745], 19);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const locations = <?php echo json_encode($family_locations); ?>;
    const houseIcon = L.divIcon({
        html: `<div style="background:#10b981;color:white;width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;border:5px solid white;box-shadow:0 6px 20px rgba(0,0,0,0.4);">
                <i class="bi bi-house-fill"></i>
               </div>`,
        iconSize: [44, 44],
        className: 'house-icon'
    });

    const markers = [];
    locations.forEach(loc => {
        let memberHTML = '<div style="max-height:280px;overflow-y:auto;margin-top:10px;">';
        loc.members.forEach((m, i) => {
            const isHead = m.is_head == 1;
            const pwdBadge = m.pwd === 'Yes' ? '<span class="badge-pwd">PWD</span>' : '';
            const seniorBadge = m.senior === 'Yes' ? '<span class="badge-senior">Senior</span>' : '';
            const relation = isHead ? '<em style="color:#c3ff8c;">(Head of Family)</em>' : (m.relationship_to_head ? `<em style="color:#a3e4d7;">(${m.relationship_to_head})</em>` : '');
            memberHTML += `
                <div class="${isHead ? 'member-head' : 'member'}" style="padding:8px 10px;border-radius:8px;margin:4px 0;">
                    <strong>${m.full_name}</strong> ${pwdBadge} ${seniorBadge}<br>
                    <small>
                        ${m.sex} ${m.age} years old ${m.civil_status}
                        ${relation}
                    </small>
                </div>`;
        });
        memberHTML += '</div>';
        const content = `
            <div>
                <strong style="font-size:1.15em;">${loc.head_name}'s Household</strong><br>
                <small style="color:#e0f2fe;">${loc.address}</small><br>
                <strong style="color:#fff;">Total Members: ${loc.total}</strong>
                ${memberHTML}
            </div>`;
        const marker = L.marker([loc.lat, loc.lng], { icon: houseIcon }).addTo(map);
        marker.bindTooltip(content, {
            permanent: false,
            direction: 'top',
            offset: [0, -22],
            className: 'custom-tooltip'
        });
        markers.push(marker);
    });

    if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.4));
    }
});
</script>
</body>
</html>