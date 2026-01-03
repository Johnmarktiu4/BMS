<?php
// system_settings.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .avatar-sm { width: 40px; height: 40px; }
        .object-fit-cover { object-fit: cover; }
        .rounded-circle { border-radius: 50% !important; }
        #cameraFeed { max-width: 100%; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="fas fa-cog m-2"></i>Term of Office</h2>
                    <p class="text-muted mb-0">Manage term of office settings</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="partials/generate_resident_pdf.php" target="_blank" class="btn btn-success btn-lg">
                        <i class="fas fa-print me-2"></i>Print
                    </a>
                    <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addResidentModal" onclick="prepareAddResident()">
                        <i class="fas fa-plus me-2"></i>Add Term of Office
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4 col-sm-6">
                            <label for="searchInput" class="form-label">Search Term of Office</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="searchInput" placeholder="Start Year, End Year...">
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label for="statusFilter" class="form-label">Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">All</option>
                                <option value="Yes">Active</option>
                                <option value="No">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label for="entriesSelect" class="form-label">Show</label>
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

    <!-- Residents Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Term of Office List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="termsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Term</th>
                                    <th>Status</th>
                                    <th>Action</th>
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
</div>

<!-- ==================== ADD/EDIT/VIEW MODAL ==================== -->
<div class="modal fade" id="addResidentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-3 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title" id="addResidentModalLabel"><i class="fas fa-user-plus me-2"></i>Add New Term of Office</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="addTermForm" enctype="multipart/form-data">
                    <input type="hidden" id="termId" name="id">
                    <!-- Personal Information -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Term Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <center>
                                <div class="col-md-6">
                                    <label for="start" class="form-label">Start *</label>
                                    <input type="text" class="form-control" id="start" name="start" required step="1" min="2000" max="2100" placeholder="e.g, 2025" inputmode="numeric">
                                </div>
                                </center>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveTermBtn" onclick="saveTerm()">Save Term of Office</button>
            </div>
        </div>
    </div>
</div>

<!-- Map Modal -->
<div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pin Your Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="locationMap" style="height: 500px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="savePinnedLocation()">Save Location</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Archive Modal -->
<div class="modal fade" id="confirmsetActiveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Set Active</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to set this term as active?</p>
                <p class="text-muted small">This action can be undone later if needed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                <button type="button" class="btn btn-danger" id="proceedToReasonBtn">Yes, Set as Active</button>
            </div>
        </div>
    </div>
</div>

<!-- Archive Reason Modal -->
<div class="modal fade" id="setActiveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-archive me-2"></i>Set Term as Active</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="archiveForm">
                    <input type="hidden" id="archivetermId">
                    <label for="archiveReason" class="form-label">Reason <span class="text-danger">*</span></label>
                    <select class="form-select" id="archiveReason" required>
                        <option value="" disabled selected>Select reason</option>
                        <option value="Current Term">Current Term</option>
                        <option value="View Archive Data">View Archive Data</option>
                    </select>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="confirmArchive()">Set as Active</button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    let currentPage = 1;
    let isEditMode = false;
    let stream = null;
    let map = null;
    let marker = null;
    const video = document.getElementById('cameraFeed');
    const canvas = document.getElementById('photoCanvas');
    // const ctx = canvas.getContext('2d');
    // const profilePreview = document.getElementById('profilePreview');
    // const profilePictureInput = document.getElementById('profilePicture');

    // function updateSeniorStatus() {
    //     const dob = document.getElementById('dateOfBirth').value;
    //     const status = document.getElementById('seniorStatusText');
    //     const div = document.getElementById('seniorIdDiv');
    //     const hidden = document.getElementById('seniorHidden');
    //     if (!dob) {
    //         status.textContent = 'Enter DOB';
    //         status.className = 'badge bg-secondary me-2';
    //         div.style.display = 'none';
    //         hidden.value = 'No';
    //         return;
    //     }
    //     const age = new Date().getFullYear() - new Date(dob).getFullYear();
    //     const isSenior = age >= 60;
    //     status.textContent = isSenior ? `Yes (Age ${age})` : `No (Age ${age})`;
    //     status.className = isSenior ? 'badge bg-success me-2' : 'badge bg-danger me-2';
    //     div.style.display = isSenior ? 'block' : 'none';
    //     hidden.value = isSenior ? 'Yes' : 'No';
    //     if (!isSenior) document.getElementById('seniorId').value = '';
    // }

    function togglePwdFields() {
        const show = document.getElementById('pwdYes').checked;
        document.getElementById('pwdFields').style.display = show ? 'block' : 'none';
        if (!show) {
            document.getElementById('pwdId').value = '';
            document.getElementById('disabilityType').value = '';
        }
    }

    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: 640, height: 480 }
            });
            video.srcObject = stream;
            video.style.display = 'block';
            document.getElementById('capturePhotoBtn').disabled = false;
            document.getElementById('stopCameraBtn').disabled = false;
            document.getElementById('startCameraBtn').disabled = true;
        } catch (err) {
            alert("Camera access denied or not available. Use 'Upload Photo' instead.");
            console.error("Camera error:", err);
        }
    }

    // function capturePhoto() {
    //     canvas.width = video.videoWidth;
    //     canvas.height = video.videoHeight;
    //     ctx.drawImage(video, 0, 0);
    //     const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
    //     profilePreview.innerHTML = `<img src="${dataUrl}" class="w-100 h-100 object-fit-cover rounded-circle">`;
    //     profilePictureInput.value = dataUrl;
    //     stopCamera();
    // }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        video.style.display = 'none';
        document.getElementById('capturePhotoBtn').disabled = true;
        document.getElementById('stopCameraBtn').disabled = true;
        document.getElementById('startCameraBtn').disabled = false;
    }

    // function handleFileUpload(event) {
    //     const file = event.target.files[0];
    //     if (file) {
    //         const reader = new FileReader();
    //         reader.onload = function(e) {
    //             profilePreview.innerHTML = `<img src="${e.target.result}" class="w-100 h-100 object-fit-cover rounded-circle">`;
    //             profilePictureInput.value = e.target.result;
    //         };
    //         reader.readAsDataURL(file);
    //     }
    // }

    function openMapModal() {
        const house = $('#houseNumber').val().trim();
        const street = $('#street').val().trim();
        if (!house || !street) {
            alert('Please fill House Number and Street first.');
            return;
        }
        $('#mapModal').modal('show');
        setTimeout(() => {
            if (!map) initMap();
            else map.invalidateSize();
        }, 300);
    }

    function initMap() {
        map = L.map('locationMap').setView([14.4678, 120.8992], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        map.on('click', e => {
            if (marker) map.removeLayer(marker);
            marker = L.marker(e.latlng).addTo(map);
            $('#lat').val(e.latlng.lat);
            $('#lng').val(e.latlng.lng);
        });
    }

    function loadHeads() {
        $.get('partials/resident_management_api.php', { action: 'get_heads' }, data => {
            const options = [{ id: '', text: 'Search...' }].concat(data.heads.map(h => ({
                id: h.id,
                text: h.full_name + ' - ' + h.address
            })));
            $('.select2-head').select2({
                data: options,
                placeholder: 'Search...',
                width: '100%',
                dropdownParent: $('#addResidentModal')
            });
        }, 'json');
    }

    $('#selectHeadOfFamily').on('change', function() {
        const headId = $(this).val();
        if (headId) {
            $.get('partials/resident_management_api.php', { action: 'get_head_address', id: headId }, data => {
                $('#houseNumber').val(data.house_number);
                $('#street').val(data.street);
            }, 'json');
        }
    });

    function prepareAddResident() {
        isEditMode = false;
        $('#addResidentModalLabel').html('<i class="fas fa-calendar me-2"></i>Add New Term of Office');
        $('#saveTermBtn').html('Add Term of Office').show();
        $('#addTermForm')[0].reset();
        $('#termId').val('');
        $('#lat').val('');
        $('#lng').val('');
        // profilePreview.innerHTML = '<i class="fas fa-user fa-2x text-muted"></i>';
        // profilePictureInput.value = '';
        $('#isVoter').prop('checked', false);
        // toggleFamilyControls();
        // togglePwdFields();
        // stopCamera();
        $('.select2-head').val(null).trigger('change');
        // updateSeniorStatus();
        $('#addTermForm').find('input, select, textarea, button').prop('disabled', false);
        $('.form-check-input').prop('disabled', false);
    }

    function editTerm(id) {
        isEditMode = true;
        $('#addTermForm').find('input, select, textarea, button').prop('disabled', false);
        $('.form-check-input').prop('disabled', false);
        $.get('partials/system_settings_api.php', { action: 'get_term', id }, data => {
            if (data.success) {
                const r = data.term;
                $('#addResidentModalLabel').html('<i class="fas fa-user-edit me-2"></i>Edit Term');
                $('#saveTermBtn').html('Update Term').show();
                $('#termId').val(r.id);
                $('#start').val(r.start);
                $('#addResidentModal').modal('show');
            }
        }, 'json');
    }

    function viewResident(id) {
        editTerm(id);
        $('#addTermForm').find('input, select, textarea, button').not('.btn-close').prop('disabled', true);
        $('.form-check-input').prop('disabled', true);
        $('#saveTermBtn').hide();
        $('#startCameraBtn, #capturePhotoBtn, #stopCameraBtn, #fileUpload').prop('disabled', true);
        $('#addResidentModalLabel').html('<i class="fas fa-eye me-2"></i>View Resident');
    }

    function saveTerm() {
        const form = document.getElementById('addTermForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        // if (!$('#lat').val() || !$('#lng').val()) {
        //     alert('Please pin the location on the map.');
        //     return;
        // }
        const btn = document.getElementById('saveTermBtn');
        const txt = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        btn.disabled = true;
        const fd = new FormData(form);
        fd.append('action', isEditMode ? 'update_term' : 'save_term');
        $.ajax({
            url: 'partials/system_settings_api.php',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: res => {
                btn.innerHTML = txt;
                btn.disabled = false;
                if (res.success) {
                    $('#addResidentModal').modal('hide');
                    showAlert('success', res.message);
                    loadTerms(currentPage);
                    setTimeout(() => {
                        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 300);
                } else showAlert('danger', res.message);
            }
        });
    }


    function loadTerms(page = 1) {
        currentPage = page;
        const search = $('#searchInput').val();
        const status = $('#statusFilter').val();
        const limit = $('#entriesSelect').val();
        $.ajax({
            url: 'partials/system_settings_api.php',
            data: { action: 'get_terms', search, status, page, limit },
            dataType: 'json',
            success: function(data) {
                let tbody = '';
                data.terms.forEach(term => {    
                    const status = term.status == 1 ? 'Active' : 'Inactive';
                    const statusBadge = status == 'Active' ? 'bg-primary' : 'bg-secondary';
                    tbody += `
                <tr>
                    <td><div class="avatar-sm"><strong>${term.id}</strong></div></td>
                    <td>${term.start}</td>
                    <td>${term.end}</td>
                    <td><strong>${term.term}</strong></td>
                    <td><span class="badge ${statusBadge}">${status}</span></td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-success" title="${status === 'Active' ? 'Inactive' : 'Active'}" onclick="editTerm(${term.id})"><i class="fas fa-edit"></i></button>
                            <button type="button" class="btn btn-outline-danger" title="Archive" onclick="openArchiveModal(${term.id})"><i class="fas fa-sync"></i></button>
                        </div>
                    </td>
                </tr>`;
                });
                $('#termsTable tbody').html(tbody);
                updatePagination(data.total, limit, page);
            }
        });
        // visuallyHideBackdrop();
    }

    function updatePagination(total, limit, page) {
        const totalPages = Math.ceil(total / limit);
        let pagination = '';
        if (totalPages > 0) {
            pagination += `<li class="page-item ${page <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="${page > 1 ? `loadTerms(${page - 1})` : ''}">Prev</a></li>`;
            for (let i = 1; i <= totalPages; i++) {
                pagination += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadTerms(${i})">${i}</a></li>`;
            }
            pagination += `<li class="page-item ${page >= totalPages ? 'disabled' : ''}"><a class="page-link" href="#" onclick="${page < totalPages ? `loadTerms(${page + 1})` : ''}">Next</a></li>`;
        }
        $('#paginationLinks').html(pagination);
        const start = (page - 1) * limit + 1;
        const end = Math.min(start + limit - 1, total);
        $('#paginationInfo').text(`Showing ${start} to ${end} of ${total} entries`);
    }

    function clearFilters() {
        $('#searchInput').val('');
        $('#statusFilter').val('');
        $('#entriesSelect').val('10');
        loadTerms(1);
    }

    let termIdToArchive = null;

    function openArchiveModal(id) {
        termIdToArchive = id;
        $('#confirmsetActiveModal').modal('show');
    }

    $(document).ready(function() {
        $('#proceedToReasonBtn').on('click', function() {
            $('#confirmsetActiveModal').modal('hide');
            $('#archivetermId').val(termIdToArchive);
            $('#archiveReason').val('');
            $('#setActiveModal').modal('show');
        });
    });

    function confirmArchive() {
        const id = $('#archivetermId').val();
        const reason = $('#archiveReason').val().trim();
        if (!reason) {
            showAlert('danger', 'Please select a reason for archiving.');
            return;
        }
        $.ajax({
            url: 'partials/system_settings_api.php',
            type: 'POST',
            data: { action: 'archive_term', id: id, reason: reason },
            dataType: 'json',
            success: function(response) {
                $('#setActiveModal').modal('hide');
                showAlert(response.success ? 'success' : 'danger', response.message);
                loadTerms(currentPage);
            }
        });
    }

    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }

    $(document).ready(function() {
        loadTerms(1);
        // loadHeads();
        // $('#dateOfBirth').on('change', updateSeniorStatus);
        let searchTimeout;
        $('#searchInput').on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadTerms(1), 500);
        });
        $('#sexFilter, #statusFilter, #entriesSelect').on('change', () => loadTerms(1));
        $('#contactNumber, #emergencyContact').on('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            e.target.value = value;
        });
    });
</script>
</body>
</html>