<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'incubator';
$pageTitle = 'مەفقەس';

// Get all incubator records
$db->query("SELECT i.*, e.quantity as orig_egg_qty, e.collection_date as egg_date,
                   f.batch_name as female_batch, m.batch_name as male_batch,
                   cu.name as customer_name, cu.phone as customer_phone
            FROM incubator i 
            LEFT JOIN eggs e ON i.egg_id = e.id 
            LEFT JOIN female_birds f ON e.female_bird_id = f.id
            LEFT JOIN male_birds m ON e.male_bird_id = m.id
            LEFT JOIN customers cu ON i.customer_id = cu.id
            ORDER BY i.status ASC, i.entry_date DESC");
$incubatorItems = $db->resultSet();

// Get top customers by incubator count
$db->query("SELECT cu.id, cu.name, cu.phone, 
                   COUNT(i.id) as total_groups, 
                   SUM(i.egg_quantity) as total_eggs,
                   SUM(CASE WHEN i.status = 'incubating' THEN 1 ELSE 0 END) as active_groups
            FROM incubator i 
            INNER JOIN customers cu ON i.customer_id = cu.id 
            GROUP BY cu.id, cu.name, cu.phone 
            ORDER BY total_groups DESC");
$topCustomers = $db->resultSet();

// Apply filters
$filterCustomer = $_GET['customer'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterSearch = trim($_GET['search'] ?? '');

// Filter items
$filteredItems = $incubatorItems;
if ($filterCustomer !== '') {
    if ($filterCustomer === '0') {
        $filteredItems = array_filter($filteredItems, function($item) {
            return empty($item['customer_name']);
        });
    } else {
        $filteredItems = array_filter($filteredItems, function($item) use ($filterCustomer) {
            return $item['customer_id'] == $filterCustomer;
        });
    }
}
if ($filterStatus !== '') {
    $filteredItems = array_filter($filteredItems, function($item) use ($filterStatus) {
        return $item['status'] === $filterStatus;
    });
}
if ($filterSearch !== '') {
    $filteredItems = array_filter($filteredItems, function($item) use ($filterSearch) {
        return stripos($item['group_name'], $filterSearch) !== false 
            || stripos($item['customer_name'] ?? '', $filterSearch) !== false
            || stripos($item['female_batch'] ?? '', $filterSearch) !== false
            || stripos($item['male_batch'] ?? '', $filterSearch) !== false
            || stripos($item['notes'] ?? '', $filterSearch) !== false;
    });
}

// Calculate totals (from all items)
$totalIncubating = 0;
$totalHatched = 0;
$totalDamaged = 0;
foreach ($incubatorItems as $item) {
    if ($item['status'] === 'incubating') {
        $totalIncubating += $item['egg_quantity'];
    } else {
        $totalHatched += $item['hatched_count'];
        $totalDamaged += $item['damaged_count'];
    }
}

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-temperature-high"></i> مەفقەس (حاضنة)</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">مەفقەس</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="add.php" class="btn btn-success">
            <i class="fas fa-plus"></i> دانانەوەی هێلکە
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> 
    <?php 
    if ($_GET['success'] == 'added') echo 'هێلکەکان بە سەرکەوتوویی دانرانەوە لە مەفقەس';
    elseif ($_GET['success'] == 'hatched') echo 'هێلکەکان بە سەرکەوتوویی هەڵاتن و جوجکەکان تۆمارکران';
    else echo 'کردارەکە بە سەرکەوتوویی ئەنجامدرا';
    ?>
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
            <div class="icon" style="background: linear-gradient(135deg, #f97316, #ea580c);">
                <i class="fas fa-temperature-high"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalIncubating); ?></h3>
                <p>هێلکە لە مەفقەس</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="fas fa-kiwi-bird"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalHatched); ?></h3>
                <p>کۆی هەڵاتوو</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($totalDamaged); ?></h3>
                <p>کۆی خەسارە</p>
            </div>
        </div>
    </div>
</div>

<!-- Filter & Search Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label"><i class="fas fa-search"></i> گەڕان</label>
                <input type="text" name="search" class="form-control" placeholder="ناوی گرووپ، کڕیار، تێبینی..." value="<?php echo htmlspecialchars($filterSearch); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label"><i class="fas fa-user-tie"></i> فلتەری کڕیار</label>
                <select name="customer" class="form-select">
                    <option value="">هەموو کڕیارەکان</option>
                    <option value="0" <?php echo $filterCustomer === '0' ? 'selected' : ''; ?>>خۆمان (بێ کڕیار)</option>
                    <?php foreach ($topCustomers as $tc): ?>
                    <option value="<?php echo $tc['id']; ?>" <?php echo $filterCustomer == $tc['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tc['name']); ?> (<?php echo $tc['total_groups']; ?> گرووپ)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label"><i class="fas fa-filter"></i> بار</label>
                <select name="status" class="form-select">
                    <option value="">هەموو</option>
                    <option value="incubating" <?php echo $filterStatus === 'incubating' ? 'selected' : ''; ?>>لە مەفقەس</option>
                    <option value="hatched" <?php echo $filterStatus === 'hatched' ? 'selected' : ''; ?>>هەڵاتوو</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-search"></i> گەڕان</button>
                    <a href="list.php" class="btn btn-outline-secondary" title="پاککردنەوە"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </form>
        <?php if ($filterCustomer !== '' || $filterStatus !== '' || $filterSearch !== ''): ?>
        <div class="mt-3 pt-3 border-top">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                ئەنجام: <strong><?php echo count($filteredItems); ?></strong> تۆمار لە کۆی <strong><?php echo count($incubatorItems); ?></strong>
                <?php if ($filterCustomer !== ''): ?>
                    <?php if ($filterCustomer === '0'): ?>
                        | کڕیار: <span class="badge bg-secondary">خۆمان</span>
                    <?php else: ?>
                        <?php foreach ($topCustomers as $tc): ?>
                            <?php if ($tc['id'] == $filterCustomer): ?>
                                | کڕیار: <span class="badge bg-primary"><?php echo htmlspecialchars($tc['name']); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($filterStatus !== ''): ?>
                    | بار: <span class="badge bg-<?php echo $filterStatus === 'incubating' ? 'warning' : 'info'; ?>">
                        <?php echo $filterStatus === 'incubating' ? 'لە مەفقەس' : 'هەڵاتوو'; ?>
                    </span>
                <?php endif; ?>
                <?php if ($filterSearch !== ''): ?>
                    | گەڕان: <span class="badge bg-dark"><?php echo htmlspecialchars($filterSearch); ?></span>
                <?php endif; ?>
            </small>
        </div>
            <?php if (count($filteredItems) === 0): ?>
            <div class="alert alert-warning mt-3 mb-0 text-center">
                <i class="fas fa-exclamation-circle"></i>
                هیچ تۆمارێک نەدۆزرایەوە بەپێی فلتەرەکانت.
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (count($topCustomers) > 0): ?>
<!-- Top Customers -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-trophy"></i> کڕیارانی سەرەکی مەفقەس
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <?php foreach ($topCustomers as $rank => $tc): 
                $rankColors = ['warning', 'secondary', 'danger', 'info', 'primary'];
                $rankColor = $rankColors[$rank] ?? 'dark';
                $rankIcons = ['fa-trophy', 'fa-medal', 'fa-award', 'fa-star', 'fa-star'];
                $rankIcon = $rankIcons[$rank] ?? 'fa-user';
            ?>
            <div class="col-lg-3 col-md-4 col-6">
                <a href="?customer=<?php echo $tc['id']; ?>" class="text-decoration-none">
                    <div class="border rounded p-3 h-100 text-center <?php echo $filterCustomer == $tc['id'] ? 'border-primary bg-primary-subtle' : ''; ?>" style="transition: all 0.2s;" 
                         onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='none'">
                        <div class="mb-2">
                            <span class="badge bg-<?php echo $rankColor; ?> rounded-pill" style="font-size: 1rem;">
                                <i class="fas <?php echo $rankIcon; ?>"></i> #<?php echo $rank + 1; ?>
                            </span>
                        </div>
                        <h6 class="mb-1 text-dark"><?php echo htmlspecialchars($tc['name']); ?></h6>
                        <div class="d-flex justify-content-center gap-3">
                            <small class="text-muted"><i class="fas fa-layer-group"></i> <?php echo $tc['total_groups']; ?> گرووپ</small>
                            <small class="text-muted"><i class="fas fa-egg"></i> <?php echo number_format($tc['total_eggs']); ?></small>
                        </div>
                        <?php if ($tc['active_groups'] > 0): ?>
                        <small class="text-success"><i class="fas fa-fire"></i> <?php echo $tc['active_groups']; ?> چالاک</small>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Active Incubating -->
<?php 
$activeItems = array_filter($filteredItems, function($item) { return $item['status'] === 'incubating'; });
$hatchedItems = array_filter($filteredItems, function($item) { return $item['status'] === 'hatched'; });
?>

<?php if (count($activeItems) > 0): ?>
<div class="card mb-4">
    <div class="card-header" style="background: linear-gradient(135deg, #f97316, #ea580c); color: white;">
        <i class="fas fa-fire"></i> هێلکەکانی ناو مەفقەس (چالاک)
    </div>
    <div class="card-body">
        <div class="row g-4">
            <?php foreach ($activeItems as $item): 
                $entryDate = new DateTime($item['entry_date']);
                $hatchDate = new DateTime($item['expected_hatch_date']);
                $today = new DateTime();
                $daysLeft = (int)$today->diff($hatchDate)->format('%r%a');
                $totalDays = 17;
                $daysPassed = $totalDays - max(0, $daysLeft);
                $progress = min(100, max(0, ($daysPassed / $totalDays) * 100));
                
                if ($daysLeft <= 0) {
                    $statusColor = 'success';
                    $statusText = 'ئامادەی هەڵاتن!';
                    $statusIcon = 'fas fa-check-circle';
                } elseif ($daysLeft <= 3) {
                    $statusColor = 'warning';
                    $statusText = $daysLeft . ' ڕۆژ ماوە';
                    $statusIcon = 'fas fa-clock';
                } else {
                    $statusColor = 'info';
                    $statusText = $daysLeft . ' ڕۆژ ماوە';
                    $statusIcon = 'fas fa-hourglass-half';
                }
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="card border-<?php echo $statusColor; ?> h-100">
                    <div class="card-header bg-<?php echo $statusColor; ?> text-white d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-egg"></i> <?php echo htmlspecialchars($item['group_name']); ?></span>
                        <span class="badge bg-white text-<?php echo $statusColor; ?>"><?php echo number_format($item['egg_quantity']); ?> هێلکە</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted"><i class="<?php echo $statusIcon; ?>"></i> <?php echo $statusText; ?></small>
                                <small class="text-muted"><?php echo round($progress); ?>%</small>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 6px;">
                                <div class="progress-bar bg-<?php echo $statusColor; ?> progress-bar-striped progress-bar-animated" 
                                     style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        
                        <ul class="list-unstyled mb-0" style="font-size: 0.9rem;">
                            <?php if ($item['customer_name']): ?>
                            <li class="mb-1">
                                <i class="fas fa-user-tie text-primary"></i>
                                <strong>کڕیار:</strong> <?php echo htmlspecialchars($item['customer_name']); ?>
                                <?php if ($item['customer_phone']): ?>
                                <small class="text-muted">(<?php echo $item['customer_phone']; ?>)</small>
                                <?php endif; ?>
                            </li>
                            <?php endif; ?>
                            <li class="mb-1">
                                <i class="fas fa-calendar-plus text-primary"></i>
                                <strong>ڕۆژی دانان:</strong> <?php echo formatDate($item['entry_date']); ?>
                            </li>
                            <li class="mb-1">
                                <i class="fas fa-calendar-check text-success"></i>
                                <strong>ڕۆژی دەرچوون:</strong> <?php echo formatDate($item['expected_hatch_date']); ?>
                            </li>
                            <?php if ($item['female_batch']): ?>
                            <li class="mb-1">
                                <i class="fas fa-venus text-danger"></i>
                                <strong>دایک:</strong> <?php echo htmlspecialchars($item['female_batch']); ?>
                            </li>
                            <?php endif; ?>
                            <?php if ($item['male_batch']): ?>
                            <li class="mb-1">
                                <i class="fas fa-mars text-primary"></i>
                                <strong>باوک:</strong> <?php echo htmlspecialchars($item['male_batch']); ?>
                            </li>
                            <?php endif; ?>
                            <?php if ($item['notes']): ?>
                            <li class="mb-1">
                                <i class="fas fa-sticky-note text-warning"></i>
                                <?php echo htmlspecialchars($item['notes']); ?>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-footer bg-transparent d-flex gap-2">
                        <?php if ($daysLeft <= 0): ?>
                        <a href="hatch.php?id=<?php echo $item['id']; ?>" class="btn btn-success btn-sm flex-fill">
                            <i class="fas fa-kiwi-bird"></i> هەڵاتن
                        </a>
                        <?php else: ?>
                        <a href="hatch.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-success btn-sm flex-fill">
                            <i class="fas fa-kiwi-bird"></i> هەڵاتن
                        </a>
                        <?php endif; ?>
                        <a href="#" onclick="return confirmDelete('delete.php?id=<?php echo $item['id']; ?>', 'ئایا دڵنیایت لە سڕینەوەی ئەم گرووپە لە مەفقەس؟')" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- History Table -->
<div class="card">
    <div class="card-header bg-secondary text-white">
        <i class="fas fa-history"></i> مێژووی مەفقەس (<?php echo count($filteredItems); ?>)
    </div>
    <div class="card-body">
        <?php if (count($incubatorItems) > 0): ?>
        <div class="table-responsive">
            <table class="table data-table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th class="text-center">ناوی گرووپ</th>
                        <th class="text-center">کڕیار</th>
                        <th class="text-center">ژمارەی هێلکە</th>
                        <th class="text-center">ڕۆژی دانان</th>
                        <th class="text-center">ڕۆژی دەرچوون</th>
                        <th class="text-center">بار</th>
                        <th class="text-center">هەڵاتوو</th>
                        <th class="text-center">خەسارە</th>
                        <th class="text-center" style="width: 120px;">کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredItems as $index => $item): 
                        $daysLeft = 0;
                        if ($item['status'] === 'incubating') {
                            $today = new DateTime();
                            $hatchDate = new DateTime($item['expected_hatch_date']);
                            $daysLeft = (int)$today->diff($hatchDate)->format('%r%a');
                        }
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $index + 1; ?></td>
                        <td class="text-center">
                            <strong><?php echo htmlspecialchars($item['group_name']); ?></strong>
                            <?php if ($item['female_batch'] || $item['male_batch']): ?>
                            <br><small class="text-muted">
                                <?php if ($item['female_batch']): ?>♀ <?php echo $item['female_batch']; ?><?php endif; ?>
                                <?php if ($item['male_batch']): ?> | ♂ <?php echo $item['male_batch']; ?><?php endif; ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($item['customer_name']): ?>
                                <span class="badge bg-primary-subtle text-primary"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($item['customer_name']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">خۆمان</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><strong><?php echo number_format($item['egg_quantity']); ?></strong></td>
                        <td class="text-center"><?php echo formatDate($item['entry_date']); ?></td>
                        <td class="text-center"><?php echo formatDate($item['expected_hatch_date']); ?></td>
                        <td class="text-center">
                            <?php if ($item['status'] === 'incubating'): ?>
                                <?php if ($daysLeft <= 0): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> ئامادەیە!</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-fire"></i> <?php echo $daysLeft; ?> ڕۆژ ماوە</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-info"><i class="fas fa-check-double"></i> هەڵاتوو</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($item['hatched_count'] > 0): ?>
                                <span class="badge bg-success"><?php echo number_format($item['hatched_count']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($item['damaged_count'] > 0): ?>
                                <span class="badge bg-danger"><?php echo number_format($item['damaged_count']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <?php if ($item['status'] === 'incubating'): ?>
                                <a href="hatch.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-success" title="هەڵاتن">
                                    <i class="fas fa-kiwi-bird"></i>
                                </a>
                                <?php endif; ?>
                                <a href="#" onclick="return confirmDelete('delete.php?id=<?php echo $item['id']; ?>', 'ئایا دڵنیایت لە سڕینەوەی ئەم تۆمارە؟')" class="btn btn-outline-danger" title="سڕینەوە">
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
            <i class="fas fa-temperature-high" style="font-size: 3rem; color: #f97316;"></i>
            <h4>هیچ هێلکەیەک لە مەفقەسدا نیە</h4>
            <p>هیچ هێلکەیەک لە مەفقەس دانەنراوە</p>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> دانانەوەی هێلکە
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
