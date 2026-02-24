<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'incubator';
$pageTitle = 'هەڵاتنی هێلکە';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: list.php');
    exit;
}

// Get incubator record
$db->query("SELECT i.*, e.female_bird_id, e.male_bird_id,
                   f.batch_name as female_batch, m.batch_name as male_batch,
                   cu.name as customer_name, cu.phone as customer_phone
            FROM incubator i 
            LEFT JOIN eggs e ON i.egg_id = e.id 
            LEFT JOIN female_birds f ON e.female_bird_id = f.id
            LEFT JOIN male_birds m ON e.male_bird_id = m.id
            LEFT JOIN customers cu ON i.customer_id = cu.id
            WHERE i.id = :id");
$db->bind(':id', $id);
$incubatorItem = $db->single();

if (!$incubatorItem) {
    header('Location: list.php');
    exit;
}

if ($incubatorItem['status'] === 'hatched') {
    header('Location: list.php');
    exit;
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hatched_count = intval($_POST['hatched_count'] ?? 0);
    $hatch_date = $_POST['hatch_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    
    $totalEggs = $incubatorItem['egg_quantity'];
    $damaged_count = $totalEggs - $hatched_count;
    
    if ($hatched_count < 0) {
        $message = 'ژمارەی هێلکەی هەڵاتوو نابێت کەمتر لە سفر بێت';
        $messageType = 'danger';
    } elseif ($hatched_count > $totalEggs) {
        $message = 'ژمارەی هێلکەی هەڵاتوو نابێت زیاتر لە کۆی هێلکەکان بێت (' . $totalEggs . ')';
        $messageType = 'danger';
    } else {
        // Update incubator record
        $db->query("UPDATE incubator SET status = 'hatched', hatched_count = :hatched, damaged_count = :damaged, 
                    notes = CONCAT(COALESCE(notes, ''), :notes), updated_at = NOW() 
                    WHERE id = :id");
        $db->bind(':hatched', $hatched_count);
        $db->bind(':damaged', $damaged_count);
        $db->bind(':notes', ($notes ? "\n" . $notes : ''));
        $db->bind(':id', $id);
        
        if ($db->execute()) {
            // Add hatched chicks to chicks table
            if ($hatched_count > 0) {
                $batchName = $incubatorItem['group_name'] . ' - جوجکە';
                $customerId = $incubatorItem['customer_id'] ?? null;
                $ownerNote = $customerId ? 'بۆ کڕیار: ' . ($incubatorItem['customer_name'] ?? '') . ' - ' : 'بۆ خۆمان - ';
                $db->query("INSERT INTO chicks (batch_name, egg_id, incubator_id, customer_id, quantity, dead_count, hatch_date, status, notes, created_at) 
                            VALUES (:batch_name, :egg_id, :incubator_id, :customer_id, :quantity, 0, :hatch_date, 'active', :notes, NOW())");
                $db->bind(':batch_name', $batchName);
                $db->bind(':egg_id', $incubatorItem['egg_id']);
                $db->bind(':incubator_id', $id);
                $db->bind(':customer_id', $customerId);
                $db->bind(':quantity', $hatched_count);
                $db->bind(':hatch_date', $hatch_date);
                $db->bind(':notes', $ownerNote . 'لە مەفقەس هەڵاتوو - ' . $incubatorItem['group_name']);
                $db->execute();
            }
            
            header('Location: list.php?success=hatched');
            exit;
        } else {
            $message = 'هەڵەیەک ڕوویدا';
            $messageType = 'danger';
        }
    }
}

// Calculate days info
$entryDate = new DateTime($incubatorItem['entry_date']);
$hatchDate = new DateTime($incubatorItem['expected_hatch_date']);
$today = new DateTime();
$daysLeft = (int)$today->diff($hatchDate)->format('%r%a');
$daysPassed = 17 - max(0, $daysLeft);

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-kiwi-bird"></i> هەڵاتنی هێلکە</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">مەفقەس</a></li>
                <li class="breadcrumb-item active">هەڵاتن</li>
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

<?php if ($daysLeft > 0): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>ئاگاداری:</strong> هێشتا <strong><?php echo $daysLeft; ?></strong> ڕۆژ ماوە بۆ کاتی دەرچوونی هێلکەکان. 
    ئایا دڵنیایت دەتەوێت ئێستا هەڵاتن تۆمار بکەیت؟
</div>
<?php endif; ?>

<!-- Incubator Info -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Current Status Card -->
        <div class="card mb-4 border-<?php echo $daysLeft <= 0 ? 'success' : 'warning'; ?>">
            <div class="card-header bg-<?php echo $daysLeft <= 0 ? 'success' : 'warning'; ?> text-white">
                <i class="fas fa-info-circle"></i> زانیاری گرووپ: <?php echo htmlspecialchars($incubatorItem['group_name']); ?>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3 col-6 text-center">
                        <div class="p-3 bg-light rounded">
                            <h4 class="text-primary mb-1"><?php echo number_format($incubatorItem['egg_quantity']); ?></h4>
                            <small class="text-muted">کۆی هێلکە</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 text-center">
                        <div class="p-3 bg-light rounded">
                            <h4 class="text-info mb-1"><?php echo formatDate($incubatorItem['entry_date']); ?></h4>
                            <small class="text-muted">ڕۆژی دانان</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 text-center">
                        <div class="p-3 bg-light rounded">
                            <h4 class="text-success mb-1"><?php echo formatDate($incubatorItem['expected_hatch_date']); ?></h4>
                            <small class="text-muted">ڕۆژی دەرچوون</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 text-center">
                        <div class="p-3 bg-light rounded">
                            <h4 class="<?php echo $daysLeft <= 0 ? 'text-success' : 'text-warning'; ?> mb-1">
                                <?php echo $daysLeft <= 0 ? 'ئامادەیە!' : $daysLeft . ' ڕۆژ'; ?>
                            </h4>
                            <small class="text-muted"><?php echo $daysLeft <= 0 ? 'دەرچوون' : 'ماوە'; ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3 pt-3 border-top">
                    <?php if ($incubatorItem['customer_name']): ?>
                    <span class="badge bg-success-subtle text-success me-2">
                        <i class="fas fa-user-tie"></i> کڕیار: <?php echo htmlspecialchars($incubatorItem['customer_name']); ?>
                        <?php if ($incubatorItem['customer_phone']): ?>(<?php echo $incubatorItem['customer_phone']; ?>)<?php endif; ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($incubatorItem['female_batch']): ?>
                    <span class="badge bg-danger-subtle text-danger me-2">
                        <i class="fas fa-venus"></i> دایک: <?php echo htmlspecialchars($incubatorItem['female_batch']); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($incubatorItem['male_batch']): ?>
                    <span class="badge bg-primary-subtle text-primary">
                        <i class="fas fa-mars"></i> باوک: <?php echo htmlspecialchars($incubatorItem['male_batch']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Hatch Form -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="fas fa-kiwi-bird"></i> تۆمارکردنی هەڵاتن
            </div>
            <div class="card-body">
                <div class="alert alert-light border">
                    <i class="fas fa-lightbulb text-warning"></i>
                    <strong>ڕێنمایی:</strong> ئەو ژمارە هێلکانەی هەڵاتوون بنووسە. پاشماوەکە بەشکل ئۆتۆماتیکی وەک خەسارە تۆمار دەکرێت 
                    و هێلکەکانی هەڵاتوو وەک جوجکە لە بەشی جوجکەکان زیاد دەکرێت.
                    <br><br>
                    <strong>نمونە:</strong> ئەگەر <?php echo $incubatorItem['egg_quantity']; ?> هێلکەت هەبووبێت و 
                    <?php echo max(1, $incubatorItem['egg_quantity'] - intval($incubatorItem['egg_quantity'] * 0.1)); ?> دانەی هەڵاتبێت، 
                    ئەوا <?php echo intval($incubatorItem['egg_quantity'] * 0.1); ?> دانە وەک خەسارە تۆمار دەکرێت.
                </div>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ژمارەی هێلکەی هەڵاتوو (جوجکە) <span class="text-danger">*</span></label>
                            <input type="number" name="hatched_count" id="hatched_count" class="form-control form-control-lg" 
                                   min="0" max="<?php echo $incubatorItem['egg_quantity']; ?>" 
                                   placeholder="چەند دانە هەڵاتوو؟" required
                                   oninput="calculateDamaged()">
                            <small class="text-muted">لە کۆی <?php echo number_format($incubatorItem['egg_quantity']); ?> هێلکە</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-times-circle text-danger"></i> خەسارە (ئۆتۆماتیکی)</label>
                            <input type="text" id="damaged_display" class="form-control form-control-lg bg-light" readonly value="0">
                            <small class="text-muted">ئەو هێلکانەی هەڵنەاتوون</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری هەڵاتن <span class="text-danger">*</span></label>
                            <input type="date" name="hatch_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="تێبینی دڵخوازانە..."></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Preview -->
                    <div id="resultPreview" class="alert alert-info d-none">
                        <h6><i class="fas fa-eye"></i> پێشبینی ئەنجام:</h6>
                        <ul class="mb-0">
                            <li><i class="fas fa-kiwi-bird text-success"></i> <span id="previewChicks">0</span> جوجکە زیاد دەکرێت بۆ بەشی جوجکەکان</li>
                            <li><i class="fas fa-times-circle text-danger"></i> <span id="previewDamaged">0</span> هێلکە وەک خەسارە تۆمار دەکرێت</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> گەڕانەوە
                        </a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check"></i> تۆمارکردنی هەڵاتن
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function calculateDamaged() {
    var total = <?php echo $incubatorItem['egg_quantity']; ?>;
    var hatched = parseInt(document.getElementById('hatched_count').value) || 0;
    var damaged = total - hatched;
    
    if (hatched < 0) hatched = 0;
    if (hatched > total) {
        hatched = total;
        document.getElementById('hatched_count').value = total;
    }
    
    damaged = Math.max(0, damaged);
    document.getElementById('damaged_display').value = damaged;
    
    // Update preview
    var preview = document.getElementById('resultPreview');
    document.getElementById('previewChicks').textContent = hatched;
    document.getElementById('previewDamaged').textContent = damaged;
    
    if (hatched > 0 || damaged > 0) {
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
    }
}
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
