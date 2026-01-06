<?php
if (!isset($_SESSION['user_type'])) {
    header("Location: ?page=dashboard");
    exit();
}
require_once 'partials/db_conn.php';
$conn = getDBConnection();

// Comprehensive activity log pulling from ALL your tables
$logsQuery = "
    -- Residents
    SELECT 'Resident Created' AS action_type, 
           CONCAT('Admin') AS user_name,
           r.created_at AS log_date,
           'residents' AS table_name,
           r.id AS ref_id,
           CONCAT('New resident: ', r.full_name) AS details
    FROM residents r
    WHERE r.archived = 0

    UNION ALL
    SELECT 'Resident Updated', 'Admin', r.updated_at, 'residents', r.id, 
           CONCAT('Updated: ', r.full_name)
    FROM residents r WHERE r.updated_at > r.created_at AND r.archived = 0

    UNION ALL
    -- Officials
    SELECT 'Official Added', 'Admin', o.created_at, 'officials', o.id,
           CONCAT('New official: ', o.full_name, ' (', o.position, ')')
    FROM officials o WHERE o.archived = 0

    UNION ALL
    SELECT 'Official Updated', 'Admin', o.updated_at, 'officials', o.id,
           CONCAT('Updated official: ', o.full_name)
    FROM officials o WHERE o.updated_at > o.created_at AND o.archived = 0

    UNION ALL
    -- Blotter Cases
    SELECT 'Blotter Filed', 
           COALESCE(CONCAT(res.first_name,' ',res.last_name), 'Unknown'), 
           b.created_at, 'blotters', b.id,
           CONCAT('Case ID: ', b.case_id, ' | ', b.nature_of_complaint)
    FROM blotters b
    LEFT JOIN residents res ON JSON_CONTAINS(b.complainant_ids, CONCAT('\"', res.id, '\"'))

    UNION ALL
    -- Incidents
    SELECT 'Incident Reported',
           COALESCE(r.full_name, i.reported_by_name, 'Anonymous'),
           i.created_at, 'incidents', i.id,
           CONCAT('Case ID: ', i.case_id, ' | Nature: ', LEFT(i.nature_of_incident, 60), '...')
    FROM incidents i
    LEFT JOIN residents r ON i.reported_by_resident_id = r.id

    UNION ALL
    -- Complaints
    SELECT 'Complaint Filed',
           COALESCE(res.full_name, c.reported_by_name, 'Walk-in'),
           c.created_at, 'complaints', c.id,
           CONCAT('Case ID: ', c.case_id)
    FROM complaints c
    LEFT JOIN residents res ON c.reported_by_resident_id = res.id

    UNION ALL
    -- Inventory Added
    SELECT 'Inventory Item Added', 'Admin', inv.created_at, 'inventory', inv.id,
           CONCAT('New item: ', inv.item_name, ' (Stock: ', inv.current_stock, ')')
    FROM inventory inv WHERE inv.archived = 0

    UNION ALL
    -- Inventory Transactions
    SELECT 
           CASE 
               WHEN it.action_type = 'Borrow' THEN 'Item Borrowed'
               WHEN it.action_type = 'Return' THEN 'Item Returned'
               WHEN it.action_type = 'Add' THEN 'Stock Added'
               WHEN it.action_type = 'Broken' THEN 'Item Damaged/Lost'
               WHEN it.action_type = 'Replace' THEN 'Item Replaced'
           END AS action_type,
           COALESCE(it.transacted_by, 'System'),
           it.created_at,
           'inventory_transactions',
           it.id,
           CONCAT(it.action_type, ' ', it.quantity, ' × ', inv.item_name,
                  IF(it.borrower_id IS NOT NULL, CONCAT(' by ', res.full_name), ''))
    FROM inventory_transactions it
    JOIN inventory inv ON it.inventory_id = inv.id
    LEFT JOIN residents res ON it.borrower_id = res.id

    UNION ALL
    -- Case Reports
    SELECT 'Case Report Filed', cr.complainant, cr.created_at, 'case_reports', cr.id,
           CONCAT('Case #: ', cr.case_number, ' | ', cr.case_type)
    FROM case_reports cr WHERE cr.archived = 0

    UNION ALL
    -- VAWC Reports
    SELECT 'VAWC Report Filed', v.victim_name, v.created_at, 'vawc_reports', v.id,
           CONCAT('Victim: ', v.victim_name, ' vs ', v.abuser_name)
    FROM vawc_reports v

    ORDER BY log_date DESC
    LIMIT 500
";

$result = mysqli_query($conn, $logsQuery);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
$logs = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-history text-primary me-2"></i> System Activity Logs</h2>
            <p class="text-muted mb-0">Complete audit trail of all actions in the barangay system</p>
        </div>
        <span class="badge bg-primary fs-6"><?= count($logs) ?> recent activities</span>
    </div>

    <div class="card border-0 shadow">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list-alt"></i> Recent Activities</h5>
            <small>Last 500 actions</small>
        </div>
        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5">
                    <p class="text-muted">No activity logs found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th width="180">Date & Time</th>
                                <th width="160">Action</th>
                                <th width="180">Performed By</th>
                                <th>Description</th>
                                <th width="100">Ref #</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="text-muted small">
                                        <?= date('M j, Y', strtotime($log['log_date'])) ?><br>
                                        <span class="text-primary"><?= date('g:i A', strtotime($log['log_date'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            <?= strpos($log['action_type'], 'Borrow') !== false ? 'bg-warning' :
                                               (strpos($log['action_type'], 'Return') !== false || strpos($log['action_type'], 'Replace') !== false ? 'bg-success' :
                                               (strpos($log['action_type'], 'Damaged') !== false || strpos($log['action_type'], 'Broken') !== false ? 'bg-danger' :
                                               (strpos($log['action_type'], 'Added') !== false || strpos($log['action_type'], 'Created') !== false ? 'bg-info' : 'bg-secondary'))) ?>">
                                            <?= htmlspecialchars($log['action_type']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-500"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                                    <td class="small"><?= htmlspecialchars($log['details'] ?? '—') ?></td>
                                    <td class="text-muted">#<?= $log['ref_id'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
mysqli_free_result($result);
closeDBConnection($conn);
?>