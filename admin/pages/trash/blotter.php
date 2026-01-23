<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blotter Management - Barangay System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: #f5f7fa; 
            font-family: 'Segoe UI', sans-serif; 
        }
        .card { 
            border-radius: 18px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.12); 
            border: none; 
        }
        .card-header { 
            border-radius: 18px 18px 0 0 !important; 
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
        .form-control, .form-select { 
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
            from { opacity: 0; } 
            to { opacity: 1; } 
        }
        .badge { 
            font-size: 0.85rem; 
            padding: 0.4rem 0.8rem; 
            border-radius: 50px; 
        }

        /* Fixed Action Buttons - Clean, Responsive, Always Clickable */
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
            th, td { 
                padding: 0.6rem !important; 
            }
        }

        @media print {
            .no-print, .modal, .card-header { 
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
            <h1 class="mb-1 fw-bold text-dark fs-3">Blotter Management</h1>
            <p class="text-muted mb-0">Manage all barangay blotter records and hearings</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#blotterModal">
                <i class="fas fa-plus me-2"></i> File Blotter
            </button>
            <a href="partials/generate_blotter_pdf.php" target="_blank" class="btn btn-info shadow-sm text-white">
                <i class="fas fa-print me-2"></i> Print All Blotters
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by Case ID, Name, Location...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Unresolved">Unresolved</option>
                        <option value="Forwarded to Police">Forwarded to Police</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" onkeydown="return false" class="form-control" id="dateFilter">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-danger w-100" onclick="clearFilters()">Clear</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Blotter Table -->
    <div class="card">
        <div class="card-header bg-dark text-white py-4">
            <h4 class="mb-0 fw-bold"><i class="fas fa-gavel me-3"></i> Blotter Records</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="blotterTable">
                    <thead>
                        <tr>
                            <th>Case ID</th>
                            <th>Complainant(s)</th>
                            <th>Defendant(s)</th>
                            <th>Nature</th>
                            <th>Status</th>
                            <th>Hearings</th>
                            <th>Date Filed</th>
                            <th class="no-print text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="blotterTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- FILE / EDIT BLOTTER MODAL -->
<div class="modal fade" id="blotterModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content rounded-4" style="max-height: 95vh;">
            <div class="modal-header bg-success text-white py-4">
                <h3 class="modal-title fw-bold" id="modalTitle">File New Blotter</h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5" style="overflow-y: auto;">
                <form id="blotterForm">
                    <input type="hidden" id="blotterId">
                    <div class="row g-5 mb-5">
                        <div class="col-md-3">
                            <label class="form-label">Case ID</label>
                            <input type="text" class="form-control form-control-lg" id="case_id" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date Filed *</label>
                            <input type="date" class="form-control form-control-lg" onkeydown="return false" id="date_filed" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Incident Time *</label>
                            <input type="time" class="form-control form-control-lg" id="incident_time" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select form-select-lg" id="status" required>
                                <option value="Unresolved">Unresolved</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Blotter">Blotter</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-5 mb-5">
                        <div class="col-md-6">
                            <label class="form-label">Location of Incident *</label>
                            <input type="text" class="form-control form-control-lg" id="location" placeholder="e.g. Purok 5, near basketball court" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barangay Incharge *</label>
                            <select class="form-select form-select-lg" id="barangay_incharge_id" required>
                                <option value="">Select Official</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-5">
                        <div class="col-lg-6">
                            <div class="card border-success shadow-sm h-100">
                                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-4">
                                    <h4 class="mb-0">Complainant(s)</h4>
                                    <button type="button" class="btn btn-outline-light" onclick="toggleNonResidentForm('complainant')">
                                        + Add Non-Resident
                                    </button>
                                </div>
                                <div class="card-body p-4">
                                    <div class="input-group mb-4">
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
                                            <div class="col-md-6"><input type="text" class="form-control non-resident-name" placeholder="Full Name *" required></div>
                                            <div class="col-md-3">
                                                <select class="form-select non-resident-sex">
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Rather not to say">Prefer not to say</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3"><input type="number" class="form-control non-resident-age" placeholder="Age *" min="1" required></div>
                                            <div class="col-12"><input type="text" class="form-control non-resident-address" placeholder="Full Address *" required></div>
                                            <div class="col-12"><input type="text" class="form-control non-resident-contact" placeholder="Contact Number"></div>
                                            <div class="col-12 text-end">
                                                <button type="button" class="btn btn-success me-3" onclick="addNonResidentToTable('complainant')">Add Person</button>
                                                <button type="button" class="btn btn-secondary" onclick="toggleNonResidentForm('complainant')">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card border-danger shadow-sm h-100">
                                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-4">
                                    <h4 class="mb-0">Defendant(s)</h4>
                                    <button type="button" class="btn btn-outline-light" onclick="toggleNonResidentForm('defendant')">
                                        + Add Non-Resident
                                    </button>
                                </div>
                                <div class="card-body p-4">
                                    <div class="input-group mb-4">
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
                                            <div class="col-md-6"><input type="text" class="form-control non-resident-name" placeholder="Full Name *" required></div>
                                            <div class="col-md-3">
                                                <select class="form-select non-resident-sex">
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Rather not to say">Prefer not to say</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3"><input type="number" class="form-control non-resident-age" placeholder="Age *" min="1" required></div>
                                            <div class="col-12"><input type="text" class="form-control non-resident-address" placeholder="Full Address *" required></div>
                                            <div class="col-12"><input type="text" class="form-control non-resident-contact" placeholder="Contact Number"></div>
                                            <div class="col-12 text-end">
                                                <button type="button" class="btn btn-danger me-3" onclick="addNonResidentToTable('defendant')">Add Person</button>
                                                <button type="button" class="btn btn-secondary" onclick="toggleNonResidentForm('defendant')">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-5 mt-5">
                        <div class="col-12">
                            <label class="form-label">Nature of Complaint *</label>
                            <input type="text" class="form-control form-control-lg" id="nature_of_complaint" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Incident Details *</label>
                            <textarea class="form-control form-control-lg" id="details" rows="6" placeholder="Describe the incident in full detail..." required></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light py-4 px-5">
                <button type="button" class="btn btn-secondary px-5" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-5" id="saveBtn" onclick="$('#blotterForm').submit()">Save Blotter</button>
            </div>
        </div>
    </div>
</div>


<!-- Schedule Hearing Modal -->
<div class="modal fade" id="scheduleHearingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Schedule Hearing</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="scheduleBlotterId">
                <input type="hidden" id="scheduleHearingNumber">
                <div class="mb-3">
                    <label class="form-label">Hearing Date</label>
                    <input type="date" class="form-control" id="hearing_date" onkeydown="return false" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hearing Time</label>
                    <input type="time" class="form-control" id="hearing_time" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Incharge Official</label>
                    <select class="form-select" id="hearing_incharge_id" required>
                        <option value="">Select Official</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmScheduleBtn">Schedule Hearing</button>
            </div>
        </div>
    </div>
</div>

<!-- Record Hearing Modal - FULLY UPDATED -->
<div class="modal fade" id="recordHearingModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-success text-white">
                <h4 class="modal-title fw-bold">Record Hearing #<span id="recordHearingNum"></span></h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5">
                <form id="recordHearingForm">
                    <input type="hidden" id="recordHearingId">
                    <input type="hidden" id="recordBlotterId">

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Hearing Date *</label>
                            <input type="date" class="form-control form-control-lg" onkeydown="return false" id="record_hearing_date" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Hearing Time *</label>
                            <input type="time" class="form-control form-control-lg" id="record_hearing_time" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Barangay Incharge *</label>
                            <select class="form-select form-select-lg" id="record_hearing_incharge" required>
                                <option value="">Select Official</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-5">

                    <h5 class="mb-4 text-primary fw-bold">Attendees</h5>
                    <div class="table-responsive mb-4" style="max-height: 400px;">
                        <table class="table table-bordered">
                            <thead class="table-success">
                                <tr>
                                    <th width="5%"><input type="checkbox" id="selectAllAttendees"></th>
                                    <th>Name</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody id="attendeesTableBody"></tbody>
                        </table>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Discussion Summary *</label>
                        <textarea class="form-control form-control-lg" id="discussion_summary" rows="6" placeholder="Summarize what was discussed..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Outcome *</label>
                        <select class="form-select form-select-lg" id="hearing_outcome" required>
                            <option value="Unresolved">Unresolved - Continue to next hearing</option>
                            <option value="Resolved">Resolved - Case Closed</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light py-4">
                <button type="button" class="btn btn-secondary btn-lg px-5" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-lg px-5" onclick="$('#recordHearingForm').submit()">Save Hearing Record</button>
            </div>
        </div>
    </div>
</div>
<!-- View Hearings Modal -->
<div class="modal fade" id="viewHearingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Hearing Records</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewHearingContent">
                Loading...
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
    let residents = [], officials = [], currentBlotter = null;
    let nonResidentStorage = { complainant: [], defendant: [] };
    let complainantFile = null, defendantFile = null;
    let hearings = [];

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    // Show summon alert when status is "Forwarded to Police"
    $('#status').on('change', function() {
        $('#summonAlert').toggle(this.value === 'Forwarded to Police');
    });

    // File Upload Drop Zones - Complainant & Defendant
    $('#complainantFileDrop, #defendantFileDrop').on('click', function() {
        const type = this.id.includes('complainant') ? 'complainant' : 'defendant';
        $(`#${type}_file`).click();
    }).on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('border-primary bg-light');
    }).on('dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('border-primary bg-light');
    }).on('drop', function(e) {
        const type = e.currentTarget.id.includes('complainant') ? 'complainant' : 'defendant';
        const file = e.originalEvent.dataTransfer.files[0];
        if (file && file.size <= 5 * 1024 * 1024) {
            window[type + 'File'] = file;
            displayFile(file, type);
        } else {
            alert('File must be less than 5MB');
        }
    });

    $('#complainant_file, #defendant_file').on('change', function() {
        const type = this.id.includes('complainant') ? 'complainant' : 'defendant';
        const file = this.files[0];
        if (file && file.size <= 5 * 1024 * 1024) {
            window[type + 'File'] = file;
            displayFile(file, type);
        }
    });

    function displayFile(file, type) {
        $(`#${type}FileList`).html(`
            <div class="alert alert-success d-flex justify-content-between align-items-center py-2 mb-0">
                <div>
                    <i class="fas fa-paperclip me-2"></i>
                    <strong>${escapeHtml(file.name)}</strong>
                    <small class="text-muted ms-2">(${(file.size/1024).toFixed(1)} KB)</small>
                </div>
                <button type="button" class="btn-close" onclick="clearFile('${type}')"></button>
            </div>
        `);
    }

    window.clearFile = function(type) {
        window[type + 'File'] = null;
        $(`#${type}FileList`).empty();
    };

    // Load Residents & Officials
    function loadResidents() {
        $.post('partials/blotter_api.php', { action: 'fetch_residents' }, function(r) {
            if (r.success) residents = r.data;
        }, 'json');
    }

    function loadOfficials() {
        $.post('partials/blotter_api.php', { action: 'fetch_officials' }, function(r) {
            if (r.success) {
                officials = r.data;
                const $selects = $('#barangay_incharge_id, #hearing_incharge_id, #record_hearing_incharge');
                $selects.empty().append('<option value="">Select Official</option>');
                officials.forEach(o => {
                    $selects.append(`<option value="${o.id}">${escapeHtml(o.full_name)} - ${o.position || 'Official'}</option>`);
                });
            }
        }, 'json');
    }

    // Resident Search
    function showResidentList(type) {
        const query = $(`#${type}Search`).val().trim().toLowerCase();
        const $list = $(`#${type}List`).empty();

        if (!query) {
            $list.append('<div class="p-4 text-center text-muted fs-5">Type to search residents...</div>').addClass('show');
            return;
        }

        const filtered = residents.filter(r =>
            r.full_name.toLowerCase().includes(query) ||
            (r.house_number && r.house_number.toLowerCase().includes(query)) ||
            (r.street && r.street.toLowerCase().includes(query))
        );

        if (!filtered.length) {
            $list.append('<div class="p-4 text-center text-muted fs-5">No residents found.</div>').addClass('show');
            return;
        }

        filtered.forEach(r => {
            const isChecked = $(`#chk_${type}_${r.id}`).length > 0 ? $(`#chk_${type}_${r.id}`).is(':checked') : false;
            $list.append(`
                <div class="list-item p-3 border-bottom hover-bg-light">
                    <input type="checkbox" id="chk_${type}_${r.id}" data-type="${type}" data-id="${r.id}"
                           data-name="${escapeHtml(r.full_name)}" data-sex="${r.sex}" data-age="${r.age}"
                           data-address="${escapeHtml(r.address || '')}" data-contact="${r.contact_number || ''}"
                           ${isChecked ? 'checked' : ''}>
                    <label for="chk_${type}_${r.id}" class="d-block cursor-pointer ms-3">
                        <strong class="fs-5">${escapeHtml(r.full_name)}</strong><br>
                        <small class="text-muted">
                            Age: ${r.age} • ${r.sex} • ${escapeHtml(r.address || 'No address')}
                        </small>
                    </label>
                </div>
            `);
        });

        $list.addClass('show');
        $list.find('input[type="checkbox"]').on('change', function() {
            renderSelectedTable(type);
        });
    }

    $('#complainantSearch, #defendantSearch').on('input', function() {
        const type = this.id.includes('complainant') ? 'complainant' : 'defendant';
        showResidentList(type);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.card-body').length) {
            $('.searchable-list').removeClass('show');
        }
    });

    // Render Selected Persons Table (Residents + Non-Residents)
    function renderSelectedTable(type) {
        const $tbody = $(`#${type}SelectedBody`).empty();

        // Residents
        $(`#${type}List input[type="checkbox"]:checked`).each(function() {
            const d = $(this).data();
            $tbody.append(`
                <tr data-resident-id="${d.id}">
                    <td><strong>${d.name}</strong></td>
                    <td>${d.sex}</td>
                    <td>${d.age}</td>
                    <td><small>${d.address}</small></td>
                    <td>${d.contact || '—'}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="uncheckResident('${type}', '${d.id}')">
                            Remove
                        </button>
                    </td>
                </tr>
            `);
        });

        // Non-Residents
        nonResidentStorage[type].forEach(function(person, index) {
            $tbody.append(`
                <tr class="table-warning">
                    <td><strong>${escapeHtml(person.name)}</strong> <em>(Non-Resident)</em></td>
                    <td>${person.sex}</td>
                    <td>${person.age}</td>
                    <td><small>${escapeHtml(person.address)}</small></td>
                    <td>${person.contact || '—'}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeNonResident('${type}', ${index})">
                            Remove
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    window.uncheckResident = function(type, id) {
        $(`#chk_${type}_${id}`).prop('checked', false);
        renderSelectedTable(type);
    };

    window.toggleNonResidentForm = function(type) {
        $(`#${type}NonResidentForm`).toggleClass('show');
    };

    window.addNonResidentToTable = function(type) {
        const $form = $(`#${type}NonResidentForm`);
        const name = $form.find('.non-resident-name').val().trim();
        const sex = $form.find('.non-resident-sex').val();
        const age = $form.find('.non-resident-age').val().trim();
        const address = $form.find('.non-resident-address').val().trim();
        const contact = $form.find('.non-resident-contact').val().trim();

        if (!name || !age || !address) {
            alert('Name, Age, and Address are required for non-resident.');
            return;
        }

        nonResidentStorage[type].push({
            name: name,
            sex: sex,
            age: parseInt(age),
            address: address,
            contact: contact,
            is_resident: false
        });

        $form.find('input, select').val('');
        $form.removeClass('show');
        renderSelectedTable(type);
    };

    window.removeNonResident = function(type, index) {
        nonResidentStorage[type].splice(index, 1);
        renderSelectedTable(type);
    };

    function getSelectedPersons(type) {
        const selected = [];

        // Residents
        $(`#${type}List input[type="checkbox"]:checked`).each(function() {
            const d = $(this).data();
            selected.push({
                id: d.id,
                name: d.name,
                sex: d.sex,
                age: d.age,
                address: d.address,
                contact: d.contact,
                is_resident: true
            });
        });

        // Non-Residents
        nonResidentStorage[type].forEach(function(p) {
            selected.push({
                name: p.name,
                sex: p.sex,
                age: p.age,
                address: p.address,
                contact: p.contact,
                is_resident: false
            });
        });

        return selected;
    }

    // Render Main Blotter Table
    function renderBlotters(data) {
        const $tbody = $('#blotterTableBody').empty();
        if (!data || data.length === 0) {
            $tbody.append('<tr><td colspan="8" class="text-center py-5 text-muted fs-4">No blotter records found</td></tr>');
            return;
        }

        data.forEach(function(b) {
            const comp = JSON.parse(b.complainant_ids || '[]').map(p =>
                `<div><strong>${escapeHtml(p.name)}</strong><br><small>${p.sex}, ${p.age}yo</small></div>`
            ).join('');

            const def = JSON.parse(b.defendant_ids || '[]').map(p =>
                `<div><strong>${escapeHtml(p.name)}</strong><br><small>${p.sex}, ${p.age}yo</small></div>`
            ).join('');

            const badgeClass = b.status === 'Forwarded to Police' ? 'bg-danger' :
                              b.status === 'Resolved' ? 'bg-success' :
                              b.status === 'Unresolved' ? 'bg-warning' : 'bg-secondary';

            $tbody.append(`
                <tr>
                    <td><strong>${escapeHtml(b.case_id)}</strong></td>
                    <td>${comp || '<em>None</em>'}</td>
                    <td>${def || '<em>None</em>'}</td>
                    <td><small>${escapeHtml(b.nature_of_complaint)}</small></td>
                    <td><span class="badge ${badgeClass}">${b.status}</span></td>
                    <td><span class="badge bg-info">${b.hearing_count || 0}/3</span></td>
                    <td>${b.date_filed}</td>
                    <td class="no-print text-center">
                        <a href="admin/partials/generate_blotter_pdf.php?id=${b.id}" target="_blank" class="btn btn-sm btn-outline-primary me-1">
                            Print
                        </a>
                        <button class="btn btn-sm btn-warning me-1" onclick="editBlotter(${b.id})">Edit</button>
                    </td>
                </tr>
            `);
        });
    }

    function loadBlotters() {
        const search = $('#searchInput').val().trim();
        const status = $('#statusFilter').val();
        const date = $('#dateFilter').val();

        $.post('partials/blotter_api.php', {
            action: 'fetch_blotters',
            search: search,
            status: status,
            date: date
        }, function(r) {
            if (r.success) renderBlotters(r.data);
        }, 'json');
    }

    // Save Blotter Form
    $('#blotterForm').on('submit', function(e) {
        e.preventDefault();

        const complainants = getSelectedPersons('complainant');
        const defendants = getSelectedPersons('defendant');

        if (complainants.length === 0 || defendants.length === 0) {
            alert('Please add at least one complainant and one defendant.');
            return;
        }

        if (!$('#barangay_incharge_id').val()) {
            alert('Please select Barangay Incharge.');
            return;
        }

        const formData = new FormData();
        formData.append('action', $('#blotterId').val() ? 'update' : 'add');
        formData.append('id', $('#blotterId').val() || '');
        formData.append('case_id', $('#case_id').val());
        formData.append('complainant_ids', JSON.stringify(complainants));
        formData.append('defendant_ids', JSON.stringify(defendants));
        formData.append('nature_of_complaint', $('#nature_of_complaint').val());
        formData.append('details', $('#details').val());
        formData.append('date_filed', $('#date_filed').val());
        formData.append('incident_time', $('#incident_time').val());
        formData.append('location', $('#location').val());
        formData.append('barangay_incharge_id', $('#barangay_incharge_id').val());
        formData.append('status', $('#status').val());

        if (complainantFile) formData.append('complainant_file', complainantFile);
        if (defendantFile) formData.append('defendant_file', defendantFile);

        $('#saveBtn').prop('disabled', true).html('Saving...');

        $.ajax({
            url: 'partials/blotter_api.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(r) {
                $('#saveBtn').prop('disabled', false).html('Save Blotter');
                if (r.success) {
                    $('#blotterModal').modal('hide');
                    loadBlotters();
                    resetForm();
                    alert('Blotter saved successfully!');
                } else {
                    alert('Error: ' + (r.message || 'Unknown error'));
                }
            },
            error: function() {
                $('#saveBtn').prop('disabled', false).html('Save Blotter');
                alert('Network error. Please try again.');
            }
        });
    });

    function resetForm() {
        $('#blotterForm')[0].reset();
        $('#blotterId').val('');
        $('#case_id').val('');
        $('#modalTitle').text('File New Blotter');
        nonResidentStorage = { complainant: [], defendant: [] };
        complainantFile = defendantFile = null;
        $('#complainantFileList, #defendantFileList').empty();
        $('#complainantSelectedBody, #defendantSelectedBody').empty();
        $('.non-resident-form').removeClass('show');
        $('.searchable-list').removeClass('show');
        $('#summonAlert').hide();

        const today = new Date().toISOString().split('T')[0];
        $('#date_filed').val(today);
        $('#incident_time').val('12:00');
    }

    window.editBlotter = function(id) {
        $.post('partials/blotter_api.php', { action: 'get', id: id }, function(r) {
            if (!r.success || !r.data) {
                alert('Blotter not found');
                return;
            }

            const b = r.data;
            resetForm();

            $('#blotterId').val(b.id);
            $('#case_id').val(b.case_id);
            $('#nature_of_complaint').val(b.nature_of_complaint);
            $('#details').val(b.details);
            $('#date_filed').val(b.date_filed);
            $('#incident_time').val(b.incident_time || '12:00');
            $('#location').val(b.location || '');
            $('#barangay_incharge_id').val(b.barangay_incharge_id || '');
            $('#status').val(b.status).trigger('change');

            const comp = JSON.parse(b.complainant_ids || '[]');
            const def = JSON.parse(b.defendant_ids || '[]');

            nonResidentStorage.complainant = comp.filter(p => !p.is_resident);
            nonResidentStorage.defendant = def.filter(p => !p.is_resident);

            setTimeout(function() {
                comp.filter(p => p.is_resident).forEach(p => {
                    $(`#chk_complainant_${p.id}`).prop('checked', true);
                });
                def.filter(p => p.is_resident).forEach(p => {
                    $(`#chk_defendant_${p.id}`).prop('checked', true);
                });
                renderSelectedTable('complainant');
                renderSelectedTable('defendant');
            }, 500);

            $('#modalTitle').text('Edit Blotter - ' + b.case_id);
            $('#blotterModal').modal('show');
        }, 'json');
    };

    function clearFilters() {
        $('#searchInput, #statusFilter, #dateFilter').val('');
        loadBlotters();
    }

    $('#searchInput, #statusFilter, #dateFilter').on('input change', function() {
        clearTimeout(window.filterTO);
        window.filterTO = setTimeout(loadBlotters, 400);
    });

    $(document).ready(function() {
        loadResidents();
        loadOfficials();
        loadBlotters();

        const today = new Date().toISOString().split('T')[0];
        $('#date_filed').val(today);
        $('#incident_time').val('12:00');

        $('#blotterModal').on('hidden.bs.modal', resetForm);
    });
</script>
</body>
</html>