<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'birds';
$pageTitle = 'زیادکردنی هەوێردەی نێر';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_name = $_POST['batch_name'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $entry_date = $_POST['entry_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    
    if (empty($batch_name) || $quantity <= 0) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        $db->query("INSERT INTO male_birds (batch_name, quantity, entry_date, notes, created_at) 
                    VALUES (:batch_name, :quantity, :entry_date, :notes, NOW())");
        $db->bind(':batch_name', $batch_name);
        $db->bind(':quantity', $quantity);
        $db->bind(':entry_date', $entry_date);
        $db->bind(':notes', $notes);
        
        if ($db->execute()) {
            header('Location: male_list.php?success=1');
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
        <h2><i class="fas fa-plus"></i> زیادکردنی هەوێردەی نێر</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="male_list.php">هەوێردەی نێر</a></li>
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
            <div class="card-header bg-info-gradient">
                <i class="fas fa-mars"></i> زانیاری گرووپی هەوێردەی نێر
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
                            <input type="number" name="quantity" class="form-control" min="1" placeholder="ژمارەی هەوێردە" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری هاتن <span class="text-danger">*</span></label>
                            <input type="date" name="entry_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="تێبینی دڵخوازانە..."></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="male_list.php" class="btn btn-secondary">
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
