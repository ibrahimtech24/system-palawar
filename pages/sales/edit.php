<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'sales';
$pageTitle = 'دەستکاریکردنی فرۆشتن';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: list.php');
    exit;
}

// Get sale
$db->query("SELECT * FROM sales WHERE id = :id");
$db->bind(':id', $id);
$sale = $db->single();

if (!$sale) {
    header('Location: list.php');
    exit;
}

// Get customers
$db->query("SELECT id, name FROM customers ORDER BY name");
$customers = $db->resultSet();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
    $item_type = $_POST['item_type'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $total_price = $quantity * $unit_price;
    $sale_date = $_POST['sale_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    
    if (empty($customer_id)) {
        $message = 'تکایە کڕیارێک هەڵبژێرە';
        $messageType = 'danger';
    } elseif (empty($item_type) || $quantity <= 0 || $unit_price <= 0) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        // Calculate quantity difference and adjust inventory
        $oldQuantity = $sale['quantity'];
        $oldItemType = $sale['item_type'];
        $oldItemId = $sale['item_id'] ?? 0;
        $qtyDiff = $oldQuantity - $quantity; // positive = return to stock, negative = deduct more
        
        // If item type changed or quantity changed, adjust inventory
        if ($oldItemId > 0) {
            // Return old quantity to old item
            if ($oldItemType !== $item_type || $qtyDiff != 0) {
                switch ($oldItemType) {
                    case 'egg':
                        $db->query("UPDATE eggs SET quantity = quantity + :qty WHERE id = :id");
                        $db->bind(':qty', $oldQuantity);
                        $db->bind(':id', $oldItemId);
                        $db->execute();
                        break;
                    case 'chick':
                        $db->query("UPDATE chicks SET quantity = quantity + :qty WHERE id = :id");
                        $db->bind(':qty', $oldQuantity);
                        $db->bind(':id', $oldItemId);
                        $db->execute();
                        break;
                    case 'male_bird':
                        $db->query("UPDATE male_birds SET quantity = quantity + :qty WHERE id = :id");
                        $db->bind(':qty', $oldQuantity);
                        $db->bind(':id', $oldItemId);
                        $db->execute();
                        break;
                    case 'female_bird':
                        $db->query("UPDATE female_birds SET quantity = quantity + :qty WHERE id = :id");
                        $db->bind(':qty', $oldQuantity);
                        $db->bind(':id', $oldItemId);
                        $db->execute();
                        break;
                }
            }
        }
        
        // Deduct new quantity from (same or new) item
        if ($oldItemId > 0 && ($oldItemType !== $item_type || $qtyDiff != 0)) {
            switch ($item_type) {
                case 'egg':
                    $db->query("UPDATE eggs SET quantity = quantity - :qty WHERE id = :id");
                    $db->bind(':qty', $quantity);
                    $db->bind(':id', $oldItemId);
                    $db->execute();
                    break;
                case 'chick':
                    $db->query("UPDATE chicks SET quantity = quantity - :qty WHERE id = :id");
                    $db->bind(':qty', $quantity);
                    $db->bind(':id', $oldItemId);
                    $db->execute();
                    break;
                case 'male_bird':
                    $db->query("UPDATE male_birds SET quantity = quantity - :qty WHERE id = :id");
                    $db->bind(':qty', $quantity);
                    $db->bind(':id', $oldItemId);
                    $db->execute();
                    break;
                case 'female_bird':
                    $db->query("UPDATE female_birds SET quantity = quantity - :qty WHERE id = :id");
                    $db->bind(':qty', $quantity);
                    $db->bind(':id', $oldItemId);
                    $db->execute();
                    break;
            }
        }
        
        $db->query("UPDATE sales SET customer_id = :customer_id, item_type = :item_type, quantity = :quantity, 
                    unit_price = :unit_price, total_price = :total_price, sale_date = :sale_date, notes = :notes 
                    WHERE id = :id");
        $db->bind(':customer_id', $customer_id);
        $db->bind(':item_type', $item_type);
        $db->bind(':quantity', $quantity);
        $db->bind(':unit_price', $unit_price);
        $db->bind(':total_price', $total_price);
        $db->bind(':sale_date', $sale_date);
        $db->bind(':notes', $notes);
        $db->bind(':id', $id);
        
        if ($db->execute()) {
            // Update related transaction amount
            $db->query("UPDATE transactions SET amount = :amount WHERE reference_type = 'sale' AND reference_id = :id");
            $db->bind(':amount', $total_price);
            $db->bind(':id', $id);
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
        <h2><i class="fas fa-edit"></i> دەستکاریکردنی فرۆشتن</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">فرۆشتنەکان</a></li>
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
            <div class="card-header bg-success-gradient">
                <i class="fas fa-cash-register"></i> زانیاری فرۆشتن
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">کڕیار <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">کڕیارێک هەڵبژێرە...</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" <?php echo $sale['customer_id'] == $customer['id'] ? 'selected' : ''; ?>><?php echo $customer['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">جۆری کالا <span class="text-danger">*</span></label>
                            <select name="item_type" class="form-select" required>
                                <option value="">هەڵبژێرە...</option>
                                <option value="egg" <?php echo $sale['item_type'] == 'egg' ? 'selected' : ''; ?>>هێلکە</option>
                                <option value="chick" <?php echo $sale['item_type'] == 'chick' ? 'selected' : ''; ?>>جوجکە</option>
                                <option value="male_bird" <?php echo $sale['item_type'] == 'male_bird' ? 'selected' : ''; ?>>هەوێردەی نێر</option>
                                <option value="female_bird" <?php echo $sale['item_type'] == 'female_bird' ? 'selected' : ''; ?>>هەوێردەی مێ</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">ژمارە <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="<?php echo $sale['quantity']; ?>" required oninput="calculateTotal()">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">نرخی یەکە <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="unit_price_display" class="form-control" value="<?php echo number_format($sale['unit_price']); ?>" required oninput="formatPrice(this); calculateTotal()">
                                <input type="hidden" name="unit_price" id="unit_price" value="<?php echo $sale['unit_price']; ?>">
                                <span class="input-group-text"><?php echo CURRENCY; ?></span>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">کۆی نرخ</label>
                            <div class="input-group">
                                <input type="text" id="total_display" class="form-control" value="<?php echo number_format($sale['total_price']); ?>" readonly>
                                <span class="input-group-text"><?php echo CURRENCY; ?></span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری فرۆشتن <span class="text-danger">*</span></label>
                            <input type="date" name="sale_date" class="form-control" value="<?php echo $sale['sale_date']; ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($sale['notes'] ?? ''); ?></textarea>
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
