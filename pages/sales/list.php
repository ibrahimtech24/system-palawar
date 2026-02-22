<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'sales';
$pageTitle = 'فرۆشتنەکان';

// Get all sales with customer info
$db->query("SELECT s.*, c.name as customer_name 
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            ORDER BY s.created_at DESC");
$sales = $db->resultSet();

// Calculate totals
$totalSales = 0;
foreach ($sales as $sale) {
    $totalSales += $sale['total_price'];
}

$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    $message = 'فرۆشتن بە سەرکەوتوویی تۆمارکرا';
    $messageType = 'success';
}

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-shopping-cart"></i> فرۆشتنەکان</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">فرۆشتنەکان</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="add.php" class="btn btn-success">
            <i class="fas fa-plus"></i> فرۆشتنی نوێ
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
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">کۆی فرۆشتنەکان</h6>
                        <h3 class="mb-0"><?php echo number_format($totalSales); ?> <?php echo CURRENCY; ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
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
                        <h6 class="mb-1">ژمارەی فرۆشتن</h6>
                        <h3 class="mb-0"><?php echo count($sales); ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">تێکڕای فرۆشتن</h6>
                        <h3 class="mb-0"><?php echo count($sales) > 0 ? number_format($totalSales / count($sales)) : 0; ?> <?php echo CURRENCY; ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> لیستی فرۆشتنەکان
    </div>
    <div class="card-body">
        <?php if (empty($sales)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h5>هیچ فرۆشتنێک نییە</h5>
            <p class="text-muted">بۆ زیادکردنی فرۆشتنی نوێ کلیک لە دوگمەی خوارەوە بکە</p>
            <a href="add.php" class="btn btn-success">
                <i class="fas fa-plus"></i> فرۆشتنی نوێ
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>کۆدی فرۆشتن</th>
                        <th>کڕیار</th>
                        <th>جۆری کالا</th>
                        <th>ژمارە</th>
                        <th>نرخی یەکە</th>
                        <th>کۆی نرخ</th>
                        <th>بەروار</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $index => $sale): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><span class="badge bg-secondary"><?php echo $sale['sale_code']; ?></span></td>
                        <td><?php echo $sale['customer_name'] ?? '<span class="text-muted">نەناسراو</span>'; ?></td>
                        <td><?php echo getItemTypeName($sale['item_type']); ?></td>
                        <td><?php echo number_format($sale['quantity']); ?></td>
                        <td><?php echo number_format($sale['unit_price']); ?> <?php echo CURRENCY; ?></td>
                        <td><strong><?php echo number_format($sale['total_price']); ?> <?php echo CURRENCY; ?></strong></td>
                        <td><?php echo date('Y/m/d', strtotime($sale['sale_date'])); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="receipt.php?id=<?php echo $sale['id']; ?>" class="btn btn-outline-success" title="وەسڵ" target="_blank">
                                    <i class="fas fa-receipt"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $sale['id']; ?>" class="btn btn-outline-primary" title="دەستکاری">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $sale['id']; ?>" class="btn btn-outline-danger" title="سڕینەوە" onclick="return confirm('ئایا دڵنیایت لە سڕینەوەی ئەم فرۆشتنە؟');">
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
