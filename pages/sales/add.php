<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'sales';
$pageTitle = 'فرۆشتنی نوێ';

// Get customers
$db->query("SELECT id, name FROM customers ORDER BY name");
$customers = $db->resultSet();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_code = generateCode('SALE');
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
    $item_type = $_POST['item_type'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $total_price = $quantity * $unit_price;
    $sale_date = $_POST['sale_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    
    if (empty($item_type) || $quantity <= 0 || $unit_price <= 0) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        $db->query("INSERT INTO sales (sale_code, customer_id, item_type, quantity, unit_price, total_price, sale_date, notes, created_at) 
                    VALUES (:sale_code, :customer_id, :item_type, :quantity, :unit_price, :total_price, :sale_date, :notes, NOW())");
        $db->bind(':sale_code', $sale_code);
        $db->bind(':customer_id', $customer_id);
        $db->bind(':item_type', $item_type);
        $db->bind(':quantity', $quantity);
        $db->bind(':unit_price', $unit_price);
        $db->bind(':total_price', $total_price);
        $db->bind(':sale_date', $sale_date);
        $db->bind(':notes', $notes);
        
        if ($db->execute()) {
            $saleId = $db->lastInsertId();
            
            // Add to transactions
            $db->query("INSERT INTO transactions (transaction_type, category, amount, description, reference_type, reference_id, transaction_date, created_at) 
                        VALUES ('income', :category, :amount, :description, 'sale', :ref_id, :date, NOW())");
            $db->bind(':category', 'فرۆشتنی ' . getItemTypeName($item_type));
            $db->bind(':amount', $total_price);
            $db->bind(':description', 'فرۆشتن - ' . $sale_code);
            $db->bind(':ref_id', $saleId);
            $db->bind(':date', $sale_date);
            $db->execute();
            
            // Redirect to receipt page
            header('Location: receipt.php?id=' . $saleId);
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
        <h2><i class="fas fa-plus"></i> فرۆشتنی نوێ</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">فرۆشتنەکان</a></li>
                <li class="breadcrumb-item active">فرۆشتنی نوێ</li>
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
            <div class="card-header bg-success-gradient">
                <i class="fas fa-cash-register"></i> زانیاری فرۆشتن
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">کڕیار</label>
                            <select name="customer_id" class="form-select">
                                <option value="">هەڵبژێرە (ئارەزوومەندانە)</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>"><?php echo $customer['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">جۆری کالا <span class="text-danger">*</span></label>
                            <select name="item_type" class="form-select" required>
                                <option value="">هەڵبژێرە...</option>
                                <option value="egg">هێلکە</option>
                                <option value="chick">جوجکە</option>
                                <option value="male_bird">هەوێردەی نێر</option>
                                <option value="female_bird">هەوێردەی مێ</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">ژمارە <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" required oninput="calculateTotal()">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">نرخی یەکە <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="unit_price_display" class="form-control" placeholder="0" required oninput="formatPrice(this); calculateTotal()">
                                <input type="hidden" name="unit_price" id="unit_price" value="0">
                                <span class="input-group-text"><?php echo CURRENCY; ?></span>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">کۆی نرخ</label>
                            <div class="input-group">
                                <input type="text" id="total_display" class="form-control" readonly>
                                <span class="input-group-text"><?php echo CURRENCY; ?></span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری فرۆشتن <span class="text-danger">*</span></label>
                            <input type="date" name="sale_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
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

<script>
function formatPrice(input) {
    // Remove non-numeric characters except for digits
    let value = input.value.replace(/[^\d]/g, '');
    
    // Convert to number and format with thousand separators
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
