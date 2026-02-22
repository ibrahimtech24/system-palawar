<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'production';
$pageTitle = 'لیستی جوجکەکان';

// Get filter
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$sql = "SELECT * FROM chicks WHERE 1=1";
if ($status) {
    $sql .= " AND status = :status";
}
$sql .= " ORDER BY hatch_date DESC";

$db->query($sql);
if ($status) {
    $db->bind(':status', $status);
}
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

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">بار</label>
                <select name="status" class="form-select">
                    <option value="">هەموو</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>چالاک</option>
                    <option value="sold" <?php echo $status === 'sold' ? 'selected' : ''; ?>>فرۆشراو</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> فلتەر
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> ڕیسێت
                </a>
            </div>
        </form>
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
                        <th>ناوی گرووپ</th>
                        <th>ژمارە</th>
                        <th>زیندوو</th>
                        <th>مردوو</th>
                        <th>تەمەن</th>
                        <th>بار</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chicks as $index => $chick): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><strong><?php echo $chick['batch_name']; ?></strong></td>
                        <td><?php echo $chick['quantity']; ?></td>
                        <td><span class="text-success"><?php echo $chick['quantity'] - $chick['dead_count']; ?></span></td>
                        <td><span class="text-danger"><?php echo $chick['dead_count']; ?></span></td>
                        <td><?php echo calculateAge($chick['hatch_date']); ?></td>
                        <td><?php echo getStatusBadge($chick['status']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?php echo $chick['id']; ?>" class="btn btn-sm btn-outline-primary" title="دەستکاری">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDelete(<?php echo $chick['id']; ?>, '<?php echo $chick['batch_name']; ?>')" class="btn btn-sm btn-outline-danger" title="سڕینەوە">
                                    <i class="fas fa-trash"></i>
                                </button>
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> دڵنیابوونەوە</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>ئایا دڵنیایت لە سڕینەوەی ئەم گرووپە؟</p>
                <p class="fw-bold text-danger" id="deleteItemName"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">نەخێر</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">بەڵێ، بیسڕەوە</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteItemName').textContent = name;
    document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
