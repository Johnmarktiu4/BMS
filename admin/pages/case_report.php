<?php
// Include database connection
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="fas fa-file-alt me-2"></i>Case Reports Management</h2>
                    <p class="text-muted mb-0">Manage and track barangay case reports</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addCaseModal">
                        <i class="fas fa-plus me-2"></i>Add Case
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-secondary btn-lg dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-export me-2"></i>Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportCases('csv')"><i class="fas fa-file-csv me-2"></i>CSV</a></li>
                            <li><a class="dropdown-item" href="#" onclick="printTable()"><i class="fas fa-print me-2"></i>Print</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Controls -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4 col-sm-6">
                            <label for="searchInput" class="form-label">Search Cases</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search by case number, complainant, or respondent...">
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label for="caseTypeFilter" class="form-label">Case Type</label>
                            <select class="form-select" id="caseTypeFilter">
                                <option value="">All</option>
                                <option value="Dispute">Dispute</option>
                                <option value="Complaint">Complaint</option>
                                <option value="Incident">Incident</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label for="statusFilter" class="form-label">Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">All</option>
                                <option value="Pending">Pending</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Dismissed">Dismissed</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label for="entriesSelect" class="form-label">Show Entries</label>
                            <select class="form-select" id="entriesSelect">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-12">
                            <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                                <i class="fas fa-undo me-1"></i>Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Case Reports Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Case Reports List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="caseReportsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Case Number</th>
                                    <th>Complainant</th>
                                    <th>Respondent</th>
                                    <th>Case Type</th>
                                    <th>Date Filed</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="caseReportsTableBody">
                                <!-- Data will be populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <small class="text-muted" id="paginationInfo">Showing 0 to 0 of 0 entries</small>
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="paginationControls">
                                <!-- Pagination links will be populated via AJAX -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Case Modal -->
<div class="modal fade" id="addCaseModal" tabindex="-1" aria-labelledby="addCaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title" id="addCaseModalLabel">
                    <i class="fas fa-file-alt me-2"></i>Add New Case
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="addCaseForm">
                    <input type="hidden" id="caseId" name="id">
                    <!-- Case Information -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Case Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="caseNumber" class="form-label">Case Number *</label>
                                    <input type="text" class="form-control" id="caseNumber" name="case_number" placeholder="e.g., CR-2025-XXX" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="caseType" class="form-label">Case Type *</label>
                                    <select class="form-select" id="caseType" name="case_type" required>
                                        <option value="">Select Case Type</option>
                                        <option value="Dispute">Dispute</option>
                                        <option value="Complaint">Complaint</option>
                                        <option value="Incident">Incident</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="complainant" class="form-label">Complainant *</label>
                                    <input type="text" class="form-control" id="complainant" name="complainant" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="respondent" class="form-label">Respondent *</label>
                                    <input type="text" class="form-control" id="respondent" name="respondent" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="dateFiled" class="form-label">Date Filed *</label>
                                    <input type="date" class="form-control" id="dateFiled" name="date_filed" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Resolved">Resolved</option>
                                        <option value="Dismissed">Dismissed</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="description" class="form-label">Description *</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter case description" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveCaseBtn" onclick="saveCase()">
                    <i class="fas fa-save me-1"></i>Save Case
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Case Modal -->
<div class="modal fade" id="viewCaseModal" tabindex="-1" aria-labelledby="viewCaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 shadow-lg">
            <div class="modal-header bg-info text-white border-0">
                <h5 class="modal-title" id="viewCaseModalLabel">
                    <i class="fas fa-file-alt me-2"></i>Case Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 id="viewCaseNumber" class="mb-3"></h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p><strong>Complainant:</strong> <span id="viewComplainant"></span></p>
                                <p><strong>Respondent:</strong> <span id="viewRespondent"></span></p>
                                <p><strong>Case Type:</strong> <span id="viewCaseType"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date Filed:</strong> <span id="viewDateFiled"></span></p>
                                <p><strong>Status:</strong> <span id="viewStatus" class="badge"></span></p>
                            </div>
                            <div class="col-12">
                                <p><strong>Description:</strong> <span id="viewDescription"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let entriesPerPage = 10;

// Show alert function
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => {
        if (alertDiv.parentNode) alertDiv.remove();
    }, 5000);
}

// Load case reports data via AJAX
function loadCases(page = 1) {
    const search = $('#searchInput').val();
    const caseType = $('#caseTypeFilter').val();
    const status = $('#statusFilter').val();
    entriesPerPage = parseInt($('#entriesSelect').val()) || 10;
    currentPage = page;

    $.ajax({
        url: 'partials/case_report_api.php',
        type: 'POST',
        data: {
            action: 'fetch',
            page: page,
            limit: entriesPerPage,
            search: search,
            case_type: caseType,
            status: status
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                updateCaseReportsTable(response.data.cases);
                updatePagination(response.data.pagination);
            } else {
                showAlert('danger', response.message || 'Failed to load cases.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error loading cases: ' + error);
        }
    });
}

// Update case reports table
function updateCaseReportsTable(cases) {
    const tbody = $('#caseReportsTableBody');
    tbody.empty();
    if (cases.length === 0) {
        tbody.append('<tr><td colspan="8" class="text-center">No cases found.</td></tr>');
        return;
    }
    cases.forEach(caseItem => {
        const statusClass = caseItem.status === 'Pending' ? 'bg-warning' : 
                          caseItem.status === 'Resolved' ? 'bg-success' : 'bg-danger';
        const row = `
            <tr>
                <td><strong>${caseItem.case_number}</strong></td>
                <td>${caseItem.complainant}</td>
                <td>${caseItem.respondent}</td>
                <td>${caseItem.case_type}</td>
                <td>${caseItem.date_filed}</td>
                <td><span class="badge ${statusClass}">${caseItem.status}</span></td>
                <td>${caseItem.description.substring(0, 50)}${caseItem.description.length > 50 ? '...' : ''}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" title="View Details" onclick="viewCase(${caseItem.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success" title="Edit" onclick="editCase(${caseItem.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-warning" title="Archive" onclick="archiveCase(${caseItem.id})">
                            <i class="fas fa-archive"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        tbody.append(row);
    });
}

// Update pagination
function updatePagination(pagination) {
    const paginationControls = $('#paginationControls');
    paginationControls.empty();
    const start = Math.min(1, pagination.total);
    const end = Math.min(pagination.limit, pagination.total);
    $('#paginationInfo').text(`Showing ${start} to ${end} of ${pagination.total} entries`);

    if (pagination.total === 0) return;

    const prevDisabled = pagination.current_page === 1 ? 'disabled' : '';
    const nextDisabled = pagination.current_page === pagination.total_pages ? 'disabled' : '';
    
    paginationControls.append(`
        <li class="page-item ${prevDisabled}">
            <a class="page-link" href="#" onclick="if (!$(this).parent().hasClass('disabled')) loadCases(${pagination.current_page - 1})">Previous</a>
        </li>
    `);
    for (let i = 1; i <= pagination.total_pages; i++) {
        paginationControls.append(`
            <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadCases(${i})">${i}</a>
            </li>
        `);
    }
    paginationControls.append(`
        <li class="page-item ${nextDisabled}">
            <a class="page-link" href="#" onclick="if (!$(this).parent().hasClass('disabled')) loadCases(${pagination.current_page + 1})">Next</a>
        </li>
    `);
}

// Save case function
function saveCase() {
    const form = $('#addCaseForm');
    const formData = new FormData(form[0]);
    const isEdit = $('#caseId').val() !== '';
    formData.append('action', isEdit ? 'update' : 'add');

    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }

    const saveBtn = $('#saveCaseBtn');
    const originalText = saveBtn.html();
    saveBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...').prop('disabled', true);

    $.ajax({
        url: 'partials/case_report_api.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            saveBtn.html(originalText).prop('disabled', false);
            if (response.status === 'success') {
                $('#addCaseModal').modal('hide');
                form[0].reset();
                showAlert('success', isEdit ? 'Case updated successfully!' : 'Case added successfully!');
                loadCases(currentPage);
            } else {
                showAlert('danger', response.message || 'Failed to save case.');
            }
        },
        error: function(xhr, status, error) {
            saveBtn.html(originalText).prop('disabled', false);
            showAlert('danger', 'Error saving case: ' + error);
        }
    });
}

// Edit case function
function editCase(id) {
    $.ajax({
        url: 'partials/case_report_api.php',
        type: 'POST',
        data: { action: 'get', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.data) {
                const caseItem = response.data;
                $('#caseId').val(caseItem.id);
                $('#caseNumber').val(caseItem.case_number);
                $('#caseType').val(caseItem.case_type);
                $('#complainant').val(caseItem.complainant);
                $('#respondent').val(caseItem.respondent);
                $('#dateFiled').val(caseItem.date_filed);
                $('#status').val(caseItem.status);
                $('#description').val(caseItem.description);
                $('#addCaseModalLabel').html('<i class="fas fa-file-alt me-2"></i>Edit Case');
                $('#addCaseModal').modal('show');
            } else {
                showAlert('danger', response.message || 'Failed to load case details.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error loading case: ' + error);
        }
    });
}

// View case function
function viewCase(id) {
    $.ajax({
        url: 'partials/case_report_api.php',
        type: 'POST',
        data: { action: 'get', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.data) {
                const caseItem = response.data;
                $('#viewCaseNumber').text(caseItem.case_number);
                $('#viewComplainant').text(caseItem.complainant);
                $('#viewRespondent').text(caseItem.respondent);
                $('#viewCaseType').text(caseItem.case_type);
                $('#viewDateFiled').text(caseItem.date_filed);
                $('#viewStatus').text(caseItem.status).removeClass('bg-warning bg-success bg-danger')
                    .addClass(caseItem.status === 'Pending' ? 'bg-warning' : 
                              caseItem.status === 'Resolved' ? 'bg-success' : 'bg-danger');
                $('#viewDescription').text(caseItem.description);
                $('#viewCaseModal').modal('show');
            } else {
                showAlert('danger', response.message || 'Failed to load case details.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error loading case: ' + error);
        }
    });
}

// Archive case function
function archiveCase(id) {
    if (!confirm('Are you sure you want to archive this case? This action cannot be undone.')) return;

    $.ajax({
        url: 'partials/case_report_api.php',
        type: 'POST',
        data: { action: 'archive', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                showAlert('success', 'Case archived successfully!');
                loadCases(currentPage);
            } else {
                showAlert('danger', response.message || 'Failed to archive case.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error archiving case: ' + error);
        }
    });
}

// Clear filters
function clearFilters() {
    $('#searchInput').val('');
    $('#caseTypeFilter').val('');
    $('#statusFilter').val('');
    $('#entriesSelect').val('10');
    entriesPerPage = 10;
    currentPage = 1;
    loadCases();
}

// Event listeners
$(document).ready(function() {
    loadCases();
    $('#searchInput').on('keyup', function() { loadCases(); });
    $('#caseTypeFilter').on('change', function() { loadCases(); });
    $('#statusFilter').on('change', function() { loadCases(); });
    $('#entriesSelect').on('change', function() { loadCases(); });

    $('#addCaseForm').on('submit', function(e) {
        e.preventDefault();
        saveCase();
    });

    $('#addCaseModal').on('hidden.bs.modal', function() {
        $('#addCaseForm')[0].reset();
        $('#caseId').val('');
        $('#addCaseModalLabel').html('<i class="fas fa-file-alt me-2"></i>Add New Case');
        $(this).find('.form-control, .form-select').removeClass('is-valid is-invalid');
    });

    const requiredInputs = $('#addCaseForm [required]');
    requiredInputs.on('blur', function() {
        if (this.value.trim()) {
            $(this).addClass('is-valid').removeClass('is-invalid');
        } else {
            $(this).addClass('is-invalid').removeClass('is-valid');
        }
    }).on('input', function() {
        if ($(this).hasClass('is-invalid') && this.value.trim()) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });

    $('#caseNumber').on('input', function() {
        let value = this.value.toUpperCase();
        if (!value.match(/^CR-\d{4}-\d+$/)) {
            $(this).addClass('is-invalid').removeClass('is-valid');
            showAlert('warning', 'Case number should follow format CR-YYYY-NNN (e.g., CR-2025-001).');
        } else {
            $(this).addClass('is-valid').removeClass('is-invalid');
        }
    });
});

// Export to CSV
function exportCases(format) {
    if (format !== 'csv') return;
    $.ajax({
        url: 'partials/case_report_api.php',
        type: 'POST',
        data: {
            action: 'fetch',
            page: 1,
            limit: 9999,
            search: $('#searchInput').val(),
            case_type: $('#caseTypeFilter').val(),
            status: $('#statusFilter').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const cases = response.data.cases;
                let csv = 'Case Number,Complainant,Respondent,Case Type,Date Filed,Status,Description\n';
                cases.forEach(caseItem => {
                    const rowData = [
                        caseItem.case_number,
                        caseItem.complainant,
                        caseItem.respondent,
                        caseItem.case_type,
                        caseItem.date_filed,
                        caseItem.status,
                        caseItem.description
                    ].map(field => `"${field.replace(/"/g, '""')}"`);
                    csv += rowData.join(',') + '\n';
                });
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'case_reports.csv';
                link.click();
                window.URL.revokeObjectURL(url);
            } else {
                showAlert('danger', 'Failed to export cases.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error exporting cases: ' + error);
        }
    });
}

// Print table
function printTable() {
    $.ajax({
        url: 'partials/case_report_api.php',
        type: 'POST',
        data: {
            action: 'fetch',
            page: 1,
            limit: 9999,
            search: $('#searchInput').val(),
            case_type: $('#caseTypeFilter').val(),
            status: $('#statusFilter').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const cases = response.data.cases;
                let tableHtml = `
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Case Number</th>
                                <th>Complainant</th>
                                <th>Respondent</th>
                                <th>Case Type</th>
                                <th>Date Filed</th>
                                <th>Status</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>`;
                cases.forEach(caseItem => {
                    tableHtml += `
                        <tr>
                            <td>${caseItem.case_number}</td>
                            <td>${caseItem.complainant}</td>
                            <td>${caseItem.respondent}</td>
                            <td>${caseItem.case_type}</td>
                            <td>${caseItem.date_filed}</td>
                            <td>${caseItem.status}</td>
                            <td>${caseItem.description.substring(0, 50)}${caseItem.description.length > 50 ? '...' : ''}</td>
                        </tr>`;
                });
                tableHtml += '</tbody></table>';

                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Case Reports List</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .table { width: 100%; border-collapse: collapse; }
                            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                            th { background-color: #343a40; color: white; }
                            @media print {
                                .table { font-size: 10px; }
                            }
                        </style>
                    </head>
                    <body>
                        <h3 class="text-center mb-4">Case Reports List</h3>
                        <div class="table-responsive">${tableHtml}</div>
                    </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.focus();
                setTimeout(() => printWindow.print(), 500);
            } else {
                showAlert('danger', 'Failed to print cases.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error printing cases: ' + error);
        }
    });
}
</script>

<style>
.card {
    border-radius: 0.5rem;
    transition: transform 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
}

.card-header {
    background-color: transparent;
    border-bottom: none;
    font-weight: 500;
    padding: 0.75rem 1.25rem;
}

.table-responsive {
    border-radius: 0.5rem;
    overflow: hidden;
}

.table th {
    font-weight: 600;
    padding: 0.75rem;
    font-size: 0.9rem;
}

.table td {
    padding: 0.75rem;
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: #f1f3f5;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* Modal specific styles */
.modal-content {
    border: none;
    border-radius: 0.75rem;
}

.modal-header {
    padding: 1.25rem;
}

.modal-body {
    padding: 1.5rem;
    background-color: #f8f9fa;
}

.modal-footer {
    padding: 1rem 1.5rem;
}

.form-control, .form-select {
    border-radius: 0.375rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.is-valid {
    border-color: #198754 !important;
    background-image: none !important;
}

.is-invalid {
    border-color: #dc3545 !important;
    background-image: none !important;
}

.alert {
    border-radius: 0.5rem;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .modal-lg {
        max-width: 90%;
    }
}

@media (max-width: 768px) {
    .modal-lg {
        max-width: 95%;
        margin: 0.5rem;
    }
    
    .table th, .table td {
        padding: 0.5rem;
        font-size: 0.85rem;
    }
    
    .btn-group .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.65rem;
    }
    
    .badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
    }
    
    .form-control, .form-select {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .modal-header, .modal-body, .modal-footer {
        padding: 1rem;
    }
    
    .table th, .table td {
        font-size: 0.8rem;
    }
    
    .card-body {
        padding: 0.75rem;
    }
}
</style>