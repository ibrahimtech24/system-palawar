<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'warehouse';
$pageTitle = 'زیادکردنی کاڵا';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = $_POST['item_name'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit = $_POST['unit'] ?? '';
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $min_quantity = intval($_POST['min_quantity'] ?? 0);
    $notes = $_POST['notes'] ?? '';
    
    if (empty($item_name) || $quantity < 0 || empty($unit)) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        $db->query("INSERT INTO warehouse (item_name, quantity, unit, unit_price, min_quantity, notes, created_at) 
                    VALUES (:item_name, :quantity, :unit, :unit_price, :min_quantity, :notes, NOW())");
        $db->bind(':item_name', $item_name);
        $db->bind(':quantity', $quantity);
        $db->bind(':unit', $unit);
        $db->bind(':unit_price', $unit_price);
        $db->bind(':min_quantity', $min_quantity);
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
        <h2><i class="fas fa-plus"></i> زیادکردنی کاڵا</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">مەخزەن</a></li>
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
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-box"></i> زانیاری کاڵا
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ناوی کاڵا <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">یەکە <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control" placeholder="کیلۆ، دانە، بەستە..." required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">ژمارە <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="0" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">نرخی یەکە (<?php echo CURRENCY; ?>)</label>
                            <input type="number" name="unit_price" class="form-control" min="0" step="0.01">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">کەمترین ژمارە</label>
                            <input type="number" name="min_quantity" class="form-control" min="0" value="0">
                            <small class="text-muted">ئاگاداری دەدرێت کاتێک ژمارە کەم دەبێت</small>
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
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> تۆمارکردن
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
