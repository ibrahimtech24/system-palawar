<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'production';
$pageTitle = 'زیادکردنی هێلکە';

// Check and add male_bird_id column if not exists
$db->query("SHOW COLUMNS FROM eggs LIKE 'male_bird_id'");
if (!$db->single()) {
    $db->query("ALTER TABLE eggs ADD COLUMN male_bird_id INT NULL AFTER female_bird_id");
    $db->execute();
}

// Get female birds for dropdown
$db->query("SELECT id, batch_name FROM female_birds WHERE status != 'dead' ORDER BY batch_name");
$femaleBirds = $db->resultSet();

// Get male birds for dropdown
$db->query("SELECT id, batch_name FROM male_birds WHERE status != 'dead' ORDER BY batch_name");
$maleBirds = $db->resultSet();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $female_bird_id = !empty($_POST['female_bird_id']) ? intval($_POST['female_bird_id']) : null;
    $male_bird_id = !empty($_POST['male_bird_id']) ? intval($_POST['male_bird_id']) : null;
    $quantity = intval($_POST['quantity'] ?? 0);
    $damaged_count = intval($_POST['damaged_count'] ?? 0);
    $collection_date = $_POST['collection_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    
    if ($quantity <= 0) {
        $message = 'تکایە ژمارەی هێلکە بنووسە';
        $messageType = 'danger';
    } else {
        $db->query("INSERT INTO eggs (female_bird_id, male_bird_id, quantity, damaged_count, collection_date, notes, created_at) 
                    VALUES (:female_bird_id, :male_bird_id, :quantity, :damaged_count, :collection_date, :notes, NOW())");
        $db->bind(':female_bird_id', $female_bird_id);
        $db->bind(':male_bird_id', $male_bird_id);
        $db->bind(':quantity', $quantity);
        $db->bind(':damaged_count', $damaged_count);
        $db->bind(':collection_date', $collection_date);
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
        <h2><i class="fas fa-plus"></i> زیادکردنی هێلکە</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">هێلکە</a></li>
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
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-warning-gradient">
                <i class="fas fa-egg"></i> زانیاری هێلکە
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-venus text-danger"></i> گرووپی مێیەکان</label>
                            <select name="female_bird_id" class="form-select">
                                <option value="">-- هەڵبژێرە --</option>
                                <?php foreach ($femaleBirds as $bird): ?>
                                <option value="<?php echo $bird['id']; ?>"><?php echo $bird['batch_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">گرووپی مێیەکان کە هێلکەیان کردووە</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-mars text-primary"></i> گرووپی نێرەکان</label>
                            <select name="male_bird_id" class="form-select">
                                <option value="">-- هەڵبژێرە --</option>
                                <?php foreach ($maleBirds as $bird): ?>
                                <option value="<?php echo $bird['id']; ?>"><?php echo $bird['batch_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">گرووپی نێرەکان (باوک)</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">ژمارەی هێلکە <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" placeholder="ژمارەی هێلکە" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">هێلکەی خراپ</label>
                            <input type="number" name="damaged_count" class="form-control" min="0" value="0" placeholder="ژمارەی هێلکەی خراپ">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری کۆکردنەوە <span class="text-danger">*</span></label>
                            <input type="date" name="collection_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
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
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> تۆمارکردن
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
