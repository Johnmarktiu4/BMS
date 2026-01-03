<?php
require_once 'partials/db_conn.php';
?>
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">Role Accounts</h2>
                <p class="text-muted mb-0">Manage official accounts with login access</p>
            </div>
            <button class="btn btn-success" onclick="openAddModal()">
                <i class="fas fa-plus me-2"></i>Add Account
            </button>
        </div>
    </div>

    <!-- Accounts Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Official Accounts</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="accountsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Full Name</th>
                                    <th>Position</th>
                                    <th>Username</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="accountsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADD / EDIT MODAL -->
<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalTitle">Add Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="accountForm">
                    <input type="hidden" id="account_id">
                    <input type="hidden" id="official_id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Official (Full Name) <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="officialSearch" placeholder="Type to search official..." autocomplete="off" required>
                                <div id="officialDropdown" class="position-absolute w-100 bg-white border rounded-bottom shadow-sm" style="top:100%; max-height:250px; overflow-y:auto; display:none; z-index:1070;">
                                    <div class="p-2 text-center text-muted">Loading officials...</div>
                                </div>
                            </div>
                            <small class="text-success d-block mt-1" id="selectedOfficial"></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Position</label>
                            <input type="text" class="form-control" id="position" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" required minlength="4" maxlength="100">
                            <small class="text-muted">Used for login • Cannot be changed later</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" minlength="6">
                            <small class="text-muted">Leave blank when editing to keep current password</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <select class="form-select" id="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Locked">Locked</option>
                            </select>
                        </div>
                    </div>

                    <!-- 3 SECURITY QUESTIONS ONLY -->
                    <hr class="my-4">
                    <h5 class="mb-3 text-primary">Security Questions (Required for Password Recovery)</h5>
                    <p class="text-muted small">These will be used to recover password if forgotten.</p>

                    <div class="mb-3">
                        <label class="form-label fw-bold">1. What is your mother's maiden name? <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sec_a1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">2. What was the name of your first pet? <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sec_a2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">3. In what city were you born? <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sec_a3" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveBtn" onclick="saveAccount()">Add Account</button>
            </div>
        </div>
    </div>
</div>

<script>
let officials = [], accounts = [];

$(function() {
    loadOfficials();
    loadAccounts();

    $('#officialSearch').on('input', debounce(filterOfficials, 250));

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#officialSearch, #officialDropdown').length) {
            $('#officialDropdown').hide();
        }
    });
});

function loadOfficials() {
    $.post('partials/role_accounts_api.php', { action: 'fetch_officials' }, function(r) {
        if (r.status === 'success') {
            officials = r.data;
            populateOfficialDropdown('');
        }
    }, 'json');
}

function loadAccounts() {
    $.post('partials/role_accounts_api.php', { action: 'fetch_accounts' }, function(r) {
        if (r.status === 'success') {
            accounts = r.data;
            updateAccountsTable();
        }
    }, 'json');
}

function updateAccountsTable() {
    const tbody = $('#accountsTableBody').empty();
    if (!accounts.length) {
        tbody.append('<tr><td colspan="5" class="text-center py-4 text-muted">No accounts found.</td></tr>');
        return;
    }
    accounts.forEach(acc => {
        const statusBadge = acc.status === 'Active' 
            ? '<span class="badge bg-success">Active</span>' 
            : '<span class="badge bg-secondary">Inactive/Locked</span>';
        tbody.append(`
            <tr>
                <td><strong>${escapeHtml(acc.full_name)}</strong></td>
                <td>${escapeHtml(acc.position)}</td>
                <td><code>${escapeHtml(acc.username)}</code></td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-warning me-1" onclick="editAccount(${acc.id})" title="Edit">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAccount(${acc.id})" title="Delete">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            </tr>
        `);
    });
}

function openAddModal() {
    $('#modalTitle').text('Add Account');
    $('#saveBtn').text('Add Account').removeClass('btn-primary').addClass('btn-success');
    $('#accountForm')[0].reset();
    $('#account_id').val('');
    $('#official_id').val('');
    $('#selectedOfficial').text('');
    $('#position').val('');
    $('#username').prop('readonly', false);
    $('#password').prop('required', true);
    $('#officialSearch').val('');
    populateOfficialDropdown('');
    new bootstrap.Modal('#accountModal').show();
}

function editAccount(id) {
    const acc = accounts.find(a => a.id == id);
    if (!acc) return;

    $('#modalTitle').text('Edit Account');
    $('#saveBtn').text('Update Account').removeClass('btn-success').addClass('btn-primary');
    $('#account_id').val(acc.id);
    $('#official_id').val(acc.official_id);
    $('#selectedOfficial').text(acc.full_name);
    $('#officialSearch').val(acc.full_name);
    $('#position').val(acc.position);
    $('#username').val(acc.username).prop('readonly', true);
    $('#status').val(acc.status);
    $('#sec_a1').val(acc.sec_a1 || '');
    $('#sec_a2').val(acc.sec_a2 || '');
    $('#sec_a3').val(acc.sec_a3 || '');
    $('#password').prop('required', false).val('');
    $('#officialDropdown').hide();
    new bootstrap.Modal('#accountModal').show();
}

function filterOfficials() {
    const q = $('#officialSearch').val().trim().toLowerCase();
    populateOfficialDropdown(q);
}

function populateOfficialDropdown(query = '') {
    const $dd = $('#officialDropdown').empty();
    const filtered = officials.filter(o => 
        o.full_name.toLowerCase().includes(query) || 
        o.position.toLowerCase().includes(query)
    );

    if (!filtered.length) {
        $dd.append('<div class="p-2 text-center text-muted">No officials found.</div>').show();
        return;
    }

    filtered.forEach(o => {
        $dd.append(`
            <div class="px-3 py-2 border-bottom" style="cursor:pointer;" 
                 onclick="selectOfficial(${o.id}, '${escapeHtml(o.full_name)}', '${escapeHtml(o.position)}')">
                <strong>${escapeHtml(o.full_name)}</strong><br>
                <small class="text-muted">${escapeHtml(o.position)}</small>
            </div>
        `);
    });
    $dd.show();
}

function selectOfficial(id, full_name, position) {
    $('#official_id').val(id);
    $('#selectedOfficial').text(full_name);
    $('#officialSearch').val(full_name);
    $('#position').val(position);
    $('#officialDropdown').hide();
}

function saveAccount() {
    const id = $('#account_id').val();
    const official_id = $('#official_id').val();
    const username = $('#username').val().trim();
    const password = $('#password').val();
    const status = $('#status').val();
    const a1 = $('#sec_a1').val().trim().toLowerCase();
    const a2 = $('#sec_a2').val().trim().toLowerCase();
    const a3 = $('#sec_a3').val().trim().toLowerCase();

    if (!official_id) return alert('Please select an official');
    if (!username) return alert('Username is required');
    if (!id && !password) return alert('Password is required for new account');
    if (!a1 || !a2 || !a3) return alert('All 3 security answers are required');

    const data = {
        action: id ? 'update_account' : 'add_account',
        id: id || '',
        official_id: official_id,
        username: username,
        password: password,
        status: status,
        sec_a1: a1,
        sec_a2: a2,
        sec_a3: a3
    };

    $.post('partials/role_accounts_api.php', data, function(r) {
        if (r.status === 'success') {
            $('#accountModal').modal('hide');
            loadAccounts();
            alert(r.message);
        } else {
            alert('Error: ' + r.message);
        }
    }, 'json');
}

function deleteAccount(id) {
    if (!confirm('Delete this account permanently? This cannot be undone.')) return;
    $.post('partials/role_accounts_api.php', { action: 'delete_account', id: id }, function(r) {
        if (r.status === 'success') {
            loadAccounts();
            alert('Account deleted successfully');
        } else {
            alert(r.message);
        }
    }, 'json');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
</script>

<style>
#officialDropdown { 
    border-top: none; 
    border-top-left-radius: 0; 
    border-top-right-radius: 0; 
}
#officialDropdown > div:hover { 
    background-color: #f8f9fa; 
}
code { font-size: 0.9em; background: #eee; padding: 2px 6px; border-radius: 4px; }
</style>