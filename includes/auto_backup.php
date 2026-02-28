<?php
/**
 * Auto Backup Helper
 * Include this file and call triggerAutoBackup() after important data changes
 * 
 * Usage:
 *   require_once 'includes/auto_backup.php';
 *   triggerAutoBackup($db, 'sale created');
 */

function triggerAutoBackup($db, $reason = 'data_change') {
    try {
        // Check if backup_on_change is enabled
        $db->query("SELECT setting_value FROM backup_settings WHERE setting_key = 'backup_on_change'");
        $result = $db->single();
        
        if (!$result || $result['setting_value'] != '1') {
            return false;
        }
        
        // Check last backup time - don't backup more than once per 5 minutes
        $db->query("SELECT setting_value FROM backup_settings WHERE setting_key = 'last_auto_backup'");
        $lastResult = $db->single();
        
        if ($lastResult && !empty($lastResult['setting_value'])) {
            $lastTime = strtotime($lastResult['setting_value']);
            if (time() - $lastTime < 300) { // 5 minute cooldown
                return false;
            }
        }
        
        // Determine backup directory based on current file location
        $backupDir = findBackupDir();
        if (!$backupDir) {
            return false;
        }
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        
        // Create backup
        return createAutoBackup($db, $backupDir, $reason);
        
    } catch (Exception $e) {
        // Silently fail - don't interrupt main operation
        return false;
    }
}

function findBackupDir() {
    // Try common relative paths
    $possiblePaths = [
        __DIR__ . '/../exports/backups/',
        __DIR__ . '/../../exports/backups/',
        dirname($_SERVER['DOCUMENT_ROOT']) . '/exports/backups/',
    ];
    
    // Also check if basePath is defined
    if (defined('SITE_URL')) {
        $possiblePaths[] = $_SERVER['DOCUMENT_ROOT'] . '/system_basir/exports/backups/';
    }
    
    foreach ($possiblePaths as $path) {
        $parent = dirname($path);
        if (is_dir($parent)) {
            if (!is_dir($path)) {
                @mkdir($path, 0777, true);
            }
            return $path;
        }
    }
    
    return false;
}

function createAutoBackup($db, $backupDir, $reason) {
    try {
        $conn = $db->getConnection();
        $dbName = DB_NAME;
        
        $stmt = $conn->query("SHOW TABLES FROM `$dbName`");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) return false;
        
        $sql = "-- Auto-backup: $reason\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        $totalRows = 0;
        
        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $row[1] . ";\n\n";
            
            $stmt = $conn->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $columnList = '`' . implode('`, `', $columns) . '`';
                
                $batches = array_chunk($rows, 100);
                foreach ($batches as $batch) {
                    $sql .= "INSERT INTO `$table` ($columnList) VALUES\n";
                    $values = [];
                    foreach ($batch as $dataRow) {
                        $rowValues = [];
                        foreach ($dataRow as $value) {
                            $rowValues[] = is_null($value) ? 'NULL' : $conn->quote($value);
                        }
                        $values[] = '(' . implode(', ', $rowValues) . ')';
                    }
                    $sql .= implode(",\n", $values) . ";\n";
                }
                $totalRows += count($rows);
            }
        }
        
        $sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '_auto.sql';
        $filePath = $backupDir . $filename;
        file_put_contents($filePath, $sql);
        $fileSize = filesize($filePath);
        
        // Record backup
        $db->query("INSERT INTO db_backups (filename, file_size, tables_count, rows_count, backup_type, notes) 
                     VALUES (:filename, :size, :tables, :rows, 'auto', :notes)");
        $db->bind(':filename', $filename);
        $db->bind(':size', $fileSize);
        $db->bind(':tables', count($tables));
        $db->bind(':rows', $totalRows);
        $db->bind(':notes', 'خۆکار: ' . $reason);
        $db->execute();
        
        // Update last backup time
        $db->query("UPDATE backup_settings SET setting_value = :val WHERE setting_key = 'last_auto_backup'");
        $db->bind(':val', date('Y-m-d H:i:s'));
        $db->execute();
        
        // Cleanup old auto-backups (keep max setting)
        $db->query("SELECT setting_value FROM backup_settings WHERE setting_key = 'max_backups'");
        $maxResult = $db->single();
        $maxBackups = $maxResult ? (int)$maxResult['setting_value'] : 30;
        
        $db->query("SELECT id, filename FROM db_backups ORDER BY created_at DESC LIMIT 999 OFFSET :offset");
        $db->bind(':offset', $maxBackups);
        $oldBackups = $db->resultSet();
        
        foreach ($oldBackups as $old) {
            $oldFile = $backupDir . $old['filename'];
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
            $db->query("DELETE FROM db_backups WHERE id = :id");
            $db->bind(':id', $old['id']);
            $db->execute();
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
