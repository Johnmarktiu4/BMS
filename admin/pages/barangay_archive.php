<?php require_once 'partials/db_conn.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Barangay Officials</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .card { border-radius: 15px; box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
        .table th { background: #2c3e50; color: white; }
        .profile-img { width: 45px; height: 45px; object-fit: cover; border: 2px solid #ddd; }
        .restore-btn { font-size: 0.85rem; }
        @media print {
            .no-print, .btn { display: none !important; }
            body { background: white; }
            .card { box-shadow: none; border: 1px solid #ccc; }
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="fas fa-archive text-muted"></i> Archived Officials</h2>
            <p class="text-muted mb-0">List of officials moved to archive</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button class="btn btn-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-success" onclick="exportArchivedOfficials()">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 no-print">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Name, position, contact...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Position</label>
                    <select class="form-select" id="positionFilter">
                        <option value="">All Positions</option>
                        <option value="Barangay Captain">Barangay Captain</option>
                        <option value="Kagawad">Kagawad</option>
                        <option value="Secretary">Secretary</option>
                        <option value="Treasurer">Treasurer</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-danger w-100" onclick="clearFilters()">Clear Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Archived Table -->
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-box-archive"></i> Archived Officials List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Photo</th>
                            <th>Full Name</th>
                            <th>Position</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Archived On</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody id="archivedOfficialsBody">
                        <!-- Filled by JS -->
                    </tbody>
                </table>
            </div>
            <div class="p-5 text-center text-muted" id="noData" style="display:none;">
                <i class="fas fa-inbox fa-3x mb-3"></i><br>
                No archived officials found.
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
// Global data
let archivedOfficials = [];

// Load archived officials
function loadArchivedOfficials() {
    $.post('partials/archived_officials_api.php', { action: 'fetch' }, res => {
        if (res.success) {
            archivedOfficials = res.data;
            renderTable();
        } else {
            alert('Error: ' + res.message);
        }
    }, 'json');
}

// Render table with filters
function renderTable() {
    const search = $('#searchInput').val().toLowerCase().trim();
    const position = $('#positionFilter').val();
    const status = $('#statusFilter').val();

    let filtered = archivedOfficials.filter(o => {
        const matchesSearch = o.full_name.toLowerCase().includes(search) ||
                              o.position.toLowerCase().includes(search) ||
                              (o.contact && o.contact.includes(search));
        const matchesPosition = !position || o.position === position;
        const matchesStatus = !status || o.status === status;
        return matchesSearch && matchesPosition && matchesStatus;
    });

    const $tbody = $('#archivedOfficialsBody').empty();
    const $noData = $('#noData');

    if (filtered.length === 0) {
        $noData.show();
        return;
    }
    $noData.hide();

    filtered.forEach(o => {
        const photo = o.profile_picture
            ? `<img src="${o.profile_picture}" class="rounded-circle profile-img" alt="Profile">`
            : `<div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center profile-img" style="width:45px;height:45px;">
                 <i class="fas fa-user"></i>
               </div>`;

        const statusBadge = o.status === 'Active'
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-secondary">Inactive</span>';

        const archivedDate = new Date(o.updated_at).toLocaleDateString('en-PH', {
            year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        $tbody.append(`
            <tr>
                <td>${photo}</td>
                <td><strong>${escapeHtml(o.full_name)}</strong></td>
                <td><span class="badge bg-success">${escapeHtml(o.position)}</span></td>
                <td>${o.contact || '—'}</td>
                <td>${statusBadge}</td>
                <td><small class="text-muted">${archivedDate}</small></td>
                <td class="no-print">
                    <button class="btn btn-sm btn-success restore-btn" onclick="restoreOfficial(${o.id})">
                        <i class="fas fa-undo"></i> Restore
                    </button>
                </td>
            </tr>
        `);
    });
}

// Restore official
function restoreOfficial(id) {
    if (!confirm('Restore this official? They will appear in the active list again.')) return;

    $.post('partials/archived_officials_api.php', {
        action: 'restore',
        id: id
    }, res => {
        if (res.success) {
            showToast('success', res.message);
            loadArchivedOfficials();
        } else {
            showToast('danger', res.message);
        }
    }, 'json');
}

// Export to CSV
function exportArchivedOfficials() {
    let csv = "Full Name,Position,Contact,Status,Archived On\n";
    archivedOfficials.forEach(o => {
        const date = new Date(o.updated_at).toLocaleDateString('en-PH');
        csv += `"${o.full_name}","${o.position}","${o.contact || ''}","${o.status}","${date}"\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'archived_officials_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}

// Helpers
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(type, message) {
    const toast = $(`
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" style="top:20px;right:20px;z-index:9999;min-width:300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    $('body').append(toast);
    setTimeout(() => toast.alert('close'), 4000);
}

function clearFilters() {
    $('#searchInput, #positionFilter, #statusFilter').val('');
    renderTable();
}

// Live filters
$('#searchInput, #positionFilter, #statusFilter').on('input change', () => {
    clearTimeout(window.filterTimeout);
    window.filterTimeout = setTimeout(renderTable, 300);
});

// Init
$(document).ready(() => {
    loadArchivedOfficials();
});
</script>
</body>
</html>