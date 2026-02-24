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

// Get available items for each type
$db->query("SELECT id, quantity, collection_date FROM eggs WHERE quantity > 0 ORDER BY collection_date DESC");
$availableEggs = $db->resultSet();

$db->query("SELECT c.id, c.egg_id, c.quantity, c.dead_count, c.hatch_date, c.status, c.customer_id, c.batch_name,
                   cu.name as customer_name
            FROM chicks c 
            LEFT JOIN customers cu ON c.customer_id = cu.id
            WHERE c.status = 'active' AND c.quantity > c.dead_count 
            ORDER BY c.hatch_date DESC");
$availableChicks = $db->resultSet();

$db->query("SELECT id, batch_name, quantity, entry_date, status FROM male_birds WHERE status = 'active' AND quantity > 0 ORDER BY entry_date DESC");
$availableMaleBirds = $db->resultSet();

$db->query("SELECT id, batch_name, quantity, entry_date, status FROM female_birds WHERE status = 'active' AND quantity > 0 ORDER BY entry_date DESC");
$availableFemaleBirds = $db->resultSet();

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
    
    // Get item ID based on type
    $item_id = 0;
    switch ($item_type) {
        case 'egg':
            $item_id = intval($_POST['item_id_egg'] ?? 0);
            break;
        case 'chick':
            $item_id = intval($_POST['item_id_chick'] ?? 0);
            break;
        case 'male_bird':
            $item_id = intval($_POST['item_id_male'] ?? 0);
            break;
        case 'female_bird':
            $item_id = intval($_POST['item_id_female'] ?? 0);
            break;
    }
    
    if (empty($customer_id)) {
        $message = 'تکایە کڕیارێک هەڵبژێرە';
        $messageType = 'danger';
    } elseif (empty($item_type) || $quantity <= 0 || $unit_price <= 0) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        // Check available quantity before sale
        $availableQty = 0;
        if ($item_id > 0) {
            switch ($item_type) {
                case 'egg':
                    $db->query("SELECT quantity FROM eggs WHERE id = :id");
                    $db->bind(':id', $item_id);
                    $result = $db->single();
                    $availableQty = $result['quantity'] ?? 0;
                    break;
                case 'chick':
                    $db->query("SELECT quantity - dead_count as available FROM chicks WHERE id = :id");
                    $db->bind(':id', $item_id);
                    $result = $db->single();
                    $availableQty = $result['available'] ?? 0;
                    break;
                case 'male_bird':
                    $db->query("SELECT quantity FROM male_birds WHERE id = :id");
                    $db->bind(':id', $item_id);
                    $result = $db->single();
                    $availableQty = $result['quantity'] ?? 0;
                    break;
                case 'female_bird':
                    $db->query("SELECT quantity FROM female_birds WHERE id = :id");
                    $db->bind(':id', $item_id);
                    $result = $db->single();
                    $availableQty = $result['quantity'] ?? 0;
                    break;
            }
        }
        
        if ($item_id > 0 && $quantity > $availableQty) {
            $message = 'ژمارەی داواکراو زیاترە لە ژمارەی بەردەست (' . $availableQty . ' دانە)';
            $messageType = 'danger';
        } else {
            $db->query("INSERT INTO sales (sale_code, customer_id, item_type, item_id, quantity, unit_price, total_price, sale_date, notes, created_at) 
                        VALUES (:sale_code, :customer_id, :item_type, :item_id, :quantity, :unit_price, :total_price, :sale_date, :notes, NOW())");
        $db->bind(':sale_code', $sale_code);
        $db->bind(':customer_id', $customer_id);
        $db->bind(':item_type', $item_type);
        $db->bind(':item_id', $item_id);
        $db->bind(':quantity', $quantity);
        $db->bind(':unit_price', $unit_price);
        $db->bind(':total_price', $total_price);
        $db->bind(':sale_date', $sale_date);
        $db->bind(':notes', $notes);
        
        if ($db->execute()) {
            $saleId = $db->lastInsertId();
            
            // Deduct quantity from inventory based on item type
            if ($item_id > 0) {
                switch ($item_type) {
                    case 'egg':
                        $db->query("UPDATE eggs SET quantity = quantity - :qty WHERE id = :id AND quantity >= :qty");
                        $db->bind(':qty', $quantity);
                        $db->bind(':id', $item_id);
                        $db->execute();
                        break;
                    case 'chick':
                        $db->query("UPDATE chicks SET quantity = quantity - :qty WHERE id = :id AND quantity >= :qty");
                        $db->bind(':qty', $quantity);
                        $db->bind(':id', $item_id);
                        $db->execute();
                        break;
                    case 'male_bird':
                        $db->query("UPDATE male_birds SET quantity = quantity - :qty WHERE id = :id AND quantity >= :qty");
                        $db->bind(':qty', $quantity);
                        $db->bind(':id', $item_id);
                        $db->execute();
                        break;
                    case 'female_bird':
                        $db->query("UPDATE female_birds SET quantity = quantity - :qty WHERE id = :id AND quantity >= :qty");
                        $db->bind(':qty', $quantity);
                        $db->bind(':id', $item_id);
                        $db->execute();
                        break;
                }
            }
            
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
                            <label class="form-label">کڕیار <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="customer_id" id="customer_id" class="form-select" required>
                                    <option value="">کڕیارێک هەڵبژێرە...</option>
                                    <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>"><?php echo $customer['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="showAddCustomerModal()" title="زیادکردنی کڕیاری نوێ">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">جۆری بەرهەم <span class="text-danger">*</span></label>
                            <select name="item_type" id="item_type" class="form-select" required onchange="showItemOptions()">
                                <option value="">هەڵبژێرە...</option>
                                <option value="egg">هێلکە</option>
                                <option value="chick">جوجکە</option>
                                <option value="male_bird">هەوێردەی نێر</option>
                                <option value="female_bird">هەوێردەی مێ</option>
                            </select>
                        </div>
                        
                        <!-- Available Items Dropdown -->
                        <div class="col-md-12" id="itemOptionsContainer">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <label class="form-label mb-2"><i class="fas fa-box"></i> بەرهەمی بەردەست</label>
                                    
                                    <!-- No selection message -->
                                    <div id="noSelectionMsg" class="alert alert-info mb-0 py-2">
                                        <i class="fas fa-info-circle"></i> تکایە سەرەتا جۆری بەرهەم هەڵبژێرە
                                    </div>
                                    
                                    <!-- Eggs -->
                                    <div id="eggOptions" class="item-options d-none">
                                <?php if (count($availableEggs) > 0): ?>
                                <select name="item_id_egg" class="form-select item-select mb-2">
                                    <option value="">هەڵبژێرە...</option>
                                    <?php foreach ($availableEggs as $egg): ?>
                                    <option value="<?php echo $egg['id']; ?>" data-qty="<?php echo $egg['quantity']; ?>">
                                        هێلکە #<?php echo $egg['id']; ?> - <?php echo $egg['quantity']; ?> دانە (<?php echo $egg['collection_date']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="alert alert-success py-2 mb-0">
                                    <i class="fas fa-check-circle"></i> کۆی هێلکەی بەردەست: <strong><?php echo array_sum(array_column($availableEggs, 'quantity')); ?></strong> دانە
                                </div>
                                <?php else: ?>
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-times-circle"></i> <strong>بەردەست نیە!</strong> هیچ هێلکەیەک لە کۆگا نیە
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Chicks -->
                            <div id="chickOptions" class="item-options d-none">
                                <!-- Customer chick notice -->
                                <div id="customerChickNotice" class="alert alert-info py-2 mb-2 d-none">
                                    <i class="fas fa-star"></i> <span id="customerChickText"></span>
                                </div>
                                <?php if (count($availableChicks) > 0): ?>
                                <select name="item_id_chick" class="form-select item-select mb-2">
                                    <option value="">هەڵبژێرە...</option>
                                    <?php foreach ($availableChicks as $chick): 
                                        $available = $chick['quantity'] - $chick['dead_count'];
                                        $ownerLabel = !empty($chick['customer_name']) ? htmlspecialchars($chick['customer_name']) : 'خۆمان';
                                    ?>
                                    <option value="<?php echo $chick['id']; ?>" data-qty="<?php echo $available; ?>" data-customer="<?php echo $chick['customer_id'] ?? 0; ?>">
                                        جوجکە #<?php echo $chick['id']; ?> - <?php echo $available; ?> دانە (<?php echo $chick['hatch_date']; ?>) [<?php echo $ownerLabel; ?>]
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="alert alert-success py-2 mb-0">
                                    <i class="fas fa-check-circle"></i> کۆی جوجکەی بەردەست: <strong><?php echo array_sum(array_map(function($c) { return $c['quantity'] - $c['dead_count']; }, $availableChicks)); ?></strong> دانە
                                </div>
                                <?php else: ?>
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-times-circle"></i> <strong>بەردەست نیە!</strong> هیچ جوجکەیەک لە کۆگا نیە
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Male Birds -->
                            <div id="maleBirdOptions" class="item-options d-none">
                                <?php if (count($availableMaleBirds) > 0): ?>
                                <select name="item_id_male" class="form-select item-select mb-2">
                                    <option value="">هەڵبژێرە...</option>
                                    <?php foreach ($availableMaleBirds as $bird): ?>
                                    <option value="<?php echo $bird['id']; ?>" data-qty="<?php echo $bird['quantity']; ?>">
                                        <?php echo $bird['batch_name']; ?> - <?php echo $bird['quantity']; ?> دانە (<?php echo $bird['entry_date']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="alert alert-success py-2 mb-0">
                                    <i class="fas fa-check-circle"></i> کۆی هەوێردەی نێری بەردەست: <strong><?php echo array_sum(array_column($availableMaleBirds, 'quantity')); ?></strong> دانە
                                </div>
                                <?php else: ?>
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-times-circle"></i> <strong>بەردەست نیە!</strong> هیچ هەوێردەی نێرێک لە کۆگا نیە
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Female Birds -->
                            <div id="femaleBirdOptions" class="item-options d-none">
                                <?php if (count($availableFemaleBirds) > 0): ?>
                                <select name="item_id_female" class="form-select item-select mb-2">
                                    <option value="">هەڵبژێرە...</option>
                                    <?php foreach ($availableFemaleBirds as $bird): ?>
                                    <option value="<?php echo $bird['id']; ?>" data-qty="<?php echo $bird['quantity']; ?>">
                                        <?php echo $bird['batch_name']; ?> - <?php echo $bird['quantity']; ?> دانە (<?php echo $bird['entry_date']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="alert alert-success py-2 mb-0">
                                    <i class="fas fa-check-circle"></i> کۆی هەوێردەی مێی بەردەست: <strong><?php echo array_sum(array_column($availableFemaleBirds, 'quantity')); ?></strong> دانە
                                </div>
                                <?php else: ?>
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-times-circle"></i> <strong>بەردەست نیە!</strong> هیچ هەوێردەی مێیەک لە کۆگا نیە
                                </div>
                                <?php endif; ?>
                            </div>
                                </div>
                            </div>
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
function showItemOptions() {
    const itemType = document.getElementById('item_type').value;
    const noSelectionMsg = document.getElementById('noSelectionMsg');
    
    // Hide all options first
    document.querySelectorAll('.item-options').forEach(function(el) {
        el.classList.add('d-none');
    });
    
    if (itemType) {
        // Hide the "no selection" message
        noSelectionMsg.classList.add('d-none');
        
        // Show the relevant option
        switch(itemType) {
            case 'egg':
                document.getElementById('eggOptions').classList.remove('d-none');
                break;
            case 'chick':
                document.getElementById('chickOptions').classList.remove('d-none');
                break;
            case 'male_bird':
                document.getElementById('maleBirdOptions').classList.remove('d-none');
                break;
            case 'female_bird':
                document.getElementById('femaleBirdOptions').classList.remove('d-none');
                break;
        }
    } else {
        // Show the "no selection" message
        noSelectionMsg.classList.remove('d-none');
    }
}

function formatPrice(input) {
    // Remove non-numeric characters except for digits
    var value = input.value.replace(/[^\d]/g, '');
    
    // Convert to number and format with thousand separators
    if (value && value !== '') {
        var num = parseInt(value, 10);
        document.getElementById('unit_price').value = num;
        input.value = num.toLocaleString('en-US');
        doCalculate();
    } else {
        document.getElementById('unit_price').value = 0;
        input.value = '';
        doCalculate();
    }
}

function doCalculate() {
    var qty = Number(document.getElementById('quantity').value) || 0;
    var price = Number(document.getElementById('unit_price').value) || 0;
    var total = qty * price;
    document.getElementById('total_display').value = total.toLocaleString('en-US');
}

function calculateTotal() {
    doCalculate();
}

// Auto-fill quantity when selecting an item (only set max, don't auto-fill)
document.querySelectorAll('.item-select').forEach(function(select) {
    select.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.dataset.qty) {
            document.getElementById('quantity').max = selectedOption.dataset.qty;
        }
    });
});

// Customer-chick mapping data
var customerChickData = {};
<?php foreach ($availableChicks as $chick): ?>
<?php if (!empty($chick['customer_id'])): ?>
if (!customerChickData[<?php echo $chick['customer_id']; ?>]) {
    customerChickData[<?php echo $chick['customer_id']; ?>] = { name: '<?php echo addslashes($chick['customer_name']); ?>', count: 0, ids: [] };
}
customerChickData[<?php echo $chick['customer_id']; ?>].count += <?php echo $chick['quantity'] - $chick['dead_count']; ?>;
customerChickData[<?php echo $chick['customer_id']; ?>].ids.push(<?php echo $chick['id']; ?>);
<?php endif; ?>
<?php endforeach; ?>

// When customer changes, check for their incubator chicks
function onCustomerChange() {
    var customerId = parseInt(document.getElementById('customer_id').value) || 0;
    var notice = document.getElementById('customerChickNotice');
    var noticeText = document.getElementById('customerChickText');
    var chickSelect = document.querySelector('select[name="item_id_chick"]');
    
    // Reset chick options visibility
    if (chickSelect) {
        var options = chickSelect.querySelectorAll('option[data-customer]');
        options.forEach(function(opt) {
            opt.style.display = '';
            opt.style.fontWeight = '';
            opt.style.color = '';
        });
    }
    
    if (customerId > 0 && customerChickData[customerId]) {
        var data = customerChickData[customerId];
        noticeText.textContent = 'ئەم کڕیارە ' + data.count + ' جوجکەی لە مەفقەسەوە هەیە! دەتوانیت ڕاستەوخۆ بیفرۆشیت.';
        notice.classList.remove('d-none');
        
        // Auto-select chick type
        document.getElementById('item_type').value = 'chick';
        showItemOptions();
        
        // Highlight & auto-select customer's chick
        if (chickSelect) {
            // Move customer chicks to top by sorting
            var firstCustomerChick = null;
            chickSelect.querySelectorAll('option[data-customer]').forEach(function(opt) {
                if (parseInt(opt.dataset.customer) === customerId) {
                    opt.style.fontWeight = 'bold';
                    opt.style.color = '#198754';
                    if (!firstCustomerChick) firstCustomerChick = opt;
                }
            });
            // Auto-select first customer chick
            if (firstCustomerChick) {
                chickSelect.value = firstCustomerChick.value;
                // Set max quantity and auto-fill
                document.getElementById('quantity').max = firstCustomerChick.dataset.qty;
                document.getElementById('quantity').value = firstCustomerChick.dataset.qty;
                doCalculate();
            }
        }
    } else {
        notice.classList.add('d-none');
    }
}

// Attach customer change event
document.getElementById('customer_id').addEventListener('change', onCustomerChange);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    showItemOptions();
});

// Show add customer modal
function showAddCustomerModal() {
    var modal = new bootstrap.Modal(document.getElementById('addCustomerModal'));
    modal.show();
}

// Save new customer
function saveNewCustomer() {
    var name = document.getElementById('new_customer_name').value.trim();
    var phone = document.getElementById('new_customer_phone').value.trim();
    var address = document.getElementById('new_customer_address').value.trim();
    
    if (!name) {
        alert('تکایە ناوی کڕیار بنووسە');
        return;
    }
    
    // Send AJAX request to save customer
    var formData = new FormData();
    formData.append('action', 'add_customer');
    formData.append('name', name);
    formData.append('phone', phone);
    formData.append('address', address);
    
    fetch('ajax_customer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add new customer to dropdown
            var select = document.getElementById('customer_id');
            var option = document.createElement('option');
            option.value = data.customer_id;
            option.textContent = name;
            select.appendChild(option);
            
            // Select the new customer
            select.value = data.customer_id;
            
            // Close modal and reset form
            var modal = bootstrap.Modal.getInstance(document.getElementById('addCustomerModal'));
            modal.hide();
            document.getElementById('new_customer_name').value = '';
            document.getElementById('new_customer_phone').value = '';
            document.getElementById('new_customer_address').value = '';
            
            // Show success message
            alert('کڕیار بە سەرکەوتوویی زیاد کرا');
        } else {
            alert('هەڵەیەک ڕوویدا: ' + data.message);
        }
    })
    .catch(error => {
        alert('هەڵەیەک ڕوویدا');
        console.error(error);
    });
}
</script>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> زیادکردنی کڕیاری نوێ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">ناو <span class="text-danger">*</span></label>
                    <input type="text" id="new_customer_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ژمارەی مۆبایل</label>
                    <input type="tel" id="new_customer_phone" class="form-control" dir="ltr">
                </div>
                <div class="mb-3">
                    <label class="form-label">ناونیشان</label>
                    <input type="text" id="new_customer_address" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> داخستن
                </button>
                <button type="button" class="btn btn-primary" onclick="saveNewCustomer()">
                    <i class="fas fa-save"></i> زیادکردن
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
