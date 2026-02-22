<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'birds';
$pageTitle = 'دەستکاری هەوێردەی مێ';

$id = $_GET['id'] ?? 0;

// Get bird data
$db->query("SELECT * FROM female_birds WHERE id = :id");
$db->bind(':id', $id);
$bird = $db->single();

if (!$bird) {
    header('Location: female_list.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batchName = $_POST['batch_name'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $entryDate = $_POST['entry_date'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($batchName) || $quantity <= 0 || empty($entryDate)) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        $db->query("UPDATE female_birds SET batch_name = :name, quantity = :qty, entry_date = :date, notes = :notes WHERE id = :id");
        $db->bind(':name', $batchName);
        $db->bind(':qty', $quantity);
        $db->bind(':date', $entryDate);
        $db->bind(':notes', $notes);
        $db->bind(':id', $id);
        
        if ($db->execute()) {
            $message = 'زانیارییەکان بە سەرکەوتوویی نوێکرانەوە';
            $messageType = 'success';
            // Refresh data
            $db->query("SELECT * FROM female_birds WHERE id = :id");
            $db->bind(':id', $id);
            $bird = $db->single();
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
        <h2><i class="fas fa-edit"></i> دەستکاری هەوێردەی مێ</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="female_list.php">هەوێردەی مێ</a></li>
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
            <div class="card-header bg-danger-gradient">
                <i class="fas fa-venus"></i> دەستکاری گرووپ: <?php echo $bird['batch_name']; ?>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ناوی گرووپ <span class="text-danger">*</span></label>
                            <input type="text" name="batch_name" class="form-control" value="<?php echo $bird['batch_name']; ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">ژمارە <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" value="<?php echo $bird['quantity']; ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری هاتن <span class="text-danger">*</span></label>
                            <input type="date" name="entry_date" class="form-control" value="<?php echo $bird['entry_date']; ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo $bird['notes']; ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i>
                                <strong>تەمەن:</strong> <?php echo calculateAge($bird['entry_date']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="female_list.php" class="btn btn-secondary">
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
