<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'production';
$pageTitle = 'دەستکاری جوجکە';

$id = $_GET['id'] ?? 0;

// Get chick data
$db->query("SELECT * FROM chicks WHERE id = :id");
$db->bind(':id', $id);
$chick = $db->single();

if (!$chick) {
    header('Location: list.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batchName = $_POST['batch_name'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $deadCount = intval($_POST['dead_count'] ?? 0);
    $hatchDate = $_POST['hatch_date'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($batchName) || $quantity <= 0 || empty($hatchDate)) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        $db->query("UPDATE chicks SET batch_name = :name, quantity = :qty, dead_count = :dead, hatch_date = :date, status = :status, notes = :notes WHERE id = :id");
        $db->bind(':name', $batchName);
        $db->bind(':qty', $quantity);
        $db->bind(':dead', $deadCount);
        $db->bind(':date', $hatchDate);
        $db->bind(':status', $status);
        $db->bind(':notes', $notes);
        $db->bind(':id', $id);
        
        if ($db->execute()) {
            $message = 'زانیارییەکان بە سەرکەوتوویی نوێکرانەوە';
            $messageType = 'success';
            $db->query("SELECT * FROM chicks WHERE id = :id");
            $db->bind(':id', $id);
            $chick = $db->single();
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
        <h2><i class="fas fa-edit"></i> دەستکاری جوجکە</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">جوجکەکان</a></li>
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
            <div class="card-header bg-success-gradient">
                <i class="fas fa-kiwi-bird"></i> دەستکاری گرووپ: <?php echo $chick['batch_name']; ?>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ناوی گرووپ <span class="text-danger">*</span></label>
                            <input type="text" name="batch_name" class="form-control" value="<?php echo $chick['batch_name']; ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">ژمارە <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" value="<?php echo $chick['quantity']; ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">ژمارەی مردوو</label>
                            <input type="number" name="dead_count" class="form-control" min="0" value="<?php echo $chick['dead_count']; ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بەرواری دەرچوون <span class="text-danger">*</span></label>
                            <input type="date" name="hatch_date" class="form-control" value="<?php echo $chick['hatch_date']; ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">بار</label>
                            <select name="status" class="form-select">
                                <option value="active" <?php echo $chick['status'] == 'active' ? 'selected' : ''; ?>>چالاک</option>
                                <option value="sold" <?php echo $chick['status'] == 'sold' ? 'selected' : ''; ?>>فرۆشراو</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo $chick['notes']; ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i>
                                <strong>تەمەن:</strong> <?php echo calculateAge($chick['hatch_date']); ?>
                                | <strong>زیندوو:</strong> <?php echo $chick['quantity'] - $chick['dead_count']; ?>
                            </div>
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
