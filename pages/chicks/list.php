<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'production';
$pageTitle = 'لیستی جوجکەکان';

// Check and add male_bird_id column if not exists
$db->query("SHOW COLUMNS FROM eggs LIKE 'male_bird_id'");
if (!$db->single()) {
    $db->query("ALTER TABLE eggs ADD COLUMN male_bird_id INT NULL AFTER female_bird_id");
    $db->execute();
}

// Build query - join with eggs and birds to get parent info
// Only show groups that have alive chicks (quantity - dead_count > 0) and not sold
$sql = "SELECT c.*, e.quantity as egg_quantity, e.collection_date as egg_date,
               f.batch_name as female_batch, m.batch_name as male_batch
        FROM chicks c 
        LEFT JOIN eggs e ON c.egg_id = e.id 
        LEFT JOIN female_birds f ON e.female_bird_id = f.id
        LEFT JOIN male_birds m ON e.male_bird_id = m.id
        WHERE (c.quantity - c.dead_count) > 0 AND c.status != 'sold'
        ORDER BY c.hatch_date DESC";

$db->query($sql);
$chicks = $db->resultSet();

// Calculate totals
$totalChicks = 0;
$totalDead = 0;
foreach ($chicks as $chick) {
    $totalChicks += $chick['quantity'];
    $totalDead += $chick['dead_count'];
}
$totalAlive = $totalChicks - $totalDead;

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-kiwi-bird"></i> لیستی جوجکەکان</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">جوجکە</li>
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
                <i class="fas fa-kiwi-bird"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalChicks); ?></h3>
                <p>کۆی جوجکە</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="fas fa-heartbeat"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalAlive); ?></h3>
                <p>زیندوو</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="fas fa-skull"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalDead); ?></h3>
                <p>مردوو</p>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header bg-success-gradient">
        <i class="fas fa-list"></i> گرووپەکانی جوجکە (<?php echo count($chicks); ?>)
    </div>
    <div class="card-body">
        <?php if (count($chicks) > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><i class="fas fa-venus text-danger"></i> دایک</th>
                        <th><i class="fas fa-mars text-primary"></i> باوک</th>
                        <th>ژمارە</th>
                        <th>زیندوو</th>
                        <th>مردوو</th>
                        <th>تەمەن</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chicks as $index => $chick): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <?php if (!empty($chick['female_batch'])): ?>
                                <span class="badge bg-danger-subtle text-danger"><?php echo $chick['female_batch']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($chick['male_batch'])): ?>
                                <span class="badge bg-primary-subtle text-primary"><?php echo $chick['male_batch']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo $chick['quantity']; ?></strong></td>
                        <td><span class="badge bg-success"><?php echo $chick['quantity'] - $chick['dead_count']; ?></span></td>
                        <td><span class="badge bg-danger"><?php echo $chick['dead_count']; ?></span></td>
                        <td><?php echo calculateAge($chick['hatch_date']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?php echo $chick['id']; ?>" class="btn btn-sm btn-outline-primary" title="دەستکاری">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $chick['id']; ?>" onclick="return confirm('ئایا دڵنیایت لە سڕینەوەی ئەم تۆمارە؟')" class="btn btn-sm btn-outline-danger" title="سڕینەوە">
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
            <i class="fas fa-kiwi-bird"></i>
            <h4>هیچ جوجکەیەک نیە</h4>
            <p>هیچ جوجکەیەک تۆمار نەکراوە</p>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> زیادکردن
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
