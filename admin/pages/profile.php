<?php
 

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Safe session values
$user_type  = $_SESSION['user_type'] ?? 'official';
$user_id    = $_SESSION['user_id'];           // This is now official_id (from officials table)
$full_name  = $_SESSION['full_name'] ?? 'User';
$username   = $_SESSION['username'] ?? 'Not set';
$position   = $_SESSION['position'] ?? 'Unknown';

$official_details = null;
$success = $error = '';

// Only for official users
if ($user_type === 'official') {
    require_once '../db_conn.php';
    $conn = getDBConnection();

    // Fetch correct official using official_id from session
    $sql = "SELECT o.*, ua.username, ua.status AS account_status
            FROM officials o
            LEFT JOIN user_roles_official_accounts ua ON o.id = ua.official_id
            WHERE o.id = ? AND o.archived = 0";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $official_details = $result->fetch_assoc();

        // Update session values with fresh DB data
        $full_name = $official_details['full_name'];
        $position  = $official_details['position'];
        $username  = $official_details['username'] ?? $username;
    } else {
        // If no official found (should not happen), show error
        $error = "Profile not found or account is archived.";
    }
    $stmt->close();
    closeDBConnection($conn);
}

// === HANDLE PROFILE UPDATE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $user_type === 'official' && $official_details) {
    $new_name    = trim($_POST['full_name']);
    $new_contact = trim($_POST['contact']);
    $new_pass    = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($new_name) || empty($new_contact)) {
        $error = "Full name and contact number are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Passwords do not match.";
    } elseif (!empty($new_pass) && strlen($new_pass) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        require_once '../db_conn.php';
        $conn = getDBConnection();
        $updated = false;

        // Update name & contact
        $stmt1 = $conn->prepare("UPDATE officials SET full_name = ?, contact = ? WHERE id = ?");
        $stmt1->bind_param('ssi', $new_name, $new_contact, $user_id);
        if ($stmt1->execute()) $updated = true;
        $stmt1->close();

        // Update password if provided
        if (!empty($new_pass)) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("UPDATE user_roles_official_accounts SET password = ? WHERE official_id = ?");
            $stmt2->bind_param('si', $hashed, $user_id);
            $stmt2->execute();
            $stmt2->close();
            $updated = true;
        }

        if ($updated) {
            $success = "Profile updated successfully!";
            $_SESSION['full_name'] = $new_name;
            $official_details['full_name'] = $new_name;
            $official_details['contact']   = $new_contact;
            $full_name = $new_name;
        } else {
            $success = "No changes were made.";
        }
        closeDBConnection($conn);
    }
}
?>

<div class="row justify-content-center mt-4">
    <div class="col-lg-8">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-gradient text-white text-center py-4" style="background: linear-gradient(135deg, #10b981, #059669);">
                <h3 class="mb-0 fw-bold"><i class="fas fa-user-circle me-2"></i> My Profile</h3>
            </div>
            <div class="card-body p-5">
                <div class="text-center mb-5">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-lg mx-auto"
                         style="width:140px; height:140px; font-size:3.5rem;">
                        <?= strtoupper(substr($full_name, 0, 2)) ?>
                    </div>
                    <h4 class="mt-4 mb-1 fw-bold"><?= htmlspecialchars($full_name) ?></h4>
                    <p class="text-muted fs-5">
                        <?php if ($user_type === 'admin'): ?>
                            <span class="badge bg-danger fs-6">Administrator</span>
                        <?php else: ?>
                            <span class="badge bg-success fs-6"><?= htmlspecialchars($position) ?></span>
                        <?php endif; ?>
                    </p>
                </div>

                <hr class="my-5">

                <div class="row g-4 text-muted">
                    <?php if ($user_type === 'official'): ?>
                        <div class="col-md-6"><strong>Username:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($username) ?></span></div>
                        <?php if ($official_details): ?>
                            <div class="col-md-6"><strong>Contact:</strong> <?= htmlspecialchars($official_details['contact'] ?? 'Not set') ?></div>
                            <div class="col-md-6"><strong>Term Start:</strong> 
                                <?= $official_details['term_start_date']?>
                            </div>
                            <div class="col-md-6"><strong>Term End:</strong> 
                                <?= $official_details['term_end_date'] 
                                    ? $official_details['term_end_date']
                                    : '<span class="text-success fw-bold">Currently Active</span>' ?>
                            </div>
                            <div class="col-md-6"><strong>Account Status:</strong> 
                                <span class="badge <?= ($official_details['account_status'] ?? '') === 'Active' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= ucfirst($official_details['account_status'] ?? 'unknown') ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="col-12 text-center">
                            <p class="lead">Welcome, Administrator!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-footer bg-light text-center py-4">
                <a href="?page=dashboard" class="btn btn-outline-secondary px-4">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <?php if ($user_type === 'official' && $official_details): ?>
                    <button class="btn btn-primary px-5 ms-3" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- EDIT PROFILE MODAL -->
<?php if ($user_type === 'official' && $official_details): ?>
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content rounded-4 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editProfileModalLabel"><i class="fas fa-user-edit"></i> Edit Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Full Name</label>
                    <input type="text" name="full_name" class="form-control form-control-lg"
                           value="<?= htmlspecialchars($official_details['full_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Contact Number</label>
                    <input type="text" name="contact" class="form-control form-control-lg"
                           value="<?= htmlspecialchars($official_details['contact']) ?>" required>
                </div>
                <hr>
                <small class="text-muted d-block mb-3">Leave blank to keep current password</small>
                <div class="mb-3">
                    <label class="form-label fw-bold">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Re-type new password">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="update_profile" class="btn btn-success px-4">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
    .card:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 20px 40px rgba(16,185,129,.15)!important; 
        transition: all .3s ease;
    }
</style>