<?php
session_start();
require_once 'db_conn.php';
$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Step 1: Verify Username + One Security Question
    if (isset($_POST['verify_identity'])) {
        $username = trim($_POST['username']);
        $question = $_POST['question']; // 'q1', 'q2', or 'q3'
        $answer   = strtolower(trim($_POST['answer']));

        if (empty($username) || empty($question) || empty($answer)) {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
            exit;
        }

        $validQuestions = ['q1', 'q2', 'q3'];
        if (!in_array($question, $validQuestions)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid question']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT ua.id, o.full_name, 
                   ua.sec_a1, ua.sec_a2, ua.sec_a3
            FROM user_roles_official_accounts ua
            JOIN officials o ON ua.official_id = o.id
            WHERE ua.username = ? AND o.archived = 0
            LIMIT 1
        ");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            $storedAnswer = $user['sec_a' . substr($question, 1)]; // sec_a1, sec_a2, sec_a3

            if ($storedAnswer === $answer) {
                $_SESSION['reset_user_id'] = $user['id'];
                echo json_encode([
                    'status' => 'success',
                    'full_name' => $user['full_name']
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Incorrect answer. Please try again.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username not found']);
        }
        $stmt->close();
        exit;
    }

    // Step 2: Reset Password
    if (isset($_POST['reset_password'])) {
        $newPass = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        $userId  = $_SESSION['reset_user_id'] ?? null;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Session expired. Please try again.']);
            exit;
        }
        if ($newPass !== $confirm) {
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
            exit;
        }
        if (strlen($newPass) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters']);
            exit;
        }

        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user_roles_official_accounts SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $hash, $userId);

        if ($stmt->execute()) {
            unset($_SESSION['reset_user_id']);
            echo json_encode(['status' => 'success', 'message' => 'Password changed successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password']);
        }
        $stmt->close();
        exit;
    }
}
closeDBConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Barangay System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Verdana, sans-serif; overflow-x: hidden; }
        .login-container {
            min-height: 100vh; display: flex; justify-content: center; align-items: center;
            background-image: url('assets/image/Logo/IMG_6417.PNG');
            background-size: cover; background-position: center; background-attachment: fixed;
            padding: 20px; position: relative;
        }
        .login-container::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.55); z-index: 0;
        }
        .login-card {
            display: flex; max-width: 1000px; width: 100%;
            background: rgba(255,255,255,0.98); border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden;
            position: relative; z-index: 1; backdrop-filter: blur(12px);
        }
        .logo-side {
            flex: 1; background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white; padding: 60px 40px; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
        }
        .logo img {
            max-width: 220px; filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3));
            animation: logoFloat 3s ease-in-out infinite;
        }
        @keyframes logoFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .welcome-text h2 { font-size: 32px; font-weight: 700; margin-bottom: 15px; }
        .welcome-text p { font-size: 16px; opacity: 0.95; max-width: 350px; margin: 0 auto; line-height: 1.6; }
        .form-side { flex: 1; padding: 60px 50px; background: white; }
        .form-title { text-align: center; margin-bottom: 35px; }
        .form-title h3 { font-size: 32px; font-weight: 700; color: #2d3748; }
        .form-title p { font-size: 15px; color: #718096; }
        .form-control, .form-select {
            border-radius: 12px; padding: 16px 18px; border: 2px solid #e2e8f0;
            background: #f7fafc; font-size: 15px; transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #10b981; box-shadow: 0 0 0 4px rgba(16,185,129,0.1); background: white;
        }
        .btn-primary {
            width: 100%; padding: 16px; border-radius: 12px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none; color: white; font-size: 17px; font-weight: 600;
            box-shadow: 0 4px 15px rgba(16,185,129,0.4); transition: all 0.3s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(16,185,129,0.6); }
        .btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }
        .loading-spinner {
            display: none; width: 18px; height: 18px;
            border: 3px solid rgba(255,255,255,0.3); border-top: 3px solid white;
            border-radius: 50%; animation: spin 0.8s linear infinite; margin-right: 10px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .back-to-login { text-align: center; margin-top: 25px; }
        .back-to-login a { color: #10b981; font-weight: 500; text-decoration: none; }
        .back-to-login a:hover { text-decoration: underline; }
        .fade-in { animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) {
            .login-card { flex-direction: column; max-width: 450px; }
            .logo-side, .form-side { padding: 40px 30px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <div class="logo-side">
                <div class="logo">
                    <img src="assets/image/Logo/Brgy3_logo-removebg-preview.png" alt="Barangay Logo">
                </div>
                <div class="welcome-text">
                    <h2>Forgot Password?</h2>
                    <p>Enter your username and answer one security question to reset your password.</p>
                </div>
            </div>
            <div class="form-side">
                <div class="form-title">
                    <h3>Verify Your Identity</h3>
                    <p id="stepDesc">Enter your username and answer one security question</p>
                </div>

                <!-- Step 1: Username + One Dropdown Question -->
                <div id="step1">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" class="form-control" id="username" placeholder="Enter your username" required autocomplete="username">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Security Question</label>
                        <select class="form-select" id="question">
                            <option value="">Choose a security question...</option>
                            <option value="q1">What is your mother's maiden name?</option>
                            <option value="q2">What was the name of your first pet?</option>
                            <option value="q3">In what city were you born?</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Your Answer</label>
                        <input type="text" class="form-control" id="answer" placeholder="Enter your answer" autocomplete="off" required>
                    </div>

                    <button type="button" class="btn btn-primary" id="verifyBtn">
                        <span class="loading-spinner" id="spinner1"></span>
                        Verify & Continue
                    </button>
                </div>

                <!-- Step 2: New Password -->
                <div id="step2" style="display: none;">
                    <div class="alert alert-success text-center mb-4">
                        Identity verified! Hello <strong id="userName"></strong>
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" id="newPassword" placeholder="New Password" minlength="6" required>
                    </div>
                    <div class="mb-4">
                        <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm New Password" required>
                    </div>
                    <button type="button" class="btn btn-primary" id="resetBtn">
                        <span class="loading-spinner" id="spinner2"></span>
                        Change Password
                    </button>
                </div>

                <div class="back-to-login">
                    <a href="index.php">Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        function showAlert(type, msg) {
            const alert = $(`
                <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                     style="top:20px; right:20px; z-index:9999; min-width:300px;">
                    ${msg}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            $('body').append(alert);
            setTimeout(() => alert.alert('close'), 6000);
        }

        $('#verifyBtn').on('click', function() {
            const username = $('#username').val().trim();
            const question = $('#question').val();
            const answer   = $('#answer').val().trim();

            if (!username) return showAlert('danger', 'Please enter your username');
            if (!question) return showAlert('danger', 'Please select a security question');
            if (!answer)   return showAlert('danger', 'Please enter your answer');

            const btn = $(this);
            const spinner = $('#spinner1');
            spinner.show();
            btn.prop('disabled', true);

            $.post('', {
                verify_identity: true,
                username: username,
                question: question,
                answer: answer
            }, function(r) {
                spinner.hide();
                btn.prop('disabled', false);

                if (r.status === 'success') {
                    $('#userName').text(r.full_name);
                    $('#stepDesc').text('Set your new password');
                    $('#step1').fadeOut(400, () => $('#step2').fadeIn(400));
                } else {
                    showAlert('danger', r.message);
                }
            }, 'json').fail(() => {
                spinner.hide();
                btn.prop('disabled', false);
                showAlert('danger', 'Connection error. Please try again.');
            });
        });

        $('#resetBtn').on('click', function() {
            const p1 = $('#newPassword').val();
            const p2 = $('#confirmPassword').val();

            if (p1.length < 6) return showAlert('danger', 'Password must be at least 6 characters');
            if (p1 !== p2) return showAlert('danger', 'Passwords do not match');

            const btn = $(this);
            const spinner = $('#spinner2');
            spinner.show();
            btn.prop('disabled', true);

            $.post('', {
                reset_password: true,
                new_password: p1,
                confirm_password: p2
            }, function(r) {
                if (r.status === 'success') {
                    showAlert('success', r.message);
                    setTimeout(() => location.href = 'index.php', 2000);
                } else {
                    showAlert('danger', r.message);
                    spinner.hide();
                    btn.prop('disabled', false);
                }
            }, 'json');
        });
    </script>
</body>
</html>