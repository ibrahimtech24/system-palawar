<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'purchases';
$pageTitle = 'کڕینی نوێ';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchase_code = generateCode('PUR');
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
        $db->query("INSERT INTO purchases (purchase_code, item_type, quantity, unit_price, total_price, purchase_date, notes, created_at) 
                    VALUES (:purchase_code, :item_type, :quantity, :unit_price, :total_price, :purchase_date, :notes, NOW())");
        $db->bind(':purchase_code', $purchase_code);
        $db->bind(':item_type', $item_type);
        $db->bind(':quantity', $quantity);
        $db->bind(':unit_price', $unit_price);
        $db->bind(':total_price', $total_price);
        $db->bind(':purchase_date', $purchase_date);
        $db->bind(':notes', $notes);
        
        if ($db->execute()) {
            // Add to transactions
            $db->query("INSERT INTO transactions (transaction_type, category, amount, description, reference_type, reference_id, transaction_date, created_at) 
                        VALUES ('expense', :category, :amount, :description, 'purchase', :ref_id, :date, NOW())");
            $db->bind(':category', 'کڕینی ' . getItemTypeName($item_type));
            $db->bind(':amount', $total_price);
            $db->bind(':description', 'کڕین - ' . $purchase_code);
            $db->bind(':ref_id', $db->lastInsertId());
            $db->bind(':date', $purchase_date);
            $db->execute();
            
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
        <h2><i class="fas fa-plus"></i> کڕینی نوێ</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">کڕینەکان</a></li>
                <li class="breadcrumb-item active">کڕینی نوێ</li>
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
                        <div class="col-md-12">
                            <label class="form-label">جۆری کالا <span class="text-danger">*</span></label>
                            <select name="item_type" class="form-select" required>
                                <option value="">هەڵبژێرە...</option>
                                <option value="egg">هێلکە</option>
                                <option value="chick">جوجکە</option>
                                <option value="male_bird">هەوێردەی نێر</option>
                                <option value="female_bird">هەوێردەی مێ</option>
                                <option value="feed">خواردن</option>
                                <option value="medicine">دەرمان</option>
                                <option value="equipment">ئامێر</option>
                                <option value="other">هیتر</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">ژمارە <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="form-control form-control-lg" min="1" required onkeyup="doCalculate()" onchange="doCalculate()">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">نرخی یەکە <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <input type="number" name="unit_price" id="unit_price" class="form-control" min="1" required onkeyup="doCalculate()" onchange="doCalculate()">
                                <span class="input-group-text"><?php echo CURRENCY; ?></span>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">کۆی نرخ</label>
                            <div class="input-group input-group-lg">
                                <input type="text" id="total_display" class="form-control bg-warning text-dark fw-bold text-center" value="0" readonly style="font-size: 1.5rem;">
                                <span class="input-group-text bg-warning text-dark"><?php echo CURRENCY; ?></span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری کڕین <span class="text-danger">*</span></label>
                            <input type="date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
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
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> تۆمارکردن
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function doCalculate() {
    var qty = Number(document.getElementById('quantity').value) || 0;
    var price = Number(document.getElementById('unit_price').value) || 0;
    var total = qty * price;
    document.getElementById('total_display').value = total.toLocaleString('en-US');
}

function formatPrice(input) {
    doCalculate();
}

function calculateTotal() {
    doCalculate();
}
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
