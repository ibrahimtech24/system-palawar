<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'warehouse';
$pageTitle = 'دەستکاریکردنی کاڵا';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: list.php');
    exit;
}

// Get item
$db->query("SELECT * FROM warehouse WHERE id = :id");
$db->bind(':id', $id);
$item = $db->single();

if (!$item) {
    header('Location: list.php');
    exit;
}

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
        $db->query("UPDATE warehouse SET item_name = :item_name, quantity = :quantity, unit = :unit, 
                    unit_price = :unit_price, min_quantity = :min_quantity, notes = :notes 
                    WHERE id = :id");
        $db->bind(':item_name', $item_name);
        $db->bind(':quantity', $quantity);
        $db->bind(':unit', $unit);
        $db->bind(':unit_price', $unit_price);
        $db->bind(':min_quantity', $min_quantity);
        $db->bind(':notes', $notes);
        $db->bind(':id', $id);
        
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
        <h2><i class="fas fa-edit"></i> دەستکاریکردنی کاڵا</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">مەخزەن</a></li>
                <li class="breadcrumb-item active">دەستکاری</li>
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
                            <input type="text" name="item_name" class="form-control" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">یەکە <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control" value="<?php echo htmlspecialchars($item['unit']); ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">ژمارە <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="0" value="<?php echo $item['quantity']; ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">نرخی یەکە (<?php echo CURRENCY; ?>)</label>
                            <input type="number" name="unit_price" class="form-control" min="0" step="0.01" value="<?php echo $item['unit_price']; ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">کەمترین ژمارە</label>
                            <input type="number" name="min_quantity" class="form-control" min="0" value="<?php echo $item['min_quantity']; ?>">
                            <small class="text-muted">ئاگاداری دەدرێت کاتێک ژمارە کەم دەبێت</small>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($item['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> گەڕانەوە
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> نوێکردنەوە
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
