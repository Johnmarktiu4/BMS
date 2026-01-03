<?php
// admin/pages/backup_restore.php
// Only UI — NO backup logic here
 
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