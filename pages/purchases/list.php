<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'purchases';
$pageTitle = 'کڕینەکان';

// Get all purchases with supplier info
$db->query("SELECT p.*, s.name as supplier_name 
            FROM purchases p 
            LEFT JOIN suppliers s ON p.supplier_id = s.id 
            ORDER BY p.created_at DESC");
$purchases = $db->resultSet();

// Calculate totals
$totalPurchases = 0;
foreach ($purchases as $purchase) {
    $totalPurchases += $purchase['total_price'];
}

$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    $message = 'کڕینەکە بە سەرکەوتوویی تۆمارکرا';
    $messageType = 'success';
}

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-shopping-bag"></i> کڕینەکان</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">کڕینەکان</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="add.php" class="btn btn-warning">
            <i class="fas fa-plus"></i> کڕینی نوێ
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
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">کۆی کڕینەکان</h6>
                        <h3 class="mb-0"><?php echo number_format($totalPurchases); ?> <?php echo CURRENCY; ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-50">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">ژمارەی کڕین</h6>
                        <h3 class="mb-0"><?php echo count($purchases); ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">تێکڕای کڕین</h6>
                        <h3 class="mb-0"><?php echo count($purchases) > 0 ? number_format($totalPurchases / count($purchases)) : 0; ?> <?php echo CURRENCY; ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Purchases Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> لیستی کڕینەکان
    </div>
    <div class="card-body">
        <?php if (empty($purchases)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
            <h5>هیچ کڕینێک نییە</h5>
            <p class="text-muted">بۆ زیادکردنی کڕینی نوێ کلیک لە دوگمەی خوارەوە بکە</p>
            <a href="add.php" class="btn btn-warning">
                <i class="fas fa-plus"></i> کڕینی نوێ
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>کۆدی کڕین</th>
                        <th>دابینکەر</th>
                        <th>جۆری کالا</th>
                        <th>ژمارە</th>
                        <th>نرخی یەکە</th>
                        <th>کۆی نرخ</th>
                        <th>بەروار</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $index => $purchase): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><span class="badge bg-secondary"><?php echo $purchase['purchase_code']; ?></span></td>
                        <td><?php echo $purchase['supplier_name'] ?? '<span class="text-muted">نەناسراو</span>'; ?></td>
                        <td><?php echo getItemTypeName($purchase['item_type']); ?></td>
                        <td><?php echo number_format($purchase['quantity']); ?></td>
                        <td><?php echo number_format($purchase['unit_price']); ?> <?php echo CURRENCY; ?></td>
                        <td><strong><?php echo number_format($purchase['total_price']); ?> <?php echo CURRENCY; ?></strong></td>
                        <td><?php echo date('Y/m/d', strtotime($purchase['purchase_date'])); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="edit.php?id=<?php echo $purchase['id']; ?>" class="btn btn-outline-primary" title="دەستکاری">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $purchase['id']; ?>" class="btn btn-outline-danger" title="سڕینەوە" onclick="return confirm('ئایا دڵنیایت لە سڕینەوەی ئەم کڕینە؟');">
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
