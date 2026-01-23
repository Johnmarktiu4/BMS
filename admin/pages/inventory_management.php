
<?php
require_once 'partials/db_conn.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="fas fa-boxes me-2"></i>Inventory Management</h2>
                    <p class="text-muted mb-0">Manage and track barangay inventory items</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus me-2"></i>Add Item
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-secondary btn-lg dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-export me-2"></i>Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportInventory('csv')"><i class="fas fa-file-csv me-2"></i>CSV</a></li>
                            <li><a class="dropdown-item" href="#" onclick="printTable()"><i class="fas fa-print me-2"></i>Print</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="inventoryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="inventory-list-tab" data-bs-toggle="tab" data-bs-target="#inventory-list" type="button" role="tab" aria-controls="inventory-list" aria-selected="true">Inventory List</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stock-monitoring-tab" data-bs-toggle="tab" data-bs-target="#stock-monitoring" type="button" role="tab" aria-controls="stock-monitoring" aria-selected="false">Stock Monitoring</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="transaction-history-tab" data-bs-toggle="tab" data-bs-target="#transaction-history" type="button" role="tab" aria-controls="transaction-history" aria-selected="false">Transaction History</button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="inventoryTabContent">
        <!-- Inventory List Tab -->
        <div class="tab-pane fade show active" id="inventory-list" role="tabpanel" aria-labelledby="inventory-list-tab">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4 col-sm-6">
                                    <label for="searchInput" class="form-label">Search Items</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="searchInput" placeholder="Search by item name...">
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-6">
                                    <label for="statusFilter" class="form-label">Status</label>
                                    <select class="form-select" id="statusFilter">
                                        <option value="">All</option>
                                        <option value="In Stock">In Stock</option>
                                        <option value="Out of Stock">Out of Stock</option>
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
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-table me-2"></i>Inventory List</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="inventoryTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Current Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inventoryTableBody">
                                        <!-- Data will be populated via AJAX -->
                                    </tbody>
                                </table>
                            </div>
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

        <!-- Stock Monitoring Tab -->
        <div class="tab-pane fade" id="stock-monitoring" role="tabpanel" aria-labelledby="stock-monitoring-tab">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Stock Monitoring</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="stockMonitoringTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Description</th>
                                            <th>Qty on Hand</th>
                                            <th>Qty Received</th>
                                            <th>Qty Lost</th>
                                            <th>Qty Damaged</th>
                                            <th>Qty Replaced</th>
                                            <th>Current Stock</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody id="stockMonitoringTableBody">
                                        <!-- Data will be populated via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History Tab -->
        <div class="tab-pane fade" id="transaction-history" role="tabpanel" aria-labelledby="transaction-history-tab">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Transaction History</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="transactionHistoryTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Action</th>
                                            <th>Quantity</th>
                                            <th>Transacted By</th>
                                            <th>Transaction Date</th>
                                            <th>Return Date</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transactionHistoryTableBody">
                                        <!-- Data will be populated via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title" id="addItemModalLabel">
                    <i class="fas fa-box-open me-2"></i>Add New Item
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="addItemForm">
                    <input type="hidden" id="itemId" name="id">
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-box me-2"></i>Item Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="itemName" class="form-label">Item Name *</label>
                                    <input type="text" class="form-control" id="itemName" name="item_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="qtyReceived" class="form-label">Quantity Received *</label>
                                    <input type="number" class="form-control" id="qtyReceived" name="qty_received" min="0" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
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
                <button type="button" class="btn btn-primary" id="saveItemBtn" onclick="saveItem()">
                    <i class="fas fa-save me-1"></i>Save Item
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title" id="transactionModalLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Record Transaction
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="transactionForm">
                    <input type="hidden" id="transactionItemId" name="inventory_id">
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Transaction Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="actionType" class="form-label">Action Type *</label>
                                    <select class="form-select" id="actionType" name="action_type" required>
                                        <option value="Borrow">Borrow</option>
                                        <option value="Return">Return</option>
                                        <option value="Broken">Broken</option>
                                        <option value="Replace">Replace</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="transactionQuantity" class="form-label">Quantity *</label>
                                    <input type="number" class="form-control" id="transactionQuantity" name="quantity" min="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="transactedBy" class="form-label">Transacted By</label>
                                    <input type="text" class="form-control" id="transactedBy" name="transacted_by">
                                </div>
                                <div class="col-md-6">
                                    <label for="transactionDate" class="form-label">Transaction Date *</label>
                                    <input type="date" class="form-control" id="transactionDate" onkeydown="return false" name="transaction_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="returnDate" class="form-label">Return Date</label>
                                    <input type="date" class="form-control" id="returnDate" onkeydown="return false" name="return_date">
                                </div>
                                <div class="col-md-12">
                                    <label for="transactionRemarks" class="form-label">Remarks</label>
                                    <textarea class="form-control" id="transactionRemarks" name="remarks" rows="3"></textarea>
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
                <button type="button" class="btn btn-primary" id="saveTransactionBtn" onclick="saveTransaction()">
                    <i class="fas fa-save me-1"></i>Save Transaction
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let entriesPerPage = 10;

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

function loadInventory(page = 1) {
    const search = $('#searchInput').val();
    const status = $('#statusFilter').val();
    entriesPerPage = parseInt($('#entriesSelect').val()) || 10;
    currentPage = page;

    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: {
            action: 'fetch_inventory',
            page: page,
            limit: entriesPerPage,
            search: search,
            status: status
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                updateInventoryTable(response.data.items);
                updatePagination(response.data.pagination);
            } else {
                showAlert('danger', response.message || 'Failed to load inventory.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error loading inventory: ' + error);
        }
    });
}

function loadStockMonitoring() {
    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: { action: 'fetch_stock_monitoring' },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                updateStockMonitoringTable(response.data);
            } else {
                showAlert('danger', response.message || 'Failed to load stock monitoring.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error loading stock monitoring: ' + error);
        }
    });
}

function loadTransactionHistory() {
    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: { action: 'fetch_transactions' },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                updateTransactionHistoryTable(response.data);
            } else {
                showAlert('danger', response.message || 'Failed to load transaction history.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error loading transaction history: ' + error);
        }
    });
}

function updateInventoryTable(items) {
    const tbody = $('#inventoryTableBody');
    tbody.empty();
    if (items.length === 0) {
        tbody.append('<tr><td colspan="4" class="text-center">No items found.</td></tr>');
        return;
    }
    items.forEach(item => {
        const borrowed_qty = item.borrowed_qty || 0;
        const returnDisabled = borrowed_qty <= 0 ? 'disabled' : '';
        const row = `
            <tr>
                <td><strong>${item.item_name}</strong></td>
                <td>${item.current_stock}</td>
                <td><span class="badge ${item.status === 'In Stock' ? 'bg-success' : 'bg-danger'}">${item.status}</span></td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" title="Edit" onclick="editItem(${item.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success" title="Borrow" onclick="openTransactionModal(${item.id}, 'Borrow')">
                            <i class="fas fa-hand-holding"></i>
                        </button>
                        <button type="button" class="btn btn-outline-info ${returnDisabled}" title="Return" onclick="openTransactionModal(${item.id}, 'Return')">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" title="Broken" onclick="openTransactionModal(${item.id}, 'Broken')">
                            <i class="fas fa-exclamation-circle"></i>
                        </button>
                        <button type="button" class="btn btn-outline-warning" title="Archive" onclick="archiveItem(${item.id})">
                            <i class="fas fa-archive"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        tbody.append(row);
    });
}

function updateStockMonitoringTable(items) {
    const tbody = $('#stockMonitoringTableBody');
    tbody.empty();
    if (items.length === 0) {
        tbody.append('<tr><td colspan="9" class="text-center">No items found.</td></tr>');
        return;
    }
    items.forEach(item => {
        const row = `
            <tr>
                <td><strong>${item.item_name}</strong></td>
                <td>${item.description || '-'}</td>
                <td>${item.qty_on_hand}</td>
                <td>${item.qty_received}</td>
                <td>${item.qty_lost}</td>
                <td>${item.qty_damaged}</td>
                <td>${item.qty_replaced}</td>
                <td>${item.current_stock}</td>
                <td>${item.remarks || '-'}</td>
            </tr>`;
        tbody.append(row);
    });
}

function updateTransactionHistoryTable(transactions) {
    const tbody = $('#transactionHistoryTableBody');
    tbody.empty();
    if (transactions.length === 0) {
        tbody.append('<tr><td colspan="7" class="text-center">No transactions found.</td></tr>');
        return;
    }
    transactions.forEach(txn => {
        const row = `
            <tr>
                <td><strong>${txn.item_name}</strong></td>
                <td>${txn.action_type}</td>
                <td>${txn.quantity}</td>
                <td>${txn.transacted_by || '-'}</td>
                <td>${txn.transaction_date}</td>
                <td>${txn.return_date || '-'}</td>
                <td>${txn.remarks || '-'}</td>
            </tr>`;
        tbody.append(row);
    });
}

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
            <a class="page-link" href="#" onclick="if (!$(this).parent().hasClass('disabled')) loadInventory(${pagination.current_page - 1})">Previous</a>
        </li>
    `);
    for (let i = 1; i <= pagination.total_pages; i++) {
        paginationControls.append(`
            <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadInventory(${i})">${i}</a>
            </li>
        `);
    }
    paginationControls.append(`
        <li class="page-item ${nextDisabled}">
            <a class="page-link" href="#" onclick="if (!$(this).parent().hasClass('disabled')) loadInventory(${pagination.current_page + 1})">Next</a>
        </li>
    `);
}

function saveItem() {
    const form = $('#addItemForm');
    const formData = new FormData(form[0]);
    const isEdit = $('#itemId').val() !== '';
    formData.append('action', isEdit ? 'update_item' : 'add_item');

    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }

    const saveBtn = $('#saveItemBtn');
    const originalText = saveBtn.html();
    saveBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...').prop('disabled', true);

    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            saveBtn.html(originalText).prop('disabled', false);
            if (response.status === 'success') {
                $('#addItemModal').modal('hide');
                form[0].reset();
                showAlert('success', isEdit ? 'Item updated successfully!' : 'Item added successfully!');
                loadInventory(currentPage);
                loadStockMonitoring();
            } else {
                showAlert('danger', response.message || 'Failed to save item.');
            }
        },
        error: function(xhr, status, error) {
            saveBtn.html(originalText).prop('disabled', false);
            showAlert('danger', 'Error saving item: ' + error);
        }
    });
}

function editItem(id) {
    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: { action: 'get_item', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.data) {
                const item = response.data;
                $('#itemId').val(item.id);
                $('#itemName').val(item.item_name);
                $('#description').val(item.description || '');
                $('#qtyReceived').val(item.qty_received);
                $('#remarks').val(item.remarks || '');
                $('#addItemModalLabel').html('<i class="fas fa-box-open me-2"></i>Edit Item');
                $('#addItemModal').modal('show');
            } else {
                showAlert('danger', response.message || 'Failed to load item details.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error loading item: ' + error);
        }
    });
}

function openTransactionModal(id, actionType) {
    $('#transactionItemId').val(id);
    $('#actionType').val(actionType);
    $('#transactionQuantity').val('');
    $('#transactedBy').val('');
    $('#transactionDate').val(new Date().toISOString().split('T')[0]);
    $('#returnDate').val('');
    $('#transactionRemarks').val('');
    $('#transactionModalLabel').html(`<i class="fas fa-exchange-alt me-2"></i>${actionType} Item`);
    $('#transactionModal').modal('show');
}

function saveTransaction() {
    const form = $('#transactionForm');
    const formData = new FormData(form[0]);
    formData.append('action', 'add_transaction');

    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }

    const saveBtn = $('#saveTransactionBtn');
    const originalText = saveBtn.html();
    saveBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...').prop('disabled', true);

    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            saveBtn.html(originalText).prop('disabled', false);
            if (response.status === 'success') {
                $('#transactionModal').modal('hide');
                form[0].reset();
                showAlert('success', 'Transaction recorded successfully!');
                loadInventory(currentPage);
                loadStockMonitoring();
                loadTransactionHistory();
            } else {
                showAlert('danger', response.message || 'Failed to record transaction.');
            }
        },
        error: function(xhr, status, error) {
            saveBtn.html(originalText).prop('disabled', false);
            showAlert('danger', 'Error saving transaction: ' + error);
        }
    });
}

function archiveItem(id) {
    if (!confirm('Are you sure you want to archive this item? This action cannot be undone.')) return;

    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: { action: 'archive_item', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                showAlert('success', 'Item archived successfully!');
                loadInventory(currentPage);
                loadStockMonitoring();
            } else {
                showAlert('danger', response.message || 'Failed to archive item.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error archiving item: ' + error);
        }
    });
}

function clearFilters() {
    $('#searchInput').val('');
    $('#statusFilter').val('');
    $('#entriesSelect').val('10');
    entriesPerPage = 10;
    currentPage = 1;
    loadInventory();
}

$(document).ready(function() {
    loadInventory();
    loadStockMonitoring();
    loadTransactionHistory();

    $('#searchInput').on('keyup', function() { loadInventory(); });
    $('#statusFilter').on('change', function() { loadInventory(); });
    $('#entriesSelect').on('change', function() { loadInventory(); });

    $('#addItemForm').on('submit', function(e) {
        e.preventDefault();
        saveItem();
    });

    $('#transactionForm').on('submit', function(e) {
        e.preventDefault();
        saveTransaction();
    });

    $('#addItemModal').on('hidden.bs.modal', function() {
        $('#addItemForm')[0].reset();
        $('#itemId').val('');
        $('#addItemModalLabel').html('<i class="fas fa-box-open me-2"></i>Add New Item');
        $(this).find('.form-control, .form-select').removeClass('is-valid is-invalid');
    });

    $('#transactionModal').on('hidden.bs.modal', function() {
        $('#transactionForm')[0].reset();
        $('#transactionItemId').val('');
        $('#transactionModalLabel').html('<i class="fas fa-exchange-alt me-2"></i>Record Transaction');
        $(this).find('.form-control, .form-select').removeClass('is-valid is-invalid');
    });

    const requiredInputs = $('#addItemForm [required], #transactionForm [required]');
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

    $('#qtyReceived, #transactionQuantity').on('input', function() {
        let value = parseInt(this.value);
        if (value < 0) this.value = 0;
    });
});

function exportInventory(format) {
    if (format !== 'csv') return;
    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: {
            action: 'fetch_inventory',
            page: 1,
            limit: 9999,
            search: $('#searchInput').val(),
            status: $('#statusFilter').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const items = response.data.items;
                let csv = 'Item Name,Current Stock,Status\n';
                items.forEach(item => {
                    const rowData = [
                        item.item_name,
                        item.current_stock,
                        item.status
                    ].map(field => `"${field}"`);
                    csv += rowData.join(',') + '\n';
                });
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'inventory.csv';
                link.click();
                window.URL.revokeObjectURL(url);
            } else {
                showAlert('danger', 'Failed to export inventory.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error exporting inventory: ' + error);
        }
    });
}

function printTable() {
    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: {
            action: 'fetch_inventory',
            page: 1,
            limit: 9999,
            search: $('#searchInput').val(),
            status: $('#statusFilter').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const items = response.data.items;
                let tableHtml = `
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>`;
                items.forEach(item => {
                    tableHtml += `
                        <tr>
                            <td>${item.item_name}</td>
                            <td>${item.current_stock}</td>
                            <td>${item.status}</td>
                        </tr>`;
                });
                tableHtml += '</tbody></table>';

                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Inventory List</title>
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
                        <h3 class="text-center mb-4">Inventory List</h3>
                        <div class="table-responsive">${tableHtml}</div>
                    </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.focus();
                setTimeout(() => printWindow.print(), 500);
            } else {
                showAlert('danger', 'Failed to print inventory.');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error printing inventory: ' + error);
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

.nav-tabs .nav-link {
    border-radius: 0.375rem 0.375rem 0 0;
}

.nav-tabs .nav-link.active {
    background-color: #0d6efd;
    color: white;
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
