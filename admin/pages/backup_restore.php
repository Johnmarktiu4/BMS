<?php
// admin/pages/backup_restore.php
// Only UI — NO backup logic here
 
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Backup & Restore Database</h2>
        <p class="text-muted">Securely manage your BMS database.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Backup -->
    <div class="col-lg-6">
        <div class="card dashboard-card h-100">
            <div class="card-header bg-success text-white">
                Backup Database
            </div>
            <div class="card-body">
                <p>Download full database backup.</p>
                <form method="post" action="backup_handler.php">
                    <button type="submit" name="backup" class="btn btn-success btn-lg w-100">
                        Download Backup (.sql)
                    </button>
                </form>
            </div>
            <div class="card-footer text-muted small">
                Includes all tables and data.
            </div>
        </div>
    </div>

    <!-- Restore -->
    <div class="col-lg-6">
        <div class="card dashboard-card h-100">
            <div class="card-header bg-warning text-dark">
                Restore Database
            </div>
            <div class="card-body">
                <p>Upload .sql file to restore.</p>          
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                        <div class="mb-3">
                            <label class="form-label">Backup File</label>
                            <input type="file" class="form-control" name="restore_file" accept=".sql" required>
                            <div class="form-text">Max 50MB</div>
                        </div>
                        <button type="submit" name="restore" class="btn btn-warning btn-lg w-100 text-dark"
                                onclick="return confirm('This will overwrite all data. Continue?');">
                            Restore Now
                        </button>
                    </form>
                <?php
                if (isset($_SESSION['restore_message'])) {
                    echo "<div class='mt-3'>" . $_SESSION['restore_message'] . "</div>";
                    unset($_SESSION['restore_message']);
                }
                ?>
            </div>
            <div class="card-footer text-danger small">
                <strong>Warning:</strong> Irreversible action.
            </div>
        </div>
    </div>
</div>

<!-- Recent Backups -->
<div class="row mt-5">
    <div class="col-12">
        <div class="card dashboard-card">
            <div class="card-header bg-info text-white">
                Recent Backups
            </div>
            <div class="card-body">
                <?php
                $backupDir = 'backups/';
                if (is_dir($backupDir)) {
                    $files = array_diff(scandir($backupDir), ['.', '..']);
                    $sqlFiles = array_filter($files, fn($f) => pathinfo($f, PATHINFO_EXTENSION) === 'sql');
                    if (empty($sqlFiles)) {
                        echo "<p class='text-muted'>No backups yet.</p>";
                    } else {
                        echo "<div class='list-group'>";
                        foreach (array_reverse($sqlFiles) as $file) {
                            $path = $backupDir . $file;
                            $size = formatBytes(filesize($path));
                            $date = date('M j, Y g:i A', filemtime($path));
                            echo "<a href='$path' download class='list-group-item list-group-item-action d-flex justify-content-between'>
                                    <div>$file<br><small class='text-muted'>$date • $size</small></div>
                                    <i class='fas fa-download text-success'></i>
                                  </a>";
                        }
                        echo "</div>";
                    }
                } else {
                    echo "<p class='text-muted'>Backup folder will be created automatically.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>



<?php
if (isset($_POST['restore'])) {
    // --- CSRF check (optional) ---
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $_SESSION['restore_message'] = "<div class='alert alert-danger'>Invalid request token.</div>";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // --- Validate upload presence ---
    if (!isset($_FILES['restore_file']) || $_FILES['restore_file']['error'] !== UPLOAD_ERR_OK) {
        $err = $_FILES['restore_file']['error'] ?? 'Unknown';
        $_SESSION['restore_message'] = "<div class='alert alert-danger'>File upload failed (code: $err).</div>";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    $file = $_FILES['restore_file'];

    // --- Validate size: 50MB max ---
    $maxBytes = 50 * 1024 * 1024; // 50 MB
    if ($file['size'] > $maxBytes) {
        $_SESSION['restore_message'] = "<div class='alert alert-danger'>File exceeds 50MB limit.</div>";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // --- Validate extension & MIME (best-effort) ---
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'sql') {
        $_SESSION['restore_message'] = "<div class='alert alert-danger'>Only .sql files are allowed.</div>";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // You can also check MIME, but many servers report text/plain or application/octet-stream.
    // $finfo = finfo_open(FILEINFO_MIME_TYPE);
    // $mime = finfo_file($finfo, $file['tmp_name']);
    // finfo_close($finfo);

    // --- Move file to a temp location ---
    $tmpDir = sys_get_temp_dir();
    $destPath = $tmpDir . DIRECTORY_SEPARATOR . uniqid('restore_', true) . '.sql';

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        $_SESSION['restore_message'] = "<div class='alert alert-danger'>Failed to move uploaded file.</div>";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // --- Connect to DB ---
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) {
        @unlink($destPath);
        $_SESSION['restore_message'] = "<div class='alert alert-danger'>DB connection failed: " . htmlspecialchars($mysqli->connect_error) . "</div>";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Increase time limit for long imports (adjust as needed)
    @set_time_limit(300); // 5 minutes

    // --- Perform restore ---
    $ok = restore_sql_file($mysqli, $destPath, $db_name, $errorMsg);

    // Cleanup temp file
    @unlink($destPath);

    if ($ok) {
        $_SESSION['restore_message'] = "<div class='alert alert-success'>Database restored successfully.</div>";
    } else {
        $_SESSION['restore_message'] = "<div class='alert alert-danger'>Restore failed: " . htmlspecialchars($errorMsg) . "</div>";
    }

    // Redirect to avoid re-POST on refresh
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

/**
 * Restores a MySQL database from a .sql file using mysqli::multi_query.
 * Handles DELIMITER sections, transactions, and foreign key checks.
 *
 * @param mysqli $mysqli
 * @param string $filePath
 * @param string $dbName
 * @param string &$errorMsg
 * @return bool
 */
function restore_sql_file(mysqli $mysqli, string $filePath, string $dbName, string &$errorMsg): bool
{
    $errorMsg = '';

    // Read entire file
    $sql = @file_get_contents($filePath);
    if ($sql === false) {
        $errorMsg = "Cannot read SQL file.";
        return false;
    }

    // Ensure we use the target DB
    // (Useful if the dump lacks explicit `USE db;`)
    if (!$mysqli->select_db($dbName)) {
        $errorMsg = "Cannot select database: " . $mysqli->error;
        return false;
    }

    // Best-effort handling of DELIMITER sections:
    // mysqli doesn't understand custom delimiters; we need to normalize them back to ';'
    // This simplistic approach splits DELIMITER blocks and restores content.
    $normalizedSql = normalize_delimiters($sql);

    // Wrap in a transaction; disable FK checks to avoid ordering issues on insert
    $mysqli->query("SET FOREIGN_KEY_CHECKS=0");
    $mysqli->begin_transaction();

    try {
        if (!$mysqli->multi_query($normalizedSql)) {
            throw new Exception("Initial query failed: " . $mysqli->error);
        }
        // flush all results
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());

        if ($mysqli->errno) {
            throw new Exception("Query error: " . $mysqli->error);
        }

        $mysqli->commit();
        $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
        return true;
    } catch (Throwable $e) {
        $mysqli->rollback();
        $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
        $errorMsg = $e->getMessage();
        return false;
    }
}

/**
 * Attempt to normalize DELIMITER usage to ';' so mysqli::multi_query can process:
 * - Detects lines like "DELIMITER $$" and "DELIMITER ;"
 * - Captures blocks until the custom delimiter appears on a line end
 * - Replaces block terminators with ';'
 *
 * Note: This is a best-effort parser. If your dump has exotic constructs, prefer CLI restore.
 */
function normalize_delimiters(string $sql): string
{
    $lines = preg_split("/\\R/", $sql);
    $out = [];
    $delimiter = ';';
    $buffer = '';

    foreach ($lines as $rawLine) {
        $line = rtrim($rawLine, "\r\n");

        // Check for DELIMITER directive
        if (preg_match('/^\\s*DELIMITER\\s+(.+)\\s*$/i', $line, $m)) {
            // flush current buffer
            if (trim($buffer) !== '') {
                $out[] = $buffer;
                $buffer = '';
            }
            $delimiter = $m[1];
            continue;
        }

        if ($delimiter === ';') {
            // Normal mode
            $buffer .= $line . "\n";
            // Split on ';' that terminate statements
            while (false !== ($pos = strpos($buffer, ';'))) {
                $stmt = substr($buffer, 0, $pos + 1);
                $out[] = $stmt;
                $buffer = substr($buffer, $pos + 1);
            }
        } else {
            // Custom delimiter mode: keep appending until line ends with delimiter
            $buffer .= $line . "\n";
            // If buffer ends with the custom delimiter at end of a line
            $delimPattern = preg_quote($delimiter, '/');
            if (preg_match("/" . $delimPattern . "\\s*\\n$/", $buffer)) {
                // Replace trailing custom delimiter with ';'
                $stmt = preg_replace("/" . $delimPattern . "\\s*\\n$/", ";\n", $buffer);
                $out[] = $stmt;
                $buffer = '';
            }
        }
    }

    // Append leftover
    if (trim($buffer) !== '') {
        // Ensure terminated
        $out[] = rtrim($buffer) . (substr(rtrim($buffer), -1) === ';' ? "" : ";\n");
    }

    // Join statements
    return implode("\n", $out);
}
?>
