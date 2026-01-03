<?php
// admin/pages/damage_broken.php
require_once 'partials/db_conn.php';
?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="fas fa-tools me-2"></i>Damaged/Broken Items</h2>
                    <p class="text-muted mb-0">Track damaged or broken items in the inventory</p>
                </div>
                <button class="btn btn-primary btn-lg" onclick="openTransactionModal(0, 'Broken')">
                    <i class="fas fa-plus me-2"></i>Record Damage/Broken
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Damaged/Broken Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="damagedTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Borrower</th>
                                    <th>Transacted By</th>
                                    <th>Transaction Date</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="damagedTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="transactionModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title" id="transactionModalLabel">
                        <i class="fas fa-exchange-alt me-2"></i>Record Damaged/Broken Item
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <form id="transactionForm">
                        <input type="hidden" id="transactionItemId" name="inventory_id">
                        <input type="hidden" id="borrowerId" name="borrower_id">

                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Transaction Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Item Name *</label>
                                        <select class="form-select" id="itemSelect" name="inventory_id" required>
                                            <option value="">Select Item</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Quantity *</label>
                                        <input type="number" class="form-control" name="quantity" min="1" required>
                                    </div>

                                    <!-- SEARCHABLE BORROWER -->
                                    <div class="col-md-6">
                                        <label class="form-label">Borrower *</label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control" id="borrowerSearchInput" 
                                                   placeholder="Search resident..." autocomplete="off" required>
                                            <div id="borrowerDropdown" class="position-absolute w-100 bg-white border rounded-bottom shadow-sm" 
                                                 style="top:100%; max-height:200px; overflow-y:auto; display:none; z-index:1070;">
                                                <div class="p-2 text-center text-muted">Type to search...</div>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-1" id="selectedBorrowerName"></small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Transacted By</label>
                                        <input type="text" class="form-control" name="transacted_by">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Transaction Date *</label>
                                        <input type="date" class="form-control" name="transaction_date" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Remarks</label>
                                        <textarea class="form-control" name="remarks" rows="3"></textarea>
                                    </div>
                                    <input type="hidden" name="action_type" value="Broken">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveTransactionBtn" onclick="saveTransaction()">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let residents = [];

// Load Residents
function loadResidents() {
    $.get('partials/get_residents.php', data => residents = data || [], 'json')
     .fail(() => showAlert('danger', 'Failed to load residents.'));
}

// Search Borrower
function searchBorrower() {
    const q = $('#borrowerSearchInput').val().trim().toLowerCase();
    const $dd = $('#borrowerDropdown').empty();
    if (!q) { $dd.hide(); $('#selectedBorrowerName').text(''); $('#borrowerId').val(''); return; }

    const filtered = residents.filter(r =>
        r.full_name.toLowerCase().includes(q) ||
        (r.first_name && r.first_name.toLowerCase().includes(q)) ||
        (r.last_name && r.last_name.toLowerCase().includes(q))
    );

    if (!filtered.length) {
        $dd.append('<div class="p-2 text-center text-muted">No residents found.</div>').show();
        return;
    }

    filtered.forEach(r => {
        $dd.append(`
            <div class="px-3 py-2 border-bottom" style="cursor:pointer;" 
                 onclick="selectBorrower(${r.id}, '${r.full_name.replace(/'/g, "\\'")}')">
                <strong>${r.full_name}</strong><br>
                <small class="text-muted">${r.house_number} ${r.street} | Age: ${r.age}</small>
            </div>
        `);
    });
    $dd.show();
}

// Select Borrower
function selectBorrower(id, name) {
    $('#borrowerId').val(id);
    $('#borrowerSearchInput').val(name);
    $('#selectedBorrowerName').text(name);
    $('#borrowerDropdown').hide();
}

// Close on outside click
$(document).on('click', e => {
    if (!$(e.target).closest('#borrowerSearchInput, #borrowerDropdown').length) {
        $('#borrowerDropdown').hide();
    }
});

function showAlert(t, m) {
    const a = $(`<div class="alert alert-${t} alert-dismissible fade show position-fixed" style="top:100px;right:20px;z-index:9999;min-width:300px;">
        ${m}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`);
    $('body').append(a);
    setTimeout(() => a.alert('close'), 5000);
}

function loadDamagedItems() {
    $.post('partials/inventory_management_api.php', { action: 'fetch_transactions' }, r => {
        if (r.status === 'success') {
            const list = r.data.filter(x => x.action_type === 'Broken');
            updateDamagedTable(list);
        } else showAlert('danger', r.message || 'Failed');
    }, 'json');
}

function updateDamagedTable(data) {
    const $tbody = $('#damagedTableBody').empty();
    if (!data.length) return $tbody.append('<tr><td colspan="6" class="text-center">No damaged items.</td></tr>');
    data.forEach(t => $tbody.append(`
        <tr>
            <td><strong>${t.item_name}</strong></td>
            <td>${t.quantity}</td>
            <td>${t.borrower_name || '-'}</td>
            <td>${t.transacted_by || '-'}</td>
            <td>${t.transaction_date}</td>
            <td>${t.remarks || '-'}</td>
        </tr>`));
}

function openTransactionModal(id, type) {
    $('#transactionItemId').val(id);
    $('#itemSelect').val(''); $('[name=quantity]').val('');
    $('#borrowerSearchInput').val(''); $('#selectedBorrowerName').text(''); $('#borrowerId').val('');
    $('[name=transacted_by]').val('');
    $('[name=transaction_date]').val(new Date().toISOString().split('T')[0]);
    $('[name=remarks]').val('');
    loadItemsForSelection();
    $('#transactionModal').modal('show');
}

function saveTransaction() {
    const f = $('#transactionForm')[0];
    if (!f.checkValidity()) return f.reportValidity();
    if (!$('#borrowerId').val()) return showAlert('danger', 'Select a valid borrower.');

    const fd = new FormData(f);
    fd.append('action', 'add_transaction');
    fd.append('borrower_name', $('#borrowerSearchInput').val());

    const $btn = $('#saveTransactionBtn').html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
    $.ajax({
        url: 'partials/inventory_management_api.php', type: 'POST', data: fd,
        processData: false, contentType: false, dataType: 'json',
        success: r => {
            $btn.html('Save').prop('disabled', false);
            if (r.status === 'success') {
                $('#transactionModal').modal('hide'); f.reset();
                $('#selectedBorrowerName').text(''); $('#borrowerId').val('');
                showAlert('success', 'Saved!');
                loadDamagedItems();
            } else showAlert('danger', r.message);
        },
        error: () => { $btn.html('Save').prop('disabled', false); showAlert('danger', 'Error'); }
    });
}

function loadItemsForSelection() {
    $.post('partials/inventory_management_api.php', { action: 'fetch_inventory', page: 1, limit: 9999 }, r => {
        const s = $('#itemSelect'); s.empty().append('<option value="">Select Item</option>');
        if (r.status === 'success') r.data.items.forEach(i => {
            if (i.current_stock > 0) s.append(`<option value="${i.id}">${i.item_name} (Stock: ${i.current_stock})</option>`);
        });
    }, 'json');
}

$(() => {
    loadResidents();
    loadDamagedItems();
    $('#borrowerSearchInput').on('input', searchBorrower);
    $('#transactionForm').on('submit', e => { e.preventDefault(); saveTransaction(); });
    $('#transactionModal').on('hidden.bs.modal', () => {
        $('#transactionForm')[0].reset();
        $('#selectedBorrowerName').text(''); $('#borrowerId').val('');
        $('#borrowerDropdown').hide();
    });
});
</script>

<style>
#borrowerDropdown { border-top: none; border-top-left-radius: 0; border-top-right-radius: 0; }
#borrowerDropdown > div:hover { background-color: #e9ecef; }
.card:hover { transform: translateY(-2px); }
.table tbody tr:hover { background-color: #f1f3f5; }
.alert { border-radius: 0.5rem; box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1); }
</style>
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