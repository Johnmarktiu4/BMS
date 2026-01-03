<?php
// archive_residents.php
?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="fas fa-archive me-2"></i>Archived Residents</h2>
                    <p class="text-muted mb-0">View and restore previously archived residents</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="archiveSearch" class="form-label">Search</label>
                    <input type="text" class="form-control" id="archiveSearch" placeholder="Name, address, contact...">
                </div>
                <div class="col-md-3">
                    <label for="archiveSexFilter" class="form-label">Sex</label>
                    <select class="form-select" id="archiveSexFilter">
                        <option value="">All</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Rather not to say">Rather not to say</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="entriesSelectArchive" class="form-label">Show Entries</label>
                    <select class="form-select" id="entriesSelectArchive">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" onclick="clearArchiveFilters()">Clear</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-users-slash me-2"></i>Archived Residents List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="archiveResidentsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Profile</th>
                            <th>Full Name</th>
                            <th>Age</th>
                            <th>Sex</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Archive Reason</th>
                            <th>Archived On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div><small class="text-muted" id="archivePaginationInfo"></small></div>
                <nav><ul class="pagination pagination-sm mb-0" id="archivePaginationLinks"></ul></nav>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreResidentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-undo me-2"></i>Restore Resident</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <strong>restore</strong> this resident?</p>
                <p id="restoreResidentName" class="fw-bold text-primary"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmRestoreBtn">Restore</button>
            </div>
        </div>
    </div>
</div>

<script>
    let archiveCurrentPage = 1;

    function loadArchivedResidents(page = 1) {
        archiveCurrentPage = page;
        const search = $('#archiveSearch').val();
        const sex = $('#archiveSexFilter').val();
        const limit = $('#entriesSelectArchive').val();

        $.ajax({
            url: 'partials/resident_management_api.php',
            data: {
                action: 'get_archived_residents',
                search, sex, page, limit
            },
            dataType: 'json',
            success: function(data) {
                let tbody = '';
                let count = (page - 1) * limit + 1;

                if (data.residents.length === 0) {
                    tbody = `<tr><td colspan="10" class="text-center text-muted">No archived residents found.</td></tr>`;
                } else {
                    data.residents.forEach(r => {
                        const profile = r.profile_picture
                            ? `<img src="../${r.profile_picture}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">`
                            : `<div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded-circle" style="width:40px;height:40px;font-size:0.8rem;">${r.full_name.charAt(0)}</div>`;

                        const sexBadge = r.sex === 'Male' ? 'bg-info' : r.sex === 'Female' ? 'bg-success' : 'bg-secondary';

                        tbody += `
                        <tr>
                            <td>${count++}</td>
                            <td>${profile}</td>
                            <td><strong>${r.full_name}</strong></td>
                            <td>${r.age}</td>
                            <td><span class="badge ${sexBadge}">${r.sex}</span></td>
                            <td><small>${r.address}</small></td>
                            <td>${r.contact_number}</td>
                            <td><small class="text-muted">${r.archive_reason || '—'}</small></td>
                            <td><small>${formatDate(r.updated_at)}</small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-success" title="Restore" onclick="openRestoreModal(${r.id}, '${escapeHtml(r.full_name)}')"><i class="fas fa-undo"></i></button>
                                </div>
                            </td>
                        </tr>`;
                    });
                }

                $('#archiveResidentsTable tbody').html(tbody);
                updateArchivePagination(data.total, limit, page);
            },
            error: () => showAlert('danger', 'Failed to load archived residents.')
        });
    }

    function updateArchivePagination(total, limit, page) {
        const totalPages = Math.ceil(total / limit);
        let pagination = '';
        if (totalPages > 0) {
            pagination += `<li class="page-item ${page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="${page > 1 ? `loadArchivedResidents(${page - 1})` : ''}">Prev</a>
            </li>`;
            for (let i = 1; i <= totalPages; i++) {
                pagination += `<li class="page-item ${i === page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadArchivedResidents(${i})">${i}</a>
                </li>`;
            }
            pagination += `<li class="page-item ${page >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="${page < totalPages ? `loadArchivedResidents(${page + 1})` : ''}">Next</a>
            </li>`;
        }
        $('#archivePaginationLinks').html(pagination);
        const start = (page - 1) * limit + 1;
        const end = Math.min(start + limit - 1, total);
        $('#archivePaginationInfo').text(`Showing ${start} to ${end} of ${total} entries`);
    }

    function clearArchiveFilters() {
        $('#archiveSearch').val('');
        $('#archiveSexFilter').val('');
        $('#entriesSelectArchive').val('10');
        loadArchivedResidents(1);
    }

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    let restoreResidentId = 0;
    function openRestoreModal(id, name) {
        restoreResidentId = id;
        $('#restoreResidentName').html(name);
        $('#restoreResidentModal').modal('show');
    }

    $('#confirmRestoreBtn').on('click', function() {
        const btn = $(this);
        const txt = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Restoring...').prop('disabled', true);

        $.ajax({
            url: 'partials/resident_management_api.php',
            data: { action: 'restore_resident', id: restoreResidentId },
            dataType: 'json',
            success: function(res) {
                btn.html(txt).prop('disabled', false);
                $('#restoreResidentModal').modal('hide');
                showAlert(res.success ? 'success' : 'danger', res.message);
                if (res.success) {
                    loadArchivedResidents(archiveCurrentPage);
                    loadHeadOfFamily(headCurrentPage); // Refresh heads if needed
                }
            }
        });
    });

    // Search with debounce
    let archiveSearchTimeout;
    $('#archiveSearch').on('keyup', function() {
        clearTimeout(archiveSearchTimeout);
        archiveSearchTimeout = setTimeout(() => loadArchivedResidents(1), 500);
    });

    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }

    $('#archiveSexFilter, #entriesSelectArchive').on('change', () => loadArchivedResidents(1));

    $(document).ready(function() {
        loadArchivedResidents(1);
    });
</script>