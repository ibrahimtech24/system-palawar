<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'birds';
$pageTitle = 'لیستی هەوێردەی نێر';

// Get filter
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$sql = "SELECT * FROM male_birds WHERE 1=1";
if ($status) {
    $sql .= " AND status = :status";
}
$sql .= " ORDER BY created_at DESC";

$db->query($sql);
if ($status) {
    $db->bind(':status', $status);
}
$birds = $db->resultSet();

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-mars"></i> لیستی هەوێردەی نێر</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">هەوێردەی نێر</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="add_male.php" class="btn btn-success">
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
                    <option value="dead" <?php echo $status === 'dead' ? 'selected' : ''; ?>>مردوو</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> فلتەر
                </button>
                <a href="male_list.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> ڕیسێت
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> هەوێردەی نێر (<?php echo count($birds); ?>)
    </div>
    <div class="card-body">
        <?php if (count($birds) > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ناوی گرووپ</th>
                        <th>ژمارە</th>
                        <th>تەمەن</th>
                        <th>بار</th>
                        <th>تێبینی</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($birds as $index => $bird): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><strong><?php echo $bird['batch_name']; ?></strong></td>
                        <td><?php echo $bird['quantity']; ?></td>
                        <td><?php echo calculateAge($bird['entry_date']); ?></td>
                        <td><?php echo getStatusBadge($bird['status']); ?></td>
                        <td><?php echo $bird['notes'] ?: '-'; ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit_male.php?id=<?php echo $bird['id']; ?>" class="btn btn-sm btn-outline-primary" title="دەستکاری">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDelete(<?php echo $bird['id']; ?>, '<?php echo $bird['batch_name']; ?>')" class="btn btn-sm btn-outline-danger" title="سڕینەوە">
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
            <i class="fas fa-dove"></i>
            <h4>هیچ هەوێردەیەک نیە</h4>
            <p>هیچ هەوێردەی نێرێک تۆمار نەکراوە</p>
            <a href="add_male.php" class="btn btn-primary">
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
    document.getElementById('confirmDeleteBtn').href = 'delete_male.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
