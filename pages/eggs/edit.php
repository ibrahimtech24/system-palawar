<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'production';
$pageTitle = 'دەستکاری هێلکە';

$id = $_GET['id'] ?? 0;

// Check and add male_bird_id column if not exists
$db->query("SHOW COLUMNS FROM eggs LIKE 'male_bird_id'");
if (!$db->single()) {
    $db->query("ALTER TABLE eggs ADD COLUMN male_bird_id INT NULL AFTER female_bird_id");
    $db->execute();
}

// Get egg data
$db->query("SELECT * FROM eggs WHERE id = :id");
$db->bind(':id', $id);
$egg = $db->single();

if (!$egg) {
    header('Location: list.php');
    exit;
}

// Get female birds for dropdown
$db->query("SELECT id, batch_name FROM female_birds WHERE status != 'dead' ORDER BY batch_name");
$femaleBirds = $db->resultSet();

// Get male birds for dropdown
$db->query("SELECT id, batch_name FROM male_birds WHERE status != 'dead' ORDER BY batch_name");
$maleBirds = $db->resultSet();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $femaleBirdId = !empty($_POST['female_bird_id']) ? intval($_POST['female_bird_id']) : null;
    $maleBirdId = !empty($_POST['male_bird_id']) ? intval($_POST['male_bird_id']) : null;
    $quantity = intval($_POST['quantity'] ?? 0);
    $damagedCount = intval($_POST['damaged_count'] ?? 0);
    $collectionDate = $_POST['collection_date'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if ($quantity <= 0 || empty($collectionDate)) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        $db->query("UPDATE eggs SET female_bird_id = :female, male_bird_id = :male, quantity = :qty, damaged_count = :damaged, collection_date = :date, notes = :notes WHERE id = :id");
        $db->bind(':female', $femaleBirdId);
        $db->bind(':male', $maleBirdId);
        $db->bind(':qty', $quantity);
        $db->bind(':damaged', $damagedCount);
        $db->bind(':date', $collectionDate);
        $db->bind(':notes', $notes);
        $db->bind(':id', $id);
        
        if ($db->execute()) {
            $message = 'زانیارییەکان بە سەرکەوتوویی نوێکرانەوە';
            $messageType = 'success';
            $db->query("SELECT * FROM eggs WHERE id = :id");
            $db->bind(':id', $id);
            $egg = $db->single();
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
        <h2><i class="fas fa-edit"></i> دەستکاری هێلکە</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">هێلکەکان</a></li>
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

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-warning-gradient">
                <i class="fas fa-egg"></i> دەستکاری تۆماری هێلکە
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-venus text-danger"></i> گرووپی مێیەکان</label>
                            <select name="female_bird_id" class="form-select">
                                <option value="">-- هەڵبژێرە --</option>
                                <?php foreach ($femaleBirds as $bird): ?>
                                <option value="<?php echo $bird['id']; ?>" <?php echo ($egg['female_bird_id'] ?? '') == $bird['id'] ? 'selected' : ''; ?>>
                                    <?php echo $bird['batch_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">گرووپی مێیەکان کە هێلکەیان کردووە</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-mars text-primary"></i> گرووپی نێرەکان</label>
                            <select name="male_bird_id" class="form-select">
                                <option value="">-- هەڵبژێرە --</option>
                                <?php foreach ($maleBirds as $bird): ?>
                                <option value="<?php echo $bird['id']; ?>" <?php echo ($egg['male_bird_id'] ?? '') == $bird['id'] ? 'selected' : ''; ?>>
                                    <?php echo $bird['batch_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">گرووپی نێرەکان (باوک)</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">ژمارەی هێلکە <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" value="<?php echo $egg['quantity']; ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">هێلکەی خراپ</label>
                            <input type="number" name="damaged_count" class="form-control" min="0" value="<?php echo $egg['damaged_count']; ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری کۆکردنەوە <span class="text-danger">*</span></label>
                            <input type="date" name="collection_date" class="form-control" value="<?php echo $egg['collection_date']; ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo $egg['notes']; ?></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> گەڕانەوە
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> نوێکردنەوە
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
