<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'production';
$pageTitle = 'لیستی هێلکەکان';

// Check and add male_bird_id column if not exists
$db->query("SHOW COLUMNS FROM eggs LIKE 'male_bird_id'");
if (!$db->single()) {
    $db->query("ALTER TABLE eggs ADD COLUMN male_bird_id INT NULL AFTER female_bird_id");
    $db->execute();
}

// Get eggs with bird info
// Only show eggs that have healthy eggs remaining (quantity - damaged_count > 0)
$db->query("SELECT e.*, f.batch_name as female_batch, m.batch_name as male_batch 
            FROM eggs e 
            LEFT JOIN female_birds f ON e.female_bird_id = f.id 
            LEFT JOIN male_birds m ON e.male_bird_id = m.id 
            WHERE e.quantity > 0 AND (e.quantity - e.damaged_count) > 0
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

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle"></i>
    <?php if ($_GET['error'] == 'incubator'): ?>
        ناتوانرێت ئەم هێلکەیە بسڕدرێتەوە چونکە لە مەفقەسدایە!
    <?php elseif ($_GET['error'] == 'sales'): ?>
        ناتوانرێت ئەم هێلکەیە بسڕدرێتەوە چونکە لە فرۆشتندا بەکارهاتووە!
    <?php else: ?>
        هەڵەیەک ڕوویدا
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
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
            <table class="table data-table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th class="text-center"><i class="fas fa-venus text-danger"></i> دایک</th>
                        <th class="text-center"><i class="fas fa-mars text-primary"></i> باوک</th>
                        <th class="text-center">کۆی ژمارە</th>
                        <th class="text-center">ساغ</th>
                        <th class="text-center">خراپ</th>
                        <th class="text-center">بەرواری کۆکردنەوە</th>
                        <th class="text-center" style="width: 100px;">کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eggs as $index => $egg): 
                        $healthyCount = $egg['quantity'] - $egg['damaged_count'];
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $index + 1; ?></td>
                        <td class="text-center">
                            <?php if ($egg['female_batch']): ?>
                                <span class="badge bg-danger-subtle text-danger"><?php echo htmlspecialchars($egg['female_batch']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($egg['male_batch']): ?>
                                <span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($egg['male_batch']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><strong><?php echo number_format($egg['quantity']); ?></strong></td>
                        <td class="text-center"><span class="badge bg-success fs-6"><?php echo number_format($healthyCount); ?></span></td>
                        <td class="text-center">
                            <?php if ($egg['damaged_count'] > 0): ?>
                                <span class="badge bg-warning text-dark"><?php echo number_format($egg['damaged_count']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?php echo formatDate($egg['collection_date']); ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="edit.php?id=<?php echo $egg['id']; ?>" class="btn btn-outline-primary" title="دەستکاری">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" onclick="return confirmDelete('delete.php?id=<?php echo $egg['id']; ?>', 'ئایا دڵنیایت لە سڕینەوەی ئەم تۆمارە؟')" class="btn btn-outline-danger" title="سڕینەوە">
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
