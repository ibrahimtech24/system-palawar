<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'production';
$pageTitle = 'دۆخی بەرهەمەکان';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $count = intval($_POST['count'] ?? 0);
    
    if ($id > 0 && $count >= 0) {
        if ($type == 'chick') {
            // Get current quantity
            $db->query("SELECT quantity FROM chicks WHERE id = :id");
            $db->bind(':id', $id);
            $item = $db->single();
            
            if ($item && $count <= $item['quantity']) {
                $db->query("UPDATE chicks SET dead_count = :count WHERE id = :id");
                $db->bind(':count', $count);
                $db->bind(':id', $id);
                
                if ($db->execute()) {
                    $message = 'دۆخی جوجکە بە سەرکەوتوویی نوێکرایەوە';
                    $messageType = 'success';
                } else {
                    $message = 'هەڵەیەک ڕوویدا';
                    $messageType = 'danger';
                }
            } else {
                $message = 'ژمارەی مردوو ناتوانێت لە کۆی ژمارە زیاتر بێت';
                $messageType = 'danger';
            }
        } elseif ($type == 'egg') {
            // Get current quantity
            $db->query("SELECT quantity FROM eggs WHERE id = :id");
            $db->bind(':id', $id);
            $item = $db->single();
            
            if ($item && $count <= $item['quantity']) {
                $db->query("UPDATE eggs SET damaged_count = :count WHERE id = :id");
                $db->bind(':count', $count);
                $db->bind(':id', $id);
                
                if ($db->execute()) {
                    $message = 'دۆخی هێلکە بە سەرکەوتوویی نوێکرایەوە';
                    $messageType = 'success';
                } else {
                    $message = 'هەڵەیەک ڕوویدا';
                    $messageType = 'danger';
                }
            } else {
                $message = 'ژمارەی خراپ ناتوانێت لە کۆی ژمارە زیاتر بێت';
                $messageType = 'danger';
            }
        }
    }
}

// Get chicks data (only active ones)
$db->query("SELECT c.*, e.collection_date as egg_date 
            FROM chicks c 
            LEFT JOIN eggs e ON c.egg_id = e.id 
            WHERE c.quantity > 0 AND (c.quantity - c.dead_count) > 0 AND c.status != 'sold'
            ORDER BY c.hatch_date DESC");
$chicks = $db->resultSet();

// Get eggs data (only active ones with healthy eggs remaining)
$db->query("SELECT e.*, f.batch_name as female_batch, m.batch_name as male_batch
            FROM eggs e 
            LEFT JOIN female_birds f ON e.female_bird_id = f.id 
            LEFT JOIN male_birds m ON e.male_bird_id = m.id
            WHERE e.quantity > 0 AND (e.quantity - e.damaged_count) > 0
            ORDER BY e.collection_date DESC");
$eggs = $db->resultSet();

// Calculate chick totals
$totalChicks = 0;
$totalChicksDead = 0;
foreach ($chicks as $chick) {
    $totalChicks += $chick['quantity'];
    $totalChicksDead += $chick['dead_count'];
}
$totalChicksAlive = $totalChicks - $totalChicksDead;

// Calculate egg totals
$totalEggs = 0;
$totalEggsDamaged = 0;
foreach ($eggs as $egg) {
    $totalEggs += $egg['quantity'];
    $totalEggsDamaged += $egg['damaged_count'];
}
$totalEggsHealthy = $totalEggs - $totalEggsDamaged;

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-heartbeat"></i> دۆخی بەرهەمەکان</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">دۆخی بەرهەمەکان</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Summary Stats -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-primary">
                <i class="fas fa-kiwi-bird"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalChicksAlive); ?></h3>
                <p>جوجکەی زیندوو</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="fas fa-skull"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalChicksDead); ?></h3>
                <p>جوجکەی مردوو</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="fas fa-egg"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalEggsHealthy); ?></h3>
                <p>هێلکەی ساغ</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-warning">
                <i class="fas fa-heart-broken"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalEggsDamaged); ?></h3>
                <p>هێلکەی خراپ</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Chicks -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-info-gradient">
                <i class="fas fa-kiwi-bird"></i> جوجکەکان
            </div>
            <div class="card-body">
                <?php if (count($chicks) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>بەروار</th>
                                <th>کۆی ژمارە</th>
                                <th>زیندوو</th>
                                <th>مردوو</th>
                                <th>کردار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chicks as $chick): ?>
                            <?php $alive = $chick['quantity'] - $chick['dead_count']; ?>
                            <tr>
                                <td><?php echo formatDate($chick['hatch_date']); ?></td>
                                <td><?php echo $chick['quantity']; ?></td>
                                <td><span class="badge bg-success"><?php echo $alive; ?></span></td>
                                <td><span class="badge bg-danger"><?php echo $chick['dead_count']; ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#statusModal" onclick="setForm('chick', <?php echo $chick['id']; ?>, 'جوجکە - <?php echo formatDate($chick['hatch_date']); ?>', <?php echo $chick['quantity']; ?>, <?php echo $chick['dead_count']; ?>, 'مردوو')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <td><strong>کۆ</strong></td>
                                <td><strong><?php echo $totalChicks; ?></strong></td>
                                <td><span class="badge bg-success"><?php echo $totalChicksAlive; ?></span></td>
                                <td><span class="badge bg-danger"><?php echo $totalChicksDead; ?></span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-kiwi-bird"></i>
                    <p>هیچ جوجکەیەک نیە</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Eggs -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-warning-gradient">
                <i class="fas fa-egg"></i> هێلکەکان
            </div>
            <div class="card-body">
                <?php if (count($eggs) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>بەروار</th>
                                <th>کۆی ژمارە</th>
                                <th>ساغ</th>
                                <th>خراپ</th>
                                <th>کردار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eggs as $egg): ?>
                            <?php $healthy = $egg['quantity'] - $egg['damaged_count']; ?>
                            <tr>
                                <td><?php echo formatDate($egg['collection_date']); ?></td>
                                <td><?php echo $egg['quantity']; ?></td>
                                <td><span class="badge bg-success"><?php echo $healthy; ?></span></td>
                                <td><span class="badge bg-warning"><?php echo $egg['damaged_count']; ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#statusModal" onclick="setForm('egg', <?php echo $egg['id']; ?>, 'هێلکە - <?php echo formatDate($egg['collection_date']); ?>', <?php echo $egg['quantity']; ?>, <?php echo $egg['damaged_count']; ?>, 'خراپ')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <td><strong>کۆ</strong></td>
                                <td><strong><?php echo $totalEggs; ?></strong></td>
                                <td><span class="badge bg-success"><?php echo $totalEggsHealthy; ?></span></td>
                                <td><span class="badge bg-warning"><?php echo $totalEggsDamaged; ?></span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-egg"></i>
                    <p>هیچ هێلکەیەک نیە</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> نوێکردنەوەی دۆخ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="type" id="statusType">
                    <input type="hidden" name="id" id="statusId">
                    
                    <div class="alert alert-info mb-3">
                        <strong>بەش:</strong> <span id="statusName"></span><br>
                        <strong>کۆی ژمارە:</strong> <span id="statusTotal"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ژمارەی <span id="statusLabel"></span></label>
                        <input type="number" name="count" id="statusCount" class="form-control" min="0" required>
                        <div class="form-text">ئەو ژمارەیە کە تا ئێستا <span id="statusLabelHint"></span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> پاشەکەوتکردن
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setForm(type, id, name, total, count, label) {
    document.getElementById('statusType').value = type;
    document.getElementById('statusId').value = id;
    document.getElementById('statusName').textContent = name;
    document.getElementById('statusTotal').textContent = total;
    document.getElementById('statusCount').value = count;
    document.getElementById('statusCount').max = total;
    document.getElementById('statusLabel').textContent = label;
    document.getElementById('statusLabelHint').textContent = label;
}
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
