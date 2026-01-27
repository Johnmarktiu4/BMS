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
           ur.full_name,
           b.created_at, 'blotters', b.id,
           CONCAT('Case ID: ', b.case_id, ' | ', b.nature_of_complaint)
    FROM blotters b
    LEFT JOIN residents res ON JSON_CONTAINS(b.complainant_ids, CONCAT('\"', res.id, '\"'))
    LEFT JOIN officials ur ON b.barangay_incharge_id = ur.id

    UNION ALL
    -- Incidents
    SELECT 'Incident Reported',
           ur.full_name,
           i.created_at, 'incidents', i.id,
           CONCAT('Case ID: ', i.case_id, ' | Nature: ', LEFT(i.nature_of_incident, 60), '...')
    FROM incidents i
    LEFT JOIN residents r ON i.reported_by_resident_id = r.id
    LEFT JOIN officials ur ON i.barangay_official_id = ur.id

    UNION ALL
    -- Complaints
    SELECT 'Complaint Filed',
           ur.full_name,
           c.created_at, 'complaints', c.id,
           CONCAT('Case ID: ', c.case_id)
    FROM complaints c
    LEFT JOIN residents res ON c.reported_by_resident_id = res.id
    LEFT JOIN officials ur ON c.barangay_official_id = ur.id

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
           COALESCE(it.transacted_by, 'Admin'),
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

// Build filter option lists
$actionOptions = [];
$userOptions = [];
foreach ($logs as $l) {
    if (!empty($l['action_type'])) $actionOptions[$l['action_type']] = true;
    $uname = $l['user_name'] ?? 'System';
    if (!empty($uname)) $userOptions[$uname] = true;
}
$actionOptions = array_keys($actionOptions);
sort($actionOptions, SORT_NATURAL | SORT_FLAG_CASE);
$userOptions = array_keys($userOptions);
sort($userOptions, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>System Activity Logs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Assume Bootstrap & Font Awesome are already included globally -->
</head>
<body>
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
          <h2 class="mb-1"><i class="fas fa-history text-primary me-2"></i> System Activity Logs</h2>
          <p class="text-muted mb-0">Complete audit trail of all actions in the barangay system</p>
      </div>
      <span id="activityBadge" class="badge bg-primary fs-6"><?= count($logs) ?> recent activities</span>
  </div>

  <div class="card border-0 shadow">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-list-alt"></i> Recent Activities</h5>
      <small>Last 500 actions</small>
    </div>

    <div class="card-body">

      <!-- ===== Filters Toolbar ===== -->
      <div class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
          <label for="filterDateFrom" class="form-label mb-1">Date from</label>
          <input type="date" id="filterDateFrom" class="form-control">
        </div>
        <div class="col-md-3">
          <label for="filterDateTo" class="form-label mb-1">Date to</label>
          <input type="date" id="filterDateTo" class="form-control">
        </div>

        <div class="col-md-3">
          <label for="filterAction" class="form-label mb-1">Action</label>
          <select id="filterAction" class="form-select">
            <option value="">All</option>
            <?php foreach ($actionOptions as $opt): ?>
              <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label for="filterUser" class="form-label mb-1">Performed By</label>
          <input list="usersList" type="text" id="filterUser" class="form-control" placeholder="e.g., Admin / Juan Dela Cruz">
          <datalist id="usersList">
            <?php foreach ($userOptions as $u): ?>
              <option value="<?= htmlspecialchars($u) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>

        <div class="col-md-6">
          <label for="filterSearch" class="form-label mb-1">Keyword (Description)</label>
          <input type="text" id="filterSearch" class="form-control" placeholder="Search description...">
        </div>

        <div class="col-md-3">
          <label for="filterRef" class="form-label mb-1">Ref #</label>
          <input type="text" id="filterRef" class="form-control" placeholder="e.g., 10234">
        </div>

        <div class="col-md-3 d-grid">
          <button id="filterReset" class="btn btn-outline-secondary">Reset Filters</button>
        </div>
      </div>
      <!-- ===== End Filters Toolbar ===== -->

      <div class="p-0">
        <?php if (empty($logs)): ?>
          <div class="text-center py-5">
            <p class="text-muted">No activity logs found.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table id="logsTable" class="table table-hover mb-0 align-middle">
              <thead class="table-secondary">
                <tr>
                  <th width="180">Date &amp; Time</th>
                  <th width="160">Action</th>
                  <th width="180">Performed By</th>
                  <th>Description</th>
                  <th width="100">Ref #</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($logs as $log): 
                $iso = date('c', strtotime($log['log_date']));
                $action = strtolower($log['action_type']);
                $user = strtolower($log['user_name'] ?? 'system');
                $desc = strtolower($log['details'] ?? '');
                $ref  = (string)$log['ref_id'];
              ?>
                <tr
                  data-iso="<?= htmlspecialchars($iso, ENT_QUOTES) ?>"
                  data-action="<?= htmlspecialchars($action, ENT_QUOTES) ?>"
                  data-user="<?= htmlspecialchars($user, ENT_QUOTES) ?>"
                  data-desc="<?= htmlspecialchars($desc, ENT_QUOTES) ?>"
                  data-ref="<?= htmlspecialchars($ref, ENT_QUOTES) ?>"
                >
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
    </div> <!-- /card-body -->
  </div>   <!-- /card -->
</div>     <!-- /container -->

<?php
mysqli_free_result($result);
closeDBConnection($conn);
?>

<!-- ===== Filtering Script (vanilla JS) ===== -->
<script>
(function () {
  const $ = (s) => document.querySelector(s);
  const rows = Array.from(document.querySelectorAll('#logsTable tbody tr'));

  const inpDateFrom = $('#filterDateFrom');
  const inpDateTo   = $('#filterDateTo');
  const selAction   = $('#filterAction');
  const inpUser     = $('#filterUser');
  const inpSearch   = $('#filterSearch');
  const inpRef      = $('#filterRef');
  const btnReset    = $('#filterReset');
  const badge       = $('#activityBadge');

  if (!rows.length) return; // nothing to do

  // Parse ISO date from data attribute
  function getRowDate(row) {
    const iso = row.dataset.iso;
    if (!iso) return null;
    const d = new Date(iso);
    return isNaN(d.getTime()) ? null : d;
  }

  function debounce(fn, delay = 200) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
  }

  function normalize(v) {
    return (v || '').toString().trim().toLowerCase();
  }

  function inDateRange(rowDate) {
    if (!rowDate) return true; // unparseable => do not exclude
    const from = inpDateFrom.value ? new Date(inpDateFrom.value + 'T00:00:00') : null;
    const to   = inpDateTo.value   ? new Date(inpDateTo.value   + 'T23:59:59') : null;
    if (from && rowDate < from) return false;
    if (to && rowDate > to) return false;
    return true;
  }

  function applyFilters() {
    const wantedAction = normalize(selAction.value);
    const wantedUser   = normalize(inpUser.value);
    const kw           = normalize(inpSearch.value);
    const refQ         = normalize(inpRef.value).replace(/^#/, '');

    let visible = 0;

    rows.forEach(row => {
      const rowDate  = getRowDate(row);
      const rowAction= row.dataset.action || '';
      const rowUser  = row.dataset.user || '';
      const rowDesc  = row.dataset.desc || '';
      const rowRef   = (row.dataset.ref || '').toLowerCase();

      const show =
        inDateRange(rowDate) &&
        (!wantedAction || rowAction.includes(wantedAction)) &&
        (!wantedUser   || rowUser.includes(wantedUser))     &&
        (!kw           || rowDesc.includes(kw))             &&
        (!refQ         || rowRef.includes(refQ));

      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    toggleNoResults(visible === 0);
    updateCount(visible, rows.length);
  }

  // Optional: No results row
  let noRow;
  function toggleNoResults(show) {
    const tbody = document.querySelector('#logsTable tbody');
    if (show) {
      if (!noRow) {
        noRow = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 5;
        td.className = 'text-center text-muted py-4';
        td.textContent = 'No results match your filters.';
        noRow.appendChild(td);
      }
      tbody.appendChild(noRow);
    } else if (noRow && noRow.parentNode) {
      noRow.parentNode.removeChild(noRow);
    }
  }

  function updateCount(visible, total) {
    if (badge) {
      badge.textContent = `${visible} of ${total} activities`;
    }
  }

  // Bind events
  inpDateFrom.addEventListener('change', applyFilters);
  inpDateTo.addEventListener('change', applyFilters);
  selAction.addEventListener('change', applyFilters);
  inpUser.addEventListener('input', debounce(applyFilters, 200));
  inpSearch.addEventListener('input', debounce(applyFilters, 200));
  inpRef.addEventListener('input', debounce(applyFilters, 200));
  btnReset.addEventListener('click', () => {
    inpDateFrom.value = '';
    inpDateTo.value = '';
    selAction.value = '';
    inpUser.value = '';
    inpSearch.value = '';
    inpRef.value = '';
    applyFilters();
  });

  // Initial run
  applyFilters();
})();
</script>
<!-- ===== End Filtering Script ===== -->
</body>
</html>
