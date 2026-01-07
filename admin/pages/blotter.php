<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Management - Barangay System</title>
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
                <h1 class="mb-1 fw-bold text-dark fs-3">Complaint Management</h1>
                <p class="text-muted mb-0">Manage all barangay complaint records and hearings</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#blotterModal">
                    <i class="fas fa-plus me-2"></i> File Complaint
                </button>
                <a href="partials/generate_blotter_pdf.php?full_name=<?php echo $_SESSION['full_name']; ?>" target="_blank" class="btn btn-success shadow-sm text-white">
                    <i class="fas fa-print me-2"></i> Print
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
                        <input type="date" class="form-control" id="dateFilter">
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
                <h4 class="mb-0 fw-bold"><i class="fas fa-gavel me-3"></i> Complaint Records</h4>
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
                    <h3 class="modal-title fw-bold" id="modalTitle">File New Complaint</h3>
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
                                <input type="date" class="form-control form-control-lg" id="date_filed" max="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Incident Time *</label>
                                <input type="time" class="form-control form-control-lg" id="incident_time" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status *</label>
                                <select class="form-select form-select-lg" id="status" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Resolved">Resolved</option>
                                    <option value="Unresolved">Unresolved</option>
                                    <option value="Forwarded to Police">Forwarded to Police</option>
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
                            <div class="col-md-6">
                                <label class="form-label">Hearing Schedule Date *</label>
                                <input type="date" class="form-control form-control-lg" id="date_schedule" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+15 days')) ?>" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hearing Time *</label>
                                <input type="time" class="form-control form-control-lg" id="time_schedule" required>
                            </div>
                        </div>


                        <!-- Summon Alert -->
                        <div class="alert alert-warning border border-danger" id="summonAlert" style="display:none;">
                            <h5><i class="fas fa-exclamation-triangle"></i> Case Forwarded to Police</h5>
                            <p class="mb-2">This case has been forwarded to the police station.</p>
                            <a href="partials/SUMMON.docx" download class="btn btn-danger">
                                <i class="fas fa-download"></i> Download Summon Document
                            </a>
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
                                                <div class="col-12"><input type="text" class="form-control non-resident-contact" placeholder="Contact Number" maxlength="11" pattern="[0-9]{1,11}"></div>
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
                                                <div class="col-12"><input type="text" class="form-control non-resident-contact" placeholder="Contact Number" maxlength="11" pattern="[0-9]{1,11}"></div>
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
                        <!-- ======================== FILE ATTACHMENTS SECTION ======================== -->
                        <div class="row g-5 mt-4">
                            <div class="col-lg-6">
                                <div class="card border-success shadow-sm">
                                    <div class="card-header bg-success text-white py-3">
                                        <h5 class="mb-0"><i class="fas fa-paperclip"></i> Complainant Attachment (Optional)</h5>
                                    </div>
                                    <div class="card-body">
                                        <input type="file" id="complainant_file" accept=".pdf,.jpg,.jpeg,.png" class="d-none">
                                        <div id="complainantFileDrop" class="border border-2 border-dashed rounded-3 p-5 text-center cursor-pointer hover-bg-light">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-success mb-3"></i>
                                            <p class="mb-2"><strong>Click to upload</strong> or drag and drop</p>
                                            <small class="text-muted">PDF, JPG, PNG · Max 5MB</small>
                                            <!-- <input type="file" id="complainant_file" accept=".pdf,.jpg,.jpeg,.png" class="d-none"> -->
                                        </div>
                                        <div id="complainantFileList" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="card border-danger shadow-sm">
                                    <div class="card-header bg-danger text-white py-3">
                                        <h5 class="mb-0"><i class="fas fa-paperclip"></i> Defendant Attachment (Optional)</h5>
                                    </div>
                                    <div class="card-body">
                                        <input type="file" id="defendant_file" accept=".pdf,.jpg,.jpeg,.png" class="d-none">
                                        <div id="defendantFileDrop" class="border border-2 border-dashed rounded-3 p-5 text-center cursor-pointer hover-bg-light">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-danger mb-3"></i>
                                            <p class="mb-2"><strong>Click to upload</strong> or drag and drop</p>
                                            <small class="text-muted">PDF, JPG, PNG · Max 5MB</small>
                                            <!-- <input type="file" id="defendant_file" accept=".pdf,.jpg,.jpeg,.png" class="d-none"> -->
                                        </div>
                                        <div id="defendantFileList" class="mt-3"></div>
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
                    <button type="button" class="btn btn-success px-5" id="saveBtn" onclick="$('#blotterForm').submit()">Save Complaint</button>
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
                        <input type="date" class="form-control" id="hearing_date" required>
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
                                <input type="date" class="form-control" id="record_hearing_date" name="record_hearing_date" min="<?=  date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+15 days')) ?>" value="<?= date('Y-m-d') ?>" required>
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
                                <option value="Forwarded to Police">Forwarded to Police</option>
                            </select>
                        </div>
                        <div class="mb-4" id="record_hearing_date_div" name="record_hearing_date_div" style="display: none;">
                            <label class="form-label">Next Hearing Schedule Date *</label>
                            <input type="date" class="form-control form-control-lg" id="date_schedule_record" min="<?= date('Y-m-d', strtotime('+1 days')) ?>" max="<?= date('Y-m-d', strtotime('+15 days')) ?>" value="<?= date('Y-m-d', strtotime('+15 days')) ?>" required>
                        </div>
                        <div class="mb-4" id="record_hearing_time_div" name="record_hearing_date_div" style="display: none;">
                            <label class="form-label">Next Hearing Time *</label>
                            <input type="time" class="form-control form-control-lg" id="time_schedule_record" required>
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
    <script>

        let residents = [],
            officials = [],
            currentBlotter = null;
        let nonResidentStorage = {
            complainant: [],
            defendant: []
        };
        let complainantFile = null,
            defendantFile = null; // RESTORED
        let hearings = [];

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        // ======================== SUMMON ALERT TOGGLE (RESTORED) ========================
        $('#status').on('change', function() {
            $('#summonAlert').toggle(this.value === 'Test');
        });

        // ======================== FILE UPLOAD FOR COMPLAINANT & DEFENDANT (FULLY RESTORED) ========================
        $('#complainantFileDrop, #defendantFileDrop').on('click', function() {
            const type = this.id.includes('complainant') ? 'complainant' : 'defendant';
            $(`#${type}_file`).click();
        }).on('dragover dragenter', e => {
            e.preventDefault();
            $(e.currentTarget).addClass('border-primary bg-light');
        }).on('dragleave drop', e => {
            e.preventDefault();
            $(e.currentTarget).removeClass('border-primary bg-light');
        }).on('drop', e => {
            const type = e.currentTarget.id.includes('complainant') ? 'complainant' : 'defendant';
            const file = e.originalEvent.dataTransfer.files[0];
            if (file && file.size <= 5 * 1024 * 1024) {
                window[type + 'File'] = file;
                displayFile(file, type);
            } else {
                alert('File too large or invalid');
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
        <div class="alert alert-success d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-paperclip"></i>
                <strong>${escapeHtml(file.name)}</strong>
                <small class="text-muted">(${(file.size/1024).toFixed(1)} KB)</small>
            </div>
            <button type="button" class="btn-close" onclick="clearFile('${type}')"></button>
        </div>
    `);
        }

        window.clearFile = function(type) {
            window[type + 'File'] = null;
            $(`#${type}FileList`).empty();
        };

        // ======================== RESIDENTS & OFFICIALS ========================
        function loadResidents() {
            $.post('partials/blotter_api.php', {
                action: 'fetch_residents'
            }, r => {
                if (r.success) residents = r.data;
            }, 'json');
        }

        function loadOfficials() {
            $.post('partials/blotter_api.php', {
                action: 'fetch_officials'
            }, r => {
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

        // ======================== RESIDENT SEARCH & SELECTION (UNCHANGED) ========================
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
            <div class="list-item p-3 border-bottom">
                <input type="checkbox" id="chk_${type}_${r.id}" data-type="${type}" data-id="${r.id}"
                       data-name="${escapeHtml(r.full_name)}" data-sex="${r.sex}" data-age="${r.age}"
                       data-address="${escapeHtml(r.address || '')}" data-contact="${r.contact || ''}" ${isChecked ? 'checked' : ''}>
                <label for="chk_${type}_${r.id}" class="mb-0 d-block cursor-pointer">
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
                <td><button type="button" class="btn btn-danger btn-sm" onclick="uncheckResident('${type}', '${d.id}')">Remove</button></td>
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
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeNonResident('${type}', ${idx})">Remove</button></td>
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
            nonResidentStorage[type].push({
                name,
                sex,
                age,
                address,
                contact
            });
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
            nonResidentStorage[type].forEach(p => selected.push({
                ...p,
                is_resident: false
            }));
            return selected;
        }

        // ======================== RENDER BLOTTERS (WITH PRINT + ALL BUTTONS) ========================
        function renderBlotters(data) {
            const $tbody = $('#blotterTableBody').empty();
            if (!data || data.length === 0) {
                $tbody.append('<tr><td colspan="8" class="text-center py-5 text-muted fs-4">No complaint records found</td></tr>');
                return;
            }

            data.forEach(b => {
                let comp = '',
                    def = '';
                try {
                    comp = JSON.parse(b.complainant_ids || '[]').map(p =>
                        `<div><strong>${escapeHtml(p.name)}</strong><br><small>${p.sex || ''}, ${p.age || ''}yo</small></div>`
                    ).join('') || '<em class="text-muted">None</em>';
                    def = JSON.parse(b.defendant_ids || '[]').map(p =>
                        `<div><strong>${escapeHtml(p.name)}</strong><br><small>${p.sex || ''}, ${p.age || ''}yo</small></div>`
                    ).join('') || '<em class="text-muted">None</em>';
                } catch (e) {
                    comp = def = '<em class="text-danger">Invalid data</em>';
                }

                const statusBadge = {
                    'Resolved': 'bg-success',
                    'Unresolved': 'bg-warning',
                    'Forwarded to Police': 'bg-danger'
                } [b.status] || 'bg-secondary';
                let pandaya = 0;
                let hearingCount = parseInt(b.hearing_count || 0);
                                const lastRecorded = parseInt(b.last_hearing_recorded || 0);
                const canRecord = hearingCount >= lastRecorded;
                if (hearingCount === 2)
                {
                    pandaya = 2;
                }
                if (hearingCount === 3)
                {
                    pandaya = 3;
                }
                if (hearingCount > 3)
                {
                    pandaya = 100;
                }
                hearingCount = parseInt(b.hearing_count - 1 || 0);
                const canSchedule = b.status !== 'Resolved' && hearingCount < 3;

                const official_full_name = "<?php echo $_SESSION['full_name']; ?>";
                let canRecordStart = true;
                let summon = false;
                if (hearingCount >= 3){
                    canRecordStart = false;
                }

                let canRecordStatus = true;

                if (b.status === 'Resolved' || b.status === 'Forwarded to Police'){
                    canRecordStatus = false;
                }
                if (hearingCount === 0)
                {
                    hearingCount = hearingCount + 1;
                }
                if (pandaya === 0){
                    summon = true;
                }
                if (pandaya === 2){
                    hearingCount = hearingCount + 1;
                }
                if (pandaya === 3){
                    hearingCount = 3;
                }
                if (pandaya > 4){
                    summon = false;
                }
                console.log(hearingCount);
                console.log('can', canRecordStart);
                console.log('can1', canRecordStatus);
                console.log('can2', canRecord);
                console.log('status', b.status);
                const officialDuty = "<?php echo $_SESSION['full_name']; ?>";

                $tbody.append(`
            <tr>
                <td><strong>${escapeHtml(b.case_id || 'N/A')}</strong></td>
                <td>${comp}</td>
                <td>${def}</td>
                <td><small>${escapeHtml(b.nature_of_complaint || '—')}</small></td>
                <td><span class="badge ${statusBadge} px-3 py-2">${escapeHtml(b.status)}</span></td>
                <td class="text-center"><span class="badge bg-info">${hearingCount}/3</span></td>
                <td><small>${b.date_filed || '—'}</small></td>
                <td class="no-print text-center">
                    <div class="btn-group-vertical btn-group-sm d-block">
                        <a href="partials/generate_blotter_pdf.php?id=${b.id}&full_name=${officialDuty}" target="_blank" class="btn btn-outline-primary mb-1">
                            <i class="fas fa-print"></i> Print
                        </a>
                        ${summon ? `<a href="partials/generate_summon_pdf.php?id=${b.id}&full_name=${official_full_name}" target="_blank" class="btn btn-outline-primary mb-1">
                            <i class="fas fa-print"></i> Print Summon
                        </a>`: ''}
                        <button class="btn btn-warning mb-1" onclick="editBlotter(${b.id})"><i class="fas fa-edit"></i> Edit</button>
                        ${canRecordStatus ? `${canRecordStart ? `${canRecord ? `<button class="btn btn-success mb-1" onclick="recordHearing(${b.id}, ${hearingCount})"><i class="fas fa-marker"></i> Record #${hearingCount}</button>` : ''}` : ''}` : ''}
                        <button class="btn btn-info text-white" onclick="viewHearings(${b.id})"><i class="fas fa-gavel"></i> View</button>
                    </div>
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
                search,
                status,
                date
            }, r => {
                if (r.success) renderBlotters(r.data);
            }, 'json');
        }

        // ======================== SAVE BLOTTER (WITH FILES + SUMMON LOGIC) ========================
        $('#blotterForm').on('submit', function(e) {
            e.preventDefault();
            const complainants = getSelectedPersons('complainant');
            const defendants = getSelectedPersons('defendant');
            if (complainants.length === 0 || defendants.length === 0) return alert('Add at least one complainant and one defendant.');
            if (!$('#barangay_incharge_id').val()) return alert('Please select Barangay Incharge.');

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
            formData.append('date_schedule', $('#date_schedule').val());
            formData.append('time_schedule', $('#time_schedule').val());
            if (complainantFile) formData.append('complainant_file', complainantFile);
            if (defendantFile) formData.append('defendant_file', defendantFile);

            $('#saveBtn').prop('disabled', true).html('Saving...');
            $.ajax({
                url: 'partials/blotter_api.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: r => {
                    $('#saveBtn').prop('disabled', false).html('Save Complaint');
                    if (r.success) {
                        $('#blotterModal').modal('hide');
                        loadBlotters();
                        resetForm();
                        alert('Complaint saved successfully!');
                    } else {
                        alert('Error: ' + (r.message || 'Failed'));
                    }
                }
            });
        });

        function resetForm() {
            $('#blotterForm')[0].reset();
            $('#blotterId, #case_id').val('');
            $('#modalTitle').text('File New Complaint');
            nonResidentStorage = {
                complainant: [],
                defendant: []
            };
            complainantFile = defendantFile = null;
            $('#complainantSelectedBody, #defendantSelectedBody, #complainantFileList, #defendantFileList').empty();
            $('.non-resident-form, .searchable-list').removeClass('show');
            $('#summonAlert').hide();
            const today = new Date().toISOString().split('T')[0];
            $('#date_filed').val(today);
            $('#incident_time').val('12:00');
        }

        window.editBlotter = function(id) {
            $.post('partials/blotter_api.php', {
                action: 'get',
                id
            }, r => {
                if (!r.success || !r.data) return alert('Complaint not found');
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
                $('#status').val(b.status);

                const comp = JSON.parse(b.complainant_ids || '[]');
                const def = JSON.parse(b.defendant_ids || '[]');
                nonResidentStorage.complainant = comp.filter(p => !p.is_resident);
                nonResidentStorage.defendant = def.filter(p => !p.is_resident);

                setTimeout(() => {
                    comp.filter(p => p.is_resident).forEach(p => $(`#chk_complainant_${p.id}`).prop('checked', true));
                    def.filter(p => p.is_resident).forEach(p => $(`#chk_defendant_${p.id}`).prop('checked', true));
                    renderSelectedTable('complainant');
                    renderSelectedTable('defendant');
                }, 300);

                $('#modalTitle').text('Edit Complaint - ' + b.case_id);
                $('#blotterModal').modal('show');
            }, 'json');
        };

        window.scheduleHearing = function(blotterId, hearingNumber) {
            $('#scheduleBlotterId').val(blotterId);
            $('#scheduleHearingNumber').val(hearingNumber);
            $('#hearing_date').val('');
            $('#hearing_time').val('');
            $('#hearing_incharge_id').val('');
            $('#scheduleHearingModal').modal('show');
        };

        $('#confirmScheduleBtn').on('click', function() {
            const date = $('#hearing_date').val();
            const time = $('#hearing_time').val();
            const incharge = $('#hearing_incharge_id').val();
            if (!date || !time || !incharge) return alert('Please fill all hearing fields.');
            $.post('partials/blotter_api.php', {
                action: 'schedule_hearing',
                blotter_id: $('#scheduleBlotterId').val(),
                hearing_number: $('#scheduleHearingNumber').val(),
                hearing_date: date,
                hearing_time: time,
                barangay_incharge_id: incharge
            }, r => {
                if (r.success) {
                    $('#scheduleHearingModal').modal('hide');
                    loadBlotters();
                    alert('Hearing scheduled successfully!');
                } else alert('Error: ' + (r.message || 'Unknown'));
            }, 'json');
        });

        window.recordHearing = function(blotterId, hearingNumber) {
            $.post('partials/blotter_api.php', {
                action: 'get_blotter_with_hearings',
                id: blotterId
            }, r => {
                if (!r.success) return alert('Error loading complaint data.');
                currentBlotter = r.data;
                hearings = r.hearings || [];
                const h = hearings.find(h => h.hearing_number == hearingNumber) || {};
                $('#recordHearingNum').text(hearingNumber);
                $('#recordHearingId').val(h.id || '');
                $('#recordBlotterId').val(blotterId);
                $('#record_hearing_time').val(h.hearing_time || '');
                $('#record_hearing_incharge').val(h.barangay_incharge_id || '');
                $('#discussion_summary').val(h.discussion_summary || '');
                $('#hearing_outcome').val(h.outcome || 'Unresolved');
                console.log('Hearing number:', hearingNumber);
                console.log('Hearing id:', h.id);
                console.log('Blotter id:', blotterId);
                const dateHearing = document.getElementById('record_hearing_date_div');
                const timeHearing = document.getElementById('record_hearing_time_div');
                if (hearingNumber === 3) {
                    console.log('Disabling options for hearing number 3');
                    updatehearing_outcome(hearingNumber);
                    dateHearing.style.display = 'none';
                    document.getElementById('date_schedule_record').removeAttribute('required')
                    timeHearing.style.display = 'none';
                    document.getElementById('date_schedule_record').removeAttribute('required')

                }
                else {
                    dateHearing.style.display = 'block';
                    timeHearing.style.display = 'block';
                }
                const allPeople = [...JSON.parse(currentBlotter.complainant_ids || '[]'), ...JSON.parse(currentBlotter.defendant_ids || '[]')];
                const attendees = h.attendees ? JSON.parse(h.attendees) : [];
                const $tbody = $('#attendeesTableBody').empty();
                allPeople.forEach(p => {
                    const checked = Array.isArray(attendees) && attendees.includes(p.name) ? 'checked' : '';
                    $tbody.append(`
                <tr>
                    <td><input type="checkbox" class="attendee-check" data-name="${escapeHtml(p.name)}" ${checked}></td>
                    <td>${escapeHtml(p.name)}</td>
                    <td><span class="badge bg-${p.is_resident ? 'info' : 'warning'}">${p.is_resident ? 'Resident' : 'Non-Resident'}</span></td>
                </tr>
            `);
                });
                $('#recordHearingModal').modal('show');
            }, 'json');
        };

        function updatehearing_outcome(numberHearing) {
            const toRestrict = ['Unresolved'];
            // Iterate options and disable/enable accordingly
            Array.from(hearing_outcome.options).forEach(opt => {
            if (toRestrict.includes(opt.value)) {
                opt.disabled = !Number.isNaN(numberHearing) && numberHearing === 3;
            }
            });

            // If current selection is now disabled, reset to empty
            const selected = hearing_outcome.value;
            const selectedOption = hearing_outcome.querySelector(`option[value="${CSS.escape(selected)}"]`);
            if (selectedOption && selectedOption.disabled) {
            hearing_outcome.value = '';
        }
        }

        $('#recordHearingForm').on('submit', function(e) {
            e.preventDefault();
            const attendees = $('.attendee-check:checked').map(function() {
                return $(this).data('name');
            }).get();
            if (!attendees.length) return alert('Select at least one attendee.');
            if (!$('#record_hearing_date').val() || !$('#record_hearing_time').val() || !$('#record_hearing_incharge').val()) {
                return alert('Please fill Date, Time, and Incharge.');
            }
            const formData = new FormData();
            console.log($('#recordHearingId').val());
            formData.append('action', 'record_hearing');
            formData.append('hearing_id', $('#recordHearingId').val());
            formData.append('blotter_id', $('#recordBlotterId').val());
            formData.append('hearing_date', $('#record_hearing_date').val());
            formData.append('hearing_time', $('#record_hearing_time').val());
            formData.append('barangay_incharge_id', $('#record_hearing_incharge').val());
            formData.append('attendees', JSON.stringify(attendees));
            formData.append('summary', $('#discussion_summary').val());
            formData.append('outcome', $('#hearing_outcome').val());
            formData.append('nexthearingSchedule', $('#date_schedule_record').val());
            formData.append('nexthearingTimeSchedule', $('#time_schedule_record').val());

            $.ajax({
                url: 'partials/blotter_api.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: r => {
                    if (r.success) {
                        $('#recordHearingModal').modal('hide');
                        loadBlotters();
                        alert('Hearing recorded successfully!');
                    } else alert('Error: ' + (r.message || 'Failed'));
                }
            });
        });

        $('#selectAllAttendees').on('change', function() {
            $('.attendee-check').prop('checked', this.checked);
        });

        window.viewHearings = function(id) {
            $('#viewHearingContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i></div>');
            $('#viewHearingModal').modal('show');
            $.post('partials/blotter_api.php', {
                action: 'get_hearings',
                blotter_id: id
            }, r => {
                let html = '';
                if (!r.success || !r.data || r.data.length === 0) {
                    html = '<div class="text-center text-muted py-5 fs-4">No hearings recorded yet.</div>';
                } else {
                    r.data.forEach(h => {
                        let att = '—';
                        if (h.attendees) {
                            try {
                                let list = typeof h.attendees === 'string' ? JSON.parse(h.attendees) : h.attendees;
                                if (typeof list === 'string') list = JSON.parse(list);
                                if (Array.isArray(list)) att = list.map(n => escapeHtml(n)).join(', ');
                            } catch (e) {
                                att = 'Error';
                            }
                        }
                        html += `
                    <div class="card mb-4 border-start border-primary border-5 shadow-sm">
                        <div class="card-body">
                            <h5 class="text-primary fw-bold mb-3">Hearing #${h.hearing_number}</h5>
                            <div class="row g-3 fs-6">
                                <div class="col-md-6"><strong>Date:</strong> ${h.hearing_date || '—'}</div>
                                <div class="col-md-6"><strong>Time:</strong> ${h.hearing_time || '—'}</div>
                                <div class="col-12"><strong>Incharge:</strong> ${escapeHtml(h.official_name || '—')}</div>
                                <div class="col-12"><strong>Attendees:</strong><br><span class="text-muted">${att}</span></div>
                                <div class="col-12"><strong>Summary:</strong><br><span class="text-muted">${escapeHtml(h.discussion_summary || '—')}</span></div>
                                <div class="col-12"><strong>Outcome:</strong> <span class="badge bg-${h.outcome === 'Resolved' ? 'success' : 'warning'}">${h.outcome || 'Unresolved'}</span></div>
                            </div>
                        </div>
                    </div>
                `;
                    });
                }
                $('#viewHearingContent').html(html);
            }, 'json');
        };

        function printTable() {
            window.print();
        }

        function clearFilters() {
            $('#searchInput, #statusFilter, #dateFilter').val('');
            loadBlotters();
        }

        $('#searchInput, #statusFilter, #dateFilter').on('input change', () => {
            clearTimeout(window.filterTO);
            window.filterTO = setTimeout(loadBlotters, 400);
        });

        $(document).ready(() => {
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