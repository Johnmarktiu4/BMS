<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Barangay Certificate Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* === ALL ORIGINAL STYLES (UNCHANGED) === */
        body {
            background: #f5f5f5;
        }

        .cert-management-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .1);
            overflow: hidden;
        }

        .cert-form-section {
            padding: 2rem;
            background: #f8f9fa;
        }

        .cert-preview-section {
            padding: 2rem;
            background: #fff;
        }

        .certificate-paper {
            background: #fff;
            border: 3px solid #dee2e6;
            border-radius: 8px;
            padding: 40px;
            min-height: 800px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
            position: relative;
        }

        .cert-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

.cert-logo {
    width: 120px;      /* Was 70px */
    height: 120px;     /* Was 70px */
    object-fit: contain;
}

        .cert-header-text .main-title {
            font-size: 13px;
            font-weight: bold;
            color: #7f8c8d;
            line-height: 1.3;
            margin: 0;
        }

        .cert-header-text .barangay-name {
            font-size: 14px;
            color: #9b59b6;
            font-style: italic;
            margin: 5px 0;
        }

        .cert-header-text .office-name {
            font-size: 15px;
            color: #3498db;
            font-style: italic;
            font-weight: 500;
        }

        .cert-title-main {
            font-size: 36px;
            font-weight: 900;
            color: #003366;
            letter-spacing: 10px;
            margin: 20px 0;
            text-transform: uppercase;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }

        .cert-body-container {
            display: flex;
            gap: 25px;
            margin-top: 25px;
        }

        .officials-sidebar {
            flex: 0 0 240px;
            border-right: 3px solid #e74c3c;
            padding-right: 15px;
        }

        .officials-title {
            color: #c0392b;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .official-entry {
            margin-bottom: 12px;
        }

        .official-name {
            font-weight: 700;
            font-size: 11px;
            color: #2c3e50;
            line-height: 1.3;
        }

        .official-title {
            font-size: 10px;
            color: #3498db;
            font-style: italic;
        }

        .section-label {
            color: #3498db;
            font-weight: 700;
            font-size: 12px;
            margin-top: 15px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .cert-main-content {
            flex: 1;
            border: 3px solid #e74c3c;
            padding: 25px;
            border-radius: 5px;
            background: #fefefe;
        }

        .cert-text {
            font-size: 13px;
            line-height: 1.9;
            color: #2c3e50;
            text-align: justify;
        }

        .cert-field-underline {
            border-bottom: 1px solid #2c3e50;
            display: inline-block;
            min-width: 120px;
            padding: 0 8px;
            font-weight: 600;
        }

        .signature-area {
            margin-top: 50px;
        }

        .applicant-signature {
            text-align: center;
            margin-bottom: 40px;
        }

        .signature-line {
            border-top: 1.5px solid #2c3e50;
            width: 180px;
            margin: 0 auto;
            padding-top: 5px;
            font-size: 12px;
            font-weight: 700;
        }

        .punong-signature .signature-line {
            width: 200px;
            margin-left: auto;
            margin-right: 0;
            margin-top: 50px;
        }

        .validity-notice {
            margin-top: 20px;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: .5rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #bdc3c7;
            transition: all .3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 .2rem rgba(52, 152, 219, .25);
        }

        .btn-generate-cert {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            color: #fff;
        }

        .btn-generate-cert:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, .4);
        }

        .btn-print-cert {
            background: linear-gradient(135deg, #27ae60, #229954);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            color: #fff;
        }

        .btn-print-cert:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, .4);
        }

        .search-input {
            border-radius: 8px;
        }

        .resident-item {
            cursor: pointer;
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }

        .resident-item:hover {
            background-color: #f8f9fa;
        }

        .resident-item.selected {
            background-color: #e3f2fd;
            font-weight: bold;
        }

        /* Print styles (unchanged) */
        @media print {
            @page {
                size: A4 portrait;
                margin: 15mm;
            }

            body * {
                visibility: hidden;
            }

            #certificatePaper,
            #certificatePaper * {
                visibility: visible;
            }

            #certificatePaper {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 20px 30px;
            }

            .cert-body-container {
                display: flex !important;
                flex-direction: row !important;
                gap: 20px;
            }

            .officials-sidebar {
                flex: 0 0 220px !important;
                width: 220px !important;
                border-right: 2px solid #e74c3c !important;
                padding-right: 15px;
            }

            .cert-main-content {
                flex: 1 !important;
                border: 2px solid #e74c3c !important;
                padding: 20px;
            }

            .no-print,
            .no-print * {
                display: none !important;
            }
        }

        @media (max-width:991px) {
            .cert-form-section {
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }

            .cert-body-container {
                flex-direction: column;
            }

            .officials-sidebar {
                border-right: none;
                border-bottom: 3px solid #e74c3c;
                padding-right: 0;
                padding-bottom: 15px;
                margin-bottom: 15px;
            }
        }

        @media (max-width:768px) {
            .certificate-paper {
                padding: 20px;
            }

            .cert-title-main {
                font-size: 28px;
                letter-spacing: 6px;
            }

            .cert-logo {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-3"><i class="fas fa-certificate me-2"></i>Certificate Management</h2>
                <p class="text-muted">Generate official barangay certificates for residents</p>
            </div>
        </div>
        <div class="cert-management-container">
            <div class="row g-0">
                <!-- ==================== FORM ==================== -->
                <div class="col-lg-5 cert-form-section">
                    <h5 class="mb-4"><i class="fas fa-edit me-2"></i>Certificate Details</h5>
                    <div class="mb-3">
                        <label class="form-label">Certificate Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="certType">
                            <option value="clearance">Barangay Clearance</option>
                            <option value="residency">Certificate of Residency</option>
                            <option value="indigency">Certificate of Indigency</option>
                        </select>
                    </div>

                    <!-- RESIDENT SEARCH BUTTON -->
                    <div class="mb-3">
                        <label class="form-label">Resident <span class="text-danger">*</span></label>
                        <button class="btn btn-outline-primary w-100 text-start d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#residentSearchModal">
                            <span id="selectedResidentName">Select Resident...</span>
                            <i class="fas fa-search"></i>
                        </button>
                        <input type="hidden" id="residentId">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="age" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sex <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sex" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Complete Address</label>
                        <input type="text" class="form-control" id="address" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="dob" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="civilStatus" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="purpose" placeholder="Employment, School requirement, etc.">
                    </div>
                    <div class="mb-3" id="residencyYearsDiv" style="display:none;">
                        <label class="form-label">Years of Residency</label>
                        <input type="number" class="form-control" id="residencyYears" placeholder="5" min="0">
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-generate-cert" onclick="generateCertificate()">
                            <i class="fas fa-file-certificate me-2"></i>Generate Certificate
                        </button>
                        <button class="btn btn-print-cert no-print" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Certificate
                        </button>
                    </div>
                </div>

                <!-- ==================== PREVIEW ==================== -->
                <div class="col-lg-7 cert-preview-section">
                    <div class="mb-3 no-print">
                        <h5><i class="fas fa-eye me-2"></i>Certificate Preview</h5>
                        <p class="text-muted small mb-0">Select a resident to generate</p>
                    </div>
                    <div class="certificate-paper" id="certificatePaper">
                        <!-- Header -->
                        <div class="cert-header">
                            <div class="logo-container">
                                <img src="image/Logo/Brgy3_logo-removebg-preview.png" alt="Barangay Logo" class="cert-logo">
                                <div class="cert-header-text">
                                    <p class="main-title">REPUBLIC OF THE PHILIPPINES<br>
                                        PROVINCE OF CAVITE<br>
                                        CITY OF CAVITE</p>
                                    <p class="barangay-name">BARANGAY 03 GEN. E. AGUINALDO (ZONE 1)</p>
                                    <p class="office-name">OFFICE OF THE PUNONG BARANGAY</p>
                                </div>
                                <img src="image/Logo/cavite-city-new-removebg-preview.png" alt="Barangay Logo" class="cert-logo">
                            </div>
                            <div class="cert-title-main" id="certTitle">BARANGAY CLEARANCE</div>
                        </div>
                        <!-- Body -->
                        <div class="cert-body-container">
                            <!-- Officials -->
                            <div class="officials-sidebar" id="officialsSidebar">
                                <div class="officials-title">BARANGAY OFFICIALS</div>
                                <!-- <div class="official-entry">
                                    <div class="official-name">HON. MARIO F. MOJICA</div>
                                    <div class="official-title">Punong Barangay</div>
                                </div>
                                <div class="section-label">BARANGAY KAGAWAD</div>
                                <div class="official-entry">
                                    <div class="official-name">KAG. ERICKSON E. SERVIDA</div>
                                    <div class="official-title">Kagawad</div>
                                </div>
                                <div class="official-entry">
                                    <div class="official-name">KAG. DENNIS Q. AZUR</div>
                                    <div class="official-title">Kagawad</div>
                                </div>
                                <div class="official-entry">
                                    <div class="official-name">KAG. MELODY L. MERCINE</div>
                                    <div class="official-title">Kagawad</div>
                                </div>
                                <div class="official-entry">
                                    <div class="official-name">KAG. JOSE E. OSERA</div>
                                    <div class="official-title">Kagawad</div>
                                </div>
                                <div class="official-entry">
                                    <div class="official-name">KAG. MARLON B. MALILLIN</div>
                                    <div class="official-title">Kagawad</div>
                                </div>
                                <div class="official-entry">
                                    <div class="official-name">KAG. ROGELIO S. VILLENA JR.</div>
                                    <div class="official-title">Kagawad</div>
                                </div>
                                <div class="official-entry">
                                    <div class="official-name">KAG. CAYETANA F. VILLANUEVA</div>
                                    <div class="official-title">Kagawad</div>
                                </div>
                                <div class="official-entry">
                                    <div class="official-name">KIM MARINELA L. MERCINE</div>
                                    <div class="official-title">SK Chairperson</div>
                                </div>
                                <div class="official-entry">
                                    <div class="official-name">TRICIA MAE NAVIDAD</div>
                                    <div class="official-title">Secretary</div>
                                </div> -->
                            </div>
                            <!-- Default content -->
                            <div class="cert-main-content" id="certContent">
                                <p class="cert-text"><strong><em>TO WHOM IT MAY CONCERN:</em></strong></p>
                                <p class="cert-text" style="text-indent:40px;">
                                    Please select a resident to generate the certificate.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== RESIDENT SEARCH MODAL ==================== -->
    <div class="modal fade" id="residentSearchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-users me-2"></i>Search Resident</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control search-input" id="residentSearch" placeholder="Type name to search..." autocomplete="off">
                    </div>
                    <div id="residentResults" style="max-height:400px; overflow-y:auto;">
                        <div class="text-center text-muted p-3">Type to search residents...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

     <script>
        // === RESIDENT SEARCH & SELECTION ===
        let residents = [];
        let selectedResident = null;
        let captain = '';

        get_captain();

        function get_captain () {
            $.ajax({
                url: 'partials/get_officials_captain.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    captain = data[0].full_name;
                },
                error: function(xhr, status, error) {
                    console.error('Failed to fetch officials:', error);
                }
            });
        }

        function get_officials() {
            $.ajax({
                url: 'partials/get_officials.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    renderOfficialsJQ(data);
                },
                error: function(xhr, status, error) {
                    console.error('Failed to fetch officials:', error);
                }
            });
        }

        function renderOfficialsJQ(list) {
            const $sidebar = $('#officialsSidebar');
            // Clear previous entries
            $sidebar.find('.official-entry, .section-label').remove();

            list.forEach(item => {
            if (item.section) {
                $('<div/>', { class: 'section-label', text: item.position }).appendTo($sidebar);
            } else {
                const $entry = $('<div/>', { class: 'official-entry' });
                $('<div/>', { class: 'official-name', text: item.full_name }).appendTo($entry);
                $('<div/>', { class: 'official-title', text: item.position }).appendTo($entry);
                $entry.appendTo($sidebar);
            }
            });
        }

        get_officials();

        function loadResidents() {
            fetch('partials/get_residents.php')
                .then(r => r.json())
                .then(data => {
                    residents = data;
                })
                .catch(err => {
                    console.error('Failed to load residents:', err);
                });
        }

        function searchResidents() {
            const query = document.getElementById('residentSearch').value.toLowerCase().trim();
            const resultsDiv = document.getElementById('residentResults');
            resultsDiv.innerHTML = '';

            if (!query) {
                resultsDiv.innerHTML = '<div class="text-center text-muted p-3">Type to search residents...</div>';
                return;
            }

            const filtered = residents.filter(r =>
                r.full_name.toLowerCase().includes(query) ||
                r.first_name.toLowerCase().includes(query) ||
                r.last_name.toLowerCase().includes(query)
            );

            if (filtered.length === 0) {
                resultsDiv.innerHTML = '<div class="text-center text-muted p-3">No residents found.</div>';
                return;
            }

            filtered.forEach(res => {
                const div = document.createElement('div');
                div.className = 'resident-item';
                div.innerHTML = `
            <strong>${res.full_name}</strong><br>
            <small class="text-muted">
                Age: ${res.age} | Sex: ${res.sex} | Address: ${res.house_number} ${res.street}
            </small>
        `;
                div.onclick = () => selectResident(res);
                resultsDiv.appendChild(div);
            });
        }

        function selectResident(res) {
            selectedResident = res;
            document.getElementById('selectedResidentName').textContent = res.full_name;
            document.getElementById('residentId').value = res.id;
            document.getElementById('age').value = res.age;
            document.getElementById('sex').value = res.sex;
            document.getElementById('address').value = `${res.house_number} ${res.street}, Barangay 3 Gen. Emilio Aguinaldo, Dalahican, Cavite City`;
            document.getElementById('dob').value = res.date_of_birth;
            document.getElementById('civilStatus').value = res.civil_status.charAt(0).toUpperCase() + res.civil_status.slice(1).toLowerCase();

            const modalElement = document.getElementById('residentSearchModal');
            const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
            modal.hide();

            // Force remove backdrop & reset body
            setTimeout(() => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 300);
        }

        // === CERTIFICATE GENERATION (FULL ORIGINAL HTML) ===
        document.getElementById('certType').addEventListener('change', function() {
            document.getElementById('residencyYearsDiv').style.display = this.value === 'residency' ? 'block' : 'none';
        });

        function generateCertificate() {
            if (!selectedResident) {
                alert('Please select a resident first.');
                return;
            }
            const type = document.getElementById('certType').value;
            const purpose = document.getElementById('purpose').value.trim();
            const years = document.getElementById('residencyYears').value || '___';
            if (!purpose) {
                alert('Please enter the purpose.');
                return;
            }

            const titles = {
                clearance: 'BARANGAY CLEARANCE',
                residency: 'CERTIFICATE OF RESIDENCY',
                indigency: 'CERTIFICATE OF INDIGENCY'
            };
            document.getElementById('certTitle').textContent = titles[type];

            const today = new Date();
            const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            const day = today.getDate();
            if (day === 1){
                daySuffix = "st";
            } else if (day === 2) {
                daySuffix = "nd";
            } else if (day === 3) {
                daySuffix = "rd";
            } else {
                daySuffix = "th";
            }
            const month = months[today.getMonth()];
            const year = today.getFullYear();

            const name = selectedResident.full_name.toUpperCase();
            const age = selectedResident.age;
            const sex = selectedResident.sex;
            const dob = selectedResident.date_of_birth;
            const civil = selectedResident.civil_status.toUpperCase();
            const addr = `${selectedResident.house_number} ${selectedResident.street}, Barangay 3 Gen. Emilio Aguinaldo, Dalahican, Cavite City`;

            let html = '';

            if (type === 'clearance') {
                html = `
            <p class="cert-text"><strong><em>TO WHOM IT MAY CONCERN:</em></strong></p>
            <p class="cert-text" style="text-indent:40px;">
                THIS IS TO CERTIFY THAT, <strong><u>${name}</u></strong>
                AGE: <strong><u>${age}</u></strong> YRS OLD /
                SEX: <strong><u>${sex}</u></strong>
                NATIONALITY: <strong>FILIPINO</strong>
                CIVIL STATUS: <strong><u>${civil}</u></strong>
                DATE OF BIRTH: <strong><u>${dob}</u></strong>
                PLACE OF BIRTH: <strong>CAVITE CITY</strong> AND PRESENTLY RESIDING
                STREET <strong>BARANGAY 3 GEN. EMILIO AGUINALDO</strong> DALAHICAN, CAVITE CITY.
            </p>
            <p class="cert-text" style="text-indent:40px;">
                THIS INDIVIDUAL IS KNOWN TO ME TO BE A RESIDENT OF THIS BARANGAY, A LAW-ABIDING
                CITIZEN HAVING A GOOD MORAL CHARACTER AND NOT CONNECTED WITH ANY SUBVERSIVE ORGANIZATION.
            </p>
            <p class="cert-text" style="text-indent:40px;">
                THIS BARANGAY CLEARANCE IS BEING ISSUED UPON REQUEST OF THE SUBJECT THIS
                <strong><u>${day}${daySuffix}</u></strong> DAY OF
                <strong><u>${month}, ${year}</u></strong>
            </p>
            <p class="cert-text">
                FOR, <strong><u>${purpose}</u></strong>
            </p>
            <p class="cert-text" style="margin-top:20px;font-size:12px;">
                Res. Cert. No.: <u>______________________</u><br>
                Place Issued: Cavite City<br>
                Date Issued: <u>${today.toLocaleDateString()}</u>
            </p>
            <div class="signature-area">
                <div class="applicant-signature"><div class="signature-line">Applicant Signature</div></div>
                <div class="punong-signature">
                <p style="text-align:right;">${captain}</p>
                    <div class="signature-line"><strong>Punong Barangay</strong><br><strong>Barangay 3</strong></div>
                </div>
            </div>`;
            } else if (type === 'residency') {
                html = `
            <p class="cert-text"><strong>To whom it may concern:</strong></p>
            <p class="cert-text" style="text-indent:40px;">
                Be it known whose information appear hereunder is a resident Barangay
            </p>
            <p class="cert-text">Name: <strong><u>${name}</u></strong></p>
            <p class="cert-text">Address: <strong><u>${addr}</u></strong></p>
            <p class="cert-text">Date of Birth: <strong><u>${dob}</u></strong></p>
            <p class="cert-text">Nationality: <strong>FILIPINO</strong></p>
            <p class="cert-text">Civil Status: <strong><u>${civil}</u></strong></p>
            <p class="cert-text">Years of Residency in Barangay: <strong><u>${years}</u></strong></p>
            <p class="cert-text" style="margin-top:30px;">
                <strong>HEREBY CERTIFY UNDER OATH THE FOREGOING DATA IS TRUE AND CORRECT</strong>
            </p>
            <div class="applicant-signature" style="margin-top:40px;">
                <div class="signature-line">Signature of Applicant</div>
            </div>
            <p class="cert-text" style="margin-top:30px;text-indent:40px;">
                This individual is known to me to be a resident of this barangay, a law-abiding
                citizen having a good moral character and not connected with any subversive organization.
            </p>
            <p class="cert-text" style="margin-top:20px;">
                This Certificate of Residency is being issued upon request of the subject this
                <strong><u>${day}${daySuffix}</u></strong> day of <strong><u>${month}</u></strong>, <strong>${year}</strong> for,
                <strong><u>${purpose}</u></strong>
            </p>
            <div class="punong-signature">
            <p style="text-align:right;">${captain}</p>
                <div class="signature-line"><strong>Punong Barangay</strong></div>
            </div>
            <p class="validity-notice text-danger">
                VALID FOR SIX (6) MONTHS ONLY/DON'T ACCEPT WITHOUT DRY SEAL
            </p>`;
            } else { // indigency
                html = `
            <p class="cert-text"><strong>To whom it may concern:</strong></p>
            <p class="cert-text" style="text-indent:40px;">
                This is to certify that, <strong><u>${name}</u></strong> resident of
                Street Barangay 3 Gen. Emilio Aguinaldo Dalahican, Cavite City. Is
                belongs to an Indigent Family, that <strong><u>${name}</u></strong> Has no
                regular monthly income or any resource of livelihood
            </p>
            <p class="cert-text" style="margin-top:40px;text-indent:40px;">
                This Certification is being issued upon request of said person in
                connection with his/her application for, <strong><u>${purpose}</u></strong>
            </p>
            <p class="cert-text" style="margin-top:40px;">
                Issued on <strong><u>${day}${daySuffix}</u></strong> day of
                <strong><u>${month}</u></strong>, <strong>${year}</strong>
            </p>
            <div class="punong-signature">
                <p style="text-align:right;">${captain}</p>
                <div class="signature-line"><strong>Punong Barangay</strong><br><strong>Barangay 3</strong></div>
            </div>
            <p class="validity-notice text-primary" style="margin-top:30px;">
                VALID FOR SIX (6) MONTHS ONLY/DON'T ACCEPT WITHOUT DRY SEAL
            </p>`;
            }

            document.getElementById('certContent').innerHTML = html;

            // Success toast
            const toast = document.createElement('div');
            toast.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 no-print';
            toast.style.zIndex = '9999';
            toast.innerHTML = `<i class="fas fa-check-circle me-2"></i>Certificate generated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // === INIT ===
        document.addEventListener('DOMContentLoaded', () => {
            loadResidents();
            document.getElementById('residentSearch').addEventListener('input', searchResidents);
        });
    </script>
    <script>
        // Clean up any leftover modals on page load
        window.addEventListener('load', () => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
        });
    </script>
</body>

</html>