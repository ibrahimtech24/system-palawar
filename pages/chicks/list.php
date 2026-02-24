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
               f.batch_name as female_batch, m.batch_name as male_batch,
               cu.name as customer_name
        FROM chicks c 
        LEFT JOIN eggs e ON c.egg_id = e.id 
        LEFT JOIN female_birds f ON e.female_bird_id = f.id
        LEFT JOIN male_birds m ON e.male_bird_id = m.id
        LEFT JOIN customers cu ON c.customer_id = cu.id
        WHERE c.quantity > 0 AND (c.quantity - c.dead_count) > 0 AND c.status != 'sold'
        AND (c.customer_id IS NULL OR c.customer_id = 0)
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

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle"></i>
    <?php if ($_GET['error'] == 'sales'): ?>
        ناتوانرێت ئەم جوجکەیە بسڕدرێتەوە چونکە لە فرۆشتندا بەکارهاتووە!
    <?php else: ?>
        هەڵەیەک ڕوویدا
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
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
            <table class="table data-table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th class="text-center"><i class="fas fa-venus text-danger"></i> دایک</th>
                        <th class="text-center"><i class="fas fa-mars text-primary"></i> باوک</th>
                        <th class="text-center">کۆی ژمارە</th>
                        <th class="text-center">زیندوو</th>
                        <th class="text-center">مردوو</th>
                        <th class="text-center">تەمەن</th>
                        <th class="text-center" style="width: 100px;">کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chicks as $index => $chick): 
                        $aliveCount = $chick['quantity'] - $chick['dead_count'];
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $index + 1; ?></td>
                        <td class="text-center">
                            <?php if (!empty($chick['female_batch'])): ?>
                                <span class="badge bg-danger-subtle text-danger"><?php echo htmlspecialchars($chick['female_batch']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($chick['male_batch'])): ?>
                                <span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($chick['male_batch']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><strong><?php echo number_format($chick['quantity']); ?></strong></td>
                        <td class="text-center"><span class="badge bg-success fs-6"><?php echo number_format($aliveCount); ?></span></td>
                        <td class="text-center">
                            <?php if ($chick['dead_count'] > 0): ?>
                                <span class="badge bg-danger"><?php echo number_format($chick['dead_count']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?php echo calculateAge($chick['hatch_date']); ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="edit.php?id=<?php echo $chick['id']; ?>" class="btn btn-outline-primary" title="دەستکاری">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" onclick="return confirmDelete('delete.php?id=<?php echo $chick['id']; ?>', 'ئایا دڵنیایت لە سڕینەوەی ئەم تۆمارە؟')" class="btn btn-outline-danger" title="سڕینەوە">
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
