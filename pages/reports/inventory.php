<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'reports';
$pageTitle = 'راپۆرتی کۆگا';

// Get warehouse items
$db->query("SELECT * FROM warehouse ORDER BY item_name");
$warehouseItems = $db->resultSet();

// Get male birds
$db->query("SELECT * FROM male_birds ORDER BY batch_name");
$maleBirds = $db->resultSet();

// Get female birds
$db->query("SELECT * FROM female_birds ORDER BY batch_name");
$femaleBirds = $db->resultSet();

// Get eggs summary
$db->query("SELECT SUM(quantity) as total, SUM(damaged_count) as damaged, SUM(quantity - damaged_count) as healthy FROM eggs");
$eggsSummary = $db->single();

// Get chicks summary
$db->query("SELECT SUM(quantity) as total, SUM(dead_count) as dead, SUM(quantity - dead_count) as alive FROM chicks");
$chicksSummary = $db->single();

// Calculate total inventory value
$db->query("SELECT SUM(quantity * unit_price) as total FROM warehouse");
$warehouseValue = $db->single()['total'] ?? 0;

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-warehouse"></i> راپۆرتی کۆگا</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">راپۆرتی کۆگا</li>
            </ol>
        </nav>
    </div>
    <div>
        <button onclick="exportToPDF('reportContent', 'inventory-report-<?php echo date('Y-m-d'); ?>')" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> داگرتن بە PDF
        </button>
    </div>
</div>

<div id="reportContent">
    <!-- Report Header -->
    <div class="text-center mb-4">
        <h3><?php echo SITE_NAME; ?></h3>
        <h4>راپۆرتی کۆگا و مەوادەکان</h4>
        <p class="text-muted">بەرواری چاپ: <?php echo date('Y/m/d H:i'); ?></p>
    </div>
    
    <!-- Summary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-primary">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="info">
                    <h3><?php echo count($warehouseItems); ?></h3>
                    <p>کالاکان</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-info">
                    <i class="fas fa-dove"></i>
                </div>
                <div class="info">
                    <h3><?php echo count($maleBirds) + count($femaleBirds); ?></h3>
                    <p>گرووپی باڵندە</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-warning">
                    <i class="fas fa-egg"></i>
                </div>
                <div class="info">
                    <h3><?php echo number_format($eggsSummary['healthy'] ?? 0); ?></h3>
                    <p>هێلکەی ساغ</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-success">
                    <i class="fas fa-kiwi-bird"></i>
                </div>
                <div class="info">
                    <h3><?php echo number_format($chicksSummary['alive'] ?? 0); ?></h3>
                    <p>جوجکەی زیندوو</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Warehouse Items -->
    <div class="card mb-4">
        <div class="card-header bg-primary-gradient">
            <i class="fas fa-boxes"></i> کالاکانی کۆگا
        </div>
        <div class="card-body">
            <?php if (count($warehouseItems) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ناوی کالا</th>
                            <th>ژمارە</th>
                            <th>یەکە</th>
                            <th>نرخی یەکە</th>
                            <th>کۆی بەها</th>
                            <th>بار</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $totalValue = 0; foreach ($warehouseItems as $index => $item): 
                            $itemValue = $item['quantity'] * $item['unit_price'];
                            $totalValue += $itemValue;
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo $item['item_name']; ?></strong></td>
                            <td><?php echo number_format($item['quantity']); ?></td>
                            <td><?php echo $item['unit']; ?></td>
                            <td><?php echo formatMoney($item['unit_price']); ?></td>
                            <td><?php echo formatMoney($itemValue); ?></td>
                            <td>
                                <?php 
                                if ($item['quantity'] < $item['min_quantity']) {
                                    echo '<span class="badge bg-danger">کەم</span>';
                                } elseif ($item['quantity'] <= $item['min_quantity'] * 1.5) {
                                    echo '<span class="badge bg-warning">نزیکە</span>';
                                } else {
                                    echo '<span class="badge bg-success">باش</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <td colspan="5" class="text-end"><strong>کۆی گشتی:</strong></td>
                            <td colspan="2"><strong><?php echo formatMoney($totalValue); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>هیچ کالایەک نیە</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <!-- Male Birds -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-info-gradient">
                    <i class="fas fa-mars"></i> هەوێردەی نێرە
                </div>
                <div class="card-body">
                    <?php if (count($maleBirds) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>گرووپ</th>
                                    <th>ژمارە</th>
                                    <th>تەمەن</th>
                                    <th>بار</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $totalMales = 0; foreach ($maleBirds as $bird): 
                                    $totalMales += $bird['quantity'];
                                ?>
                                <tr>
                                    <td><?php echo $bird['batch_name']; ?></td>
                                    <td><?php echo $bird['quantity']; ?></td>
                                    <td><?php echo calculateAge($bird['entry_date']); ?></td>
                                    <td><?php echo getStatusBadge($bird['status']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <td><strong>کۆ</strong></td>
                                    <td colspan="3"><strong><?php echo number_format($totalMales); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-dove"></i>
                        <p>هیچ هەوێردەیەک نیە</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Female Birds -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-danger-gradient">
                    <i class="fas fa-venus"></i> هەوێردەی مێیە
                </div>
                <div class="card-body">
                    <?php if (count($femaleBirds) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>گرووپ</th>
                                    <th>ژمارە</th>
                                    <th>تەمەن</th>
                                    <th>بار</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $totalFemales = 0; foreach ($femaleBirds as $bird): 
                                    $totalFemales += $bird['quantity'];
                                ?>
                                <tr>
                                    <td><?php echo $bird['batch_name']; ?></td>
                                    <td><?php echo $bird['quantity']; ?></td>
                                    <td><?php echo calculateAge($bird['entry_date']); ?></td>
                                    <td><?php echo getStatusBadge($bird['status']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <td><strong>کۆ</strong></td>
                                    <td colspan="3"><strong><?php echo number_format($totalFemales); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-dove"></i>
                        <p>هیچ هەوێردەیەک نیە</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Eggs & Chicks Summary -->
    <div class="row g-4">
        <!-- Eggs Summary -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-warning-gradient">
                    <i class="fas fa-egg"></i> خولاصەی هێلکە
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="text-primary"><?php echo number_format($eggsSummary['total'] ?? 0); ?></h4>
                            <small class="text-muted">کۆی هێلکە</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-success"><?php echo number_format($eggsSummary['healthy'] ?? 0); ?></h4>
                            <small class="text-muted">ساغ</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-danger"><?php echo number_format($eggsSummary['damaged'] ?? 0); ?></h4>
                            <small class="text-muted">خراپ</small>
                        </div>
                    </div>
                    <?php if (($eggsSummary['total'] ?? 0) > 0): ?>
                    <hr>
                    <div class="progress" style="height: 20px;">
                        <?php 
                        $healthyPercent = (($eggsSummary['healthy'] ?? 0) / ($eggsSummary['total'] ?? 1)) * 100;
                        $damagedPercent = (($eggsSummary['damaged'] ?? 0) / ($eggsSummary['total'] ?? 1)) * 100;
                        ?>
                        <div class="progress-bar bg-success" style="width: <?php echo $healthyPercent; ?>%">
                            <?php echo round($healthyPercent, 1); ?>%
                        </div>
                        <div class="progress-bar bg-danger" style="width: <?php echo $damagedPercent; ?>%">
                            <?php echo round($damagedPercent, 1); ?>%
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Chicks Summary -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-success-gradient">
                    <i class="fas fa-kiwi-bird"></i> خولاصەی جوجکە
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="text-primary"><?php echo number_format($chicksSummary['total'] ?? 0); ?></h4>
                            <small class="text-muted">کۆی جوجکە</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-success"><?php echo number_format($chicksSummary['alive'] ?? 0); ?></h4>
                            <small class="text-muted">زیندوو</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-danger"><?php echo number_format($chicksSummary['dead'] ?? 0); ?></h4>
                            <small class="text-muted">مردوو</small>
                        </div>
                    </div>
                    <?php if (($chicksSummary['total'] ?? 0) > 0): ?>
                    <hr>
                    <div class="progress" style="height: 20px;">
                        <?php 
                        $alivePercent = (($chicksSummary['alive'] ?? 0) / ($chicksSummary['total'] ?? 1)) * 100;
                        $deadPercent = (($chicksSummary['dead'] ?? 0) / ($chicksSummary['total'] ?? 1)) * 100;
                        ?>
                        <div class="progress-bar bg-success" style="width: <?php echo $alivePercent; ?>%">
                            <?php echo round($alivePercent, 1); ?>%
                        </div>
                        <div class="progress-bar bg-danger" style="width: <?php echo $deadPercent; ?>%">
                            <?php echo round($deadPercent, 1); ?>%
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
