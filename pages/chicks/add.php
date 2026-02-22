<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'production';
$pageTitle = 'زیادکردنی جوجکە';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_name = $_POST['batch_name'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $dead_count = intval($_POST['dead_count'] ?? 0);
    $hatch_date = $_POST['hatch_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'active';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($batch_name) || $quantity <= 0) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        $db->query("INSERT INTO chicks (batch_name, quantity, dead_count, hatch_date, status, notes, created_at) 
                    VALUES (:batch_name, :quantity, :dead_count, :hatch_date, :status, :notes, NOW())");
        $db->bind(':batch_name', $batch_name);
        $db->bind(':quantity', $quantity);
        $db->bind(':dead_count', $dead_count);
        $db->bind(':hatch_date', $hatch_date);
        $db->bind(':status', $status);
        $db->bind(':notes', $notes);
        
        if ($db->execute()) {
            header('Location: list.php?success=1');
            exit;
        } else {
            $message = 'هەڵەیەک ڕوویدا';
            $messageType = 'danger';
        }
    }
}

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-plus"></i> زیادکردنی جوجکە</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">جوجکە</a></li>
                <li class="breadcrumb-item active">زیادکردن</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Form -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-success-gradient">
                <i class="fas fa-kiwi-bird"></i> زانیاری گرووپی جوجکە
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ناوی گرووپ <span class="text-danger">*</span></label>
                            <input type="text" name="batch_name" class="form-control" placeholder="بۆ نموونە: گرووپ ١" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">ژمارە <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" placeholder="ژمارەی جوجکە" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">ژمارەی مردوو</label>
                            <input type="number" name="dead_count" class="form-control" min="0" value="0" placeholder="ژمارەی مردوو">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری دەرچوون <span class="text-danger">*</span></label>
                            <input type="date" name="hatch_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بار</label>
                            <select name="status" class="form-select">
                                <option value="active">چالاک</option>
                                <option value="sold">فرۆشراو</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="تێبینی دڵخوازانە..."></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> گەڕانەوە
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> تۆمارکردن
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
