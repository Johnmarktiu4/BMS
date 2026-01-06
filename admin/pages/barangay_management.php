<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Officials Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .card {
            border-radius: 0.5rem;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
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

        .form-control,
        .form-select {
            border-radius: 0.375rem;
        }

        #profilePreview {
            width: 120px;
            height: 120px;
            transition: border-color 0.2s ease;
        }

        #profilePreview:hover {
            border-color: #0d6efd;
        }

        .alert {
            border-radius: 0.5rem;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .modal-lg {
                max-width: 95%;
                margin: 0.5rem;
            }

            #profilePreview {
                width: 100px;
                height: 100px;
            }

            #cameraFeed {
                max-width: 250px;
            }
        }

        #officialResidentDropdown {
            border-top: none;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

        #officialResidentDropdown>div:hover {
            background-color: #e9ecef;
        }
    </style>
</head>

<body>

    <?php require_once 'partials/db_conn.php'; ?>

    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0">Barangay Officials Management</h2>
                        <p class="text-muted mb-0">Manage and view all barangay officials</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addOfficialModal">
                            Add Official
                        </button>
<a href="partials/generate_officials_pdf.php" target="_blank" class="btn btn-success btn-lg">
    <i class="fas fa-print me-2"></i>Print  
</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Controls -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4 col-sm-6">
                                <label for="searchInput" class="form-label">Search Officials</label>
                                <div class="input-group">
                                    <span class="input-group-text">Search</span>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name or contact...">
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label for="positionFilter" class="form-label">Position</label>
                                <select class="form-select" id="positionFilter">
                                    <option value="">All</option>
                                    <option value="Barangay Captain">Captain</option>
                                    <option value="Kagawad">Kagawad</option>
                                    <option value="Secretary">Secretary</option>
                                    <option value="Treasurer">Treasurer</option>
                                    <option value="SK Chairman">SK Chairman</option>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label for="statusFilter" class="form-label">Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
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
                                <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Officials Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Officials List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="officialsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Profile</th>
                                        <th>Full Name</th>
                                        <th>Position</th>
                                        <th>Term Start</th>
                                        <th>Term End</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="officialsTableBody"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div><small class="text-muted" id="paginationInfo">Showing 0 to 0 of 0 entries</small></div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Official Modal -->
    <div class="modal fade" id="addOfficialModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title" id="addOfficialModalLabel">Add New Official</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <form id="addOfficialForm">
                        <input type="hidden" id="officialId" name="id">
                        <input type="hidden" id="fullName" name="full_name">
                        <input type="hidden" id="selectedOfficialId" name="resident_id">

                        <!-- Profile Picture -->
                        <!-- <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div id="profilePreview" class="mx-auto mb-3 rounded-circle overflow-hidden d-flex align-items-center justify-content-center bg-white border" style="width: 120px; height: 120px;">
                                <i class="fas fa-user fa-3x text-muted"></i>
                            </div>
                            <video id="cameraFeed" autoplay playsinline style="width: 100%; max-width: 300px; display: none;" class="mb-2"></video>
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-primary btn-sm" id="startCameraBtn" onclick="startCamera()">Start Camera</button>
                                <button type="button" class="btn btn-success btn-sm" id="capturePhotoBtn" onclick="capturePhoto()" disabled>Capture</button>
                                <button type="button" class="btn btn-danger btn-sm" id="stopCameraBtn" onclick="stopCamera()" disabled>Stop</button>
                            </div>
                            <input type="hidden" id="profilePicture" name="profile_picture">
                            <small class="text-muted d-block mt-2">Use camera to capture profile picture</small>
                        </div>
                    </div> -->

                        <!-- Official Info -->
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h6 class="mb-0">Official Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- SEARCHABLE RESIDENT -->
                                    <div class="col-md-6">
                                        <label class="form-label">Resident (Full Name) <span class="text-danger">*</span></label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control" id="officialResidentSearchInput" placeholder="Search resident..." autocomplete="off" required>
                                            <div id="officialResidentDropdown" class="position-absolute w-100 bg-white border rounded-bottom shadow-sm" style="top:100%; max-height:200px; overflow-y:auto; display:none; z-index:1070;">
                                                <div class="p-2 text-center text-muted">Type to search residents...</div>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-1" id="selectedOfficialName">Select Resident...</small>
                                    </div>

                                    <!-- POSITION -->
                                    <div class="col-md-6">
                                        <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
                                        <select class="form-select" id="position" name="position" required>
                                            <option value="">Select Position</option>
                                            <option value="Barangay Captain">Barangay Captain</option>
                                            <option value="Kagawad">Kagawad</option>
                                            <option value="Secretary">Secretary</option>
                                            <option value="Treasurer">Treasurer</option>
                                            <option value="SK Chairman">SK Chairman</option>
                                        </select>
                                    </div>

                                    <!-- TERM START -->
                                    <!-- <div class="col-md-6">
                                        <label for="termStart" class="form-label">Term Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="termStart" name="term_start_date" required>
                                    </div> -->

                                    <!-- TERM END -->
                                    <!-- <div class="col-md-6">
                                        <label for="termEnd" class="form-label">Term End Date</label>
                                        <input type="date" class="form-control" id="termEnd" name="term_end_date">
                                    </div> -->

                                    <!-- STATUS -->
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                        </select>
                                    </div>

                                    <!-- CONTACT -->
                                    <div class="col-md-6">
                                        <label for="contact" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="contact" name="contact" placeholder="09XXXXXXXXX" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveOfficialBtn" onclick="saveOfficial()">Save Official</button>
                </div>
            </div>
        </div>
    </div>
<!-- Confirmation Modal (Are you sure?) -->
<div class="modal fade" id="confirmArchiveOfficialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Archive</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to archive this official?</p>
                <p class="text-muted small">This action can be undone later if needed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                <button type="button" class="btn btn-danger" id="proceedArchiveOfficialBtn">Yes, Archive</button>
            </div>
        </div>
    </div>
</div>

<!-- Reason Modal (exactly like residents) -->
<div class="modal fade" id="archiveOfficialReasonModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-archive me-2"></i>Archive Official</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="archiveOfficialForm">
                    <input type="hidden" id="archiveOfficialId">
                    <label for="archiveOfficialReason" class="form-label">Reason <span class="text-danger">*</span></label>
                    <select class="form-select" id="archiveOfficialReason" required>
                        <option value="" disabled selected>Select reason</option>
                        <option value="Deceased">Deceased</option>
                        <option value="Transferred Residence">Transferred Residence</option>
                        <option value="Duplicate Record">Duplicate Record</option>
                        <option value="Term Ended">Term Ended</option>
                        <option value="Resigned">Resigned</option>
                    </select>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="confirmArchiveOfficial()">Archive</button>
            </div>
        </div>
    </div>
</div>
    <!-- View Modal -->
    <div class="modal fade" id="viewOfficialModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 shadow-lg">
                <div class="modal-header bg-info text-white border-0">
                    <h5 class="modal-title">Official Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div id="viewProfilePicture" class="mx-auto mb-3 rounded-circle overflow-hidden d-flex align-items-center justify-content-center bg-white border" style="width: 120px; height: 120px;">
                                <i class="fas fa-user fa-3x text-muted"></i>
                            </div>
                            <h5 id="viewFullName" class="mb-3"></h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <p><strong>Position:</strong> <span id="viewPosition"></span></p>
                                    <p><strong>Term Start:</strong> <span id="viewTermStart"></span></p>
                                    <p><strong>Term End:</strong> <span id="viewTermEnd"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Contact:</strong> <span id="viewContact"></span></p>
                                    <p><strong>Status:</strong> <span id="viewStatus" class="badge"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let stream = null;
        const video = document.getElementById('cameraFeed');
        const canvas = document.createElement('canvas');
        const profilePictureInput = document.getElementById('profilePicture');
        const profilePreview = document.getElementById('profilePreview');
        let currentPage = 1,
            entriesPerPage = 10;
        let officialResidents = [];

        // Load Residents
        function loadOfficialResidents() {
            $.get('partials/get_residents_up.php', data => {
                officialResidents = data || [];
            }, 'json').fail(() => showAlert('danger', 'Failed to load residents.'));
        }

        // Search Residents
        function searchOfficialResidents() {
            const query = $('#officialResidentSearchInput').val().trim().toLowerCase();
            const $dropdown = $('#officialResidentDropdown').empty();
            if (!query) {
                $dropdown.hide();
                resetResidentSelection();
                return;
            }
            const filtered = officialResidents.filter(r =>
                r.full_name.toLowerCase().includes(query) ||
                (r.first_name && r.first_name.toLowerCase().includes(query)) ||
                (r.last_name && r.last_name.toLowerCase().includes(query))
            );
            if (!filtered.length) {
                $dropdown.append('<div class="p-2 text-center text-muted">No residents found.</div>').show();
                return;
            }
            filtered.forEach(res => {
                $dropdown.append(`
            <div class="px-3 py-2 border-bottom" style="cursor:pointer;"
                 onclick="selectOfficialResident(${res.id}, '${res.full_name.replace(/'/g, "\\'")}', '${res.contact_number || ''}')">
                <strong>${res.full_name}</strong><br>
                <small class="text-muted">Age: ${res.age} | ${res.sex} | ${res.house_number} ${res.street}</small>
            </div>
        `);
            });
            $dropdown.show();
        }

        function selectOfficialResident(id, name, contact) {
            $('#selectedOfficialId').val(id);
            $('#fullName').val(name);
            $('#officialResidentSearchInput').val(name);
            $('#selectedOfficialName').text(name);
            $('#contact').val(contact);
            $('#officialResidentDropdown').hide();
        }

        function resetResidentSelection() {
            $('#selectedOfficialName').text('Select Resident...');
            $('#selectedOfficialId').val('');
            $('#fullName').val('');
        }

        // Camera
        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user'
                    }
                });
                video.srcObject = stream;
                video.style.display = 'block';
                $('#capturePhotoBtn, #stopCameraBtn').prop('disabled', false);
                $('#startCameraBtn').prop('disabled', true);
            } catch (err) {
                showAlert('danger', 'Camera access failed: ' + err.message);
            }
        }

        function capturePhoto() {
            canvas.width = video.videoWidth;
            canvas.height = video.height;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const data = canvas.toDataURL('image/jpeg', 0.8);
            profilePreview.innerHTML = `<img src="${data}" class="w-100 h-100 object-fit-cover rounded-circle">`;
            profilePictureInput.value = data;
            stopCamera();
        }

        function stopCamera() {
            if (stream) stream.getTracks().forEach(t => t.stop());
            video.style.display = 'none';
            $('#capturePhotoBtn, #stopCameraBtn').prop('disabled', true);
            $('#startCameraBtn').prop('disabled', false);
        }

        // Load Officials
        function loadOfficials(page = 1) {
            const search = $('#searchInput').val();
            const position = $('#positionFilter').val();
            const status = $('#statusFilter').val();
            const startTerm = "<?php echo $_SESSION['elec_year']; ?>";
            entriesPerPage = parseInt($('#entriesSelect').val()) || 10;
            currentPage = page;
            $.post('partials/barangay_management_api.php', {
                action: 'fetch',
                page,
                limit: entriesPerPage,
                search,
                position,
                term: startTerm,
                status
            }, data => {
                if (data.status === 'success') {
                    updateOfficialsTable(data.data.officials);
                    updatePagination(data.data.pagination);
                } else showAlert('danger', data.message);
            }, 'json').fail(() => showAlert('danger', 'Error loading data'));
        }

        function updateOfficialsTable(officials) {
            const $tbody = $('#officialsTableBody').empty();
            if (!officials.length) return $tbody.append('<tr><td colspan="8" class="text-center">No officials found.</td></tr>');
            officials.forEach(o => {
                const profile = o.profile_picture ?
                    `<img src="${o.profile_picture}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">` :
                    `<div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;font-size:0.8rem;">${o.full_name[0]}</div>`;
                $tbody.append(`
            <tr>
                <td>${profile}</td>
                <td><strong>${o.full_name}</strong></td>
                <td>${o.position}</td>
                <td>${o.term_start_date}</td>
                <td>${o.term_end_date || '—'}</td>
                <td>${o.contact}</td>
                <td><span class="badge ${o.status === 'Active' ? 'bg-success' : 'bg-danger'}">${o.status}</span></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewOfficial(${o.id})">View</button>
                         <button class="btn btn-outline-warning" onclick="archiveOfficial(${o.id})">Archive</button>
                    </div>
                </td>
            </tr>
        `);
            });
        }

        function updatePagination(p) {
            const $info = $('#paginationInfo');
            const start = (p.current_page - 1) * p.limit + 1;
            const end = Math.min(start + p.limit - 1, p.total);
            $info.text(`Showing ${start} to ${end} of ${p.total} entries`);
            const $ul = $('#paginationControls').empty();
            $ul.append(`<li class="page-item ${p.current_page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="loadOfficials(${p.current_page - 1})">Prev</a></li>`);
            for (let i = 1; i <= p.total_pages; i++) {
                $ul.append(`<li class="page-item ${i === p.current_page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadOfficials(${i})">${i}</a></li>`);
            }
            $ul.append(`<li class="page-item ${p.current_page === p.total_pages ? 'disabled' : ''}"><a class="page-link" href="#" onclick="loadOfficials(${p.current_page + 1})">Next</a></li>`);
        }

        // Save
        function saveOfficial() {
            const $form = $('#addOfficialForm');
            if (!$form[0].checkValidity()) return $form[0].reportValidity();
            if (!$('#selectedOfficialId').val()) return showAlert('danger', 'Please select a resident.');
            const formData = new FormData($form[0]);
            formData.append('action', $('#officialId').val() ? 'update' : 'add');
            const term = "<?php echo $_SESSION['elec_year']; ?>";
            formData.append('term', term);
            const $btn = $('#saveOfficialBtn').html('Saving...').prop('disabled', true);
            $.ajax({
                url: 'partials/barangay_management_api.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: r => {
                    $btn.html('Save Official').prop('disabled', false);
                    if (r.status === 'success') {
                        $('#addOfficialModal').modal('hide');
                        $form[0].reset();
                        resetResidentSelection();
                        // profilePreview.innerHTML = '<i class="fas fa-user fa-3x text-muted"></i>';
                        showAlert('success', r.message);
                        loadOfficials(currentPage);
                    } else showAlert('danger', r.message);
                },
                error: () => {
                    $btn.html('Save Official').prop('disabled', false);
                    showAlert('danger', r.message || 'Server error. Please try again.');
                }
            });
        }

        // Edit
        function editOfficial(id) {
            $.post('partials/barangay_management_api.php', {
                action: 'get',
                id
            }, r => {
                if (r.status === 'success') {
                    const o = r.data;
                    $('#officialId').val(o.id);
                    $('#fullName').val(o.full_name);
                    $('#selectedOfficialName').text(o.full_name);
                    $('#selectedOfficialId').val(o.resident_id || o.id);
                    $('#officialResidentSearchInput').val(o.full_name);
                    $('#position').val(o.position);
                    $('#status').val(o.status);
                    $('#contact').val(o.contact);
                    if (o.profile_picture) {
                        profilePreview.innerHTML = `<img src="${o.profile_picture}" class="w-100 h-100 object-fit-cover rounded-circle">`;
                        profilePictureInput.value = o.profile_picture;
                    } else {
                        profilePreview.innerHTML = '<i class="fas fa-user fa-3x text-muted"></i>';
                        profilePictureInput.value = '';
                    }
                    $('#addOfficialModalLabel').html('Edit Official');
                    $('#addOfficialModal').modal('show');
                }
            }, 'json');
        }

        // View
        function viewOfficial(id) {
            $.post('partials/barangay_management_api.php', {
                action: 'get',
                id
            }, r => {
                if (r.status === 'success') {
                    const o = r.data;
                    $('#viewFullName').text(o.full_name);
                    $('#viewPosition').text(o.position);
                    $('#viewTermStart').text(o.term_start_date);
                    $('#viewTermEnd').text(o.term_end_date);
                    $('#viewContact').text(o.contact);
                    $('#viewStatus').text(o.status).removeClass().addClass('badge ' + (o.status === 'Active' ? 'bg-success' : 'bg-danger'));
                    $('#viewProfilePicture').html(o.profile_picture ?
                        `<img src="${o.profile_picture}" class="w-100 h-100 object-fit-cover rounded-circle">` :
                        '<i class="fas fa-user fa-3x text-muted"></i>');
                    $('#viewOfficialModal').modal('show');
                }
            }, 'json');
        }

let officialIdToArchive = null; // Temporary storage

// Step 1: Open confirmation modal
function archiveOfficial(id) {
    officialIdToArchive = id;
    $('#confirmArchiveOfficialModal').modal('show');
}

// Step 2: When user clicks "Yes, Archive" → open reason modal
$(document).ready(function() {
    $('#proceedArchiveOfficialBtn').on('click', function() {
        $('#confirmArchiveOfficialModal').modal('hide');

        $('#archiveOfficialId').val(officialIdToArchive);
        $('#archiveOfficialReason').val(''); // reset
        $('#archiveOfficialReasonModal').modal('show');
    });
});

// Step 3: Final archive with reason
function confirmArchiveOfficial() {
    const id = $('#archiveOfficialId').val();
    const reason = $('#archiveOfficialReason').val()?.trim();

    if (!reason) {
        showAlert('danger', 'Please select a reason for archiving.');
        return;
    }

    $.post('partials/barangay_management_api.php', {
        action: 'archive',
        id: id,
        reason: reason
    }, function(r) {
        if (r.status === 'success') {
            $('#archiveOfficialReasonModal').modal('hide');
            showAlert('success', r.message || 'Official archived successfully.');
            loadOfficials(currentPage);
        } else {
            showAlert('danger', r.message || 'Failed to archive official.');
        }
    }, 'json').fail(function() {
        showAlert('danger', 'Server error. Please try again.');
    });
}

        // Filters & Init
        function clearFilters() {
            $('#searchInput, #positionFilter, #statusFilter').val('');
            $('#entriesSelect').val('10');
            loadOfficials();
        }

        function showAlert(type, msg) {
            const $a = $(`<div class="alert alert-${type} alert-dismissible fade show position-fixed" style="top:20px;right:20px;z-index:9999;min-width:300px;">
        ${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`);
            $('body').append($a);
            setTimeout(() => $a.alert('close'), 5000);
        }

        function exportOfficials(format) {
            $.post('partials/barangay_management_api.php', {
                action: 'export',
                format
            }, r => {
                if (r.status === 'success') {
                    const blob = new Blob([r.data], {
                        type: 'text/csv'
                    });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'officials.csv';
                    a.click();
                }
            });
        }

        function printTable() {
            window.print();
        }

        $(() => {
            loadOfficialResidents();
            loadOfficials();
            $('#officialResidentSearchInput').on('input', searchOfficialResidents);
            $('#searchInput, #positionFilter, #statusFilter, #entriesSelect').on('change keyup', () => loadOfficials());
            $('#addOfficialModal').on('hidden.bs.modal', () => {
                $('#addOfficialForm')[0].reset();
                $('#officialId').val('');
                resetResidentSelection();
                $('#officialResidentSearchInput').val('');
                $('#addOfficialModalLabel').html('Add New Official');
                profilePreview.innerHTML = '<i class="fas fa-user fa-3x text-muted"></i>';
                stopCamera();
                $('#officialResidentDropdown').hide();
            });
            $('#contact').on('input', e => e.target.value = e.target.value.replace(/\D/g, '').slice(0, 11));
        });
    </script>
</body>

</html>