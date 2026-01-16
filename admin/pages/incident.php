<?php require_once 'partials/db_conn.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Reports & Complaints - Barangay System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', sans-serif;
        }

        .card {
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            border: none;
        }

        .card-header {
            border-radius: 18px 18px 0 0 !important;
            color: white;
        }

        .table th {
            background: #1e293b;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.9rem;
        }

        .table td {
            padding: 0.9rem;
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .table tbody tr:hover {
            background-color: #f1f5f9;
        }

        .form-label {
            font-weight: 600;
            color: #1e293b;
            font-size: 1rem;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 0.65rem 1rem;
            font-size: 1rem;
        }

        .btn {
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }

        .action-buttons .btn {
            white-space: nowrap;
            min-width: 90px;
            font-size: 0.85rem !important;
            padding: 0.45rem 0.8rem !important;
        }

        .searchable-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: white;
            position: absolute;
            z-index: 1000;
            width: 100%;
            display: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-top: 8px;
        }

        .searchable-list.show {
            display: block;
        }

        .list-item {
            display: flex;
            align-items: center;
            padding: 0.9rem 1.2rem;
            cursor: pointer;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.2s;
        }

        .list-item:hover {
            background-color: #f1f5f9;
        }

        .list-item input[type="checkbox"] {
            margin-right: 1rem;
            transform: scale(1.2);
        }

        .non-resident-form {
            background: #fef3c7;
            border: 2px dashed #f59e0b;
            border-radius: 16px;
            padding: 1.8rem;
            margin-top: 1.5rem;
            display: none;
        }

        .non-resident-form.show {
            display: block;
            animation: fadeIn 0.4s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .file-drop-area {
            border: 3px dashed #94a3b8;
            background: #f8fafc;
            padding: 40px;
            text-align: center;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-drop-area:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
            }

            .table {
                font-size: 0.85rem;
            }

            th,
            td {
                padding: 0.6rem !important;
            }
        }

        @media print {

            .no-print,
            .modal,
            .card-header {
                display: none !important;
            }

            .card {
                box-shadow: none;
                border: 2px solid #333;
            }

            body {
                background: white;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4 px-3 px-md-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
            <div>
                <h1 class="mb-1 fw-bold text-dark fs-3">Incident Reports</h1>
                <p class="text-muted mb-0">Manage all barangay incident reports</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#incidentModal">
                    <i class="fas fa-plus me-2"></i> Add Incident
                </button>
                <button class="btn btn-success shadow-sm" onclick="downloadAllReports()">
                    <i class="fas fa-download me-2"></i> Print
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by Case ID or Nature">
                    </div>
                    <div style="display: none;">
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="New">New</option>
                            <option value="Settled">Settled</option>
                            <option value="On Going">On Going</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="dateFilter">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-danger w-100" onclick="clearFilters()">Clear</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header bg-dark text-white py-4">
                <h4 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-3"></i> Incident Records</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="incidentTable">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Nature of Incident</th>
                                <th>Persons Involved</th>
                                <th>Barangay Incharge</th>
                                <th>Date Reported</th>
                                <th class="no-print text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="incidentTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Incident Modal - EXACT SAME FIELDS AS YOUR ORIGINAL -->
    <div class="modal fade" id="incidentModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rounded-4">
                <div class="modal-header bg-success text-white py-4">
                    <h3 class="modal-title fw-bold" id="modalTitle">Add Incident Report</h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="incidentForm">
                    <div class="modal-body p-5">
                        <input type="hidden" id="incidentId">

                        <div class="row g-4 mb-4">
                            <div style="display: none;">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg" id="status">
                                    <option value="New">New</option>
                                    <option value="Settled">Settled</option>
                                    <option value="On Going">On Going</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date Reported <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-lg" id="date_reported" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+15 days')) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date Incident <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-lg" max="<?= date('Y-m-d') ?>" id="date_incident" required>
                            </div>
                        </div>


                        <div class="card mb-4 border-primary shadow-sm">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-4">
                                <h5 class="mb-0">Persons Involved (Optional)</h5>
                                <button type="button" class="btn btn-outline-light btn-sm" onclick="toggleNonResidentForm()">
                                    + Add Non-Resident
                                </button>
                            </div>
                            <div class="card-body p-4">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="personSearch" placeholder="Search residents by name...">
                                    <button class="btn btn-outline-secondary" type="button">Search</button>
                                </div>
                                <div class="searchable-list" id="personList">
                                    <div class="p-4 text-center text-muted">Type to search residents...</div>
                                </div>
                                <div class="table-responsive mt-3" style="max-height: 350px;">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Contact</th>
                                                <th>Address</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="selectedPersonsBody"></tbody>
                                    </table>
                                </div>
                                <div class="non-resident-form" id="nonResidentForm">
                                    <div class="row g-3">
                                        <div class="col-md-5"><input type="text" class="form-control" placeholder="Full Name *" id="nonResidentName"></div>
                                        <div class="col-md-3"><input type="text" class="form-control" placeholder="Contact Number" id="nonResidentContact" maxlength="11" pattern="[0-9]{1,11}"></div>
                                        <div class="col-md-3"><input type="text" class="form-control" placeholder="Full Address" id="nonResidentAddress"></div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-success w-100" onclick="addNonResident()">Add</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Nature of Incident <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-lg" id="nature_of_incident" rows="5" required></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Barangay Incharge <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="barangay_official_id" required>
                                <option value="">Select Official</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Supporting Documents</label>
                            <input type="file" id="supporting_docs" multiple accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                            <div class="file-drop-area" id="fileDropArea">
                                <p class="mb-1">Drag & drop files here or click to upload</p>
                                <small class="text-muted">PDF, JPG, PNG accepted</small>
                                <!-- <input type="file" id="supporting_docs" multiple accept=".pdf,.jpg,.jpeg,.png" style="display:none;"> -->
                            </div>
                            <div id="fileList" class="mt-3"></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-4">
                        <button type="button" class="btn btn-secondary btn-lg px-5" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-lg px-5" id="saveBtn">Save Incident</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
     <script>
        // EXACT SAME LOGIC AS YOUR ORIGINAL — ZERO CHANGES TO FUNCTIONALITY
        let residents = [],
            officials = [],
            selectedPersons = [],
            uploadedFiles = [];

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        function formatAddress(r) {
            const parts = [];
            if (r.house_number) parts.push(r.house_number);
            if (r.street) parts.push(r.street);
            if (r.municipality) parts.push(r.municipality);
            if (r.province) parts.push(r.province);
            if (r.zip_code) parts.push(r.zip_code);
            return parts.length ? parts.join(', ') : 'No address recorded';
        }

        function loadResidents() {
            $.post('partials/incident_api.php', {
                action: 'fetch_residents'
            }, r => {
                if (r.success) residents = r.data;
            }, 'json');
        }

        function loadOfficials() {
            $.post('partials/incident_api.php', {
                action: 'fetch_officials'
            }, r => {
                if (r.success) {
                    officials = r.data;
                    const $select = $('#barangay_official_id').empty().append('<option value="">Select Official</option>');
                    officials.forEach(o => $select.append(`<option value="${o.id}">${escapeHtml(o.full_name)}</option>`));
                }
            }, 'json');
        }

        function showPersonList() {
            const query = $('#personSearch').val().trim().toLowerCase();
            const $list = $('#personList').empty();
            if (!query) {
                $list.append('<div class="p-3 text-center text-muted">Type to search residents...</div>').addClass('show');
                return;
            }
            const filtered = residents.filter(r =>
                r.full_name.toLowerCase().includes(query) ||
                r.house_number?.toLowerCase().includes(query) ||
                r.street?.toLowerCase().includes(query)
            );
            if (!filtered.length) {
                $list.append('<div class="p-3 text-center text-muted">No residents found.</div>').addClass('show');
                return;
            }
            filtered.forEach(r => {
                const isChecked = selectedPersons.some(p => p.is_resident && p.id == r.id);
                const address = formatAddress(r);
                $list.append(`
                <div class="list-item">
                    <input type="checkbox" id="chk_person_${r.id}"
                           data-id="${r.id}"
                           data-name="${escapeHtml(r.full_name)}"
                           data-contact="${r.contact_number || ''}"
                           data-address="${escapeHtml(address)}" ${isChecked ? 'checked' : ''}>
                    <label for="chk_person_${r.id}" class="mb-0 flex-grow-1">
                        <strong>${escapeHtml(r.full_name)}</strong><br>
                        <small class="text-muted">${r.contact_number || 'No contact'} • ${address}</small>
                    </label>
                </div>
            `);
            });
            $list.addClass('show');
            $list.find('input[type="checkbox"]').on('change', renderSelectedPersons);
        }

        function renderSelectedPersons() {
            const $tbody = $('#selectedPersonsBody').empty();
            let hasAny = false;

            $('#personList input[type="checkbox"]:checked').each(function() {
                const d = $(this).data();
                hasAny = true;
                $tbody.append(`
                <tr data-resident-id="${d.id}">
                    <td><strong>${d.name}</strong></td>
                    <td>${d.contact || '—'}</td>
                    <td><small>${d.address}</small></td>
                    <td><button type="button" class="btn btn-sm btn-danger" onclick="uncheckPerson(${d.id})">X</button></td>
                </tr>
            `);
            });

            selectedPersons.filter(p => !p.is_resident).forEach((p, idx) => {
                hasAny = true;
                $tbody.append(`
                <tr class="table-warning">
                    <td><strong>${escapeHtml(p.name)}</strong> (Non-Resident)</td>
                    <td>${p.contact || '—'}</td>
                    <td><small>${escapeHtml(p.address || 'No address')}</small></td>
                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeNonResident(${idx})">X</button></td>
                </tr>
            `);
            });

            if (!hasAny) {
                $tbody.append('<tr><td colspan="4" class="text-center text-muted py-3">No persons selected (optional)</td></tr>');
            }
        }

        window.uncheckPerson = id => {
            $(`#chk_person_${id}`).prop('checked', false);
            renderSelectedPersons();
        };
        window.toggleNonResidentForm = () => $('#nonResidentForm').toggleClass('show');
        window.addNonResident = () => {
            const name = $('#nonResidentName').val().trim();
            const contact = $('#nonResidentContact').val().trim();
            const address = $('#nonResidentAddress').val().trim();
            if (!name) return alert('Full name is required.');
            selectedPersons.push({
                is_resident: false,
                name,
                contact,
                address: address || 'No address provided'
            });
            $('#nonResidentName, #nonResidentContact, #nonResidentAddress').val('');
            $('#nonResidentForm').removeClass('show');
            renderSelectedPersons();
        };
        window.removeNonResident = idx => {
            selectedPersons.splice(idx, 1);
            renderSelectedPersons();
        };

        function getSelectedPersons() {
            const selected = [];
            $('#personList input[type="checkbox"]:checked').each(function() {
                const d = $(this).data();
                selected.push({
                    id: d.id,
                    name: d.name,
                    contact: d.contact,
                    address: d.address,
                    is_resident: true
                });
            });
            selectedPersons.filter(p => !p.is_resident).forEach(p => selected.push({
                name: p.name,
                contact: p.contact,
                address: p.address,
                is_resident: false
            }));
            return selected;
        }

        $('#personSearch').on('input', showPersonList);
        $(document).on('click', e => {
            if (!$(e.target).closest('.card-body').length) $('#personList').removeClass('show');
        });

        function renderIncidents(incidents) {
            const $tbody = $('#incidentTableBody').empty();
            if (!incidents.length) {
                $tbody.append('<tr><td colspan="7" class="text-center py-5 text-muted fs-4">No incidents found</td></tr>');
                return;
            }
            incidents.forEach(i => {
const persons = Array.isArray(i.persons_involved) && i.persons_involved.length 
    ? i.persons_involved.map(name => escapeHtml(name)).join(', ') 
    : '—';
                    const statusBadge = {
                    'New': 'bg-danger',
                    'Settled': 'bg-success',
                    'On Going': 'bg-warning',
                    'Closed': 'bg-secondary'
                } [i.status] || 'bg-dark';
                $tbody.append(`
                <tr>
                    <td><strong>${escapeHtml(i.case_id)}</strong></td>
                    <td><small>${escapeHtml(i.nature_of_incident || '—')}</small></td>
                    <td><small>${persons}</small></td>
                    <td>${escapeHtml(i.official_name || '—')}</td>
                    <td>${i.date_reported}</td>
                    <td class="no-print">
                        <div class="action-buttons">
                            <button class="btn btn-warning btn-sm" onclick="editIncident(${i.id})">Edit</button>
                            <button class="btn btn-danger btn-sm text-white" onclick="downloadSingleReport(${i.id})">PDF</button>
                        </div>
                    </td>
                </tr>
            `);
            });
        }

        function loadIncidents() {
            const search = $('#searchInput').val().trim();
            const status = $('#statusFilter').val();
            const date = $('#dateFilter').val();
            $.post('partials/incident_api.php', {
                action: 'fetch_incidents',
                search,
                status,
                date
            }, r => {
                if (r.success) renderIncidents(r.data);
            }, 'json');
        }
        const officialDuty = "<?php echo $_SESSION['full_name']; ?>";

        window.downloadSingleReport = id => window.open('partials/generate_incident_pdf.php?id=' + id +'&full_name=' + officialDuty, '_blank');
        window.downloadAllReports = () => window.open('partials/generate_incident_pdf.php?all=1' +'&full_name=' + officialDuty, '_blank');

        $('#incidentForm').on('submit', function(e) {
            e.preventDefault();
            const persons = getSelectedPersons();
            const nature = $('#nature_of_incident').val().trim();
            if (!nature) return alert('Please enter the Nature of Incident.');

            const formData = new FormData();
            formData.append('action', $('#incidentId').val() ? 'update' : 'add');
            formData.append('id', $('#incidentId').val() || '');
            formData.append('status', $('#status').val());
            formData.append('date_reported', $('#date_reported').val());
            formData.append('date_incident', $('#date_incident').val());
            formData.append('nature_of_incident', nature);
            formData.append('persons_involved', JSON.stringify(persons));
            formData.append('barangay_official_id', $('#barangay_official_id').val());
            uploadedFiles.forEach(f => formData.append('files[]', f));

            $('#saveBtn').prop('disabled', true).html('Saving...');
            $.ajax({
                url: 'partials/incident_api.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: r => {
                    $('#saveBtn').prop('disabled', false).html('Save Incident');
                    if (r.success) {
                        $('#incidentModal').modal('hide');
                        loadIncidents();
                        resetForm();
                    } else alert('Error: ' + (r.message || 'Unknown'));
                }
            });
        });

        function resetForm() {
            $('#incidentForm')[0].reset();
            $('#incidentId').val('');
            selectedPersons = [];
            uploadedFiles = [];
            $('#fileList').empty();
            $('#modalTitle').text('Add Incident Report');
            $('#personSearch').val('');
            $('#personList').removeClass('show');
            $('#nonResidentForm').removeClass('show');
            $('#selectedPersonsBody').empty();
            const today = new Date().toISOString().split('T')[0];
            $('#date_reported').val(today);
        }

        window.editIncident = id => {
            $.post('partials/incident_api.php', {
                action: 'get',
                id
            }, r => {
                if (!r.success || !r.data) return alert('Not found');
                const i = r.data;
                resetForm();
                $('#incidentId').val(i.id);
                $('#status').val(i.status);
                $('#date_reported').val(i.date_reported);
                $('#date_incident').val(i.date_incident || '');
                $('#nature_of_incident').val(i.nature_of_incident || '');
                $('#barangay_official_id').val(i.barangay_official_id);
                selectedPersons = JSON.parse(i.persons_involved || '[]').map(p => ({
                    ...p,
                    address: p.address || 'No address'
                }));
                renderSelectedPersons();
                $('#modalTitle').text('Edit Incident - ' + i.case_id);
                $('#incidentModal').modal('show');
            }, 'json');
        };

        $('#fileDropArea').on('click', () => $('#supporting_docs').click())
            .on('dragover dragenter', e => {
                e.preventDefault();
                $(e.target).addClass('dragover');
            })
            .on('dragleave drop', e => {
                e.preventDefault();
                $(e.target).removeClass('dragover');
            })
            .on('drop', e => handleFiles(e.originalEvent.dataTransfer.files));
        $('#supporting_docs').on('change', e => handleFiles(e.target.files));

        function handleFiles(files) {
            Array.from(files).forEach(file => {
                if (uploadedFiles.find(f => f.name === file.name)) return;
                uploadedFiles.push(file);
                $('#fileList').append(`<div class="badge bg-info me-2 mb-1 p-2">${escapeHtml(file.name)} <span class="text-danger ms-2" style="cursor:pointer;" onclick="removeFile('${file.name.replace(/'/g, "\\'")}')">×</span></div>`);
            });
        }
        window.removeFile = name => {
            uploadedFiles = uploadedFiles.filter(f => f.name !== name);
            $(`#fileList .badge:contains(${name})`).remove();
        };

        function clearFilters() {
            $('#searchInput, #statusFilter, #dateFilter').val('');
            loadIncidents();
        }

        $('#searchInput, #statusFilter, #dateFilter').on('input change', () => {
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(loadIncidents, 300);
        });

        const inputNonResidentName = document.getElementById('nonResidentName');
        inputNonResidentName.addEventListener('input', (e) => {
            const el = e.target;
            const { selectionStart, selectionEnd, value } = el;

            if (!value) return;

            const first = value.charAt(0).toUpperCase();
            const rest = value.slice(1);
            const newValue = first + rest;

            if (newValue !== value) {
            // Update value without breaking the caret position
            const offset = newValue.length - value.length;
            el.value = newValue;

            // Restore cursor position (keeps selection if any)
            const newStart = Math.max(1, selectionStart + offset);
            const newEnd = Math.max(1, selectionEnd + offset);
            el.setSelectionRange(newStart, newEnd);
            }
        });

        $(document).ready(() => {
            loadResidents();
            loadOfficials();
            loadIncidents();
            const today = new Date().toISOString().split('T')[0];
            $('#date_reported').val(today);
            $('#incidentModal').on('hidden.bs.modal', resetForm);
            $('#nonResidentContact').on('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            e.target.value = value;
        });
        });
    </script>
</body>

</html>