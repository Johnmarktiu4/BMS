<?php
// resident_management.php
?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="fas fa-users me-2"></i>Household Resident Management</h2>
                    <p class="text-muted mb-0">Manage and view all residents in the barangay</p>
                </div>

            </div>
        </div>
    </div>


    <div class="tab-content" id="residentTabContent">


        <!-- Household Tab -->
        <div class="tab-pane fade show active" id="head-family">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-crown me-2"></i>Head of Household List</h5>
                        </div>
                        <div class="card-body">
                            <!-- Print All Button -->
                            <div class="mb-3 text-end">
                                <a href="<?php echo "partials/generate_household_pdf.php?full_name=" . $_SESSION['full_name']; ?>" target="_blank" class="btn btn-success btn-sm">
                                    <i class="fas fa-print me-1"></i> Print  
                                </a>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="headFamilyTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Profile</th>
                                            <th>Full Name</th>
                                            <th>Age</th>
                                            <th>Sex</th>
                                            <th>Address</th>
                                            <th>Contact</th>
                                            <th>Members</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div><small class="text-muted" id="headPaginationInfo"></small></div>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0" id="headPaginationLinks"></ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addResidentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title" id="addResidentModalLabel"><i class="fas fa-user-plus me-2"></i>Add New Resident</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="addResidentForm" enctype="multipart/form-data">
                    <input type="hidden" id="residentId" name="id">
                    <!-- Family Status -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-crown me-2"></i>Family Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="headOfFamily" name="head_of_family" onchange="toggleFamilyControls()">
                                <label class="form-check-label" for="headOfFamily">Head of Family</label>
                            </div>
                            <div id="familyMemberControls" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="selectHeadOfFamily" class="form-label">Select Head of Family</label>
                                        <select class="form-select select2-head" id="selectHeadOfFamily" name="selected_head_of_family">
                                            <option value="">Search head...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="relationshipToHead" class="form-label">Relationship to Head *</label>
                                        <select class="form-select" id="relationshipToHead" name="relationship_to_head" required>
                                            <option value="">Select relationship</option>
                                            <optgroup label="Immediate Family">
                                                <option value="Father">Father</option>
                                                <option value="Mother">Mother</option>
                                                <option value="Son">Son</option>
                                                <option value="Daughter">Daughter</option>
                                                <option value="Husband">Husband</option>
                                                <option value="Wife">Wife</option>
                                            </optgroup>
                                            <optgroup label="Extended Family">
                                                <option value="Brother">Brother</option>
                                                <option value="Sister">Sister</option>
                                                <option value="Grandfather">Grandfather</option>
                                                <option value="Grandmother">Grandmother</option>
                                                <option value="Grandson">Grandson</option>
                                                <option value="Granddaughter">Granddaughter</option>
                                                <option value="Uncle">Uncle</option>
                                                <option value="Aunt">Aunt</option>
                                                <option value="Nephew">Nephew</option>
                                                <option value="Niece">Niece</option>
                                                <option value="Cousin">Cousin</option>
                                            </optgroup>
                                            <optgroup label="In-Laws">
                                                <option value="Father-in-law">Father-in-law</option>
                                                <option value="Mother-in-law">Mother-in-law</option>
                                                <option value="Son-in-law">Son-in-law</option>
                                                <option value="Daughter-in-law">Daughter-in-law</option>
                                                <option value="Brother-in-law">Brother-in-law</option>
                                                <option value="Sister-in-law">Sister-in-law</option>
                                            </optgroup>
                                            <optgroup label="Step Family">
                                                <option value="Stepfather">Stepfather</option>
                                                <option value="Stepmother">Stepmother</option>
                                                <option value="Stepson">Stepson</option>
                                                <option value="Stepdaughter">Stepdaughter</option>
                                                <option value="Stepbrother">Stepbrother</option>
                                                <option value="Stepsister">Stepsister</option>
                                            </optgroup>
                                            <optgroup label="Other Household Members">
                                                <option value="Partner">Partner</option>
                                                <option value="Guardian">Guardian</option>
                                                <option value="Ward">Ward</option>
                                                <option value="Housemate">Housemate</option>
                                                <option value="Roommate">Roommate</option>
                                                <option value="Caregiver">Caregiver</option>
                                                <option value="Domestic Helper">Domestic Helper</option>
                                            </optgroup>
                                            <optgroup label="Optional / Special Cases">
                                                <option value="Adoptive Father">Adoptive Father</option>
                                                <option value="Adoptive Mother">Adoptive Mother</option>
                                                <option value="Adopted Son">Adopted Son</option>
                                                <option value="Adopted Daughter">Adopted Daughter</option>
                                                <option value="Foster Parent">Foster Parent</option>
                                                <option value="Foster Child">Foster Child</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Picture -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <div id="profilePreview" class="mx-auto mb-3 rounded-circle overflow-hidden d-flex align-items-center justify-content-center bg-white border" style="width:120px;height:120px;">
                                    <i class="fas fa-user fa-2x text-muted"></i>
                                </div>
                                <video id="cameraFeed" autoplay playsinline style="width:100%;max-width:300px;display:none;" class="mb-2"></video>
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-primary btn-sm" id="startCameraBtn" onclick="startCamera()">Start Camera</button>
                                    <button type="button" class="btn btn-success btn-sm" id="capturePhotoBtn" onclick="capturePhoto()" disabled>Capture</button>
                                    <button type="button" class="btn btn-danger btn-sm" id="stopCameraBtn" onclick="stopCamera()" disabled>Stop</button>
                                </div>
                                <input type="hidden" id="profilePicture" name="profile_picture">
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-id-card me-2"></i>Personal Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="firstName" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="firstName" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="middleName" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middleName" name="middle_name">
                                </div>
                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="lastName" name="last_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="suffix" class="form-label">Suffix</label>
                                    <input type="text" class="form-control" id="suffix" name="suffix" placeholder="Jr., Sr., III">
                                </div>
                                <div class="col-md-6">
                                    <label for="civilStatus" class="form-label">Civil Status *</label>
                                    <select class="form-select" id="civilStatus" name="civil_status" required>
                                        <option value="">Select</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                        <option value="Separated">Separated</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="sex" class="form-label">Sex *</label>
                                    <select class="form-select" id="sex" name="sex" required>
                                        <option value="">Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Rather not to say">Rather not to say</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="dateOfBirth" class="form-label">Date of Birth *</label>
                                    <input type="date" class="form-control" id="dateOfBirth" onkeydown="return false" name="date_of_birth" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="placeOfBirth" class="form-label">Place of Birth *</label>
                                    <input type="text" class="form-control" id="placeOfBirth" name="place_of_birth" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="religion" class="form-label">Religion</label>
                                    <input type="text" class="form-control" id="religion" name="religion">
                                </div>
                                <div class="col-md-6">
                                    <label for="nationality" class="form-label">Nationality</label>
                                    <input type="text" class="form-control" id="nationality" name="nationality" value="Filipino">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Address Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="houseNumber" class="form-label">House Number *</label>
                                    <input type="text" class="form-control" id="houseNumber" name="house_number" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="street" class="form-label">Street *</label>
                                    <select class="form-select" id="street" name="street" required>
                                        <option value="">Select</option>
                                        <option value="Reyes St.">Reyes St.</option>
                                        <option value="Militar St.">Militar St.</option>
                                        <option value="Tandang Sora St.">Tandang Sora St.</option>
                                        <option value="Custodio St.">Custodio St.</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="province" class="form-label">Province</label>
                                    <input type="text" class="form-control" id="province" name="province" value="Cavite" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="municipality" class="form-label">Municipality</label>
                                    <input type="text" class="form-control" id="municipality" name="municipality" value="Cavite City" readonly>
                                </div>
                                <div class="col-md-12">
                                    <label for="zipCode" class="form-label">Zip Code</label>
                                    <input type="text" class="form-control" id="zipCode" name="zip_code" value="4100" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-phone me-2"></i>Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="contactNumber" class="form-label">Contact Number *</label>
                                    <input type="tel" class="form-control" id="contactNumber" name="contact_number" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="emailAddress" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="emailAddress" name="email_address">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Special Categories -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-users me-2"></i>Special Categories</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">PWD</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pwd" id="pwdYes" value="Yes" onchange="togglePwdFields()">
                                        <label class="form-check-label" for="pwdYes">Yes</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pwd" id="pwdNo" value="No" checked onchange="togglePwdFields()">
                                        <label class="form-check-label" for="pwdNo">No</label>
                                    </div>
                                    <div class="mt-2" id="pwdFields" style="display:none;">
                                        <label for="pwdId" class="form-label">PWD ID</label>
                                        <input type="text" class="form-control mb-2" id="pwdId" name="pwd_id">
                                        <label for="disabilityType" class="form-label">Type of Disability</label>
                                        <select class="form-select" id="disabilityType" name="disability_type">
                                            <option value="">Select</option>
                                            <option>Visual Disability</option>
                                            <option>Hearing Disability</option>
                                            <option>Speech and Language Disability</option>
                                            <option>Orthopedic / Mobility Disability</option>
                                            <option>Mental / Psychosocial Disability</option>
                                            <option>Intellectual Disability</option>
                                            <option>Learning Disability</option>
                                            <option>Chronic Illness</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Senior Citizen</label>
                                    <div class="d-flex align-items-center">
                                        <span id="seniorStatusText" class="badge bg-secondary me-2">Calculating...</span>
                                        <small class="text-muted"></small>
                                    </div>
                                    <div class="mt-2" id="seniorIdDiv" style="display:none;">
                                        <label for="seniorId" class="form-label">Senior ID</label>
                                        <input type="text" class="form-control" id="seniorId" name="senior_id">
                                    </div>
                                    <input type="hidden" name="senior" id="seniorHidden" value="No">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Solo Parent</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="solo_parent" id="soloParentYes" value="Yes">
                                        <label class="form-check-label" for="soloParentYes">Yes</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="solo_parent" id="soloParentNo" value="No" checked>
                                        <label class="form-check-label" for="soloParentNo">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency -->
                    <div class="card mb-0 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Emergency Contact</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="emergencyName" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="emergencyName" name="emergency_name">
                                </div>
                                <div class="col-md-4">
                                    <label for="emergencyRelationship" class="form-label">Relationship</label>
                                    <select class="form-select" id="emergencyRelationship" name="emergency_relationship">
                                        <option value="">Select</option>
                                        <!-- Same dropdown as relationship_to_head -->
                                        <optgroup label="Immediate Family">
                                            <option value="Father">Father</option>
                                            <option value="Mother">Mother</option>
                                            <option value="Son">Son</option>
                                            <option value="Daughter">Daughter</option>
                                            <option value="Husband">Husband</option>
                                            <option value="Wife">Wife</option>
                                        </optgroup>
                                        <!-- ... repeat all optgroups ... -->
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="emergencyContact" class="form-label">Contact</label>
                                    <input type="tel" class="form-control" id="emergencyContact" name="emergency_contact">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveResidentBtn" onclick="saveResident()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- First: Simple Confirmation Modal -->
<div class="modal fade" id="confirmArchiveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Archive</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to archive this resident?</p>
                <p class="text-muted small">This action can be undone later if needed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                <button type="button" class="btn btn-danger" id="proceedToReasonBtn">Yes, Archive</button>
            </div>
        </div>
    </div>
</div>

<!-- Your existing Archive Reason Modal (unchanged) -->
<div class="modal fade" id="archiveReasonModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-archive me-2"></i>Archive Resident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="archiveForm">
                    <input type="hidden" id="archiveResidentId">
                    <label for="archiveReason" class="form-label">Reason <span class="text-danger">*</span></label>
                    <select class="form-select" id="archiveReason" required>
                        <option value="" disabled selected>Select reason</option>
                        <option value="Deceased">Deceased</option>
                        <option value="Transferred Residence">Transferred Residence</option>
                        <option value="Duplicate Record">Duplicate Record</option>
                    </select>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="confirmArchive()">Archive</button>
            </div>
        </div>
    </div>
</div>

<!-- View Family Modal -->
<!-- View Family Modal -->
<div class="modal fade" id="viewFamilyMembersModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewFamilyMembersModalLabel">
                    <i class="fas fa-users me-2"></i>Family Members
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 id="headName" class="mb-3"></h6>
                <div class="table-responsive">
                    <table class="table table-striped table-sm" id="familyMembersTable">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Age</th>
                                <th>Sex</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPage = 1,
        headCurrentPage = 1,
        isEditMode = false,
        stream = null;
    const video = document.getElementById('cameraFeed'),
        canvas = document.createElement('canvas');
    const profilePreview = document.getElementById('profilePreview'),
        profilePictureInput = document.getElementById('profilePicture');

    function updateSeniorStatus() {
        const dob = document.getElementById('dateOfBirth').value;
        const status = document.getElementById('seniorStatusText');
        const div = document.getElementById('seniorIdDiv');
        const hidden = document.getElementById('seniorHidden');
        if (!dob) {
            status.textContent = 'Enter DOB';
            status.className = 'badge bg-secondary me-2';
            div.style.display = 'none';
            hidden.value = 'No';
            return;
        }
        const age = new Date().getFullYear() - new Date(dob).getFullYear();
        const isSenior = age >= 60;
        status.textContent = isSenior ? `Yes (Age ${age})` : `No (Age ${age})`;
        status.className = isSenior ? 'badge bg-success me-2' : 'badge bg-danger me-2';
        div.style.display = isSenior ? 'block' : 'none';
        hidden.value = isSenior ? 'Yes' : 'No';
        if (!isSenior) document.getElementById('seniorId').value = '';
    }

    function toggleFamilyControls() {
        const isHead = document.getElementById('headOfFamily').checked;
        document.getElementById('familyMemberControls').style.display = isHead ? 'none' : 'block';
        if (isHead) {
            $('#selectHeadOfFamily').val(null).trigger('change');
            $('#relationshipToHead').val('').prop('required', false);
        } else {
            $('#relationshipToHead').prop('required', true);
        }
    }

    function togglePwdFields() {
        const show = document.getElementById('pwdYes').checked;
        document.getElementById('pwdFields').style.display = show ? 'block' : 'none';
        if (!show) {
            document.getElementById('pwdId').value = '';
            document.getElementById('disabilityType').value = '';
        }
    }

    async function startCamera() {
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'user'
            }
        });
        video.srcObject = stream;
        video.style.display = 'block';
        document.getElementById('capturePhotoBtn').disabled = false;
        document.getElementById('stopCameraBtn').disabled = false;
        document.getElementById('startCameraBtn').disabled = true;
    }

    function capturePhoto() {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        const data = canvas.toDataURL('image/jpeg', 0.8);
        profilePreview.innerHTML = `<img src="${data}" class="w-100 h-100 object-fit-cover">`;
        profilePictureInput.value = data;
        stopCamera();
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        video.style.display = 'none';
        document.getElementById('capturePhotoBtn').disabled = true;
        document.getElementById('stopCameraBtn').disabled = true;
        document.getElementById('startCameraBtn').disabled = false;
    }

    function loadHeads() {
        $.get('partials/resident_management_api.php', {
            action: 'get_heads'
        }, data => {
            const options = [{
                id: '',
                text: 'Search...'
            }].concat(data.heads.map(h => ({
                id: h.id,
                text: h.full_name
            })));
            $('.select2-head').select2({
                data: options,
                placeholder: 'Search...',
                width: '100%',
                dropdownParent: $('#addResidentModal')
            });
        }, 'json');
    }

    function loadHeadAddress(headId) {
        $.get('partials/resident_management_api.php', {
            action: 'get_head_address',
            id: headId
        }, data => {
            $('#houseNumber').val(data.house_number);
            $('#street').val(data.street);
        }, 'json');
    }

    $('#selectHeadOfFamily').on('change', function() {
        const headId = $(this).val();
        if (headId) loadHeadAddress(headId);
    });

    function prepareAddResident() {
        isEditMode = false;
        $('#addResidentModalLabel').html('<i class="fas fa-user-plus me-2"></i>Add New Resident');
        $('#saveResidentBtn').html('Save Resident');
        $('#addResidentForm')[0].reset();
        $('#residentId').val('');
        profilePreview.innerHTML = '<i class="fas fa-user fa-2x text-muted"></i>';
        profilePictureInput.value = '';
        toggleFamilyControls();
        togglePwdFields();
        stopCamera();
        $('.select2-head').val(null).trigger('change');
        updateSeniorStatus();
    }

    function editResident(id) {
        isEditMode = true;
        $.get('partials/resident_management_api.php', {
            action: 'get_resident',
            id
        }, data => {
            if (data.success) {
                const r = data.resident;
                $('#addResidentModalLabel').html('<i class="fas fa-user-edit me-2"></i>Edit Resident');
                $('#saveResidentBtn').html('Update Resident');
                $('#residentId').val(r.id);
                $('#firstName').val(r.first_name);
                $('#middleName').val(r.middle_name);
                $('#lastName').val(r.last_name);
                $('#suffix').val(r.suffix);
                $('#civilStatus').val(r.civil_status);
                $('#sex').val(r.sex);
                $('#dateOfBirth').val(r.date_of_birth);
                $('#placeOfBirth').val(r.place_of_birth);
                $('#religion').val(r.religion);
                $('#nationality').val(r.nationality);
                $('#houseNumber').val(r.house_number);
                $('#street').val(r.street);
                $('#contactNumber').val(r.contact_number);
                $('#emailAddress').val(r.email_address);
                $('#pwdYes').prop('checked', r.pwd === 'Yes');
                $('#pwdNo').prop('checked', r.pwd === 'No');
                $('#pwdId').val(r.pwd_id);
                $('#disabilityType').val(r.disability_type);
                $('#soloParentYes').prop('checked', r.solo_parent === 'Yes');
                $('#soloParentNo').prop('checked', r.solo_parent !== 'Yes');
                $('#headOfFamily').prop('checked', r.is_head_of_family == 1);
                $('.select2-head').val(r.head_of_family_id).trigger('change');
                $('#relationshipToHead').val(r.relationship_to_head);
                $('#emergencyName').val(r.emergency_name);
                $('#emergencyRelationship').val(r.emergency_relationship);
                $('#emergencyContact').val(r.emergency_contact);
                if (r.profile_picture) {
                    profilePreview.innerHTML = `<img src="${r.profile_picture}" class="w-100 h-100 object-fit-cover">`;
                } else {
                    profilePreview.innerHTML = '<i class="fas fa-user fa-2x text-muted"></i>';
                }
                toggleFamilyControls();
                togglePwdFields();
                $('#addResidentModal').modal('show');
                updateSeniorStatus();
            }
        }, 'json');
    }

    function saveResident() {
        const form = document.getElementById('addResidentForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        const btn = document.getElementById('saveResidentBtn');
        const txt = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        btn.disabled = true;
        const fd = new FormData(form);
        fd.append('action', isEditMode ? 'update_resident' : 'add_resident');
        $.ajax({
            url: 'partials/resident_management_api.php',
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
                    loadResidents(currentPage);
                    loadHeadOfFamily(headCurrentPage);
                } else showAlert('danger', res.message);
            }
        });
    }

    function loadResidents(page = 1) {
        currentPage = page;
        const search = $('#searchInput').val();
        const sex = $('#sexFilter').val();
        const status = $('#statusFilter').val();
        const limit = $('#entriesSelect').val();
        $.ajax({
            url: 'partials/resident_management_api.php',
            data: {
                action: 'get_residents',
                search,
                sex,
                status,
                page,
                limit
            },
            dataType: 'json',
            success: function(data) {
                let tbody = '';
                data.residents.forEach(resident => {
                    const profile = resident.profile_picture ?
                        `<img src="${resident.profile_picture}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">` :
                        `<div class="bg-success text-white d-flex align-items-center justify-content-center rounded-circle" style="width:40px;height:40px;">${resident.full_name.charAt(0).toUpperCase()}</div>`;
                    const sexBadge = resident.sex === 'Male' ? 'bg-info' : 'bg-success';
                    const headBadge = resident.is_head_of_family == 1 ? 'bg-warning' : 'bg-secondary';
                    const headText = resident.is_head_of_family == 1 ? 'Yes' : 'No';
                    const statusBadge = resident.registered == 1 ? 'bg-success' : 'bg-danger';
                    const statusText = resident.registered == 1 ? 'Registered' : 'Not Registered';
                    tbody += `
                    <tr>
                        <td><div class="avatar-sm">${profile}</div></td>
                        <td><strong>${resident.full_name}</strong></td>
                        <td>${resident.age}</td>
                        <td><span class="badge ${sexBadge}">${resident.sex}</span></td>
                        <td>${resident.civil_status}</td>
                        <td>${resident.address}</td>
                        <td>${resident.contact_number}</td>
                        <td><span class="badge ${headBadge}">${headText}</span></td>
                        <td><span class="badge ${statusBadge}">${statusText}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary" title="View" onclick="viewResident(${resident.id})"><i class="fas fa-eye"></i></button>
                                <button type="button" class="btn btn-outline-success" title="Edit" onclick="editResident(${resident.id})"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-outline-warning" title="Archive" onclick="openArchiveModal(${resident.id})"><i class="fas fa-archive"></i></button>
                            </div>
                        </td>
                    </tr>`;
                });
                $('#residentsTable tbody').html(tbody);
                updatePagination(data.total, limit, page);
            }
        });
    }

    function updatePagination(total, limit, page) {
        const totalPages = Math.ceil(total / limit);
        let pagination = '';
        if (totalPages > 0) {
            pagination += `<li class="page-item ${page <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="${page > 1 ? `loadResidents(${page - 1})` : ''}">Prev</a></li>`;
            for (let i = 1; i <= totalPages; i++) {
                pagination += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadResidents(${i})">${i}</a></li>`;
            }
            pagination += `<li class="page-item ${page >= totalPages ? 'disabled' : ''}"><a class="page-link" href="#" onclick="${page < totalPages ? `loadResidents(${page + 1})` : ''}">Next</a></li>`;
        }
        $('#paginationLinks').html(pagination);
        const start = (page - 1) * limit + 1;
        const end = Math.min(start + limit - 1, total);
        $('#paginationInfo').text(`Showing ${start} to ${end} of ${total} entries`);
    }

    function clearFilters() {
        $('#searchInput').val('');
        $('#sexFilter').val('');
        $('#statusFilter').val('');
        $('#entriesSelect').val('10');
        loadResidents(1);
    }

    function loadHeadOfFamily(page = 1) {
        headCurrentPage = page;
        const limit = 10;
        $.ajax({
            url: 'partials/resident_management_api.php',
            data: {
                action: 'get_heads',
                page,
                limit
            },
            dataType: 'json',
            success: function(data) {
                let tbody = '';
                data.heads.forEach(head => {
                    const profile = head.profile_picture ?
                        `<img src="${head.profile_picture}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">` :
                        `<div class="bg-success text-white d-flex align-items-center justify-content-center rounded-circle" style="width:40px;height:40px;">${head.full_name.charAt(0).toUpperCase()}</div>`;
                    const sexBadge = head.sex === 'Male' ? 'bg-info' : 'bg-success';
                    const memberCount = (head.family_members?.length || 0) + 1;
                    tbody += `
                    <tr>
                        <td class="text-center align-middle"><span class="badge bg-success fs-6">${memberCount}</span></td>
                        <td><div class="avatar-sm">${profile}</div></td>
                        <td><strong>${head.full_name}</strong></td>
                        <td>${head.age}</td>
                        <td><span class="badge ${sexBadge}">${head.sex}</span></td>
                        <td>${head.address}</td>
                        <td>${head.contact_number}</td>
                        <td>
                            <button class="btn btn-sm btn-info me-1" onclick="viewFamilyMembers(${head.id})">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <a href="partials/generate_household_pdf.php?head_id=${head.id}&full_name=<?php echo $_SESSION['full_name']; ?>" target="_blank" class="btn btn-sm btn-warning">
                                <i class="fas fa-print"></i> Print
                            </a>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary" title="View" onclick="viewResident(${head.id})"><i class="fas fa-eye"></i></button>
                                <button type="button" class="btn btn-outline-success" title="Edit" onclick="editResident(${head.id})"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-outline-warning" title="Archive" onclick="openArchiveModal(${head.id})"><i class="fas fa-archive"></i></button>
                            </div>
                        </td>
                    </tr>`;
                });
                $('#headFamilyTable tbody').html(tbody);
                updateHeadPagination(data.total, limit, page);
            }
        });
    }

    function updateHeadPagination(total, limit, page) {
        const totalPages = Math.ceil(total / limit);
        let pagination = '';
        if (totalPages > 0) {
            pagination += `<li class="page-item ${page <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="${page > 1 ? `loadHeadOfFamily(${page - 1})` : ''}">Prev</a></li>`;
            for (let i = 1; i <= totalPages; i++) {
                pagination += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadHeadOfFamily(${i})">${i}</a></li>`;
            }
            pagination += `<li class="page-item ${page >= totalPages ? 'disabled' : ''}"><a class="page-link" href="#" onclick="${page < totalPages ? `loadHeadOfFamily(${page + 1})` : ''}">Next</a></li>`;
        }
        $('#headPaginationLinks').html(pagination);
        const start = (page - 1) * limit + 1;
        const end = Math.min(start + limit - 1, total);
        $('#headPaginationInfo').text(`Showing ${start} to ${end} of ${total} entries`);
    }

    function viewFamilyMembers(headId) {
        $.ajax({
            url: 'partials/resident_management_api.php',
            data: {
                action: 'get_family_members',
                head_id: headId
            },
            dataType: 'json',
            success: function(data) {
                if (!data.success) {
                    showAlert('danger', data.message || 'Failed to load family members');
                    return;
                }

                $('#headName').html(`<strong>Family of:</strong> ${data.head_full_name} <small class="text-muted">(Head, Age ${data.head_age}, ${data.head_sex})</small>`);

                let tbody = '';
                let count = 1;

                // Head row
                tbody += `
<tr style="background-color: #fff3cd;">
    <td><strong>${count++}</strong></td>
    <td><strong>${data.head_full_name}</strong> <small class="text-muted">(Head)</small></td>
    <td><span class="badge bg-warning">Head</span></td>
    <td>${data.head_age}</td>
    <td><span class="badge ${data.head_sex === 'Male' ? 'bg-info' : 'bg-success'}">${data.head_sex}</span></td>
</tr>`;

                // Members
                if (data.members.length > 0) {
                    data.members.forEach(member => {
                        const sexBadge = member.sex === 'Male' ? 'bg-info' : 'bg-success';
                        const relBadge = member.relationship_to_head ?
                            `<span class="badge bg-secondary">${member.relationship_to_head}</span>` :
                            '<em class="text-muted">—</em>';
                        tbody += `
        <tr>
            <td>${count++}</td>
            <td>${member.full_name}</td>
            <td>${relBadge}</td>
            <td>${member.age}</td>
            <td><span class="badge ${sexBadge}">${member.sex}</span></td>
        </tr>`;
                    });
                } else {
                    tbody += `<tr><td colspan="6" class="text-center text-muted">No other family members</td></tr>`;
                }

                $('#familyMembersTable tbody').html(tbody);
                $('#viewFamilyMembersModal').modal('show');
            },
            error: function() {
                showAlert('danger', 'Network error. Please try again.');
            }
        });
    }

    function viewResident(id) {
        editResident(id); // Reuse edit modal as view-only
        $('#saveResidentBtn').hide();
        $('#addResidentModal .modal-title').prepend('<i class="fas fa-eye me-2"></i>');
    }
let residentIdToArchive = null; // Temporary holder for the ID

// Step 1: Open confirmation modal
function openArchiveModal(id) {
    residentIdToArchive = id;
    $('#confirmArchiveModal').modal('show');
}

// Step 2: When user clicks "Yes, Archive" → open reason modal
$(document).ready(function() {
    $('#proceedToReasonBtn').on('click', function() {
        $('#confirmArchiveModal').modal('hide');
        
        // Now open the reason modal
        $('#archiveResidentId').val(residentIdToArchive);
        $('#archiveReason').val(''); // reset selection
        $('#archiveReasonModal').modal('show');
    });
});

// Your existing confirmArchive (unchanged)
function confirmArchive() {
    const id = $('#archiveResidentId').val();
    const reason = $('#archiveReason').val().trim();

    if (!reason) {
        showAlert('danger', 'Please select a reason for archiving.');
        return;
    }

    $.ajax({
        url: 'partials/resident_management_api.php',
        type: 'POST',
        data: {
            action: 'archive_resident',
            id: id,
            reason: reason
        },
        dataType: 'json',
        success: function(response) {
            $('#archiveReasonModal').modal('hide');
            showAlert(response.success ? 'success' : 'danger', response.message);
            loadResidents(currentPage);
        },
        error: function() {
            showAlert('danger', 'An error occurred. Please try again.');
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
        loadResidents(1);
        loadHeads();
        loadHeadOfFamily(1);
        $('#dateOfBirth').on('change', updateSeniorStatus);
        let searchTimeout;
        $('#searchInput').on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadResidents(1), 500);
        });
        $('#sexFilter, #statusFilter, #entriesSelect').on('change', () => loadResidents(1));
        $('#residentTabs a').on('shown.bs.tab', function(e) {
            if (e.target.id === 'head-family-tab') loadHeadOfFamily(1);
        });
    });

    $('#contactNumber, #emergencyContact').on('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);
        e.target.value = value;
    });
</script>
<style>
    /* General styles */
    .avatar-sm {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

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

    /* Modal specific styles */
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

    .form-control,
    .form-select {
        border-radius: 0.375rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    .form-control[readonly] {
        background-color: #e9ecef;
        opacity: 1;
    }

    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .is-valid {
        border-color: #198754 !important;
        background-image: none !important;
    }

    .is-invalid {
        border-color: #dc3545 !important;
        background-image: none !important;
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

    /* Select2 styles */
    .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }

    .select2-container--default .select2-selection--single .select2-selection__clear {
        margin-right: 10px;
    }

    /* Tab styles */
    .nav-tabs .nav-link {
        border-radius: 0.375rem 0.375rem 0 0;
        margin-right: 2px;
    }

    .nav-tabs .nav-link.active {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }

    .nav-tabs .nav-link:hover {
        border-color: #0d6efd;
    }

    /* Responsive adjustments */
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

        .table th,
        .table td {
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

        #profilePreview {
            width: 100px;
            height: 100px;
        }

        #cameraFeed {
            max-width: 250px;
        }

        .form-control,
        .form-select {
            font-size: 0.9rem;
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

        .avatar-sm div {
            width: 30px !important;
            height: 30px !important;
            font-size: 0.9rem;
        }

        .card-body {
            padding: 0.75rem;
        }

        #profilePreview {
            width: 80px;
            height: 80px;
        }

        #cameraFeed {
            max-width: 200px;
        }
    }
</style>