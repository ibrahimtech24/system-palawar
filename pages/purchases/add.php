<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'purchases';
$pageTitle = 'کڕینی نوێ';

// Get existing batch names for each type
$db->query("SELECT DISTINCT batch_name FROM male_birds WHERE status = 'active' ORDER BY batch_name");
$maleBatches = $db->resultSet();

$db->query("SELECT DISTINCT batch_name FROM female_birds WHERE status = 'active' ORDER BY batch_name");
$femaleBatches = $db->resultSet();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchase_code = generateCode('PUR');
    $item_type = $_POST['item_type'] ?? '';
    $item_name = $_POST['item_name'] ?? '';
    $batch_name = $_POST['batch_name'] ?? '';
    $unit = $_POST['unit'] ?? 'دانە';
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $total_price = $quantity * $unit_price;
    $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    
    // Warehouse item types
    $warehouseTypes = ['feed', 'medicine', 'equipment', 'other'];
    $needsItemName = in_array($item_type, $warehouseTypes);
    
    // Bird/livestock item types
    $birdTypes = ['male_bird', 'female_bird', 'egg', 'chick'];
    $needsBatchName = in_array($item_type, $birdTypes);
    
    if (empty($item_type) || $quantity <= 0 || $unit_price <= 0 || ($needsItemName && empty($item_name)) || ($needsBatchName && empty($batch_name))) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        // Combine item name with notes if it's a warehouse item
        $fullNotes = $needsItemName ? 'کاڵا: ' . $item_name . ($notes ? ' - ' . $notes : '') : $notes;
        if ($needsBatchName) {
            $fullNotes = 'گرووپ: ' . $batch_name . ($notes ? ' - ' . $notes : '');
        }
        
        $db->query("INSERT INTO purchases (purchase_code, item_type, quantity, unit_price, total_price, purchase_date, notes, created_at) 
                    VALUES (:purchase_code, :item_type, :quantity, :unit_price, :total_price, :purchase_date, :notes, NOW())");
        $db->bind(':purchase_code', $purchase_code);
        $db->bind(':item_type', $item_type);
        $db->bind(':quantity', $quantity);
        $db->bind(':unit_price', $unit_price);
        $db->bind(':total_price', $total_price);
        $db->bind(':purchase_date', $purchase_date);
        $db->bind(':notes', $fullNotes);
        
        if ($db->execute()) {
            $purchaseId = $db->lastInsertId();
            
            // Add to transactions
            $db->query("INSERT INTO transactions (transaction_type, category, amount, description, reference_type, reference_id, transaction_date, created_at) 
                        VALUES ('expense', :category, :amount, :description, 'purchase', :ref_id, :date, NOW())");
            $db->bind(':category', 'کڕینی ' . getItemTypeName($item_type));
            $db->bind(':amount', $total_price);
            $db->bind(':description', 'کڕین - ' . $purchase_code);
            $db->bind(':ref_id', $purchaseId);
            $db->bind(':date', $purchase_date);
            $db->execute();
            
            // Auto add to warehouse for warehouse-type items
            if ($needsItemName) {
                // Check if item already exists in warehouse
                $db->query("SELECT * FROM warehouse WHERE item_name = :item_name");
                $db->bind(':item_name', $item_name);
                $existingItem = $db->single();
                
                if ($existingItem) {
                    // Update existing item quantity
                    $db->query("UPDATE warehouse SET quantity = quantity + :quantity, unit_price = :unit_price, updated_at = NOW() WHERE id = :id");
                    $db->bind(':quantity', $quantity);
                    $db->bind(':unit_price', $unit_price);
                    $db->bind(':id', $existingItem['id']);
                    $db->execute();
                } else {
                    // Insert new item to warehouse
                    $db->query("INSERT INTO warehouse (item_name, quantity, unit, unit_price, min_quantity, notes, created_at) 
                                VALUES (:item_name, :quantity, :unit, :unit_price, 0, :notes, NOW())");
                    $db->bind(':item_name', $item_name);
                    $db->bind(':quantity', $quantity);
                    $db->bind(':unit', $unit);
                    $db->bind(':unit_price', $unit_price);
                    $db->bind(':notes', 'زیادکرا لە کڕین: ' . $purchase_code);
                    $db->execute();
                }
            }
            
            // Auto add to bird/livestock tables
            if ($needsBatchName) {
                switch ($item_type) {
                    case 'male_bird':
                        $db->query("INSERT INTO male_birds (batch_name, quantity, entry_date, notes, status, created_at) 
                                    VALUES (:batch_name, :quantity, :entry_date, :notes, 'active', NOW())");
                        $db->bind(':batch_name', $batch_name);
                        $db->bind(':quantity', $quantity);
                        $db->bind(':entry_date', $purchase_date);
                        $db->bind(':notes', 'کڕین: ' . $purchase_code . ($notes ? ' - ' . $notes : ''));
                        $db->execute();
                        break;
                        
                    case 'female_bird':
                        $db->query("INSERT INTO female_birds (batch_name, quantity, entry_date, notes, status, created_at) 
                                    VALUES (:batch_name, :quantity, :entry_date, :notes, 'active', NOW())");
                        $db->bind(':batch_name', $batch_name);
                        $db->bind(':quantity', $quantity);
                        $db->bind(':entry_date', $purchase_date);
                        $db->bind(':notes', 'کڕین: ' . $purchase_code . ($notes ? ' - ' . $notes : ''));
                        $db->execute();
                        break;
                        
                    case 'egg':
                        $db->query("INSERT INTO eggs (quantity, damaged_count, collection_date, notes, created_at) 
                                    VALUES (:quantity, 0, :collection_date, :notes, NOW())");
                        $db->bind(':quantity', $quantity);
                        $db->bind(':collection_date', $purchase_date);
                        $db->bind(':notes', 'کڕین: ' . $purchase_code . ' - گرووپ: ' . $batch_name . ($notes ? ' - ' . $notes : ''));
                        $db->execute();
                        break;
                        
                    case 'chick':
                        $db->query("INSERT INTO chicks (egg_id, quantity, dead_count, hatch_date, notes, status, created_at) 
                                    VALUES (NULL, :quantity, 0, :hatch_date, :notes, 'active', NOW())");
                        $db->bind(':quantity', $quantity);
                        $db->bind(':hatch_date', $purchase_date);
                        $db->bind(':notes', 'کڕین: ' . $purchase_code . ' - گرووپ: ' . $batch_name . ($notes ? ' - ' . $notes : ''));
                        $db->execute();
                        break;
                }
            }
            
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
                        <div class="col-md-6">
                            <label class="form-label">جۆری بەرهەم <span class="text-danger">*</span></label>
                            <select name="item_type" id="item_type" class="form-select" required onchange="toggleItemFields()">
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
                        
                        <div class="col-md-6" id="item_name_group" style="display: none;">
                            <label class="form-label">ناوی کاڵا <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="item_name" class="form-control" placeholder="ناوی کاڵا بنووسە...">
                            <small class="text-muted">ئەم کاڵایە خۆکارانە زیاد دەبێت بۆ مەخزەن</small>
                        </div>
                        
                        <div class="col-md-6" id="unit_group" style="display: none;">
                            <label class="form-label">یەکە</label>
                            <input type="text" name="unit" id="unit" class="form-control" placeholder="کیلۆ، دانە، بەستە..." value="دانە">
                        </div>
                        
                        <div class="col-md-6" id="batch_name_group" style="display: none;">
                            <label class="form-label">ناوی گرووپ <span class="text-danger">*</span></label>
                            
                            <!-- هەڵبژاردنی گرووپی هەوێردەی نێر -->
                            <div id="male_batch_options" class="batch-options d-none">
                                <select name="batch_name_male" id="batch_name_male" class="form-select batch-select" onchange="handleBatchSelect(this)">
                                    <option value="">گرووپ هەڵبژێرە یان نوێ زیاد بکە...</option>
                                    <option value="__new__">➕ گرووپی نوێ زیادبکە</option>
                                    <?php foreach ($maleBatches as $batch): ?>
                                    <option value="<?php echo htmlspecialchars($batch['batch_name']); ?>"><?php echo htmlspecialchars($batch['batch_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- هەڵبژاردنی گرووپی هەوێردەی مێ -->
                            <div id="female_batch_options" class="batch-options d-none">
                                <select name="batch_name_female" id="batch_name_female" class="form-select batch-select" onchange="handleBatchSelect(this)">
                                    <option value="">گرووپ هەڵبژێرە یان نوێ زیاد بکە...</option>
                                    <option value="__new__">➕ گرووپی نوێ زیادبکە</option>
                                    <?php foreach ($femaleBatches as $batch): ?>
                                    <option value="<?php echo htmlspecialchars($batch['batch_name']); ?>"><?php echo htmlspecialchars($batch['batch_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- هێلکە و جوجکە - تەنها نووسین -->
                            <div id="egg_chick_batch_options" class="batch-options d-none">
                                <input type="text" name="batch_name_other" id="batch_name_other" class="form-control" placeholder="بۆ نموونە: گرووپ ١">
                            </div>
                            
                            <!-- خانەی نووسینی ناوی نوێ -->
                            <div id="new_batch_input" class="mt-2 d-none">
                                <input type="text" id="new_batch_name" class="form-control" placeholder="ناوی گرووپی نوێ بنووسە...">
                            </div>
                            
                            <!-- خانەی شاراوە بۆ ناردنی داتا -->
                            <input type="hidden" name="batch_name" id="batch_name">
                            
                            <small class="text-muted">ئەم بەرهەمە خۆکارانە زیاد دەبێت بۆ بەشی تایبەتی خۆی</small>
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

function toggleItemFields() {
    var itemType = document.getElementById('item_type').value;
    var warehouseTypes = ['feed', 'medicine', 'equipment', 'other'];
    var birdTypes = ['male_bird', 'female_bird', 'egg', 'chick'];
    var isWarehouseItem = warehouseTypes.includes(itemType);
    var isBirdItem = birdTypes.includes(itemType);
    
    // Show/hide warehouse fields
    document.getElementById('item_name_group').style.display = isWarehouseItem ? 'block' : 'none';
    document.getElementById('unit_group').style.display = isWarehouseItem ? 'block' : 'none';
    
    // Show/hide bird batch name field
    document.getElementById('batch_name_group').style.display = isBirdItem ? 'block' : 'none';
    
    // Hide all batch options first
    document.querySelectorAll('.batch-options').forEach(function(el) {
        el.classList.add('d-none');
    });
    document.getElementById('new_batch_input').classList.add('d-none');
    
    // Show the right batch options based on type
    if (itemType === 'male_bird') {
        document.getElementById('male_batch_options').classList.remove('d-none');
    } else if (itemType === 'female_bird') {
        document.getElementById('female_batch_options').classList.remove('d-none');
    } else if (itemType === 'egg' || itemType === 'chick') {
        document.getElementById('egg_chick_batch_options').classList.remove('d-none');
    }
    
    // Reset batch name
    document.getElementById('batch_name').value = '';
    
    if (isWarehouseItem) {
        document.getElementById('item_name').setAttribute('required', 'required');
    } else {
        document.getElementById('item_name').removeAttribute('required');
        document.getElementById('item_name').value = '';
    }
}

function handleBatchSelect(selectElement) {
    var value = selectElement.value;
    var newBatchInput = document.getElementById('new_batch_input');
    var batchNameField = document.getElementById('batch_name');
    var newBatchNameInput = document.getElementById('new_batch_name');
    
    if (value === '__new__') {
        // Show input for new batch name
        newBatchInput.classList.remove('d-none');
        newBatchNameInput.focus();
        batchNameField.value = '';
    } else if (value) {
        // Use selected batch name
        newBatchInput.classList.add('d-none');
        newBatchNameInput.value = '';
        batchNameField.value = value;
    } else {
        newBatchInput.classList.add('d-none');
        newBatchNameInput.value = '';
        batchNameField.value = '';
    }
}

// Update hidden batch_name field when typing new name
document.addEventListener('DOMContentLoaded', function() {
    var newBatchInput = document.getElementById('new_batch_name');
    var otherBatchInput = document.getElementById('batch_name_other');
    var batchNameField = document.getElementById('batch_name');
    
    if (newBatchInput) {
        newBatchInput.addEventListener('input', function() {
            batchNameField.value = this.value;
        });
    }
    
    if (otherBatchInput) {
        otherBatchInput.addEventListener('input', function() {
            batchNameField.value = this.value;
        });
    }
    
    toggleItemFields();
});
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
