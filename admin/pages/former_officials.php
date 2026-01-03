<?php require_once 'partials/db_conn.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Former Barangay Officials</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 15px; box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
        .table th { background: #2c3e50; color: white; }
        .term-badge { font-size: 0.8rem; }
        .profile-img { width: 50px; height: 50px; object-fit: cover; }
        @media print {
            .no-print, .btn { display: none !important; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="fas fa-user-tie text-primary"></i> Former Barangay Officials</h2>
            <p class="text-muted mb-0">List of officials whose term has ended</p>
        </div>
        <div class="d-flex gap-2 no-print">
 
 
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 no-print">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search Name / Position</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="e.g. Juan Dela Cruz, Captain...">
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
                    <label class="form-label">Term Ended Year</label>
                    <select class="form-select" id="yearFilter">
                        <option value="">All Years</option>
                        <!-- Will be populated by JS -->
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">Clear</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Former Officials Table -->
    <div class="card">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-history"></i> Former Officials List</h5>
            <span class="badge bg-light text-dark" id="totalCount">0</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Photo</th>
                            <th>Full Name</th>
                            <th>Position</th>
                            <th>Term</th>
                            <th>Contact</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="formerOfficialsBody">
                        <!-- Filled by JS -->
                    </tbody>
                </table>
            </div>
            <div class="p-4 text-center text-muted" id="noData" style="display:none;">
                <i class="fas fa-info-circle fa-2x mb-3"></i><br>
                No former officials found.
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
let formerOfficials = [];

// Load Former Officials
function loadFormerOfficials() {
    $.post('partials/former_officials_api.php', { action: 'fetch' }, response => {
        if (response.success) {
            formerOfficials = response.data;
            populateYearFilter();
            renderTable();
        } else {
            alert('Error loading former officials: ' + response.message);
        }
    }, 'json');
}

// Render Table
function renderTable() {
    const search = $('#searchInput').val().toLowerCase().trim();
    const position = $('#positionFilter').val();
    const year = $('#yearFilter').val();

    let filtered = formerOfficials;

    if (search) {
        filtered = filtered.filter(o =>
            o.full_name.toLowerCase().includes(search) ||
            o.position.toLowerCase().includes(search)
        );
    }
    if (position) {
        filtered = filtered.filter(o => o.position === position);
    }
    if (year) {
        filtered = filtered.filter(o => new Date(o.term_end_date).getFullYear() == year);
    }

    const $tbody = $('#formerOfficialsBody').empty();
    const $noData = $('#noData');
    $('#totalCount').text(filtered.length + ' Former Official' + (filtered.length !== 1 ? 's' : ''));

    if (!filtered.length) {
        $noData.show();
        return;
    }
    $noData.hide();

    filtered.forEach(o => {
        const photo = o.profile_picture
            ? `<img src="${o.profile_picture}" class="rounded-circle profile-img" alt="${o.full_name}">`
            : `<div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center profile-img"><i class="fas fa-user"></i></div>`;

        const term = `${formatDate(o.term_start_date)} – ${formatDate(o.term_end_date)}`;
        const badge = o.term_end_date ? 'bg-danger' : 'bg-warning';

        $tbody.append(`
            <tr>
                <td>${photo}</td>
                <td><strong>${escapeHtml(o.full_name)}</strong></td>
                <td><span class="badge bg-success">${escapeHtml(o.position)}</span></td>
                <td>
                    <span class="term-badge badge ${badge}">${term}</span>
                </td>
                <td>${o.contact || '—'}</td>
                <td><span class="badge bg-secondary">Former</span></td>
            </tr>
        `);
    });
}

// Populate Year Filter
function populateYearFilter() {
    const years = [...new Set(formerOfficials.map(o => new Date(o.term_end_date).getFullYear()))]
        .sort((a, b) => b - a);
    const $select = $('#yearFilter').empty().append('<option value="">All Years</option>');
    years.forEach(y => $select.append(`<option value="${y}">${y}</option>`));
}

// Helpers
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

// Export CSV
function exportFormerOfficials() {
    let csv = "Full Name,Position,Term Start,Term End,Contact\n";
    formerOfficials.forEach(o => {
        csv += `"${o.full_name}","${o.position}","${o.term_start_date}","${o.term_end_date || ''}","${o.contact || ''}"\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'former_officials_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}

// Filters
$('#searchInput, #positionFilter, #yearFilter').on('input change', () => {
    clearTimeout(window.filterTimeout);
    window.filterTimeout = setTimeout(renderTable, 300);
});

function clearFilters() {
    $('#searchInput, #positionFilter, #yearFilter').val('');
    renderTable();
}

// Init
$(document).ready(() => {
    loadFormerOfficials();
});
</script>
</body>
</html>