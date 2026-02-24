<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'incubator';
$pageTitle = 'دانانەوەی هێلکە لە مەفقەس';

// Get available eggs (healthy eggs from warehouse/eggs table)
$db->query("SELECT e.*, f.batch_name as female_batch, m.batch_name as male_batch,
                   (e.quantity - e.damaged_count) as healthy_count
            FROM eggs e 
            LEFT JOIN female_birds f ON e.female_bird_id = f.id 
            LEFT JOIN male_birds m ON e.male_bird_id = m.id 
            WHERE e.quantity > 0 AND (e.quantity - e.damaged_count) > 0
            ORDER BY e.collection_date DESC");
$availableEggs = $db->resultSet();

// Get customers for dropdown
$db->query("SELECT id, name, phone FROM customers ORDER BY name ASC");
$customers = $db->resultSet();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name'] ?? '');
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $egg_id = intval($_POST['egg_id'] ?? 0);
    $egg_quantity = intval($_POST['egg_quantity'] ?? 0);
    $entry_date = $_POST['entry_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($group_name)) {
        $message = 'تکایە ناوی گرووپ بنووسە';
        $messageType = 'danger';
    } elseif ($egg_id <= 0) {
        $message = 'تکایە گرووپی هێلکە هەڵبژێرە';
        $messageType = 'danger';
    } elseif ($egg_quantity <= 0) {
        $message = 'تکایە ژمارەی هێلکە بنووسە';
        $messageType = 'danger';
    } else {
        // Check if enough eggs available
        $db->query("SELECT quantity, damaged_count FROM eggs WHERE id = :id");
        $db->bind(':id', $egg_id);
        $eggRecord = $db->single();
        
        if (!$eggRecord) {
            $message = 'ئەم گرووپی هێلکەیە بوونی نیە';
            $messageType = 'danger';
        } else {
            $availableHealthy = $eggRecord['quantity'] - $eggRecord['damaged_count'];
            
            if ($egg_quantity > $availableHealthy) {
                $message = 'ژمارەی هێلکەی داواکراو زیاترە لە هێلکەی بەردەست (' . $availableHealthy . ')';
                $messageType = 'danger';
            } else {
                // Calculate expected hatch date (17 days)
                $hatchDate = date('Y-m-d', strtotime($entry_date . ' +17 days'));
                
                // Insert into incubator
                $db->query("INSERT INTO incubator (group_name, customer_id, egg_id, egg_quantity, entry_date, expected_hatch_date, status, notes, created_at) 
                            VALUES (:group_name, :customer_id, :egg_id, :egg_quantity, :entry_date, :expected_hatch_date, 'incubating', :notes, NOW())");
                $db->bind(':group_name', $group_name);
                $db->bind(':customer_id', $customer_id > 0 ? $customer_id : null);
                $db->bind(':egg_id', $egg_id);
                $db->bind(':egg_quantity', $egg_quantity);
                $db->bind(':entry_date', $entry_date);
                $db->bind(':expected_hatch_date', $hatchDate);
                $db->bind(':notes', $notes);
                
                if ($db->execute()) {
                    // Deduct eggs from eggs table (reduce quantity)
                    $newQuantity = $eggRecord['quantity'] - $egg_quantity;
                    $db->query("UPDATE eggs SET quantity = :quantity WHERE id = :id");
                    $db->bind(':quantity', $newQuantity);
                    $db->bind(':id', $egg_id);
                    $db->execute();
                    
                    header('Location: list.php?success=added');
                    exit;
                } else {
                    $message = 'هەڵەیەک ڕوویدا';
                    $messageType = 'danger';
                }
            }
        }
    }
}

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-plus"></i> دانانەوەی هێلکە لە مەفقەس</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">مەفقەس</a></li>
                <li class="breadcrumb-item active">دانانەوە</li>
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

<!-- Info Alert -->
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <strong>ئاگاداری:</strong> کاتێک هێلکە لە مەفقەس دادەنێیت، ئەو ژمارە هێلکەیە لە مەخزەنی هێلکە کەم دەکرێت و 
    بەشکل ئۆتۆماتیکی ڕۆژی دەرچوون (١٧ ڕۆژ دواتر) دیاری دەکرێت.
</div>

<!-- Form -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #f97316, #ea580c); color: white;">
                <i class="fas fa-temperature-high"></i> زانیاری مەفقەس
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ناوی گرووپ <span class="text-danger">*</span></label>
                            <input type="text" name="group_name" class="form-control" placeholder="نمونە: گرووپی ١" required
                                   value="<?php echo isset($_POST['group_name']) ? htmlspecialchars($_POST['group_name']) : ''; ?>">
                            <small class="text-muted">ناوێک بۆ ئەم گرووپە لە مەفقەس</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-user-tie text-primary"></i> کڕیار</label>
                            <select name="customer_id" class="form-select">
                                <option value="0">-- بێ کڕیار (خۆمان) --</option>
                                <?php foreach ($customers as $cust): ?>
                                <option value="<?php echo $cust['id']; ?>" <?php echo (isset($_POST['customer_id']) && $_POST['customer_id'] == $cust['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cust['name']); ?>
                                    <?php if ($cust['phone']): ?> (<?php echo $cust['phone']; ?>)<?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">کڕیارەکە کە داوای مەفقەسی کردووە</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری دانان <span class="text-danger">*</span></label>
                            <input type="date" name="entry_date" id="entry_date" class="form-control" 
                                   value="<?php echo isset($_POST['entry_date']) ? $_POST['entry_date'] : date('Y-m-d'); ?>" required
                                   onchange="calculateHatchDate()">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">گرووپی هێلکە <span class="text-danger">*</span></label>
                            <select name="egg_id" id="egg_id" class="form-select" required onchange="updateMaxQuantity()">
                                <option value="">-- گرووپی هێلکە هەڵبژێرە --</option>
                                <?php foreach ($availableEggs as $egg): 
                                    $healthyCount = $egg['quantity'] - $egg['damaged_count'];
                                    $parentInfo = [];
                                    if ($egg['female_batch']) $parentInfo[] = 'دایک: ' . $egg['female_batch'];
                                    if ($egg['male_batch']) $parentInfo[] = 'باوک: ' . $egg['male_batch'];
                                    $parentStr = !empty($parentInfo) ? ' [' . implode(' | ', $parentInfo) . ']' : '';
                                ?>
                                <option value="<?php echo $egg['id']; ?>" data-max="<?php echo $healthyCount; ?>">
                                    <?php echo $healthyCount; ?> هێلکەی ساغ (<?php echo formatDate($egg['collection_date']); ?>)<?php echo $parentStr; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">هێلکەیەک لە مەخزەن هەڵبژێرە بۆ دانانەوە لە مەفقەس</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">ژمارەی هێلکە بۆ مەفقەس <span class="text-danger">*</span></label>
                            <input type="number" name="egg_quantity" id="egg_quantity" class="form-control" min="1" max="9999" 
                                   placeholder="ژمارەی هێلکە بنووسە"
                                   value="<?php echo isset($_POST['egg_quantity']) ? intval($_POST['egg_quantity']) : ''; ?>" required>
                            <small class="text-muted" id="maxQuantityHint">تکایە یەکەم گرووپی هێلکە هەڵبژێرە</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-calendar-check text-success"></i> ڕۆژی چاوەڕوانکراوی دەرچوون</label>
                            <input type="text" id="expected_hatch_display" class="form-control bg-light" readonly
                                   value="<?php echo date('Y/m/d', strtotime('+17 days')); ?>">
                            <small class="text-muted">١٧ ڕۆژ دوای دانان - بەشکل ئۆتۆماتیکی</small>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="تێبینی دڵخوازانە..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> گەڕانەوە
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> دانانەوە لە مەفقەس
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updateMaxQuantity() {
    var select = document.getElementById('egg_id');
    var option = select.options[select.selectedIndex];
    var maxQty = option.getAttribute('data-max');
    var input = document.getElementById('egg_quantity');
    var hint = document.getElementById('maxQuantityHint');
    
    if (maxQty) {
        input.max = maxQty;
        hint.textContent = 'زۆرترین: ' + maxQty + ' هێلکە';
        hint.className = 'text-success';
    } else {
        input.removeAttribute('max');
        hint.textContent = 'تکایە یەکەم گرووپی هێلکە هەڵبژێرە';
        hint.className = 'text-muted';
    }
}

function calculateHatchDate() {
    var entryDate = document.getElementById('entry_date').value;
    if (entryDate) {
        var date = new Date(entryDate);
        date.setDate(date.getDate() + 17);
        var formatted = date.getFullYear() + '/' + 
                       String(date.getMonth() + 1).padStart(2, '0') + '/' + 
                       String(date.getDate()).padStart(2, '0');
        document.getElementById('expected_hatch_display').value = formatted;
    }
}

// Initialize on page load
calculateHatchDate();
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
