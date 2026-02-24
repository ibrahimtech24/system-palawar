<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'warehouse';
$pageTitle = 'مەخزەن';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->query("DELETE FROM warehouse WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();
    header('Location: list.php?deleted=1');
    exit;
}

// Get all items
$db->query("SELECT * FROM warehouse ORDER BY created_at DESC");
$items = $db->resultSet();

// Calculate totals
$totalValue = 0;
$lowStockCount = 0;
foreach ($items as $item) {
    $totalValue += ($item['quantity'] * $item['unit_price']);
    if ($item['min_quantity'] > 0 && $item['quantity'] <= $item['min_quantity']) {
        $lowStockCount++;
    }
}

$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    $message = 'کاڵاکە بە سەرکەوتوویی زیادکرا';
    $messageType = 'success';
}
if (isset($_GET['deleted'])) {
    $message = 'کاڵاکە بە سەرکەوتوویی سڕایەوە';
    $messageType = 'success';
}

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-warehouse"></i> مەخزەن</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">مەخزەن</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="add.php" class="btn btn-success">
            <i class="fas fa-plus"></i> زیادکردنی کاڵا
        </a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">کۆی کاڵاکان</h6>
                        <h3 class="mb-0"><?php echo count($items); ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">کۆی بەها</h6>
                        <h3 class="mb-0"><?php echo number_format($totalValue); ?> <?php echo CURRENCY; ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-<?php echo $lowStockCount > 0 ? 'danger' : 'secondary'; ?> text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">کاڵای کەم</h6>
                        <h3 class="mb-0"><?php echo $lowStockCount; ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Items Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> لیستی کاڵاکان
    </div>
    <div class="card-body">
        <?php if (empty($items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-warehouse fa-4x text-muted mb-3"></i>
            <h5>هیچ کاڵایەک نییە</h5>
            <p class="text-muted">بۆ زیادکردنی کاڵای نوێ کلیک لە دوگمەی خوارەوە بکە</p>
            <a href="add.php" class="btn btn-success">
                <i class="fas fa-plus"></i> زیادکردنی کاڵا
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ناوی کاڵا</th>
                        <th>ژمارە</th>
                        <th>یەکە</th>
                        <th>نرخی یەکە</th>
                        <th>کۆی بەها</th>
                        <th>بارودۆخ</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): 
                        $isLowStock = $item['min_quantity'] > 0 && $item['quantity'] <= $item['min_quantity'];
                        $totalItemValue = $item['quantity'] * $item['unit_price'];
                    ?>
                    <tr class="<?php echo $isLowStock ? 'table-danger' : ''; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td><strong><?php echo $item['item_name']; ?></strong></td>
                        <td><?php echo number_format($item['quantity']); ?></td>
                        <td><?php echo $item['unit']; ?></td>
                        <td><?php echo number_format($item['unit_price']); ?> <?php echo CURRENCY; ?></td>
                        <td><strong><?php echo number_format($totalItemValue); ?> <?php echo CURRENCY; ?></strong></td>
                        <td>
                            <?php if ($isLowStock): ?>
                            <span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> کەمە</span>
                            <?php else: ?>
                            <span class="badge bg-success"><i class="fas fa-check"></i> باشە</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-primary" title="دەستکاری">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" onclick="return confirmDelete('list.php?delete=<?php echo $item['id']; ?>', 'ئایا دڵنیایت لە سڕینەوەی ئەم کاڵایە؟')" class="btn btn-outline-danger" title="سڕینەوە">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
