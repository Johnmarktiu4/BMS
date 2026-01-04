<?php
session_start();
require_once 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Invalid credentials'];

    $input     = trim($_POST['email'] ?? ''); // This field is now "username or email"
    $password  = $_POST['password'] ?? '';
    $conn      = getDBConnection();

    if (!$conn) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit;
    }

    // === 1. HARDCODED ADMIN LOGIN ===
    if ($input === 'admin@gmail.com' && $password === 'Admin123') {
        $_SESSION['user_id']     = 0;
        $_SESSION['user_type']   = 'admin';
        $_SESSION['full_name']   = 'Administrator';
        $_SESSION['position']    = 'System Administrator';
        $_SESSION['elec_year']   = '2025 - 2029';

        $response = [
            'status'   => 'success',
            'message'  => 'Welcome, Administrator!',
            'redirect' => 'admin/index.php'
        ];
        echo json_encode($response);
        exit;
    }

    // === 2. OFFICIAL ACCOUNT LOGIN (BY USERNAME ONLY) ===
    if (empty($input) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter username and password']);
        exit;
    }

$stmt = $conn->prepare("
    SELECT ua.official_id, ua.username, ua.password, ua.status, 
           o.full_name, o.position
    FROM user_roles_official_accounts ua
    JOIN officials o ON ua.official_id = o.id
    WHERE ua.username = ? AND o.archived = 0 
    LIMIT 1
");
    $stmt->bind_param('s', $input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['status'] !== 'Active') {
            $response['message'] = 'Your account is inactive or locked. Contact administrator.';
            $response['status'] = 'error';
        } elseif (password_verify($password, $user['password'])) {
            // Login Success
            $_SESSION['user_id'] = $user['official_id'];
            $_SESSION['user_type']   = $user['position'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['full_name']   = $user['full_name'];
            $_SESSION['position']    = $user['position'];
            $_SESSION['elec_year']   = '2025 - 2029';

            $response = [
                'status'   => 'success',
                'message'  => 'Login successful!',
                'redirect' => 'admin/index.php'
            ];
        } else {
            $response['message'] = 'Incorrect password';
        }
    } else {
        $response['message'] = 'Username not found';
    }

    $stmt->close();
    echo json_encode($response);
    $conn->close();
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'lock') {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Account Locked'];

    $input     = trim($_GET['email'] ?? '');
    $conn      = getDBConnection();
    if (!$conn) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit;
    }
    $stmt = $conn->prepare("
        UPDATE user_roles_official_accounts 
        SET status = 'Locked' 
        WHERE username = ?
    ");
    $stmt->bind_param('s', $input);
    if ($stmt->execute()) {
        $response = [
            'status'   => 'success',
            'message'  => 'Your account has been locked. Please contact the administrator.'
        ];
    } else {
        $response['message'] = 'Failed to lock account. Please try again.';
    }
    $stmt->close();
    echo json_encode($response);
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barangay Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow-x: hidden; }
        .login-container { 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            position: relative; 
            background-image: url('assets/image/Logo/IMG_6417.PNG'); 
            background-size: cover; 
            background-position: center; 
            background-repeat: no-repeat; 
            background-attachment: fixed; 
            padding: 20px; 
        }
        .login-container::before { 
            content: ''; 
            position: absolute; 
            top: 0; left: 0; right: 0; bottom: 0; 
            background: rgba(0, 0, 0, 0.5); 
            z-index: 0; 
        }
        .login-card { 
            display: flex; 
            max-width: 1000px; 
            width: 100%; 
            background: rgba(255, 255, 255, 0.98); 
            border-radius: 24px; 
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); 
            overflow: hidden; 
            position: relative; 
            z-index: 1; 
            backdrop-filter: blur(10px); 
        }
        .logo-side { 
            flex: 1; 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
            color: white; 
            padding: 60px 40px; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center; 
            position: relative; 
            overflow: hidden; 
        }
        .logo-side::before { 
            content: ''; 
            position: absolute; 
            width: 300px; height: 300px; 
            background: rgba(255, 255, 255, 0.1); 
            border-radius: 50%; 
            top: -100px; right: -100px; 
        }
        .logo-side::after { 
            content: ''; 
            position: absolute; 
            width: 250px; height: 250px; 
            background: rgba(255, 255, 255, 0.08); 
            border-radius: 50%; 
            bottom: -80px; left: -80px; 
        }
        .logo img { 
            max-width: 220px; 
            height: auto; 
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3)); 
            animation: logoFloat 3s ease-in-out infinite; 
        }
        @keyframes logoFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
        .welcome-text h2 { font-size: 32px; font-weight: 700; margin-bottom: 15px; }
        .welcome-text p { font-size: 16px; opacity: 0.95; max-width: 350px; margin: 0 auto; line-height: 1.6; }
        .form-side { flex: 1; padding: 60px 50px; background: white; }
        .form-title h3 { font-size: 32px; font-weight: 700; color: #2d3748; margin-bottom: 8px; }
        .form-title p { font-size: 15px; color: #718096; }
        .form-control { 
            border-radius: 12px; 
            padding: 16px 18px; 
            border: 2px solid #e2e8f0; 
            font-size: 15px; 
            background: #f7fafc; 
            transition: all 0.3s ease; 
        }
        .form-control:focus { 
            border-color: #10b981; 
            box-shadow: 0 0 0 4px rgba(16,185,129,0.1); 
            background: white; 
        }
        .form-floating label { padding: 16px 18px; color: #718096; }
        .password-toggle { 
            position: absolute; 
            right: 18px; top: 50%; 
            transform: translateY(-50%); 
            background: none; border: none; 
            color: #a0aec0; font-size: 18px; 
            cursor: pointer; z-index: 10; 
        }
        .password-toggle:hover { color: #10b981; }
        .btn-login { 
            width: 100%; 
            padding: 16px; 
            border-radius: 12px; 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
            border: none; 
            font-size: 17px; 
            font-weight: 600; 
            color: white; 
            box-shadow: 0 4px 15px rgba(16,185,129,0.4); 
        }
        .btn-login:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 25px rgba(16,185,129,0.6); 
        }
        .loading-spinner { 
            display: none; 
            width: 18px; height: 18px; 
            border: 3px solid rgba(255,255,255,0.3); 
            border-top: 3px solid white; 
            border-radius: 50%; 
            animation: spin 0.8s linear infinite; 
            margin-right: 10px; 
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .forgot-password-wrapper { 
            margin-top: 20px; 
            text-align: center; 
        }
        .forgot-password-wrapper a { 
            color: #10b981; 
            font-weight: 500; 
            text-decoration: none; 
        }
        .forgot-password-wrapper a:hover { 
            color: #059669; 
            text-decoration: underline; 
        }
        .fade-in { animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) {
            .login-card { flex-direction: column; max-width: 450px; }
            .logo-side, .form-side { padding: 40px 30px; }
        }
        .alert { 
            position: fixed; top: 20px; right: 20px; z-index: 9999; 
            min-width: 320px; border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.2); 
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <div class="logo-side">
                <div class="logo-container">
                    <div class="logo">
                        <img src="assets/image/Logo/Brgy3_logo-removebg-preview.png" alt="Barangay Logo">
                    </div>
                </div>
                <div class="welcome-text">
                    <h2>Welcome Back!</h2>
                    <p>Enter your username and password to access the system.</p>
                </div>
            </div>
            <div class="form-side">
                <div class="form-title">
                    <h3>Sign In</h3>
                    <p>Use your username to log in</p>
                </div>
                <form id="loginForm">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="email" placeholder="Enter your username" required autocomplete="username">
                        <label for="username"><i class="fas fa-user me-2"></i>Username</label>
                    </div>
                    <div class="form-floating position-relative mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required autocomplete="current-password">
                        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="forgot-password-wrapper text-end">
                        <a href="forgot_password.php">Forgot your password?</a>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-login">
                            <span class="loading-spinner" id="loadingSpinner"></span>
                            <span id="loginText">Sign In</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        var count = 0;
        // Toggle Password Visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const icon = this.querySelector('i');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // Alert Helper
        function showAlert(type, message) {
            const alert = $(`
                <div class="alert alert-${type} alert-dismissible fade show" style="position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            $('body').append(alert);
            setTimeout(() => alert.alert('close'), 5000);
        }

        // Login Form Submit
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            if (!this.checkValidity()) return this.reportValidity();

            const btn = $('.btn-login');
            const spinner = $('#loadingSpinner');
            const text = $('#loginText');
            count++;
            spinner.show();
            text.text('Signing in...');
            btn.prop('disabled', true);
            $.ajax({
                url: 'index.php',
                method: 'POST',
                data: {
                    action: 'login',
                    email: $('#username').val().trim(),  // This is actually the username
                    password: $('#password').val()
                },
                dataType: 'json',
                success: function(r) {
                    spinner.hide();
                    text.text('Sign In');
                    btn.prop('disabled', false);

                    if (r.status === 'success') {
                        showAlert('success', r.message || 'Login successful!');
                        setTimeout(() => window.location.href = r.redirect, 800);
                    } else {
                        showAlert('danger', r.message || 'Login failed');
                        if (r.status === 'error' && r.message.includes('locked')) {
                            btn.prop('disabled', true);
                        }
                        if (count === 4) {
                            showAlert('warning', 'Your account will be locked if you fail to login one more time (5 attempts).');
                        }
                        if (count >= 5) {
                            showAlert('danger', 'Too many failed attempts. Please contact the administrator.');
                            btn.prop('disabled', true);
                            $.ajax({
                                url: 'index.php',
                                method: 'GET',
                                data: {
                                    action: 'lock',
                                    email: $('#username').val().trim()
                                },
                                dataType: 'json',
                                success: function(res) {
                                    if (res.status === 'success') {
                                        showAlert('danger', res.message || 'Your account has been locked.');
                                    } else {
                                        showAlert('danger', res.message || 'Failed to lock account.');
                                    }
                                },
                            });
                        }
                    }
                },
                error: function() {
                    spinner.hide();
                    text.text('Sign In');
                    btn.prop('disabled', false);
                    showAlert('danger', 'Connection error. Please try again.');
                }
            });
        });
    </script>
</body>
</html>