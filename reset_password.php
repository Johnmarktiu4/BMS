<?php
session_start();
require_once 'db_conn.php';

if (!isset($_SESSION['reset_official_id']) || !isset($_SESSION['reset_code'])) {
    header('Location: forgot_password.php');
    exit;
}

$official_id = $_SESSION['reset_official_id'];
$message = '';
$plain_code = $_SESSION['plain_code'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $new_password = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($code, $_SESSION['reset_code'])) {
        $message = '<div class="alert alert-danger">Incorrect code.</div>';
    } elseif ($new_password !== $confirm) {
        $message = '<div class="alert alert-danger">Passwords do not match.</div>';
    } elseif (strlen($new_password) < 6) {
        $message = '<div class="alert alert-danger">Password must be 6+ characters.</div>';
    } else {
        $conn = getDBConnection();
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user_roles_official_accounts SET password = ? WHERE official_id = ?");
        $stmt->bind_param('si', $hashed, $official_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            session_unset();
            session_destroy();
            $message = '<div class="alert alert-success text-center">
                <i class="fas fa-check-circle fa-3x mb-3"></i><br>
                <h5>Password Changed Successfully!</h5>
                <a href="index.php" class="btn btn-success mt-3">Go to Login</a>
            </div>';
        }
        $stmt->close();
        closeDBConnection($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card { max-width: 460px; width: 100%; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 40px 20px; text-align: center; }
        .card-header img { width: 100px; }
        .card-body { padding: 40px; background: white; }
        .code-input {
            font-size: 36px;
            letter-spacing: 12px;
            text-align: center;
            font-weight: bold;
            border-radius: 12px;
        }
        .btn-change { background: linear-gradient(135deg, #10b981, #059669); border: none; padding: 14px; border-radius: 12px; font-size: 17px; font-weight: 600; }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <img src="assets/image/Logo/Brgy3_logo-removebg-preview.png" alt="Logo">
        <h4 class="mt-3">Enter Reset Code</h4>
    </div>

    <div class="card-body">
        <?php echo $message; ?>

        <?php if (!str_contains($message, 'success')): ?>
        <form method="POST">
            <div class="mb-4">
                <label class="form-label text-center d-block">6-Digit Code</label>
                <input type="text" name="code" class="form-control form-control-lg code-input" 
                       maxlength="6" value="<?php echo htmlspecialchars($plain_code); ?>" 
                       required autofocus autocomplete="off">
                <small class="text-muted text-center d-block mt-2">Check your email</small>
            </div>

            <div class="mb-3">
                <input type="password" name="new_password" class="form-control form-control-lg" 
                       placeholder="New Password" required>
            </div>
            <div class="mb-4">
                <input type="password" name="confirm_password" class="form-control form-control-lg" 
                       placeholder="Confirm Password" required>
            </div>

            <button type="submit" class="btn btn-success w-100 btn-change">
                <i class="fas fa-lock me-2"></i> Change Password
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>