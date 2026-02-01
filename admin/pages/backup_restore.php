<?php
// admin/pages/backup_restore.php
// Option B: Show restore message immediately (no redirect/reload)
declare(strict_types=1);

// --- Bootstrap (must be before any HTML) ---
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// --- DB config: adjust to your environment ---
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bms';

// CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Will hold the message to display immediately after processing
$inline_message = '';

// -------------------- RESTORE HANDLER (runs before HTML) --------------------
if (isset($_POST['restore'])) {
    $errorMsg = '';

    // CSRF
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $inline_message = "<div class='alert alert-danger'>Invalid request token.</div>";
    } elseif (!isset($_FILES['restore_file']) || ($_FILES['restore_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $err = $_FILES['restore_file']['error'] ?? 'Unknown';
        $inline_message = "<div class='alert alert-danger'>File upload failed (code: {$err}).</div>";
    } else {
        $file = $_FILES['restore_file'];

        // Size: 50MB max
        $maxBytes = 50 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
            $inline_message = "<div class='alert alert-danger'>File exceeds 50MB limit.</div>";
        } else {
            // Extension
            $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
            if ($ext !== 'sql') {
                $inline_message = "<div class='alert alert-danger'>Only .sql files are allowed.</div>";
            } else {
                // Move to temp
                $tmpDest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('restore_', true) . '.sql';
                if (!move_uploaded_file($file['tmp_name'], $tmpDest)) {
                    $inline_message = "<div class='alert alert-danger'>Failed to move uploaded file.</div>";
                } else {
                    // Connect DB
                    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
                    if ($mysqli->connect_error) {
                        @unlink($tmpDest);
                        $inline_message = "<div class='alert alert-danger'>DB connection failed: " . htmlspecialchars($mysqli->connect_error) . "</div>";
                    } else {
                        @set_time_limit(300); // long import allowed

                        // Optional: drop all objects first
                        $dropAll = isset($_POST['drop_all']) && $_POST['drop_all'] === '1';
                        if ($dropAll) {
                            $dropErr = '';
                            if (!drop_all_tables_and_views($mysqli, $db_name, $dropErr)) {
                                @unlink($tmpDest);
                                $inline_message = "<div class='alert alert-danger'>Failed to drop all tables: " . htmlspecialchars($dropErr) . "</div>";
                            }
                        }

                        // If no prior error, restore
                        if ($inline_message === '') {
                            $ok = restore_sql_file($mysqli, $tmpDest, $db_name, $errorMsg);
                            @unlink($tmpDest);

                            if ($ok) {
                                $inline_message = "<div class='alert alert-success'>Database restored successfully.</div>";
                                // Avoid “resubmit form” prompt on refresh (no reload needed)
                                // We'll print a small script after the message down in HTML
                            } else {
                                $inline_message = "<div class='alert alert-danger'>Restore failed: " . htmlspecialchars($errorMsg) . "</div>";
                            }
                        }
                    }
                }
            }
        }
    }
}

?>
<!-- -------------------- PAGE HTML -------------------- -->
<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Backup &amp; Restore Database</h2>
        <p class="text-muted">Securely manage your BMS database.</p>

        <?php if (!empty($inline_message)): ?>
            <div class="mt-2"><?= $inline_message ?></div>
            <!-- Replace the current history state to prevent resubmit on refresh -->
            <script>
                if (history.replaceState) {
                    history.replaceState(null, '', location.pathname + location.search);
                }
            </script>
        <?php endif; ?>
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
                <form id="restore-form" method="post" enctype="multipart/form-data" action="">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                    <div class="mb-3">
                        <label class="form-label">Backup File</label>
                        <input type="file" class="form-control" name="restore_file" accept=".sql" required>
                        <div class="form-text">Max 50MB</div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="drop_all" name="drop_all" value="1">
                        <label class="form-check-label" for="drop_all">
                            Drop all tables before restore (clean slate)
                        </label>
                    </div>

                    <button type="submit" name="restore" class="btn btn-warning btn-lg w-100 text-dark"
                            onclick="return confirm('This will ' + (document.getElementById('drop_all').checked ? 'DROP ALL TABLES and ' : '') + 'overwrite data. Continue?');">
                        Restore Now
                    </button>
                </form>
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
                // Helper available here for human-readable sizes
                if (!function_exists('formatBytes')) {
                    function formatBytes(int $bytes, int $precision = 2): string {
                        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                        $bytes = max($bytes, 0);
                        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
                        $pow = min($pow, count($units) - 1);
                        $bytes /= (1 << (10 * $pow));
                        return round($bytes, $precision) . ' ' . $units[$pow];
                    }
                }

                $backupDir = 'backups/'; // relative to this page's URL
                if (is_dir($backupDir)) {
                    $files = array_values(array_filter(
                        array_diff(scandir($backupDir), ['.', '..']),
                        static function ($f) use ($backupDir) {
                            return is_file($backupDir . $f) && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'sql';
                        }
                    ));

                    if (empty($files)) {
                        echo "<p class='text-muted'>No backups yet.</p>";
                    } else {
                        // Newest first
                        usort($files, static function ($a, $b) use ($backupDir) {
                            return filemtime($backupDir . $b) <=> filemtime($backupDir . $a);
                        });

                        echo "<div class='list-group'>";
                        foreach ($files as $file) {
                            $path = $backupDir . $file;
                            $size = formatBytes((int)filesize($path));
                            $date = date('M j, Y g:i A', (int)filemtime($path));
                            $safeFile = htmlspecialchars($file);
                            $href = $backupDir . rawurlencode($file);

                            echo "<a href='{$href}' download class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>
                                    <div>{$safeFile}<br><small class='text-muted'>{$date} • {$size}</small></div>
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
// -------------------- HELPERS --------------------

/**
 * Drop all views first, then all base tables, with FK checks toggled.
 */
function drop_all_tables_and_views(mysqli $mysqli, string $dbName, string &$errorMsg): bool
{
    $errorMsg = '';

    if (!$mysqli->select_db($dbName)) {
        $errorMsg = "Cannot select database: " . $mysqli->error;
        return false;
    }

    $escIdent = static function (string $ident): string {
        return '`' . str_replace('`', '``', $ident) . '`';
    };
    $dbEsc = $escIdent($dbName);

    if (!$mysqli->query("SET FOREIGN_KEY_CHECKS=0")) {
        $errorMsg = "Cannot disable FOREIGN_KEY_CHECKS: " . $mysqli->error;
        return false;
    }

    // Drop views first
    $views = [];
    if ($res = $mysqli->query("SHOW FULL TABLES FROM {$dbEsc} WHERE Table_type='VIEW'")) {
        while ($row = $res->fetch_array(MYSQLI_NUM)) {
            $views[] = (string)$row[0];
        }
        $res->free();
    } else {
        $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
        $errorMsg = "Cannot list views: " . $mysqli->error;
        return false;
    }
    foreach ($views as $v) {
        if (!$mysqli->query("DROP VIEW IF EXISTS " . $escIdent($v))) {
            $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
            $errorMsg = "Error dropping view {$v}: " . $mysqli->error;
            return false;
        }
    }

    // Then drop tables
    $tables = [];
    if ($res2 = $mysqli->query("SHOW FULL TABLES FROM {$dbEsc} WHERE Table_type='BASE TABLE'")) {
        while ($row = $res2->fetch_array(MYSQLI_NUM)) {
            $tables[] = (string)$row[0];
        }
        $res2->free();
    } else {
        $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
        $errorMsg = "Cannot list tables: " . $mysqli->error;
        return false;
    }
    foreach ($tables as $t) {
        if (!$mysqli->query("DROP TABLE IF EXISTS " . $escIdent($t))) {
            $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
            $errorMsg = "Error dropping table {$t}: " . $mysqli->error;
            return false;
        }
    }

    if (!$mysqli->query("SET FOREIGN_KEY_CHECKS=1")) {
        $errorMsg = "Cannot re-enable FOREIGN_KEY_CHECKS: " . $mysqli->error;
        return false;
    }

    return true;
}

/**
 * Restore SQL file using mysqli::multi_query() with transaction and FK handling.
 */
function restore_sql_file(mysqli $mysqli, string $filePath, string $dbName, string &$errorMsg): bool
{
    $errorMsg = '';

    $sql = @file_get_contents($filePath);
    if ($sql === false) {
        $errorMsg = "Cannot read SQL file.";
        return false;
    }

    if (!$mysqli->select_db($dbName)) {
        $errorMsg = "Cannot select database: " . $mysqli->error;
        return false;
    }

    $normalizedSql = normalize_delimiters($sql);

    $mysqli->query("SET FOREIGN_KEY_CHECKS=0");
    $mysqli->begin_transaction();

    try {
        if (!$mysqli->multi_query($normalizedSql)) {
            throw new Exception("Initial query failed: " . $mysqli->error);
        }
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
 * Normalize DELIMITER blocks so every statement ends with ';' for multi_query().
 * Handles common mysqldump patterns (procedures/triggers).
 */
function normalize_delimiters(string $sql): string
{
    $lines = preg_split("/\\R/", $sql);
    $out = [];
    $delimiter = ';';
    $buffer = '';

    foreach ($lines as $rawLine) {
        $line = rtrim($rawLine, "\r\n");

        // DELIMITER $$  /  DELIMITER ;
        if (preg_match('/^\\s*DELIMITER\\s+(.+)\\s*$/i', $line, $m)) {
            if (trim($buffer) !== '') {
                $out[] = $buffer;
                $buffer = '';
            }
            $delimiter = $m[1];
            continue;
        }

        if ($delimiter === ';') {
            $buffer .= $line . "\n";
            while (false !== ($pos = strpos($buffer, ';'))) {
                $stmt = substr($buffer, 0, $pos + 1);
                $out[] = $stmt;
                $buffer = substr($buffer, $pos + 1);
            }
        } else {
            $buffer .= $line . "\n";
            $delimPattern = preg_quote($delimiter, '/');
            if (preg_match("/" . $delimPattern . "\\s*\\n$/", $buffer)) {
                $stmt = preg_replace("/" . $delimPattern . "\\s*\\n$/", ";\n", $buffer);
                $out[] = $stmt;
                $buffer = '';
            }
        }
    }

   return implode("\n", $out);
}