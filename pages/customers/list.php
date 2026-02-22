<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'customers';
$pageTitle = 'لیستی کڕیاران';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM customers WHERE id = :id");
    $db->bind(':id', $id);
    if ($db->execute()) {
        setMessage('success', 'کڕیارەکە بە سەرکەوتوویی سڕایەوە');
    }
    redirect('list.php');
}

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $notes = sanitize($_POST['notes'] ?? '');
    
    $db->query("INSERT INTO customers (name, phone, address, notes) VALUES (:name, :phone, :address, :notes)");
    $db->bind(':name', $name);
    $db->bind(':phone', $phone);
    $db->bind(':address', $address);
    $db->bind(':notes', $notes);
    
    if ($db->execute()) {
        setMessage('success', 'کڕیارەکە بە سەرکەوتوویی زیادکرا');
    }
    redirect('list.php');
}

// Get customers
$db->query("SELECT * FROM customers ORDER BY created_at DESC");
$customers = $db->resultSet();

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-user-tie"></i> لیستی کڕیاران</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">کڕیاران</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button onclick="exportToPDF('customersTable', 'customers-report')" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> PDF
        </button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> زیادکردن
        </button>
    </div>
</div>

<!-- Data Table -->
<div class="card" id="customersTable">
    <div class="card-header">
        <i class="fas fa-list"></i> کڕیاران (<?php echo count($customers); ?>)
    </div>
    <div class="card-body">
        <?php if (count($customers) > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ناو</th>
                        <th>تەلەفۆن</th>
                        <th>ناونیشان</th>
                        <th>تێبینی</th>
                        <th class="no-print">کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $index => $customer): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><strong><?php echo $customer['name']; ?></strong></td>
                        <td><?php echo $customer['phone'] ?: '-'; ?></td>
                        <td><?php echo $customer['address'] ?: '-'; ?></td>
                        <td><?php echo $customer['notes'] ?: '-'; ?></td>
                        <td class="no-print">
                            <a href="<?php echo $basePath; ?>pages/reports/customers.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info btn-action" title="راپۆرت">
                                <i class="fas fa-chart-bar"></i>
                            </a>
                            <button onclick="confirmDelete('list.php?delete=<?php echo $customer['id']; ?>', '<?php echo $customer['name']; ?>')" class="btn btn-sm btn-danger btn-action" title="سڕینەوە">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <h4>هیچ کڕیارێک نیە</h4>
            <p>هیچ کڕیارێک تۆمار نەکراوە</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> زیادکردنی کڕیار</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ناو *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تەلەفۆن</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ناونیشان</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تێبینی</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" class="btn btn-success">پاشەکەوتکردن</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
