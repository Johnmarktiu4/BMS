<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blotter Reports - Barangay System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; font-family: 'Segoe UI', sans-serif; }
        .card { border-radius: 18px; box-shadow: 0 8px 25px rgba(0,0,0,0.12); border: none; }
        .card-header { border-radius: 18px 18px 0 0 !important; }
        .table th { background: #1e293b; color: white; font-weight: 600; font-size: 1.05rem; padding: 1rem; }
        .table td { padding: 1rem; vertical-align: middle; font-size: 1rem; }
        .table tbody tr:hover { background-color: #f1f5f9; }
        .form-label { font-weight: 600; color: #1e293b; font-size: 1.05rem; }
        .form-control, .form-select { border-radius: 12px; padding: 0.75rem 1rem; font-size: 1.05rem; }
        .btn { border-radius: 12px; padding: 0.75rem 1.5rem; font-weight: 600; font-size: 1.05rem; }
        .file-drop-area {
            border: 3px dashed #94a3b8;
            background: #f8fafc;
            padding: 40px;
            text-align: center;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }
        .file-drop-area:hover { border-color: #3b82f6; background: #eff6ff; }
        .file-drop-area.dragover { border-color: #2563eb; background: #dbeafe; transform: scale(1.02); }
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-top: 8px;
        }
        .searchable-list.show { display: block; }
        .list-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.2rem;
            cursor: pointer;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.2s;
        }
        .list-item:hover { background-color: #f1f5f9; }
        .list-item input[type="checkbox"] { margin-right: 1rem; transform: scale(1.3); }
        .selected-table tbody tr { font-size: 1rem; }
        .non-resident-form {
            background: #fef3c7;
            border: 2px dashed #f59e0b;
            border-radius: 16px;
            padding: 2rem;
            margin-top: 1.5rem;
            display: none;
        }
        .non-resident-form.show { display: block; animation: fadeIn 0.4s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .non-resident-form .form-control,
        .non-resident-form .form-select { font-size: 1.1rem; padding: 0.8rem; border-radius: 12px; }
        .badge { font-size: 0.95rem; padding: 0.5rem 1rem; border-radius: 50px; }
        @media print {
            .no-print, .modal, .btn, .card-header { display: none !important; }
            .card { box-shadow: none; border: 2px solid #333; }
            body { background: white; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="mb-1 fw-bold text-dark">Blotter Reports</h1>
            <p class="text-muted fs-5 mb-0">Manage all barangay blotter records</p>
        </div>
<div class="d-flex gap-3">
    <button class="btn btn-success btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#complaintModal">
        <i class="fas fa-plus me-2"></i> Add Blotter
    </button>
    <a href="partials/generate_complaint_pdf.php?full_name=<?php echo $_SESSION['full_name']; ?>" target="_blank" class="btn btn-success btn-lg shadow-sm">
        <i class="fas fa-print me-2"></i> Print  
    </a>
</div>
    </div>

    <div class="card mb-5">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-lg" id="searchInput" placeholder="Search by Case ID or Name">
                </div>
                <div style="display: none;">
                    <select class="form-select form-select-lg" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="New">New</option>
                        <option value="Settled">Settled</option>
                        <option value="On Going">On Going</option>
                        <option value="Closed">Closed</option>
                        <option value="Blotter">Blotter</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control form-control-lg" id="dateFilter">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-danger btn-lg w-100" onclick="clearFilters()">Clear</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white py-4">
            <h4 class="mb-0 fw-bold"><i class="fas fa-gavel me-3"></i> Blotter Records</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="complaintTable">
                    <thead class="table-dark">
                        <tr>
                            <th width="10%">Case ID</th>
                            <th width="20%">Complainant(s)</th>
                            <th width="20%">Defendant(s)</th>
                            <th width="15%">Official</th>
                            <th width="10%">Date Reported</th>
                            <th width="10%">Date Incident</th>
                                                                                <th width="8%">Print</th>

                         </tr>
                    </thead>
                    <tbody id="complaintTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ADD / EDIT COMPLAINT MODAL -->
<div class="modal fade" id="complaintModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content rounded-4" style="max-height: 95vh;">
            <div class="modal-header bg-success text-white py-4">
                <h3 class="modal-title fw-bold" id="modalTitle">Add Blotter</h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5" style="overflow-y: auto;">
                <form id="complaintForm">
                    <input type="hidden" id="complaintId">

                    <div class="row g-5 mb-5">
                        <div style="display: none;">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="status">
                                <option value="New">New</option>
                                <option value="Settled">Settled</option>
                                <option value="On Going">On Going</option>
                                <option value="Closed">Closed</option>
                                <option value="Blotter">Blotter</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date Reported <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-lg" id="date_reported" max="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Incident <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-lg" id="date_incident" max="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="row g-5">
                        <!-- COMPLAINANT SECTION -->
                        <div class="col-lg-6">
                            <div class="card border-success shadow-sm h-100">
                                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-4">
                                    <h4 class="mb-0">Complainant(s)</h4>
                                    <button type="button" class="btn btn-outline-light btn-lg" onclick="toggleNonResidentForm('complainant')">
                                        + Add Non-Resident
                                    </button>
                                </div>
                                <div class="card-body p-4">
                                    <div class="input-group input-group-lg mb-4">
                                        <input type="text" class="form-control" id="complainantSearch" placeholder="Search resident by name, house no, street...">
                                        <button class="btn btn-outline-secondary" type="button">Search</button>
                                    </div>

                                    <div class="searchable-list" id="complainantList">
                                        <div class="p-4 text-center text-muted fs-5">Type to search residents...</div>
                                    </div>

                                    <div class="table-responsive mt-4" style="max-height: 400px;">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-success">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Sex</th>
                                                    <th>Age</th>
                                                    <th>Address</th>
                                                    <th>Contact</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="complainantSelectedBody"></tbody>
                                        </table>
                                    </div>

                                    <div class="non-resident-form" id="complainantNonResidentForm">
                                        <h4 class="text-success mb-4">Add Non-Resident Complainant</h4>
                                        <div class="row g-4">
                                            <div class="col-md-6"><input type="text" class="form-control form-control-lg non-resident-name" placeholder="Full Name *" required></div>
                                            <div class="col-md-3">
                                                <select class="form-select form-select-lg non-resident-sex">
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Rather not to say">Prefer not to say</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3"><input type="number" class="form-control form-control-lg non-resident-age" placeholder="Age *" min="1" required></div>
                                            <div class="col-12"><input type="text" class="form-control form-control-lg non-resident-address" placeholder="Full Address (House #, Street, Barangay, City) *" required></div>
                                            <div class="col-12"><input type="text" class="form-control form-control-lg non-resident-contact" placeholder="Contact Number (optional)"  maxlength="11" pattern="[0-9]{1,11}"></div>
                                            <div class="col-12 text-end">
                                                <button type="button" class="btn btn-success btn-lg me-3" onclick="addNonResidentToTable('complainant')">Add Person</button>
                                                <button type="button" class="btn btn-secondary btn-lg" onclick="toggleNonResidentForm('complainant')">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DEFENDANT SECTION -->
                        <div class="col-lg-6">
                            <div class="card border-danger shadow-sm h-100">
                                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-4">
                                    <h4 class="mb-0">Defendant(s)</h4>
                                    <button type="button" class="btn btn-outline-light btn-lg" onclick="toggleNonResidentForm('defendant')">
                                        + Add Non-Resident
                                    </button>
                                </div>
                                <div class="card-body p-4">
                                    <div class="input-group input-group-lg mb-4">
                                        <input type="text" class="form-control" id="defendantSearch" placeholder="Search resident by name, house no, street...">
                                        <button class="btn btn-outline-secondary" type="button">Search</button>
                                    </div>

                                    <div class="searchable-list" id="defendantList">
                                        <div class="p-4 text-center text-muted fs-5">Type to search residents...</div>
                                    </div>

                                    <div class="table-responsive mt-4" style="max-height: 400px;">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-danger">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Sex</th>
                                                    <th>Age</th>
                                                    <th>Address</th>
                                                    <th>Contact</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="defendantSelectedBody"></tbody>
                                        </table>
                                    </div>

                                    <div class="non-resident-form" id="defendantNonResidentForm">
                                        <h4 class="text-danger mb-4">Add Non-Resident Defendant</h4>
                                        <div class="row g-4">
                                            <div class="col-md-6"><input type="text" class="form-control form-control-lg non-resident-name" placeholder="Full Name *" required></div>
                                            <div class="col-md-3">
                                                <select class="form-select form-select-lg non-resident-sex">
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Rather not to say">Prefer not to say</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3"><input type="number" class="form-control form-control-lg non-resident-age" placeholder="Age *" min="1" required></div>
                                            <div class="col-12"><input type="text" class="form-control form-control-lg non-resident-address" placeholder="Full Address (House #, Street, Barangay, City) *" required></div>
                                            <div class="col-12"><input type="text" class="form-control form-control-lg non-resident-contact" placeholder="Contact Number (optional)"  maxlength="11" pattern="[0-9]{1,11}"></div>
                                            <div class="col-12 text-end">
                                                <button type="button" class="btn btn-danger btn-lg me-3" onclick="addNonResidentToTable('defendant')">Add Person</button>
                                                <button type="button" class="btn btn-secondary btn-lg" onclick="toggleNonResidentForm('defendant')">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-5 mt-5">
                        <div class="col-md-6">
                            <label class="form-label">Complainant Document</label>
                            <input type="file" id="complainantFile" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                            <div class="file-drop-area" id="complainantFileDrop">
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                <p class="mb-0 fw-bold">Drop file here or click to upload</p>
                                <small class="text-muted">PDF, JPG, PNG only</small>
                                <!-- <input type="file" id="complainantFile" accept=".pdf,.jpg,.jpeg,.png" style="display:none;"> -->
                            </div>
                            <div id="complainantFileList" class="mt-3"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Defendant Document</label>
                            <input type="file" id="defendantFile" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                            <div class="file-drop-area" id="defendantFileDrop">
                                <i class="fas fa-cloud-upload-alt fa-3x text-danger mb-3"></i>
                                <p class="mb-0 fw-bold">Drop file here or click to upload</p>
                                <small class="text-muted">PDF, JPG, PNG only</small>
                                <!-- <input type="file" id="defendantFile" accept=".pdf,.jpg,.jpeg,.png" style="display:none;"> -->
                            </div>
                            <div id="defendantFileList" class="mt-3"></div>
                        </div>
                    </div>

                    <div class="row g-5 mt-5">
                        <div class="col-md-6">
                            <label class="form-label">Barangay Official In Charge <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="barangay_official_id" required>
                                <option value="">Select Official</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Incident Details</label>
                            <textarea class="form-control form-control-lg" id="details" rows="6" placeholder="Describe the incident in detail..."></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light py-4 px-5">
                <button type="button" class="btn btn-secondary btn-lg px-5" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-lg px-5" id="saveBtn" onclick="$('#complaintForm').submit()">Add Blotter</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
 <script>
    let residents = [], officials = [];
    let complainantFile = null, defendantFile = null;
    let nonResidentStorage = { complainant: [], defendant: [] };

    function loadResidents() {
        $.post('partials/compliant_api.php', { action: 'fetch_residents' }, r => {
            if (r.success) residents = r.data;
        }, 'json');
    }

    function loadOfficials() {
        $.post('partials/compliant_api.php', { action: 'fetch_officials' }, r => {
            if (r.success) {
                officials = r.data;
                const $select = $('#barangay_official_id').empty();
                $select.append('<option value="">Select Official</option>');
                officials.forEach(o => $select.append(`<option value="${o.id}">${escapeHtml(o.full_name)} - ${o.position || 'Official'}</option>`));
            }
        }, 'json');
    }

    function showResidentList(type) {
        const query = $(`#${type}Search`).val().trim().toLowerCase();
        const $list = $(`#${type}List`).empty();
        if (!query) {
            $list.append('<div class="p-4 text-center text-muted fs-5">Type to search residents...</div>').addClass('show');
            return;
        }
        const filtered = residents.filter(r =>
            r.full_name.toLowerCase().includes(query) ||
            r.house_no?.toLowerCase().includes(query) ||
            r.street?.toLowerCase().includes(query)
        );
        if (!filtered.length) {
            $list.append('<div class="p-4 text-center text-muted fs-5">No residents found.</div>').addClass('show');
            return;
        }
        filtered.forEach(r => {
            const isChecked = $(`input[data-id="${r.id}"][data-type="${type}"]`).is(':checked');
            $list.append(`
                <div class="list-item">
                    <input type="checkbox" id="chk_${type}_${r.id}" data-type="${type}" data-id="${r.id}"
                           data-name="${escapeHtml(r.full_name)}" data-sex="${r.sex}" data-age="${r.age}"
                           data-address="${escapeHtml(r.address || '')}" data-contact="${r.contact || ''}" ${isChecked ? 'checked' : ''}>
                    <label for="chk_${type}_${r.id}" class="mb-0 flex-grow-1">
                        <strong class="fs-5">${escapeHtml(r.full_name)}</strong><br>
                        <small class="text-muted">Age: ${r.age} • ${r.sex} • ${escapeHtml(r.address || 'No address')}</small>
                    </label>
                </div>
            `);
        });
        $list.addClass('show');
        $list.find('input[type="checkbox"]').on('change', () => renderSelectedTable(type));
    }

    $('#complainantSearch, #defendantSearch').on('input', function() {
        const type = this.id.includes('complainant') ? 'complainant' : 'defendant';
        showResidentList(type);
    });

    $(document).on('click', e => {
        if (!$(e.target).closest('.card-body').length) $('.searchable-list').removeClass('show');
    });

    function renderSelectedTable(type) {
        const $tbody = $(`#${type}SelectedBody`).empty();
        $(`#${type}List input[type="checkbox"]:checked`).each(function() {
            const d = $(this).data();
            $tbody.append(`
                <tr data-resident-id="${d.id}">
                    <td><strong>${d.name}</strong></td>
                    <td>${d.sex}</td>
                    <td>${d.age}</td>
                    <td><small>${d.address}</small></td>
                    <td>${d.contact || '—'}</td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="uncheckResident('${type}', '${d.id}')">X</button></td>
                </tr>
            `);
        });
        nonResidentStorage[type].forEach((p, idx) => {
            $tbody.append(`
                <tr class="table-warning">
                    <td><strong>${escapeHtml(p.name)}</strong> (Non-Resident)</td>
                    <td>${p.sex}</td>
                    <td>${p.age}</td>
                    <td><small>${escapeHtml(p.address)}</small></td>
                    <td>${p.contact || '—'}</td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeNonResident('${type}', ${idx})">X</button></td>
                </tr>
            `);
        });
    }

    window.uncheckResident = (type, id) => {
        $(`#chk_${type}_${id}`).prop('checked', false);
        renderSelectedTable(type);
    };

    window.toggleNonResidentForm = type => $(`#${type}NonResidentForm`).toggleClass('show');
    
    window.addNonResidentToTable = type => {
        const $form = $(`#${type}NonResidentForm`);
        const name = $form.find('.non-resident-name').val().trim();
        const sex = $form.find('.non-resident-sex').val();
        const age = parseInt($form.find('.non-resident-age').val());
        const address = $form.find('.non-resident-address').val().trim();
        const contact = $form.find('.non-resident-contact').val().trim();
        if (!name || !age || !address) return alert('Please fill all required fields for non-resident.');
        nonResidentStorage[type].push({ name, sex, age, address, contact });
        $form.find('input, select').val('');
        $form.removeClass('show');
        renderSelectedTable(type);
    };

    window.removeNonResident = (type, idx) => {
        nonResidentStorage[type].splice(idx, 1);
        renderSelectedTable(type);
    };

    function getSelectedPersons(type) {
        const selected = [];
        $(`#${type}List input[type="checkbox"]:checked`).each(function() {
            const d = $(this).data();
            selected.push({ id: d.id, name: d.name, sex: d.sex, age: d.age, address: d.address, contact: d.contact, is_resident: true });
        });
        nonResidentStorage[type].forEach(p => selected.push({ ...p, is_resident: false }));
        return selected;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    $('#complainantFileDrop, #defendantFileDrop').on('click', function() {
        const type = this.id.includes('complainant') ? 'complainant' : 'defendant';
        $(`#${type}File`).click();
    }).on('dragover dragenter', e => { e.preventDefault(); $(e.currentTarget).addClass('dragover'); })
      .on('dragleave drop', e => { e.preventDefault(); $(e.currentTarget).removeClass('dragover'); })
      .on('drop', e => {
          const type = e.currentTarget.id.includes('complainant') ? 'complainant' : 'defendant';
          const file = e.originalEvent.dataTransfer.files[0];
          if (file) handleFile(file, type);
      });

    $('#complainantFile, #defendantFile').on('change', function() {
        const type = this.id.includes('complainant') ? 'complainant' : 'defendant';
        if (this.files[0]) handleFile(this.files[0], type);
    });

    function handleFile(file, type) {
        window[type + 'File'] = file;
        $(`#${type}FileList`).html(`
            <div class="alert alert-info d-flex justify-content-between align-items-center py-3">
                <div>
                    <i class="fas fa-file me-2"></i>
                    <strong>${escapeHtml(file.name)}</strong> (${(file.size/1024).toFixed(1)} KB)
                </div>
                <button type="button" class="btn-close" onclick="clearFile('${type}')"></button>
            </div>
        `);
    }

    window.clearFile = type => {
        window[type + 'File'] = null;
        $(`#${type}FileList`).empty();
    };

    $('#complaintForm').on('submit', function(e) {
        e.preventDefault();
        const complainants = getSelectedPersons('complainant');
        const defendants = getSelectedPersons('defendant');
        if (complainants.length === 0 || defendants.length === 0) return alert('Please select at least one complainant and one defendant.');
        if (!$('#barangay_official_id').val()) return alert('Please select a barangay official.');

        const formData = new FormData();
        formData.append('action', $('#complaintId').val() ? 'update' : 'add');
        formData.append('id', $('#complaintId').val() || '');
        formData.append('status', $('#status').val());
        formData.append('date_reported', $('#date_reported').val());
        formData.append('date_incident', $('#date_incident').val());
        formData.append('barangay_official_id', $('#barangay_official_id').val());
        formData.append('details', $('#details').val());
        formData.append('complainants', JSON.stringify(complainants));
        formData.append('defendants', JSON.stringify(defendants));
        if (complainantFile) formData.append('complainant_file', complainantFile);
        if (defendantFile) formData.append('defendant_file', defendantFile);

        $('#saveBtn').prop('disabled', true).html('Saving...');
        $.ajax({
            url: 'partials/compliant_api.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: r => {
                $('#saveBtn').prop('disabled', false).html($('#complaintId').val() ? 'Update Blotter' : 'Add Blotter');
                if (r.success) {
                    $('#complaintModal').modal('hide');
                    loadComplaints();
                    resetForm();
                    alert('Complaint saved successfully!');
                } else {
                    alert('Error: ' + r.message);
                }
            }
        });
    });

    function resetForm() {
        $('#complaintForm')[0].reset();
        $('#complaintId').val('');
        $('#modalTitle').text('Add Blotter');
        $('#saveBtn').html('Add Blotter');
        complainantFile = defendantFile = null;
        $('#complainantFileList, #defendantFileList').empty();
        $('.searchable-list').empty().removeClass('show');
        $('#complainantSelectedBody, #defendantSelectedBody').empty();
        $('.non-resident-form').removeClass('show');
        nonResidentStorage = { complainant: [], defendant: [] };
        const today = new Date().toISOString().split('T')[0];
        $('#date_reported, #date_incident').val(today);
    }

    function loadComplaints() {
        const search = $('#searchInput').val().trim();
        const status = $('#statusFilter').val();
        const date = $('#dateFilter').val();
        $.post('partials/compliant_api.php', { action: 'fetch_compliants', search, status, date }, r => {
            if (r.success) renderComplaints(r.data);
        }, 'json');
    }

function renderComplaints(data) {
    const $tbody = $('#complaintTableBody').empty();
    if (!data.length) {
        $tbody.append('<tr><td colspan="8" class="text-center py-5 text-muted fs-4">No blotter records found</td></tr>');
        return;
    }
    const officialDuty = "<?php echo $_SESSION['full_name']; ?>";

    data.forEach(c => {
        const comp = c.complainants.map(p => 
            `<div><strong>${escapeHtml(p.name)}</strong><br><small>${p.sex}, ${p.age}yo ${p.is_resident ? '' : '(Non-Resident)'}</small></div>`
        ).join('');

        const def = c.defendants.map(p => 
            `<div><strong>${escapeHtml(p.name)}</strong><br><small>${p.sex}, ${p.age}yo ${p.is_resident ? '' : '(Non-Resident)'}</small></div>`
        ).join('');

        const badgeClass = {
            'New': 'bg-primary', 'Settled': 'bg-success', 'On Going': 'bg-warning',
            'Closed': 'bg-secondary', 'Blotter': 'bg-danger'
        }[c.status] || 'bg-dark';
$tbody.append(`
    <tr>
        <td><strong class="fs-5">${escapeHtml(c.case_id)}</strong></td>
        <td>${comp || '<em class="text-muted">None</em>'}</td>
        <td>${def || '<em class="text-muted">None</em>'}</td>
        <td>${escapeHtml(c.official_name || '—')}</td>
        <td>${c.date_reported}</td>
        <td>${c.date_incident}</td>
        <td>
            <a href="partials/generate_complaint_pdf.php?id=${c.id}&full_name=${officialDuty}" 
               target="_blank" 
               class="btn btn-sm btn-outline-info" 
               title="Print Complaint Report">
                <i class="fas fa-print"></i>
            </a>
        </td>
    </tr>
`);
    });
}

    window.editComplaint = function(id) {
        $.post('partials/compliant_api.php', { action: 'get', id }, r => {
            if (!r.success || !r.data) return alert('Blotter not found');
            const c = r.data;
            resetForm();
            $('#complaintId').val(c.id);
            $('#status').val(c.status);
            $('#date_reported').val(c.date_reported);
            $('#date_incident').val(c.date_incident);
            $('#details').val(c.details || '');
            $('#barangay_official_id').val(c.barangay_official_id);
            $('#modalTitle').text('Edit Blotter');
            $('#saveBtn').html('Update Blotter');

            // Load complainants/defendants (simplified for demo)
            setTimeout(() => {
                $('#complaintModal').modal('show');
            }, 300);
        }, 'json');
    };

    function printTable() { window.print(); }
    function clearFilters() {
        $('#searchInput, #statusFilter, #dateFilter').val('');
        loadComplaints();
    }

    $('#searchInput, #statusFilter, #dateFilter').on('input change', () => {
        clearTimeout(window.filterTO);
        window.filterTO = setTimeout(loadComplaints, 400);
    });

    $(document).ready(() => {
        loadResidents();
        loadOfficials();
        loadComplaints();
        const today = new Date().toISOString().split('T')[0];
        $('#date_reported, #date_incident').val(today);
        $('#complaintModal').on('hidden.bs.modal', resetForm);
        const nonResidentContact = document.querySelectorAll('.non-resident-contact');
        $(nonResidentContact).on('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            e.target.value = value;
        });
    });
</script>
</body>
</html>