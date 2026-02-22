<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'production';
$pageTitle = 'لیستی هێلکەکان';

// Get eggs with female bird info
$db->query("SELECT e.*, f.batch_name as female_batch 
            FROM eggs e 
            LEFT JOIN female_birds f ON e.female_bird_id = f.id 
            ORDER BY e.collection_date DESC");
$eggs = $db->resultSet();

// Calculate totals
$totalEggs = 0;
$totalDamaged = 0;
foreach ($eggs as $egg) {
    $totalEggs += $egg['quantity'];
    $totalDamaged += $egg['damaged_count'];
}
$totalHealthy = $totalEggs - $totalDamaged;

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-egg"></i> لیستی هێلکەکان</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">هێلکە</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="add.php" class="btn btn-success">
            <i class="fas fa-plus"></i> زیادکردن
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> کردارەکە بە سەرکەوتوویی ئەنجامدرا
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-warning alert-dismissible fade show">
    <i class="fas fa-trash"></i> تۆمارەکە سڕایەوە
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon bg-primary">
                <i class="fas fa-egg"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalEggs); ?></h3>
                <p>کۆی هێلکە</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalHealthy); ?></h3>
                <p>هێلکەی ساغ</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalDamaged); ?></h3>
                <p>هێلکەی خراپ</p>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header bg-warning-gradient">
        <i class="fas fa-list"></i> تۆمارەکانی هێلکە (<?php echo count($eggs); ?>)
    </div>
    <div class="card-body">
        <?php if (count($eggs) > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>هەوێردەی مێ</th>
                        <th>ژمارە</th>
                        <th>ساغ</th>
                        <th>خراپ</th>
                        <th>بەرواری کۆکردنەوە</th>
                        <th>تێبینی</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eggs as $index => $egg): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo $egg['female_batch'] ?: 'نەناسراو'; ?></td>
                        <td><strong><?php echo $egg['quantity']; ?></strong></td>
                        <td><span class="text-success"><?php echo $egg['quantity'] - $egg['damaged_count']; ?></span></td>
                        <td><span class="text-danger"><?php echo $egg['damaged_count']; ?></span></td>
                        <td><?php echo formatDate($egg['collection_date']); ?></td>
                        <td><?php echo $egg['notes'] ?: '-'; ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?php echo $egg['id']; ?>" class="btn btn-sm btn-outline-primary" title="دەستکاری">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $egg['id']; ?>" onclick="return confirm('ئایا دڵنیایت لە سڕینەوەی ئەم تۆمارە؟')" class="btn btn-sm btn-outline-danger" title="سڕینەوە">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-egg"></i>
            <h4>هیچ تۆمارێکی هێلکە نیە</h4>
            <p>هیچ هێلکەیەک تۆمار نەکراوە</p>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> زیادکردن
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
