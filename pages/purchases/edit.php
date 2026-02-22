<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'purchases';
$pageTitle = 'دەستکاریکردنی کڕین';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: list.php');
    exit;
}

// Get purchase
$db->query("SELECT * FROM purchases WHERE id = :id");
$db->bind(':id', $id);
$purchase = $db->single();

if (!$purchase) {
    header('Location: list.php');
    exit;
}

// Get suppliers
$db->query("SELECT id, name FROM suppliers ORDER BY name");
$suppliers = $db->resultSet();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
    $item_type = $_POST['item_type'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $total_price = $quantity * $unit_price;
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    
    if (empty($item_type) || $quantity <= 0 || $unit_price <= 0) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        $db->query("UPDATE purchases SET supplier_id = :supplier_id, item_type = :item_type, quantity = :quantity, 
                    unit_price = :unit_price, total_price = :total_price, purchase_date = :purchase_date, notes = :notes 
                    WHERE id = :id");
        $db->bind(':supplier_id', $supplier_id);
        $db->bind(':item_type', $item_type);
        $db->bind(':quantity', $quantity);
        $db->bind(':unit_price', $unit_price);
        $db->bind(':total_price', $total_price);
        $db->bind(':purchase_date', $purchase_date);
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
        <h2><i class="fas fa-edit"></i> دەستکاریکردنی کڕین</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">کڕینەکان</a></li>
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
            <div class="card-header bg-warning-gradient">
                <i class="fas fa-shopping-bag"></i> زانیاری کڕین
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">دابینکەر</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">هەڵبژێرە (ئارەزوومەندانە)</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['id']; ?>" <?php echo $purchase['supplier_id'] == $supplier['id'] ? 'selected' : ''; ?>><?php echo $supplier['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">جۆری کالا <span class="text-danger">*</span></label>
                            <select name="item_type" class="form-select" required>
                                <option value="">هەڵبژێرە...</option>
                                <option value="egg" <?php echo $purchase['item_type'] == 'egg' ? 'selected' : ''; ?>>هێلکە</option>
                                <option value="chick" <?php echo $purchase['item_type'] == 'chick' ? 'selected' : ''; ?>>جوجکە</option>
                                <option value="male_bird" <?php echo $purchase['item_type'] == 'male_bird' ? 'selected' : ''; ?>>هەوێردەی نێر</option>
                                <option value="female_bird" <?php echo $purchase['item_type'] == 'female_bird' ? 'selected' : ''; ?>>هەوێردەی مێ</option>
                                <option value="feed" <?php echo $purchase['item_type'] == 'feed' ? 'selected' : ''; ?>>خواردن</option>
                                <option value="medicine" <?php echo $purchase['item_type'] == 'medicine' ? 'selected' : ''; ?>>دەرمان</option>
                                <option value="equipment" <?php echo $purchase['item_type'] == 'equipment' ? 'selected' : ''; ?>>ئامێر</option>
                                <option value="other" <?php echo $purchase['item_type'] == 'other' ? 'selected' : ''; ?>>هیتر</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">ژمارە <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="<?php echo $purchase['quantity']; ?>" required oninput="calculateTotal()">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">نرخی یەکە <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="unit_price_display" class="form-control" value="<?php echo number_format($purchase['unit_price']); ?>" required oninput="formatPrice(this); calculateTotal()">
                                <input type="hidden" name="unit_price" id="unit_price" value="<?php echo $purchase['unit_price']; ?>">
                                <span class="input-group-text"><?php echo CURRENCY; ?></span>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">کۆی نرخ</label>
                            <div class="input-group">
                                <input type="text" id="total_display" class="form-control" value="<?php echo number_format($purchase['total_price']); ?>" readonly>
                                <span class="input-group-text"><?php echo CURRENCY; ?></span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری کڕین <span class="text-danger">*</span></label>
                            <input type="date" name="purchase_date" class="form-control" value="<?php echo $purchase['purchase_date']; ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($purchase['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> گەڕانەوە
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> نوێکردنەوە
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function formatPrice(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if (value) {
        let num = parseInt(value);
        document.getElementById('unit_price').value = num;
        input.value = num.toLocaleString('en-US');
    } else {
        document.getElementById('unit_price').value = 0;
        input.value = '';
    }
}

function calculateTotal() {
    const qty = parseFloat(document.getElementById('quantity').value) || 0;
    const price = parseFloat(document.getElementById('unit_price').value) || 0;
    const total = qty * price;
    document.getElementById('total_display').value = total.toLocaleString('en-US');
}
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
