<?php
// admin/partials/db_backup.php
// Backup & Restore Functions — NO direct access guard needed

// =============================
// BACKUP DATABASE
// =============================
function backupDatabase($conn) {
    $tables = [];
    $result = mysqli_query($conn, "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    if (!$result) return "-- ERROR: No tables found.\n";

    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }

    $sql = "-- Barangay Management System - Database Backup\n";
    $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Host: " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\n\n";
    $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql .= "START TRANSACTION;\n";
    $sql .= "SET time_zone = \"+00:00\";\n\n";

    foreach ($tables as $table) {
        $create = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
        $sql .= "--\n-- Table structure for table `$table`\n--\n";
        $sql .= $create[1] . ";\n\n";

        $result = mysqli_query($conn, "SELECT * FROM `$table`");
        if ($result && mysqli_num_rows($result) > 0) {
            $num_fields = mysqli_num_fields($result);
            $sql .= "--\n-- Dumping data for table `$table`\n--\n";
            while ($row = mysqli_fetch_row($result)) {
                $sql .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $num_fields; $j++) {
                    if ($row[$j] === null) {
                        $sql .= 'NULL';
                    } else {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = str_replace("\n", "\\n", $row[$j]);
                        $sql .= '"' . $row[$j] . '"';
                    }
                    if ($j < $num_fields - 1) $sql .= ',';
                }
                $sql .= ");\n";
            }
        }
        $sql .= "\n";
    }
    $sql .= "COMMIT;\n";
    return $sql;
}

// =============================
// RESTORE DATABASE
// =============================
function restoreDatabase($conn, $file_path) {
    $sql = file_get_contents($file_path);
    if ($sql === false) return "Failed to read file.";

    $sql = str_replace("\r", "", $sql);
    $statements = array_filter(array_map('trim', explode(";", $sql)));

    mysqli_autocommit($conn, false);
    try {
        foreach ($statements as $stmt) {
            if (!empty($stmt) && !str_starts_with($stmt, '--') && !str_starts_with($stmt, '/*')) {
                if (!mysqli_query($conn, $stmt)) {
                    throw new Exception(mysqli_error($conn));
                }
            }
        }
        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return "Restore failed: " . $e->getMessage();
    } finally {
        mysqli_autocommit($conn, true);
    }
}

// =============================
// FORMAT BYTES
// =============================
function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size > 1024 && $i < count($units)-1; $i++) $size /= 1024;
    return round($size, $precision) . ' ' . $units[$i];
}
?>