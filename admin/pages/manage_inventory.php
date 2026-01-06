<?php
// admin/pages/manage_inventory.php
require_once 'partials/db_conn.php';
?>
<div class="container-fluid py-4 px-3 px-md-5">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="mb-0"><i class="fas fa-boxes me-2"></i>Manage Inventory</h2>
                    <p class="text-muted mb-0">Add and monitor barangay inventory items</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success btn-md action-btn" data-bs-toggle="modal" data-bs-target="#stockMonitoringModal">
                        <i class="fas fa-chart-line me-2"></i>Stock Monitoring
                    </button>
                    <button class="btn btn-success btn-md action-btn" data-bs-toggle="modal" data-bs-target="#stockInModal">
                        <i class="fas fa-plus-circle me-2"></i>Stock In
                    </button>
                    <button class="btn btn-warning btn-md action-btn" data-bs-toggle="modal" data-bs-target="#stockOutModal">
                        <i class="fas fa-minus-circle me-2"></i>Stock Out
                    </button>
                    <button class="btn btn-success btn-md action-btn" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-box me-2"></i>Add Item
                    </button>
                    <button class="btn btn-success btn-md action-btn" onclick="window.open('partials/generate_inventory_report.php', '_blank')">
                        <i class="fas fa-file-pdf me-2"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Monitoring Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Inventory</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="stockMonitoringTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Item Name</th>
                                    <th>Description</th>
                                    <th>Current Stock</th>
                                    <th>Received</th>
                                    <th>Lost</th>
                                    <th>Damaged</th>
                                    <th>Replaced</th>
                                    <th>Declared Value (per unit)</th>
                                    <th>Remarks</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="stockMonitoringTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="addItemModalLabel"><i class="fas fa-box me-2"></i>Add New Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <form id="addItemForm">
                        <input type="hidden" id="itemId" name="id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Item Name *</label>
                                <input type="text" class="form-control" id="itemName" name="item_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Declared Value (per unit)</label>
                                <input type="number" step="0.01" class="form-control" id="declaredValue" name="declared_value" min="0">
                            </div>
                            <div class="col-md-6">
                                <label for="withExpiration" class="form-label">With Expiration? *</label>
                                <select class="form-select" id="withExpiration" name="with_expiration" required>
                                    <option value="">Select</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <p class="text-muted mb-3">Do you wish to stock in this item now?</p>
                            <button type="button" class="btn btn-success px-4" id="proceedToStockIn">Yes, Stock In</button>
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">No, Save Only</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock In Modal -->
    <div class="modal fade" id="stockInModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg">
                <div class="modal-header bg-success text-white border-0">
                    <h5><i class="fas fa-plus-circle me-2"></i>Stock In Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="stockInForm">
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-bold">Search Items</label>
                            <input type="text" class="form-control form-control-lg" id="itemSearchInput" placeholder="Type item name to add..." autocomplete="off">
                            <div id="itemSearchDropdown" class="shadow border rounded position-absolute top-100 start-0 w-100" style="z-index: 2000; max-height: 300px; overflow-y: auto; display: none; background: white;"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Item *</label>
                            <input type="text" class="form-control" id="itemmss" name="itemmss" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="acquisitionType" class="form-label">Acquisition Type *</label>
                            <select class="form-select" id="acquisitionType" name="acquisition_type" onchange="isDonatedBy()" required>
                                <option value="">Select</option>
                                <option value="Purchase">Purchase</option>
                                <option value="Donation">Donation</option>
                            </select>
                        </div>
                        <div id="donatedByField" style="display : none;">
                            <label class="form-label">Donated By </label>
                            <input type="text" class="form-control" id="donatedBy" name="donated_by" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity to Add *</label>
                            <input type="number" class="form-control" name="quantity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Date</label>
                            <input type="date" class="form-control" name="transaction_date" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div id="expirationByField" style="display : none;">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" class="form-control"id="expiration_date" name="expiration_date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success px-4" onclick="performStockIn()">Confirm Stock In</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Monitoring Modal -->
    <div class="modal fade" id="stockMonitoringModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg">
                <div class="modal-header bg-success text-white border-0">
                    <h5><i class="fas fa-plus-circle me-2"></i>Stock Monitoring</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="stockInOutMonitoringTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Item Name</th>
                                    <th>Description</th>
                                    <th>Movement Type</th>
                                    <th>Quantity</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div><small class="text-muted" id="paginationInfo"></small></div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Out Modal -->
    <div class="modal fade" id="stockOutModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5><i class="fas fa-minus-circle me-2"></i>Stock Out / Replace</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="stockOutForm">
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-bold">Search Items</label>
                            <input type="text" class="form-control form-control-lg" id="itemSearchInput2" placeholder="Type item name to add..." autocomplete="off">
                            <div id="itemSearchDropdown2" class="shadow border rounded position-absolute top-100 start-0 w-100" style="z-index: 2000; max-height: 300px; overflow-y: auto; display: none; background: white;"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Item *</label>
                            <input type="text" class="form-control" id="itemmssOut" name="itemmssOut" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Action Type *</label>
                            <select class="form-select" name="action_type" required>
                                <option value="Lost">Lost</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Expired">Expired</option>
                                <option value="Replace">Replace (Add Back)</option>
                            </select>
                        </div>
                        <div id="expirationByFieldSelect" style="display : none;">
                            <label class="form-label">Expiration Date</label>
                            <select class="form-select" id="stockOutExpiredItemSelect" onchange="getQuantity()" required></select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <input type="number" class="form-control" name="quantity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Date</label>
                            <input type="date" class="form-control" name="transaction_date" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning px-4" onclick="performStockOut()">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const today = new Date().toISOString().split('T')[0];
    // Set the value of the input
    document.getElementById('expiration_date').value = today;
    let currentPage = 1;
    let inventory = [], selectedItems = [];
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
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
                showAlert('danger', response.message || 'Failed to load inventory.');
            }
        }
    });
}

function loadInventory() {
    $.post('partials/inventory_management_api.php', { action: 'fetch_inventory', limit: 999 }, function(r) {
        if (r.status === 'success') {
            inventory = r.data.items.filter(i => parseInt(i.current_stock) > 0);
        }
    }, 'json');
}

function loadStockInOutMonitoring(page, search = 0) {
    currentPage = page;
    console.log('ss', search);
    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: { action: 'fetch_stock_in_out_monitoring', page, search, limit: 10 },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                console.log('ss', search);
                console.log(response.data);
                stockInOutMonitoringTable(response.data, currentPage, response.total);
            } else {
                showAlert('danger', response.message || 'Failed to load stock movement.');
            }
        }
    });
}

function loadStockInOutMonitoring2(search) {
    currentPage = 1;
    console.log('ss', search);
    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: { action: 'fetch_stock_in_out_monitoring2', search, limit: 10 },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                console.log('ss', search);
                console.log(response.data);
                stockInOutMonitoringTable(response.data, currentPage, response.total);
            } else {
                showAlert('danger', response.message || 'Failed to load stock movement.');
            }
        }
    });
}

function isDonatedBy(){
    var acquisitionType = document.getElementById("acquisitionType").value;
    var donatedByField = document.getElementById("donatedByField");
    var donatedByInput = document.getElementById("donatedBy");

    if(acquisitionType === "Donation"){
        donatedByField.style.display = "block";
        donatedByInput.disabled = false;
    } else {
        donatedByField.style.display = "none";
        donatedByInput.disabled = true;
        donatedByInput.value = "";
    }
}

function getQuantity(){
    const item_id = $('#stockOutItemSelect').val();
    const expiration_date = $('#stockOutExpiredItemSelect').val();
    $.post('partials/inventory_management_api.php', {
        action: 'get_quantity_by_expiration',
        item_id: item_id,
        expiration_date: expiration_date
    }, r => {
        if (r.status === 'success') {
            var quantityInput = $('[name=quantity]', '#stockOutForm');
            quantityInput.attr('max', r.data[0].quantity);
            quantityInput.val(r.data[0].quantity);
            quantityInput.prop('readonly', true);
        }
        else{
            showAlert('danger', response.message || 'Failed to load quantity for the selected expiration date.');
        }
    }, 'json');
}

function selectExpirationDate(){
    const item_id = selectedItems[0].id;
    $.post('partials/inventory_management_api.php', {
        action: 'get_expiration_dates',
        item_id: item_id
    }, r => {
        if (r.status === 'success') {
            const expirationSelect = $('#stockOutExpiredItemSelect');
            expirationSelect.empty();
            expirationSelect.append('<option value="">Select Expiration Date</option>');
            r.data.forEach(date => {
                expirationSelect.append(`<option value="${date.expiration_date}">${date.expiration_date}</option>`);
            });
            isWithExpirationSO();
        }
        else{
            showAlert('danger', response.message || 'Failed to load expiration dates.');
        }
    }, 'json');
}

function isWithExpiration() {
    const item_id = selectedItems[0].id;
    console.log(item_id);
    $.post('partials/inventory_management_api.php', {
        action: 'is_with_expiration',
        item_id: item_id
    }, r => {
        if (r.status === 'success') {
            const isWithExpiry = document.getElementById('expirationByField');
           if (r.data[0].with_expiration == 1){
            isWithExpiry.style.display = 'block';
           }
           else{
            isWithExpiry.style.display = 'none';
           }
        }
        else{
            showAlert('danger', response.message || 'Failed to load if the item have expiration.');
        }
    }, 'json');
}

function isWithExpirationSO() {
    const item_id = $('#stockOutItemSelect').val();
    $.post('partials/inventory_management_api.php', {
        action: 'is_with_expiration',
        item_id: item_id
    }, r => {
        if (r.status === 'success') {
            const isWithExpirySO = document.getElementById('expirationByFieldSelect');
           if (r.data[0].with_expiration == 1){
            isWithExpirySO.style.display = 'block';
           }
           else{
            isWithExpirySO.style.display = 'none';
           }
        }
        else{
            showAlert('danger', response.message || 'Failed to load if the item have expiration.');
        }
    }, 'json');
}

function updateStockMonitoringTable(items) {
    const tbody = $('#stockMonitoringTableBody');
    tbody.empty();
    if (!items.length) {
        tbody.append('<tr><td colspan="6" class="text-center py-5 text-muted">No inventory items found.</td></tr>');
        return;
    }

    items.forEach(item => {
        const unitValue = item.declared_value ? parseFloat(item.declared_value) : 0;
        const totalValue = unitValue * item.current_stock;

        tbody.append(`
            <tr>
                <td><strong>${item.item_name}</strong></td>
                <td>${item.description || '—'}</td>
                <td class="text-center"><strong>${item.current_stock}</strong></td>
                <td class="text-center">${item.qty_received}</td>
                <td class="text-center">${item.qty_lost}</td>
                <td class="text-center">${item.qty_damaged}</td>
                <td class="text-center">${item.qty_replaced}</td>
                <td class="text-center">${unitValue > 0 ? '₱' + unitValue.toFixed(2) : '—'}</td>
                <td>${item.remarks || '—'}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#stockMonitoringModal" onclick="loadStockInOutMonitoring2(${item.id})" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editItem(${item.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="archiveItem(${item.id})" title="Archive">
                        <i class="fas fa-archive"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

function stockInOutMonitoringTable(items, page, total) {
    console.log(items);
    let tbody = '';
    
    if (!items.length) {
        tbody += '<tr><td colspan="6" class="text-center py-5 text-muted">No stock movement items found.</td></tr>';
        return;
    }
    items.forEach(item => {
        const movementBadge = item.movement_type === 'Stock In' ? 'success' : 'warning';
        tbody += `
            <tr>
                <td><strong>${item.item_name}</strong></td>
                <td>${item.description || '—'}</td>
                <td class="text-center"><span class="badge bg-${movementBadge}">${item.movement_type}</span></td>
                <td class="text-center"><strong>${item.qty}</strong></td>
                <td>${item.remarks || '—'}</td>
            </tr>
        `;
    });
    $('#stockInOutMonitoringTable tbody').html(tbody);
    updatePagination(total, 10, page);
}

function updatePagination(totalItems, itemsPerPage, currentPage) {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    let pagination = '';
    if (totalPages > 0) {
            pagination += `<li class="page-item ${currentPage <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="${currentPage > 1 ? `loadStockInOutMonitoring(${currentPage - 1})` : ''}">Prev</a></li>`;
            for (let i = 1; i <= totalPages; i++) {
                pagination += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" onclick="loadStockInOutMonitoring(${i})">${i}</a></li>`;
            }
            pagination += `<li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}"><a class="page-link" href="#" onclick="${currentPage < totalPages ? `loadStockInOutMonitoring(${currentPage + 1})` : ''}">Next</a></li>`;
    }
    $('#paginationLinks').html(pagination);
    const start = (currentPage - 1) * itemsPerPage + 1;
    const end = Math.min(start * itemsPerPage, totalItems);
    $('#paginationInfo').text(`Showing ${start} to ${end} of ${totalItems} entries`);
}


function printSingleReport(id) {
    window.open(`partials/generate_single_inventory_pdf.php?id=${id}`, '_blank');
}

function saveItem(proceedToStock = false) {
    const form = $('#addItemForm')[0];
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    const formData = new FormData(form);
    formData.append('action', $('#itemId').val() ? 'update_item' : 'add_item');
    if (proceedToStock) formData.append('proceed_to_stock', '1');

    $.ajax({
        url: 'partials/inventory_management_api.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(r) {
            if (r.status === 'success') {
                $('#addItemModal').modal('hide');
                form.reset();
                showAlert('success', r.message);
                loadStockMonitoring();
                if (proceedToStock && r.item_id) {
                    setTimeout(() => $('#stockInItemSelect').val(r.item_id) && $('#stockInModal').modal('show'), 300);
                }
            } else {
                showAlert('danger', r.message);
            }
        }
    });
}

function performStockIn() {
    const itemId = selectedItems[0].id;
    const qty = $('[name=quantity]', '#stockInForm').val();
    if (!itemId || !qty) return showAlert('warning', 'Please fill all required fields');

    $.post('partials/inventory_management_api.php', {
        action: 'stock_in',
        inventory_id: itemId,
        quantity: qty,
        transaction_date: $('[name=transaction_date]', '#stockInForm').val(),
        remarks: $('[name=remarks]', '#stockInForm').val(),
        acquisition_type: $('[name=acquisition_type]', '#stockInForm').val(),
        donated_by: $('[name=donated_by]', '#stockInForm').val(),
        expiration_date: $('[name=expiration_date]', '#stockInForm').val()
    }, r => {
        if (r.status === 'success') {
            $('#stockInModal').modal('hide');
            $('#stockInForm')[0].reset();
            showAlert('success', 'Stock In recorded successfully!');
            loadStockMonitoring();
        } else {
            showAlert('danger', r.message);
        }
    }, 'json');
}

function performStockOut() {
    const itemId = selectedItems[0].id;
    const qty = $('[name=quantity]', '#stockOutForm').val();
    const action = $('[name=action_type]', '#stockOutForm').val();
    if (!itemId || !qty) return showAlert('warning', 'Please fill all required fields');

    $.post('partials/inventory_management_api.php', {
        action: 'add_transaction',
        inventory_id: itemId,
        action_type: action === 'Replace' ? 'Replace' : 'Broken',
        quantity: qty,
        transaction_date: $('[name=transaction_date]', '#stockOutForm').val(),
        remarks: $('[name=remarks]', '#stockOutForm').val(),
        borrower_name: action === 'Lost' ? 'Lost Item' : 'Damaged',
        expirationSelected: $('#stockOutExpiredItemSelect').val()
    }, r => {
        if (r.status === 'success') {
            $('#stockOutModal').modal('hide');
            $('#stockOutForm')[0].reset();
            showAlert('success', 'Stock Out recorded!');
            loadStockMonitoring();
        } else {
            showAlert('danger', r.message);
        }
    }, 'json');
}

function loadItemsSelect() {
    $.post('partials/inventory_management_api.php', {
        action: 'fetch_inventory',
        limit: 9999
    }, r => {
        if (r.status === 'success') {
            const opts = '<option value="">Select Item</option>';
            const items = r.data.items.map(i => `<option value="${i.id}">${i.item_name} (Stock: ${i.current_stock})</option>`).join('');
            $('#stockInItemSelect, #stockOutItemSelect').html(opts + items);
        }
    }, 'json');
}

function editItem(id) {
    $.post('partials/inventory_management_api.php', {
        action: 'get_item',
        id
    }, r => {
        if (r.status === 'success') {
            const i = r.data;
            $('#itemId').val(i.id);
            $('#itemName').val(i.item_name);
            $('#description').val(i.description || '');
            $('#declaredValue').val(i.declared_value || '');
            $('#remarks').val(i.remarks || '');
            $('#addItemModalLabel').html('<i class="fas fa-edit me-2"></i>Edit Item');
            $('#addItemModal').modal('show');
        }
    }, 'json');
}

function archiveItem(id) {
    if (!confirm('Are you sure you want to archive this item?')) return;
    $.post('partials/inventory_management_api.php', {
        action: 'archive_item',
        id
    }, r => {
        if (r.status === 'success') {
            showAlert('success', 'Item archived successfully!');
            loadStockMonitoring();
        }
    }, 'json');
}

    function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function addItemToBorrow(id, name, stock) {
    if (selectedItems.find(x => x.id == id)) {
        alert('Item already added!');
        return;
    }
    selectedItems = [];
    selectedItems.push({ id, name, stock });
    renderSelectedItems();
    $('#itemSearchInput').val('').focus();
    $('#itemSearchDropdown').hide();
}

function addItemToBorrow2(id, name, stock) {
    if (selectedItems.find(x => x.id == id)) {
        alert('Item already added!');
        return;
    }
    selectedItems = [];
    selectedItems.push({ id, name, stock });
    renderSelectedItems2();
    $('#itemSearchInput2').val('').focus();
    $('#itemSearchDropdown2').hide();
}

function renderSelectedItems2() {
    const inputElement = document.getElementById("itemSearchInput2");
    inputElement.value = selectedItems[0].name;
    const inputElement2 = document.getElementById("itemmssOut");
    inputElement2.value = selectedItems[0].name;
    selectExpirationDate();
}

function renderSelectedItems() {
    const inputElement = document.getElementById("itemSearchInput");
    inputElement.value = selectedItems[0].name;
    const inputElement2 = document.getElementById("itemmss");
    inputElement2.value = selectedItems[0].name;
    isWithExpiration();
}


// Item search
    $('#itemSearchInput2').on('input', debounce(function() {
        const query = this.value.trim().toLowerCase();
        const $dd = $('#itemSearchDropdown2').empty();
        if (!query) { $dd.hide(); return; }
        const matches = inventory.filter(i => i.item_name.toLowerCase().includes(query) && parseInt(i.current_stock) > 0);
        if (matches.length === 0) {
            $dd.append('<div class="dropdown-item text-muted">No available items</div>').show();
            return;
        }
        matches.forEach(item => {
            $dd.append(`
                <div class="dropdown-item" onclick="addItemToBorrow2(${item.id}, '${escapeHtml(item.item_name)}', ${item.current_stock})">
                    <strong>${escapeHtml(item.item_name)}</strong>
                    <span class="badge bg-secondary float-end">Stock: ${item.current_stock}</span>
                </div>
            `);
        });
        $dd.show();
    }, 300));

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

$(document).ready(function() {
    loadStockMonitoring();
    loadItemsSelect();
    loadInventory();
    loadStockInOutMonitoring(currentPage);
    $('#proceedToStockIn').on('click', () => saveItem(true));
    $('#addItemModal').on('hidden.bs.modal', () => {
        $('#addItemForm')[0].reset();
        $('#itemId').val('');
        $('#addItemModalLabel').html('<i class="fas fa-box me-2"></i>Add New Item');
    });
    $('#stockInModal, #stockOutModal').on('show.bs.modal', loadItemsSelect);
});
</script>
<style>
    .card {
        border-radius: 0.75rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .card-header {
        background-color: #212529;
        border-bottom: none;
        font-weight: 600;
        padding: 1rem 1.5rem;
        border-radius: 0.75rem 0.75rem 0 0 !important;
    }

    .table-responsive {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .table th {
        font-weight: 600;
        padding: 0.9rem 0.75rem;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .table td {
        padding: 0.75rem;
        vertical-align: middle;
        font-size: 0.9rem;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* === BUTTON CONSISTENCY === */
    .btn-md {
        min-height: 48px;
        padding: 0.5rem 1rem;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .btn-md i {
        font-size: 1.1rem;
    }

    /* === MODAL CONSISTENCY === */
    .modal-content {
        border: none;
        border-radius: 0.75rem;
        overflow: hidden;
    }

    .modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }

    .modal-body {
        padding: 1.5rem;
        background-color: #f8f9fa;
    }

    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: none;
        background-color: #fff;
    }

    .modal-footer .btn {
        min-width: 110px;
    }

    /* === FORM CONTROL FOCUS === */
    .form-control,
    .form-select {
        border-radius: 0.5rem;
        transition: all 0.2s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    /* === RESPONSIVE === */
    @media (max-width: 992px) {
        .btn-md {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {

        .table th,
        .table td {
            padding: 0.5rem;
            font-size: 0.85rem;
        }

        .modal-lg {
            max-width: 95%;
            margin: 1rem auto;
        }
    }

    @media (max-width: 576px) {

        .modal-header,
        .modal-body,
        .modal-footer {
            padding: 1rem;
        }

        .table th,
        .table td {
            font-size: 0.8rem;
        }

        .card-body {
            padding: 0.75rem;
        }

        .btn-md {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
        }
    }
</style>