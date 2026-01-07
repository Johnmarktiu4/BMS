<?php
 
$loggedInUserName = $_SESSION['full_name'] ?? 'Guest User';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-hand-holding text-success me-2"></i>Borrowed Items Management</h2>
            <p class="text-muted mb-0">Track all barangay property currently borrowed or returned</p>
        </div>
        <button class="btn btn-success btn-lg shadow-sm" onclick="openBorrowModal()">
            <i class="fas fa-plus-circle"></i> Borrow Property
        </button>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button">
                <i class="fas fa-clock"></i> Active Borrowed Items
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button">
                <i class="fas fa-history"></i> Full History
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Active Borrows Tab -->
        <div class="tab-pane fade show active" id="active">
            <div class="card border-0 shadow">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Currently Borrowed Items</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle" id="activeTable">
                            <thead class="table-dark">
                                <tr>
                                    <th width="120">Status</th>
                                    <th>Borrower</th>
                                    <th>Address</th>
                                    <th width="140">Total Items</th>
                                    <th width="130">Borrowed Date</th>
                                    <th width="130">Return By</th>
                                    <th width="340">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="activeTableBody">
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> Loading active borrows...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Tab -->
        <div class="tab-pane fade" id="history">
            <div class="card border-0 shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Complete Transaction History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="historyTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Type</th>
                                    <th>Borrower</th>
                                    <th>Item</th>
                                    <th>Remaining Qty</th>
                                    <th>Borrowed Qty</th>
                                    <th>Borrow Date</th>
                                    <th>Returned Date</th>
                                    <!-- <th>Status</th> -->
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> Loading history...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Borrowed Items Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h5>Borrower: <strong id="view_borrower_name"></strong></h5>
                <p class="text-muted mb-3" id="view_address"></p>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Borrowed On</th>
                                <th>Return Date</th>
                            </tr>
                        </thead>
                        <tbody id="viewItemsBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Borrow Modal -->
<div class="modal fade" id="borrowModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Add Borrow Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="borrowForm">
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold">Borrower <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="borrowerSearch" placeholder="Search by name, house no, or street..." autocomplete="off" required>
                        <div id="borrowerDropdown" class="shadow border rounded position-absolute top-100 start-0 w-100" style="z-index: 2000; max-height: 300px; overflow-y: auto; display: none; background: white;"></div>
                        <div class="mt-2" id="selectedBorrower"></div>
                        <input type="hidden" id="borrowerId">
                    </div>

                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold">Add Items</label>
                        <input type="text" class="form-control form-control-lg" id="itemSearchInput" placeholder="Type item name to add..." autocomplete="off">
                        <div id="itemSearchDropdown" class="shadow border rounded position-absolute top-100 start-0 w-100" style="z-index: 2000; max-height: 300px; overflow-y: auto; display: none; background: white;"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Selected Items</label>
                        <div class="table-responsive" style="max-height: 300px;">
                            <table class="table table-bordered table-sm" id="selectedItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Available</th>
                                        <th>Qty</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Borrow Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="borrow_date_input" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Return By (Max 7 days) <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="return_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                            <small class="text-muted">Maximum 7 days from borrow date</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Transacted By</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($loggedInUserName) ?>" readonly>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Remarks (Optional)</label>
                        <textarea class="form-control" rows="3" name="remarks" placeholder="e.g. For barangay event on Dec 25..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" onclick="saveBorrow()">
                    <i class="fas fa-save"></i> Save Borrow Record
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-undo"></i> Record Return</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Borrower: <strong id="return_borrower_name"></strong></h6>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered" id="returnItemsTable">
                        <thead class="table-primary">
                            <tr>
                                <th>Item</th>
                                <th>Borrowed Qty</th>
                                <th>Good Condition</th>
                                <th>Damaged</th>
                                <th>Lost</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <label>Remarks</label>
                    <textarea class="form-control" id="return_remarks" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveReturn()">Confirm Return</button>
            </div>
        </div>
    </div>
</div>

<!-- Replace Modal -->
<div class="modal fade" id="replaceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-sync-alt"></i> Record Replacement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="replace_transaction_id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Borrower</label>
                        <input type="text" class="form-control" id="replace_borrower_name" readonly>
                    </div>
                    <div class="col-md-6">
                        <label>Item</label>
                        <input type="text" class="form-control" id="replace_item_name" readonly>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label>Quantity Replaced</label>
                        <input type="number" class="form-control" id="replace_qty" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label>Reason</label>
                        <select class="form-select" id="replace_reason">
                            <option>Damaged</option>
                            <option>Lost</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Date</label>
                        <input type="date" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                    </div>
                </div>
                <div class="mt-3">
                    <label>Remarks</label>
                    <textarea class="form-control" id="replace_remarks" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="saveReplacement()">Record Replacement</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-money-bill-wave"></i> Record Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="payment_transaction_id">
                <input type="hidden" id="payment_declared_value">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Borrower</label>
                        <input type="text" class="form-control" id="payment_borrower_name" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Item</label>
                        <input type="text" class="form-control" id="payment_item_name" readonly>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-4">
                        <label class="form-label">Qty Paid For</label>
                        <input type="number" class="form-control" id="payment_qty" min="1" value="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Value per Unit (₱)</label>
                        <input type="text" class="form-control" id="payment_unit_value" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Due (₱)</label>
                        <input type="text" class="form-control bg-light fw-bold" id="payment_total_due" readonly>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Amount Paid (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="payment_amount_paid" required placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <select class="form-select" id="payment_reason" required>
                            <option value="" disabled selected>Select reason...</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Lost">Lost</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Additional Remarks (Optional)</label>
                    <textarea class="form-control" id="payment_remarks" rows="3" placeholder="Any extra notes..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-info" onclick="savePayment()">
                    <i class="fas fa-check"></i> Record Payment
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .badge-not-due { background: #28a745; color: white; padding: 0.4em 0.8em; border-radius: 50px; font-size: 0.85em; }
    .badge-due { background: #ffc107; color: black; padding: 0.4em 0.8em; border-radius: 50px; font-size: 0.85em; }
    .badge-overdue { background: #dc3545; color: white; padding: 0.4em 0.8em; border-radius: 50px; font-size: 0.85em; }
    .badge-returned { background: #17a2b8; color: white; padding: 0.4em 0.8em; border-radius: 50px; font-size: 0.85em; }
    #borrowerDropdown, #itemSearchDropdown { background: white; border: 1px solid #ddd; border-radius: 8px; }
    .dropdown-item { padding: 10px 15px; cursor: pointer; }
    .dropdown-item:hover { background: #f0f8ff; }
</style>

<script>
// Global variables
let residents = [], inventory = [], allTransactions = [], selectedItems = [];
let currentReturnData = [];

$(document).ready(function() {
    loadResidents();
    loadInventory();
    loadAllTransactions();

    const today = new Date().toISOString().split('T')[0];
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 7);
    $('#return_date').attr('max', maxDate.toISOString().split('T')[0]);

    // Borrower search
    $('#borrowerSearch').on('input', debounce(function() {
        const query = this.value.trim().toLowerCase();
        const $dd = $('#borrowerDropdown').empty();
        if (!query) { $dd.hide(); return; }
        const matches = residents.filter(r => 
            r.full_name.toLowerCase().includes(query) ||
            r.house_number.toLowerCase().includes(query) ||
            r.street.toLowerCase().includes(query)
        ).slice(0, 15);

        if (matches.length === 0) {
            $dd.append('<div class="dropdown-item text-muted">No resident found</div>').show();
            return;
        }

        matches.forEach(r => {
            const addr = `${r.house_number} ${r.street}, ${r.municipality}`;
            $dd.append(`
                <div class="dropdown-item" onclick="selectBorrower(${r.id}, '${escapeHtml(r.full_name)}', '${escapeHtml(addr)}')">
                    <strong>${escapeHtml(r.full_name)}</strong><br>
                    <small class="text-muted">${addr}</small>
                </div>
            `);
        });
        $dd.show();
    }, 300));

    // Item search
    $('#itemSearchInput').on('input', debounce(function() {
        const query = this.value.trim().toLowerCase();
        const $dd = $('#itemSearchDropdown').empty();
        if (!query) { $dd.hide(); return; }
        const matches = inventory.filter(i => i.item_name.toLowerCase().includes(query) && parseInt(i.current_stock) > 0);
        if (matches.length === 0) {
            $dd.append('<div class="dropdown-item text-muted">No available items</div>').show();
            return;
        }
        matches.forEach(item => {
            $dd.append(`
                <div class="dropdown-item" onclick="addItemToBorrow(${item.id}, '${escapeHtml(item.item_name)}', ${item.current_stock})">
                    <strong>${escapeHtml(item.item_name)}</strong>
                    <span class="badge bg-secondary float-end">Stock: ${item.current_stock}</span>
                </div>
            `);
        });
        $dd.show();
    }, 300));

    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#borrowerSearch, #borrowerDropdown, #itemSearchInput, #itemSearchDropdown').length) {
            $('#borrowerDropdown, #itemSearchDropdown').hide();
        }
    });

    // Payment total calculation
    $('#payment_qty, #payment_amount_paid').on('input', function() {
        const qty = parseInt($('#payment_qty').val()) || 0;
        const unit = parseFloat($('#payment_declared_value').val()) || 0;
        $('#payment_total_due').val((qty * unit).toFixed(2));
    });
});

function loadResidents() {
    $.get('partials/get_residents_up.php', function(data) {
        residents = data || [];
    });
}

function loadInventory() {
    $.post('partials/inventory_management_api.php', { action: 'fetch_inventory', limit: 999 }, function(r) {
        if (r.status === 'success') {
            inventory = r.data.items.filter(i => parseInt(i.current_stock) > 0);
        }
    }, 'json');
}

function loadAllTransactions() {
    $.post('partials/inventory_management_api.php', { action: 'fetch_all_transactions' }, function(r) {
        if (r.status === 'success') {
            allTransactions = r.data.map(t => ({
                ...t,
                declared_value: parseFloat(t.declared_value) || 0
            }));
            updateTables();
        }
    }, 'json');
}

function updateTables() {
    // === ACTIVE BORROWS (with correct remaining qty) ===
    const activeBorrows = allTransactions.filter(t => 
        t.action_type === 'Borrow' && 
        (!t.returned_date || t.returned_date === '0000-00-00')
    );

    const grouped = {};
    activeBorrows.forEach(borrow => {
        const returns = allTransactions.filter(t => 
            t.inventory_id == borrow.inventory_id &&
            t.borrower_id == borrow.borrower_id &&
            t.action_type === 'Return'
        ).reduce((sum, r) => sum + parseInt(r.quantity), 0);

        const broken = allTransactions.filter(t => 
            t.inventory_id == borrow.inventory_id &&
            t.borrower_id == borrow.borrower_id &&
            t.action_type === 'Broken'
        ).reduce((sum, r) => sum + parseInt(r.quantity), 0);

        const replaced = allTransactions.filter(t => 
            t.inventory_id == borrow.inventory_id &&
            t.borrower_id == borrow.borrower_id &&
            t.action_type === 'Replace'
        ).reduce((sum, r) => sum + parseInt(r.quantity), 0);

        const remaining = borrow.quantity - returns - broken - replaced;
        // if (remaining <= 0) return; // fully settled → skip

        const key = `${borrow.borrower_name}|${borrow.borrower_id}`;
        if (!grouped[key]) {
            grouped[key] = {
                name: borrow.borrower_name,
                id: borrow.borrower_id,
                items: [],
                latestReturn: borrow.return_date
            };
        }
        grouped[key].items.push({ ...borrow, remaining });
    });

    const $activeBody = $('#activeTableBody').empty();
    if (Object.keys(grouped).length === 0) {
        $activeBody.append('<tr><td colspan="7" class="text-center py-5 text-muted">No active borrowed items</td></tr>');
    } else {
        Object.values(grouped).forEach(g => {
            const totalRemaining = g.items.reduce((s, i) => s + i.remaining, 0);
            const status = getBorrowStatus(g.latestReturn);
            $activeBody.append(`
                <tr>
                    <td>${status}</td>
                    <td><strong>${escapeHtml(g.name)}</strong></td>
                    <td><small>${getResidentAddress(g.id)}</small></td>
                    <td><span class="badge bg-info">${g.items.length} item(s)<br>${totalRemaining} pcs left</span></td>
                    <td>${formatDate(g.items[0].transaction_date)}</td>
                    <td>${formatDate(g.latestReturn)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="viewBorrowedItems('${escapeHtml(g.name)}', ${g.id || 'null'})">View</button>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="viewBorrowedItems('${escapeHtml(g.name)}', ${g.id || 'null'})">Edit</button>
                        <button class="btn btn-sm btn-success me-1" onclick="openReturnModal('${escapeHtml(g.name)}', ${g.id || 'null'})">Return</button>
                        <button class="btn btn-sm btn-info me-1" onclick="openReplaceModal(${g.items[0].id}, '${escapeHtml(g.name)}', '${escapeHtml(g.items[0].item_name)}', ${totalRemaining})">Replace</button>
                        <button class="btn btn-sm btn-warning" onclick="openPaymentModal(${g.items[0].id}, '${escapeHtml(g.name)}', '${escapeHtml(g.items[0].item_name)}', ${totalRemaining}, ${g.items[0].declared_value})">Pay</button>
                    </td>
                </tr>
            `);
        });
    }

    // History table stays exactly the same
    const $historyBody = $('#historyTableBody').empty();
    
    allTransactions.forEach(t => {
        const name = (t.borrower_name || '').trim().toLowerCase();
        if (name === 'unknown' || name === '') return; // skip rendering

        const isFullyReturned = t.action_type === 'Borrow' && t.returned_date && t.returned_date !== '0000-00-00';
        const rowClass = isFullyReturned ? 'table-success' : (t.action_type === 'Borrow' ? 'table-warning' : '');
        const statusBadge = isFullyReturned 
            ? '<span class="badge badge-returned">RETURNED</span>' 
            : (t.action_type === 'Borrow' ? getBorrowStatus(t.return_date) : '');

        $historyBody.append(`
            <tr class="${rowClass}">
                <td><strong>${t.action_type === 'Borrow' ? 'Borrowed' : t.action_type}</strong></td>
                <td>${escapeHtml(t.borrower_name || '—')}</td>
                <td>${escapeHtml(t.item_name)}</td>
                <td><strong>${t.quantity}</strong></td>
                <td><strong>${t.borrowed_quantity}</strong></td>
                <td>${formatDate(t.transaction_date)}</td>
                <td>${t.returned_date && t.returned_date !== '0000-00-00' 
                    ? formatDate(t.returned_date) 
                    : '<span class="text-danger">Not Returned</span>'}</td>
            </tr>
        `);
    });
}

function viewBorrowedItems(borrowerName, borrowerId) {
    const borrows = allTransactions.filter(t => 
        t.action_type === 'Borrow' && 
        !t.returned_date && 
        (t.borrower_id == borrowerId || t.borrower_name === borrowerName)
    );

    $('#view_borrower_name').text(borrowerName);
    $('#view_address').text(getResidentAddress(borrowerId));

    const tbody = $('#viewItemsBody').empty();
    borrows.forEach(t => {
        tbody.append(`
            <tr>
                <td>${escapeHtml(t.item_name)}</td>
                <td><strong>${t.quantity}</strong></td>
                <td>${formatDate(t.transaction_date)}</td>
                <td>${formatDate(t.return_date)}</td>
            </tr>
        `);
    });

    new bootstrap.Modal(document.getElementById('viewModal')).show();
}

function getBorrowStatus(returnDateStr) {
    const returnDate = new Date(returnDateStr);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    returnDate.setHours(0, 0, 0, 0);
    const diffDays = Math.ceil((returnDate - today) / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return '<span class="badge badge-overdue">OVERDUE</span>';
    if (diffDays <= 1) return '<span class="badge badge-due">DUE SOON</span>';
    return `<span class="badge badge-not-due">NOT DUE</span><br><small>${diffDays} day(s) left</small>`;
}

function getResidentAddress(id) {
    console.log('Getting address for resident ID:', id);
    if (!id) return '—';
    const r = residents.find(x => x.id == id);
    return r ? `${r.house_number} ${r.street}, ${r.municipality}` : '—';
}

function selectBorrower(id, name, address) {
    $('#borrowerId').val(id);
    $('#selectedBorrower').html(`<strong class="text-success">${name}</strong><br><small>${address}</small>`);
    $('#borrowerSearch').val(name);
    $('#borrowerDropdown').hide();
}

function addItemToBorrow(id, name, stock) {
    if (selectedItems.find(x => x.id == id)) {
        alert('Item already added!');
        return;
    }
    selectedItems.push({ id, name, stock });
    renderSelectedItems();
    $('#itemSearchInput').val('').focus();
    $('#itemSearchDropdown').hide();
}

function removeItemFromBorrow(id) {
    selectedItems = selectedItems.filter(x => x.id != id);
    renderSelectedItems();
}

function renderSelectedItems() {
    const tbody = $('#selectedItemsTable tbody').empty();
    if (selectedItems.length === 0) {
        tbody.append('<tr><td colspan="4" class="text-center text-muted py-3">No items selected</td></tr>');
        return;
    }
    selectedItems.forEach(item => {
        tbody.append(`
            <tr data-id="${item.id}">
                <td>${escapeHtml(item.name)}</td>
                <td><span class="badge bg-secondary">${item.stock}</span></td>
                <td><input type="number" class="form-control form-control-sm qty-input" min="1" max="${item.stock}" value="1"></td>
                <td><button class="btn btn-sm btn-danger" onclick="removeItemFromBorrow(${item.id})">Remove</button></td>
            </tr>
        `);
    });
}

function openBorrowModal() {
    selectedItems = [];
    $('#borrowerSearch, #itemSearchInput').val('');
    $('#selectedBorrower').empty();
    $('#borrowerId').val('');
    renderSelectedItems();
    new bootstrap.Modal(document.getElementById('borrowModal')).show();
}

function saveBorrow() {
    const borrowerId = $('#borrowerId').val();
    if (!borrowerId) return alert('Please select a borrower');

    const items = [];
    $('#selectedItemsTable tbody tr').each(function() {
        const id = $(this).data('id');
        if (id) {
            const qty = parseInt($(this).find('.qty-input').val()) || 1;
            if (qty > 0) items.push({ inventory_id: id, quantity: qty });
        }
    });

    if (items.length === 0) return alert('Please add at least one item');
    if (!$('#return_date').val()) return alert('Please set return date');

    $.post('partials/inventory_management_api.php', {
        action: 'borrow_multiple',
        borrower_id: borrowerId,
        borrower_name: $('#borrowerSearch').val(),
        items: JSON.stringify(items),
        transaction_date: $('#borrow_date_input').val(),
        transacted_by: '<?= htmlspecialchars($loggedInUserName) ?>',
        return_date: $('#return_date').val(),
        remarks: $('[name=remarks]').val()
    }, function(r) {
        if (r.status === 'success') {
            $('#borrowModal').modal('hide');
            loadAllTransactions();
            alert('The equipment(s) successfully borrowed');
        } else {
            alert('Error: ' + r.message);
        }
    }, 'json');
}

function openReturnModal(borrowerName, borrowerId) {
    $('#return_borrower_name').text(borrowerName);
    currentReturnData = allTransactions
        .filter(t => t.action_type === 'Borrow' && !t.returned_date && (t.borrower_id == borrowerId || t.borrower_name === borrowerName))
        .map(t => ({ ...t, good: t.quantity, damaged: 0 }));

    const tbody = $('#returnItemsTable tbody').empty();
    currentReturnData.forEach(item => {
        tbody.append(`
            <tr>
                <td>${escapeHtml(item.item_name)}</td>
                <td>${item.quantity}</td>
                <td><input type="number" class="form-control form-control-sm good-qty" min="0" max="${item.quantity}" value="${item.quantity}" data-id="${item.id}"></td>
                <td><input type="number" class="form-control form-control-sm damaged-qty" min="0" max="${item.quantity}" value="0" data-id="${item.id}"></td>
                <td><input type="number" class="form-control form-control-sm damaged-qty" min="0" max="${item.quantity}" value="0" data-id="${item.id}"></td>
            </tr>
        `);
    });

    $('#returnItemsTable').off('input').on('input', '.good-qty, .damaged-qty', function() {
        const id = $(this).data('id');
        const good = parseInt($(this).closest('tr').find('.good-qty').val()) || 0;
        const damaged = parseInt($(this).closest('tr').find('.damaged-qty').val()) || 0;
        const total = good + damaged;
        const max = currentReturnData.find(x => x.id == id).quantity;
        console.log(total);
        if (total > max) {
            $(this).val(max - (this.classList.contains('good-qty') ? damaged : good));
        }
    });

    new bootstrap.Modal(document.getElementById('returnModal')).show();
}

function saveReturn() {
    const returns = [];
    $('#returnItemsTable tbody tr').each(function() {
        const id = $(this).find('.good-qty').data('id');
        const good = parseInt($(this).find('.good-qty').val()) || 0;
        const damaged = parseInt($(this).find('.damaged-qty').val()) || 0;
        if (good + damaged > 0) {
            returns.push({ transaction_id: id, return_qty: good, damaged_qty: damaged });
        }
    });

    if (returns.length === 0) return alert('No items to return');

    $.post('partials/inventory_management_api.php', {
        action: 'return_multiple',
        returns: JSON.stringify(returns),
        remarks: $('#return_remarks').val()
    }, function(r) {
        if (r.status === 'success') {
            $('#returnModal').modal('hide');
            loadAllTransactions();
            alert('Return processed successfully!');
        } else {
            alert('Error: ' + r.message);
        }
    }, 'json');
}

function openReplaceModal(transId, borrower, item, maxQty) {
    $('#replace_transaction_id').val(transId);
    $('#replace_borrower_name').val(borrower);
    $('#replace_item_name').val(item);
    $('#replace_qty').val(1).attr('max', maxQty);
    $('#replace_remarks').val('');
    new bootstrap.Modal(document.getElementById('replaceModal')).show();
}

function saveReplacement() {
    const qty = $('#replace_qty').val();
    if (!qty || qty <= 0) return alert('Enter valid quantity');

    $.post('partials/inventory_management_api.php', {
        action: 'record_replacement',
        transaction_id: $('#replace_transaction_id').val(),
        quantity: qty,
        reason: $('#replace_reason').val(),
        remarks: $('#replace_remarks').val()
    }, function(r) {
        if (r.status === 'success') {
            $('#replaceModal').modal('hide');
            loadAllTransactions();
            alert('Replacement recorded!');
        } else {
            alert('Error: ' + r.message);
        }
    }, 'json');
}

function openPaymentModal(transId, borrower, item, maxQty, declaredValue) {
    $('#payment_transaction_id').val(transId);
    $('#payment_borrower_name').val(borrower);
    $('#payment_item_name').val(item);
    $('#payment_qty').val(1).attr('max', maxQty);
    $('#payment_declared_value').val(declaredValue);
    $('#payment_unit_value').val(parseFloat(declaredValue).toFixed(2));
    $('#payment_total_due').val((1 * declaredValue).toFixed(2));
    $('#payment_amount_paid').val('');
    $('#payment_reason').val('');
    $('#payment_remarks').val('');
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function savePayment() {
    const qty = $('#payment_qty').val();
    const amount = $('#payment_amount_paid').val();
    const reason = $('#payment_reason').val();
    const remarks = $('#payment_remarks').val().trim();

    if (!qty || qty <= 0 || !amount || amount <= 0 || !reason) {
        alert('Please fill in all required fields (Qty, Amount Paid, Reason)');
        return;
    }

    const finalRemarks = remarks ? `${reason}: ${remarks}` : reason;

    $.post('partials/inventory_management_api.php', {
        action: 'record_payment',
        transaction_id: $('#payment_transaction_id').val(),
        quantity: qty,
        amount_paid: amount,
        reason: reason,
        remarks: finalRemarks
    }, function(r) {
        if (r.status === 'success') {
            $('#paymentModal').modal('hide');
            loadAllTransactions();
            alert('Payment recorded successfully!');
        } else {
            alert('Error: ' + r.message);
        }
    }, 'json');
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Set date restrictions when Borrow Modal opens
$('#borrowModal').on('shown.bs.modal', function () {
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];

    // Set min date to today (no past dates)
    $('#borrow_date_input').attr('min', todayStr).val(todayStr);

    // Calculate max return date: today + 7 days
    const maxReturn = new Date();
    maxReturn.setDate(today.getDate() + 7);
    const maxReturnStr = maxReturn.toISOString().split('T')[0];

    $('#return_date').attr({
        'min': todayStr,
        'max': maxReturnStr
    });

    // Auto-update return_date max when borrow_date changes
    $('#borrow_date_input').on('change', function () {
        const borrowDate = new Date(this.value);
        if (isNaN(borrowDate)) return;

        const maxAllowed = new Date(borrowDate);
        maxAllowed.setDate(borrowDate.getDate() + 7);
        const maxStr = maxAllowed.toISOString().split('T')[0];

        $('#return_date').attr({
            'min': this.value,
            'max': maxStr
        });

        // If current return_date is invalid, reset it
        if ($('#return_date').val() && $('#return_date').val() < this.value) {
            $('#return_date').val(this.value);
        }
    });
 
});
</script>