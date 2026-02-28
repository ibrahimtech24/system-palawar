<?php
/**
 * Database Backup System
 * Works based on actual files in the backup folder (not just DB records)
 */
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'reports';
$pageTitle = 'باکئەپی داتابەیس';

// Direct PDO connection
$pdo = $db->getConnection();

$message = '';
$messageType = '';

// Check for session flash messages (from redirect after delete)
if (isset($_SESSION['backup_msg'])) {
    $message = $_SESSION['backup_msg'];
    $messageType = $_SESSION['backup_msg_type'] ?? 'success';
    unset($_SESSION['backup_msg'], $_SESSION['backup_msg_type']);
}

// ===== ACTION: Create Backup (direct download, no server save) =====
if (isset($_POST['create_backup'])) {
    try {
        $dbName = DB_NAME;
        
        $tablesStmt = $pdo->query("SHOW TABLES FROM `$dbName`");
        $allTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $sql = "-- Database Backup: $dbName\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        $totalRows = 0;
        $tableCount = 0;
        
        foreach ($allTables as $table) {
            $tableCount++;
            
            $showStmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $createRow = $showStmt->fetch(PDO::FETCH_NUM);
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $createRow[1] . ";\n\n";
            
            $dataStmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $cols = array_keys($rows[0]);
                $colList = '`' . implode('`, `', $cols) . '`';
                
                foreach (array_chunk($rows, 100) as $chunk) {
                    $sql .= "INSERT INTO `$table` ($colList) VALUES\n";
                    $vals = array();
                    foreach ($chunk as $r) {
                        $rv = array();
                        foreach ($r as $v) {
                            $rv[] = is_null($v) ? 'NULL' : $pdo->quote($v);
                        }
                        $vals[] = '(' . implode(', ', $rv) . ')';
                    }
                    $sql .= implode(",\n", $vals) . ";\n";
                }
                $totalRows += count($rows);
            }
        }
        
        $sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
        
        // Direct download - no file saved on server
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
        
    } catch (Exception $e) {
        $message = 'هەڵە: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// ===== ACTION: Clear All Data =====
if (isset($_POST['clear_all_data']) && isset($_POST['confirm_clear']) && $_POST['confirm_clear'] === 'سڕینەوە') {
    try {
        $dbName = DB_NAME;
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $tablesStmt = $pdo->query("SHOW TABLES FROM `$dbName`");
        $allTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // System tables that should not be cleared
        $skipTables = ['db_backups', 'backup_settings', 'monthly_reports', 'monthly_report_backups'];
        
        $cleared = 0;
        foreach ($allTables as $table) {
            if (in_array($table, $skipTables)) continue;
            $pdo->exec("TRUNCATE TABLE `$table`");
            $cleared++;
        }
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        $message = "هەموو داتاکان سڕانەوە! ($cleared خشتە بەتاڵ کرانەوە)";
        $messageType = 'success';
    } catch (Exception $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        $message = 'هەڵە: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// ===== ACTION: Upload & Restore =====
if (isset($_POST['upload_backup']) && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file'];
    
    // Handle upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'فایلەکە زۆر گەورەیە! ئەندازەی فایل لە سنوورەکە زیاترە.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = 'فایلەکە بە تەواوی ئەپلۆد نەکرا!';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = 'هیچ فایلێک هەڵنەبژێردرا!';
                break;
            default:
                $message = 'هەڵە لە ئەپلۆدکردنی فایل! (کۆدی هەڵە: ' . $file['error'] . ')';
        }
        $messageType = 'danger';
    } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'sql') {
        $message = 'تەنها فایلی .sql ڕێگەپێدراوە';
        $messageType = 'danger';
    } else {
        // Increase PHP limits for large restores
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');
        
        $content = file_get_contents($file['tmp_name']);
        if (!empty(trim($content))) {
            $errorCount = 0;
            $errorMessages = [];
            $successCount = 0;
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // ---- Robust SQL parser: character-by-character ----
            // Properly handles quoted strings, comments, and semicolons
            $statements = [];
            $current = '';
            $inString = false;
            $stringChar = '';
            $escaped = false;
            $len = strlen($content);
            
            for ($i = 0; $i < $len; $i++) {
                $char = $content[$i];
                
                // Handle backslash escaping inside strings
                if ($escaped) {
                    $current .= $char;
                    $escaped = false;
                    continue;
                }
                
                if ($inString && $char === '\\') {
                    $current .= $char;
                    $escaped = true;
                    continue;
                }
                
                // Inside a quoted string
                if ($inString) {
                    $current .= $char;
                    if ($char === $stringChar) {
                        // Check for escaped quote by doubling (e.g., '' or "")
                        if ($i + 1 < $len && $content[$i + 1] === $stringChar) {
                            $current .= $content[++$i];
                        } else {
                            $inString = false;
                        }
                    }
                    continue;
                }
                
                // Start of quoted string or backtick identifier
                if ($char === '\'' || $char === '"' || $char === '`') {
                    $inString = true;
                    $stringChar = $char;
                    $current .= $char;
                    continue;
                }
                
                // Single-line comment: -- or #
                if (($char === '-' && $i + 1 < $len && $content[$i + 1] === '-') ||
                    $char === '#') {
                    // Skip to end of line
                    while ($i < $len && $content[$i] !== "\n") {
                        $i++;
                    }
                    // Add newline to maintain formatting
                    $current .= "\n";
                    continue;
                }
                
                // Multi-line comment: /* ... */
                if ($char === '/' && $i + 1 < $len && $content[$i + 1] === '*') {
                    $i += 2;
                    while ($i < $len - 1 && !($content[$i] === '*' && $content[$i + 1] === '/')) {
                        $i++;
                    }
                    $i++; // skip the '/'
                    continue;
                }
                
                // Statement terminator
                if ($char === ';') {
                    $stmt = trim($current);
                    if (!empty($stmt)) {
                        $statements[] = $stmt;
                    }
                    $current = '';
                    continue;
                }
                
                $current .= $char;
            }
            
            // Don't forget the last statement if it doesn't end with ;
            $lastStmt = trim($current);
            if (!empty($lastStmt)) {
                $statements[] = $lastStmt;
            }
            
            // Execute each statement
            foreach ($statements as $stmt) {
                // Skip SET FOREIGN_KEY_CHECKS since we handle it manually
                $upper = strtoupper(trim($stmt));
                if (strpos($upper, 'SET FOREIGN_KEY_CHECKS') === 0) {
                    continue;
                }
                
                try {
                    $pdo->exec($stmt);
                    $successCount++;
                } catch (PDOException $ex) {
                    $errorCount++;
                    if ($errorCount <= 5) {
                        $errorMessages[] = mb_substr($ex->getMessage(), 0, 150);
                    }
                }
            }
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            if ($errorCount > 0) {
                $message = 'باکئەپ گەڕایەوە بە ' . $successCount . ' فەرمان، بەڵام ' . $errorCount . ' هەڵە ڕوویدا';
                if (!empty($errorMessages)) {
                    $message .= '<br><small class="text-muted">' . implode('<br>', $errorMessages) . '</small>';
                }
                $messageType = 'warning';
            } else {
                $message = 'باکئەپ بە سەرکەوتوویی گەڕایەوە! (' . $file['name'] . ' - ' . $successCount . ' فەرمان جێبەجێکرا)';
                $messageType = 'success';
            }
        } else {
            $message = 'فایلەکە بەتاڵە';
            $messageType = 'danger';
        }
    }
}

require_once $basePath . 'includes/header.php';
?>

<div class="page-header no-print">
    <div>
        <h2><i class="fas fa-database"></i> باکئەپی داتابەیس</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">باکئەپی داتابەیس</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Create Backup Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-download fa-4x" style="color: #667eea;"></i>
                </div>
                <h4 class="mb-3">داگرتنی باکئەپ</h4>
                <p class="text-muted mb-4">باکئەپێکی تەواو لە داتابەیس دروست بکە و لە کۆمپیوتەرەکەت خەزنی بکە</p>
                <form method="POST">
                    <button type="submit" name="create_backup" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-download me-2"></i> داگرتنی باکئەپ
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Restore Backup Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-upload fa-4x" style="color: #11998e;"></i>
                </div>
                <h4 class="mb-3">گەڕاندنەوەی باکئەپ</h4>
                <p class="text-muted mb-4">فایلی باکئەپی .sql هەڵبژێرە بۆ گەڕاندنەوەی داتابەیس</p>
                <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('دڵنیایت لە گەڕاندنەوەی باکئەپ؟ داتای ئێستا دەگۆڕدرێت!');">
                    <div class="mb-3">
                        <input type="file" name="sql_file" class="form-control" accept=".sql" required>
                        <small class="text-muted">تەنها فایلی .sql</small>
                    </div>
                    <button type="submit" name="upload_backup" class="btn btn-success btn-lg px-4">
                        <i class="fas fa-undo me-2"></i> گەڕاندنەوەی باکئەپ
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Clear All Data Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-danger">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-trash-alt fa-4x text-danger"></i>
                </div>
                <h4 class="mb-3 text-danger">سڕینەوەی هەموو داتاکان</h4>
                <p class="text-muted mb-4">هەموو داتاکان دەسڕێتەوە (فرۆشتن، کڕین، مامەڵە، کوشک، هاوشێوە...)</p>
                <button type="button" class="btn btn-outline-danger btn-lg px-4" data-bs-toggle="modal" data-bs-target="#clearDataModal">
                    <i class="fas fa-exclamation-triangle me-2"></i> سڕینەوەی داتاکان
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Clear Data Confirmation Modal -->
<div class="modal fade" id="clearDataModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> ئاگاداری!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    <h5>دڵنیایت لە سڕینەوەی هەموو داتاکان؟</h5>
                    <p class="text-muted">ئەم کارە ناگەڕێتەوە! سەرەتا باکئەپ بگرە.</p>
                    <p class="text-muted small">ئەم خشتانە بەتاڵ دەکرێنەوە: فرۆشتن، کڕین، مامەڵەکان، هێلکە، کوشک، مریشکە نێر/مێ، کڕیار، قەرز، کۆگا، ئامێر</p>
                    <div class="mt-3">
                        <label class="form-label fw-bold">بۆ دڵنیابوون، بنووسە: <span class="text-danger">سڕینەوە</span></label>
                        <input type="text" name="confirm_clear" class="form-control text-center" placeholder="سڕینەوە" autocomplete="off" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                    <button type="submit" name="clear_all_data" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> سڕینەوەی هەموو داتاکان
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
