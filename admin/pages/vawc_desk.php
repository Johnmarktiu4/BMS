<?php
// admin/pages/vawc_desk.php
include 'partials/db_conn.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAWC Desk - Barangay Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }
        .card { border-radius: 15px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1); }
        .form-label { font-weight: 500; }
        .table { border-radius: 8px; overflow: hidden; }
        .table th { background: #3498db; color: white; }
        .modal-lg { max-width: 800px; }
        .filter-container { margin-bottom: 1rem; }
        @media print {
            .no-print { display: none !important; }
            .table { font-size: 12px; }
            .card { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h2 class="mb-4"><i class="fas fa-hands-helping me-2"></i>VAWC Desk</h2>
        
        <!-- Filter and Search -->
        <div class="card mb-4">
            <div class="card-body no-print">
                <div class="row filter-container">
                    <div class="col-md-4 mb-2">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by Victim Name or Abuser Name">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select class="form-select" id="filterDate">
                            <option value="">Filter by Date</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select class="form-select" id="filterStatus">
                            <option value="">Filter by Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Reported to DILG">Reported to DILG</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-primary w-100" onclick="printTable()"><i class="fas fa-print me-2"></i>Print</button>
                    </div>
                </div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#vawcModal"><i class="fas fa-plus me-2"></i>Add New VAWC Report</button>
            </div>
        </div>

        <!-- VAWC Modal -->
        <div class="modal fade" id="vawcModal" tabindex="-1" aria-labelledby="vawcModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="vawcModalLabel">Add New VAWC Report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="vawcForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Victim Information</h6>
                                    <div class="mb-3">
                                        <label for="victim_name" class="form-label">Name</label>
                                        <select class="form-select select2" id="victim_name" name="victim_name" required style="width: 100%;">
                                            <option value="">Select Resident</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="victim_dob" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="victim_dob" name="victim_dob" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="victim_age" class="form-label">Age</label>
                                        <input type="number" class="form-control" id="victim_age" name="victim_age" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="victim_address" class="form-label">Address</label>
                                        <textarea class="form-control" id="victim_address" name="victim_address" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="victim_contact" class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" id="victim_contact" name="victim_contact" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="relationship_to_abuser" class="form-label">Relationship to Abuser</label>
                                        <input type="text" class="form-control" id="relationship_to_abuser" name="relationship_to_abuser" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Abuser Information</h6>
                                    <div class="mb-3">
                                        <label for="abuser_name" class="form-label">Name</label>
                                        <select class="form-select select2" id="abuser_name" name="abuser_name" required style="width: 100%;">
                                            <option value="">Select Resident</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="abuser_is_resident" class="form-label">Is Resident?</label>
                                        <select class="form-select" id="abuser_is_resident" name="abuser_is_resident" required>
                                            <option value="">Select</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="abuser_address" class="form-label">Address</label>
                                        <textarea class="form-control" id="abuser_address" name="abuser_address" rows="3" required></textarea>
                                    </div>
                                    <h6>Incident Details</h6>
                                    <div class="mb-3">
                                        <label for="incident_date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="incident_date" name="incident_date" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="incident_time" class="form-label">Time</label>
                                        <input type="time" class="form-control" id="incident_time" name="incident_time" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="incident_place" class="form-label">Place</label>
                                        <input type="text" class="form-control" id="incident_place" name="incident_place" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="incident_description" class="form-label">Description</label>
                                        <textarea class="form-control" id="incident_description" name="incident_description" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="witnesses_evidence" class="form-label">Witnesses or Evidence</label>
                                        <textarea class="form-control" id="witnesses_evidence" name="witnesses_evidence" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Reported to DILG">Reported to DILG</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- VAWC Table -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">VAWC Reports</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover" id="vawcTable">
                    <thead>
                        <tr>
                            <th>Victim Name</th>
                            <th>DOB</th>
                            <th>Age</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Relationship</th>
                            <th>Abuser Name</th>
                            <th>Abuser Resident</th>
                            <th>Abuser Address</th>
                            <th>Incident Date</th>
                            <th>Incident Time</th>
                            <th>Incident Place</th>
                            <th>Status</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="vawcTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for searchable dropdowns
            $('.select2').select2({
                width: '100%',
                placeholder: "Select Resident",
                allowClear: true,
                dropdownParent: $('#vawcModal')
            });

            // Fetch residents for victim and abuser dropdowns
            $.ajax({
                url: 'partials/vawc_api.php',
                type: 'POST',
                data: { action: 'fetch_residents' },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        let options = '<option value="">Select Resident</option>';
                        response.data.forEach(resident => {
                            options += `<option value="${resident.id}">${resident.full_name}</option>`;
                        });
                        $('#victim_name').html(options);
                        $('#abuser_name').html(options);
                    } else {
                        console.warn('No residents found or error:', response.message || 'Empty data');
                        alert('No residents found. Please check the database or contact support.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error fetching residents:', status, error, xhr.responseText);
                    alert('Error loading residents: ' + (xhr.responseText || 'Server error. Check console for details.'));
                }
            });

            // Handle resident selection to prefill fields
            $('#victim_name').on('change', function() {
                let residentId = $(this).val();
                if (residentId) {
                    $.ajax({
                        url: 'partials/vawc_api.php',
                        type: 'POST',
                        data: { action: 'get_resident_details', id: residentId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                let resident = response.data;
                                $('#victim_dob').val(resident.date_of_birth);
                                $('#victim_age').val(resident.age);
                                $('#victim_address').val(resident.address);
                                $('#victim_contact').val(resident.contact_number);
                            }
                        }
                    });
                } else {
                    $('#victim_dob').val('');
                    $('#victim_age').val('');
                    $('#victim_address').val('');
                    $('#victim_contact').val('');
                }
            });

            $('#abuser_name').on('change', function() {
                let residentId = $(this).val();
                if (residentId) {
                    $.ajax({
                        url: 'partials/vawc_api.php',
                        type: 'POST',
                        data: { action: 'get_resident_details', id: residentId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                let resident = response.data;
                                $('#abuser_is_resident').val('Yes');
                                $('#abuser_address').val(resident.address);
                            }
                        }
                    });
                } else {
                    $('#abuser_is_resident').val('');
                    $('#abuser_address').val('');
                }
            });

            // Calculate age from DOB
            $('#victim_dob').on('change', function() {
                const dob = new Date($(this).val());
                const ageDifMs = Date.now() - dob.getTime();
                const ageDate = new Date(ageDifMs);
                $('#victim_age').val(Math.abs(ageDate.getUTCFullYear() - 1970));
            });

            // Fetch VAWC records
            let allReports = [];
            function loadVawcRecords(filters = {}) {
                $.ajax({
                    url: 'partials/vawc_api.php',
                    type: 'POST',
                    data: { action: 'fetch_reports', ...filters },
                    dataType: 'json',
                    success: function(response) {
                        console.log('VAWC response:', response);
                        if (response.success) {
                            allReports = response.data;
                            let rows = '';
                            response.data.forEach(report => {
                                rows += `
                                    <tr>
                                        <td>${report.victim_name}</td>
                                        <td>${report.victim_dob}</td>
                                        <td>${report.victim_age}</td>
                                        <td>${report.victim_address}</td>
                                        <td>${report.victim_contact}</td>
                                        <td>${report.relationship_to_abuser}</td>
                                        <td>${report.abuser_name}</td>
                                        <td>${report.abuser_is_resident}</td>
                                        <td>${report.abuser_address}</td>
                                        <td>${report.incident_date}</td>
                                        <td>${report.incident_time}</td>
                                        <td>${report.incident_place}</td>
                                        <td>${report.status}</td>
                                        <td class="no-print">
                                            <button class="btn btn-sm btn-warning edit-btn" data-id="${report.id}" data-bs-toggle="modal" data-bs-target="#vawcModal"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-danger delete-btn" data-id="${report.id}"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>`;
                            });
                            $('#vawcTableBody').html(rows);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error fetching VAWC reports:', status, error, xhr.responseText);
                    }
                });
            }

            loadVawcRecords();

            // Handle form submission
            $('#vawcForm').on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize();
                console.log('Form data:', formData);
                $.ajax({
                    url: 'partials/vawc_api.php',
                    type: 'POST',
                    data: formData + '&action=add_report',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Submit response:', response);
                        if (response.success) {
                            alert('VAWC report ' + ($('#report_id').length ? 'updated' : 'added') + ' successfully!');
                            $('#vawcForm')[0].reset();
                            $('#victim_name').val('').trigger('change');
                            $('#abuser_name').val('').trigger('change');
                            $('#report_id').remove();
                            $('#vawcForm').find('button[type="submit"]').html('<i class="fas fa-save me-2"></i>Submit');
                            $('#vawcModal').modal('hide');
                            loadVawcRecords();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error submitting VAWC report:', status, error, xhr.responseText);
                        alert('Error submitting VAWC report: ' + (xhr.responseText || 'Server error. Check console for details.'));
                    }
                });
            });

            // Handle edit button click
            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: 'partials/vawc_api.php',
                    type: 'POST',
                    data: { action: 'fetch_report', id: id },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Fetch report response:', response);
                        if (response.success) {
                            let report = response.data;
                            $('#vawcModalLabel').text('Edit VAWC Report');
                            $('#victim_name').val(report.victim_name).trigger('change');
                            $('#victim_dob').val(report.victim_dob);
                            $('#victim_age').val(report.victim_age);
                            $('#victim_address').val(report.victim_address);
                            $('#victim_contact').val(report.victim_contact);
                            $('#relationship_to_abuser').val(report.relationship_to_abuser);
                            $('#abuser_name').val(report.abuser_name).trigger('change');
                            $('#abuser_is_resident').val(report.abuser_is_resident);
                            $('#abuser_address').val(report.abuser_address);
                            $('#incident_date').val(report.incident_date);
                            $('#incident_time').val(report.incident_time);
                            $('#incident_place').val(report.incident_place);
                            $('#incident_description').val(report.incident_description);
                            $('#witnesses_evidence').val(report.witnesses_evidence);
                            $('#status').val(report.status);
                            $('#vawcForm').append('<input type="hidden" name="id" id="report_id" value="' + report.id + '">');
                            $('#vawcForm').find('button[type="submit"]').html('<i class="fas fa-save me-2"></i>Update');
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error fetching report:', status, error, xhr.responseText);
                    }
                });
            });

            // Handle delete button click
            $(document).on('click', '.delete-btn', function() {
                if (confirm('Are you sure you want to delete this report?')) {
                    let id = $(this).data('id');
                    $.ajax({
                        url: 'partials/vawc_api.php',
                        type: 'POST',
                        data: { action: 'delete_report', id: id },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Delete response:', response);
                            if (response.success) {
                                alert('Report deleted successfully!');
                                loadVawcRecords();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error deleting report:', status, error, xhr.responseText);
                        }
                    });
                }
            });

            // Handle search
            $('#searchInput').on('input', function() {
                let searchTerm = $(this).val().toLowerCase();
                let filtered = allReports.filter(report => 
                    report.victim_name.toLowerCase().includes(searchTerm) ||
                    report.abuser_name.toLowerCase().includes(searchTerm)
                );
                let rows = '';
                filtered.forEach(report => {
                    rows += `
                        <tr>
                            <td>${report.victim_name}</td>
                            <td>${report.victim_dob}</td>
                            <td>${report.victim_age}</td>
                            <td>${report.victim_address}</td>
                            <td>${report.victim_contact}</td>
                            <td>${report.relationship_to_abuser}</td>
                            <td>${report.abuser_name}</td>
                            <td>${report.abuser_is_resident}</td>
                            <td>${report.abuser_address}</td>
                            <td>${report.incident_date}</td>
                            <td>${report.incident_time}</td>
                            <td>${report.incident_place}</td>
                            <td>${report.status}</td>
                            <td class="no-print">
                                <button class="btn btn-sm btn-warning edit-btn" data-id="${report.id}" data-bs-toggle="modal" data-bs-target="#vawcModal"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="${report.id}"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>`;
                });
                $('#vawcTableBody').html(rows);
            });

            // Handle date filter
            $('#filterDate').on('change', function() {
                let filter = $(this).val();
                let filters = {};
                let today = new Date();
                if (filter === 'today') {
                    filters.incident_date = today.toISOString().split('T')[0];
                } else if (filter === 'week') {
                    let weekAgo = new Date(today.setDate(today.getDate() - 7)).toISOString().split('T')[0];
                    filters.date_range = weekAgo;
                } else if (filter === 'month') {
                    let monthAgo = new Date(today.setDate(today.getDate() - 30)).toISOString().split('T')[0];
                    filters.date_range = monthAgo;
                }
                loadVawcRecords(filters);
            });

            // Handle status filter
            $('#filterStatus').on('change', function() {
                let status = $(this).val();
                let filters = status ? { status: status } : {};
                loadVawcRecords(filters);
            });

            // Reset modal on close
            $('#vawcModal').on('hidden.bs.modal', function() {
                $('#vawcForm')[0].reset();
                $('#victim_name').val('').trigger('change');
                $('#abuser_name').val('').trigger('change');
                $('#report_id').remove();
                $('#vawcForm').find('button[type="submit"]').html('<i class="fas fa-save me-2"></i>Submit');
                $('#vawcModalLabel').text('Add New VAWC Report');
            });
        });

        // Print table function
        function printTable() {
            window.print();
        }
    </script>
</body>
</html>