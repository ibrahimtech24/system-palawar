<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'birds';
$pageTitle = 'دۆخی هەوێردەکان';

// Make sure columns exist - check and add if needed
try {
    // Check if dead_count column exists in male_birds
    $db->query("SHOW COLUMNS FROM male_birds LIKE 'dead_count'");
    $result = $db->resultSet();
    if (empty($result)) {
        $db->query("ALTER TABLE male_birds ADD COLUMN dead_count INT DEFAULT 0");
        $db->execute();
    }
    
    // Check if dead_count column exists in female_birds
    $db->query("SHOW COLUMNS FROM female_birds LIKE 'dead_count'");
    $result = $db->resultSet();
    if (empty($result)) {
        $db->query("ALTER TABLE female_birds ADD COLUMN dead_count INT DEFAULT 0");
        $db->execute();
    }
} catch (Exception $e) {
    // Columns may already exist or other error
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $deadCount = intval($_POST['dead_count'] ?? 0);
    
    if ($id > 0 && $deadCount >= 0) {
        $table = ($type == 'male') ? 'male_birds' : 'female_birds';
        
        // Get current quantity
        $db->query("SELECT quantity FROM $table WHERE id = :id");
        $db->bind(':id', $id);
        $bird = $db->single();
        
        if ($bird) {
            $maxDead = $bird['quantity'];
            if ($deadCount > $maxDead) {
                $message = 'ژمارەی مردوو ناتوانێت لە کۆی ژمارە زیاتر بێت';
                $messageType = 'danger';
            } else {
                $db->query("UPDATE $table SET dead_count = :dead WHERE id = :id");
                $db->bind(':dead', $deadCount);
                $db->bind(':id', $id);
                
                if ($db->execute()) {
                    $message = 'دۆخ بە سەرکەوتوویی نوێکرایەوە';
                    $messageType = 'success';
                } else {
                    $message = 'هەڵەیەک ڕوویدا';
                    $messageType = 'danger';
                }
            }
        }
    }
}

// Get all birds with their status
$db->query("SELECT id, batch_name, quantity, IFNULL(dead_count, 0) as dead_count, entry_date FROM male_birds ORDER BY created_at DESC");
$maleBirds = $db->resultSet();

$db->query("SELECT id, batch_name, quantity, IFNULL(dead_count, 0) as dead_count, entry_date FROM female_birds ORDER BY created_at DESC");
$femaleBirds = $db->resultSet();

// Calculate totals
$totalMale = 0;
$totalMaleDead = 0;
$totalMaleAlive = 0;
foreach ($maleBirds as $bird) {
    $totalMale += $bird['quantity'];
    $totalMaleDead += $bird['dead_count'];
}
$totalMaleAlive = $totalMale - $totalMaleDead;

$totalFemale = 0;
$totalFemaleDead = 0;
$totalFemaleAlive = 0;
foreach ($femaleBirds as $bird) {
    $totalFemale += $bird['quantity'];
    $totalFemaleDead += $bird['dead_count'];
}
$totalFemaleAlive = $totalFemale - $totalFemaleDead;

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-heartbeat"></i> دۆخی هەوێردەکان</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">دۆخی هەوێردەکان</li>
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
                <i class="fas fa-mars"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalMaleAlive); ?></h3>
                <p>نێری زیندوو</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="fas fa-skull"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalMaleDead); ?></h3>
                <p>نێری مردوو</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="fas fa-venus"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalFemaleAlive); ?></h3>
                <p>مێی زیندوو</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-warning">
                <i class="fas fa-skull"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalFemaleDead); ?></h3>
                <p>مێی مردوو</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Male Birds -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-info-gradient">
                <i class="fas fa-mars"></i> هەوێردەی نێر
            </div>
            <div class="card-body">
                <?php if (count($maleBirds) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>گرووپ</th>
                                <th>کۆی ژمارە</th>
                                <th>زیندوو</th>
                                <th>مردوو</th>
                                <th>کردار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($maleBirds as $bird): ?>
                            <?php $alive = $bird['quantity'] - $bird['dead_count']; ?>
                            <tr>
                                <td><strong><?php echo $bird['batch_name']; ?></strong></td>
                                <td><?php echo $bird['quantity']; ?></td>
                                <td><span class="badge bg-success"><?php echo $alive; ?></span></td>
                                <td><span class="badge bg-danger"><?php echo $bird['dead_count']; ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deadModal" onclick="setDeadForm('male', <?php echo $bird['id']; ?>, '<?php echo $bird['batch_name']; ?>', <?php echo $bird['quantity']; ?>, <?php echo $bird['dead_count']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <td><strong>کۆ</strong></td>
                                <td><strong><?php echo $totalMale; ?></strong></td>
                                <td><span class="badge bg-success"><?php echo $totalMaleAlive; ?></span></td>
                                <td><span class="badge bg-danger"><?php echo $totalMaleDead; ?></span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-dove"></i>
                    <p>هیچ هەوێردەی نێرێک نیە</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Female Birds -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-danger-gradient">
                <i class="fas fa-venus"></i> هەوێردەی مێ
            </div>
            <div class="card-body">
                <?php if (count($femaleBirds) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>گرووپ</th>
                                <th>کۆی ژمارە</th>
                                <th>زیندوو</th>
                                <th>مردوو</th>
                                <th>کردار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($femaleBirds as $bird): ?>
                            <?php $alive = $bird['quantity'] - $bird['dead_count']; ?>
                            <tr>
                                <td><strong><?php echo $bird['batch_name']; ?></strong></td>
                                <td><?php echo $bird['quantity']; ?></td>
                                <td><span class="badge bg-success"><?php echo $alive; ?></span></td>
                                <td><span class="badge bg-danger"><?php echo $bird['dead_count']; ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deadModal" onclick="setDeadForm('female', <?php echo $bird['id']; ?>, '<?php echo $bird['batch_name']; ?>', <?php echo $bird['quantity']; ?>, <?php echo $bird['dead_count']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <td><strong>کۆ</strong></td>
                                <td><strong><?php echo $totalFemale; ?></strong></td>
                                <td><span class="badge bg-success"><?php echo $totalFemaleAlive; ?></span></td>
                                <td><span class="badge bg-danger"><?php echo $totalFemaleDead; ?></span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-dove"></i>
                    <p>هیچ هەوێردەی مێیەک نیە</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Dead Count Modal -->
<div class="modal fade" id="deadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-skull"></i> تۆمارکردنی مردوو</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="type" id="deadType">
                    <input type="hidden" name="id" id="deadId">
                    
                    <div class="alert alert-info mb-3">
                        <strong>گرووپ:</strong> <span id="deadBatchName"></span><br>
                        <strong>کۆی ژمارە:</strong> <span id="deadTotal"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ژمارەی مردوو</label>
                        <input type="number" name="dead_count" id="deadCount" class="form-control" min="0" required>
                        <div class="form-text">ئەو ژمارەیە کە تا ئێستا مردوون</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-save"></i> پاشەکەوتکردن
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setDeadForm(type, id, batchName, total, deadCount) {
    document.getElementById('deadType').value = type;
    document.getElementById('deadId').value = id;
    document.getElementById('deadBatchName').textContent = batchName;
    document.getElementById('deadTotal').textContent = total;
    document.getElementById('deadCount').value = deadCount;
    document.getElementById('deadCount').max = total;
}
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
