<?php
  

require_once 'partials/db_conn.php';
require_once 'partials/db_backup.php';

$conn = getDBConnection();

// === BACKUP ===
if (isset($_POST['backup'])) {
    $backup = backupDatabase($conn);
    $filename = "bms_backup_" . date('Y-m-d_His') . ".sql";

    if (ob_get_length()) ob_clean();

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($backup));
    header('Cache-Control: no-cache');
    header('Pragma: no-cache');

    echo $backup;
    closeDBConnection($conn);
    exit();
}

// === RESTORE ===
if (isset($_POST['restore']) && isset($_FILES['restore_file'])) {
    $file = $_FILES['restore_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['restore_message'] = "<div class='alert alert-danger'>Upload error.</div>";
    } elseif ($ext !== 'sql') {
        $_SESSION['restore_message'] = "<div class='alert alert-danger'>Only .sql files allowed.</div>";
    } elseif ($file['size'] > 50 * 1024 * 1024) {
        $_SESSION['restore_message'] = "<div class='alert alert-danger'>File too large (max 50MB).</div>";
    } else {
        $uploadDir = 'backups/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $uploadPath = $uploadDir . 'restore_' . time() . '.sql';

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $result = restoreDatabase($conn, $uploadPath);
            if ($result === true) {
                $_SESSION['restore_message'] = "<div class='alert alert-success'>Database restored successfully!</div>";
                @unlink($uploadPath);
            } else {
                $_SESSION['restore_message'] = "<div class='alert alert-danger'>$result</div>";
                @unlink($uploadPath);
            }
        } else {
            $_SESSION['restore_message'] = "<div class='alert alert-danger'>Failed to save file.</div>";
        }
    }
}

closeDBConnection($conn);
header("Location: ?page=backup_restore");
exit();
?>