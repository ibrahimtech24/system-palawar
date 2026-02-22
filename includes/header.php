<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo isset($basePath) ? $basePath : ''; ?>css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php">
                <i class="fas fa-feather-alt"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage ?? '') === 'home' ? 'active' : ''; ?>" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php">
                            <i class="fas fa-home"></i> سەرەکی
                        </a>
                    </li>
                    
                    <!-- مەخزەن Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($currentPage ?? '') === 'warehouse' ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-warehouse"></i> مەخزەن
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/warehouse/list.php"><i class="fas fa-list"></i> لیستی کاڵاکان</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/warehouse/add.php"><i class="fas fa-plus"></i> زیادکردنی کاڵا</a></li>
                        </ul>
                    </li>
                    
                    <!-- هەوێردەکان Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($currentPage ?? '') === 'birds' ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-dove"></i> هەوێردە
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header"><i class="fas fa-mars"></i> هەوێردەی نێر</h6></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/birds/male_list.php"><i class="fas fa-list"></i> لیستی نێرەکان</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/birds/add_male.php"><i class="fas fa-plus"></i> زیادکردنی نێر</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-venus"></i> هەوێردەی مێ</h6></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/birds/female_list.php"><i class="fas fa-list"></i> لیستی مێیەکان</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/birds/add_female.php"><i class="fas fa-plus"></i> زیادکردنی مێ</a></li>
                        </ul>
                    </li>
                    
                    <!-- هێلکە و جوجکە Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($currentPage ?? '') === 'production' ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-egg"></i> هێلکە و جوجکە
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header"><i class="fas fa-egg"></i> هێلکە</h6></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/eggs/list.php"><i class="fas fa-list"></i> لیستی هێلکەکان</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/eggs/add.php"><i class="fas fa-plus"></i> زیادکردنی هێلکە</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-kiwi-bird"></i> جوجکە</h6></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/chicks/list.php"><i class="fas fa-list"></i> لیستی جوجکەکان</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/chicks/add.php"><i class="fas fa-plus"></i> زیادکردنی جوجکە</a></li>
                        </ul>
                    </li>
                    
                    <!-- فرۆشتن و کڕین Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($currentPage ?? '') === 'sales' ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-shopping-cart"></i> فرۆشتن و کڕین
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header"><i class="fas fa-cash-register"></i> فرۆشتن</h6></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/sales/list.php"><i class="fas fa-list"></i> لیستی فرۆشتنەکان</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/sales/add.php"><i class="fas fa-plus"></i> فرۆشتنی نوێ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-truck"></i> کڕین</h6></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/purchases/list.php"><i class="fas fa-list"></i> لیستی کڕینەکان</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/purchases/add.php"><i class="fas fa-plus"></i> کڕینی نوێ</a></li>
                        </ul>
                    </li>
                    
                    <!-- کڕیاران و دابینکەران -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i> کەسەکان
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/customers/list.php"><i class="fas fa-user-tie"></i> کڕیاران</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/suppliers/list.php"><i class="fas fa-truck"></i> دابینکەران</a></li>
                        </ul>
                    </li>
                    
                    <!-- راپۆرتەکان Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($currentPage ?? '') === 'reports' ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar"></i> راپۆرتەکان
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/reports/monthly.php"><i class="fas fa-calendar-alt"></i> راپۆرتی مانگانە</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/reports/sales.php"><i class="fas fa-chart-line"></i> راپۆرتی فرۆشتن</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/reports/inventory.php"><i class="fas fa-boxes"></i> راپۆرتی مەخزەن</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/reports/customers.php"><i class="fas fa-users"></i> راپۆرتی کڕیاران</a></li>
                        </ul>
                    </li>
                    
                    <!-- مێژووی مامەڵەکان -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage ?? '') === 'transactions' ? 'active' : ''; ?>" href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/transactions/list.php">
                            <i class="fas fa-history"></i> مێژوو
                        </a>
                    </li>
                </ul>
                
                <!-- Search and utilities -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#searchModal">
                            <i class="fas fa-search"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Search Modal -->
    <div class="modal fade" id="searchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-search"></i> گەڕان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="<?php echo isset($basePath) ? $basePath : ''; ?>search.php" method="GET">
                        <div class="input-group">
                            <input type="text" class="form-control" name="q" placeholder="گەڕان لە سیستەمدا...">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Wrapper -->
    <main class="main-content">
        <?php
        $message = getMessage();
        if ($message): ?>
        <div class="alert alert-<?php echo $message['type'] === 'success' ? 'success' : ($message['type'] === 'error' ? 'danger' : 'warning'); ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <?php echo $message['text']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
