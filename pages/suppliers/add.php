<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'suppliers';
$pageTitle = 'زیادکردنی دابینکەر';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($name)) {
        $message = 'تکایە ناوی دابینکەر بنووسە';
        $messageType = 'danger';
    } else {
        $db->query("INSERT INTO suppliers (name, phone, address, notes, created_at) VALUES (:name, :phone, :address, :notes, NOW())");
        $db->bind(':name', $name);
        $db->bind(':phone', $phone);
        $db->bind(':address', $address);
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
        <h2><i class="fas fa-user-plus"></i> زیادکردنی دابینکەر</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">دابینکەران</a></li>
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

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-truck"></i> زانیاری دابینکەر
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ناو <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">ژمارەی مۆبایل</label>
                            <input type="tel" name="phone" class="form-control" dir="ltr">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">ناونیشان</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">تێبینی</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
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
