<?php
// Helper Functions

function generateCode($prefix = 'CODE') {
    return $prefix . '-' . date('Ymd') . '-' . rand(1000, 9999);
}

function formatDate($date, $format = 'Y/m/d') {
    return date($format, strtotime($date));
}

function formatMoney($amount) {
    return number_format($amount, 0) . ' د.ع';
}

function calculateAge($birthDate, $type = 'string') {
    if (empty($birthDate)) return '-';
    
    $birth = new DateTime($birthDate);
    $now = new DateTime();
    $diff = $now->diff($birth);
    
    if ($type === 'days') {
        return $diff->days;
    } elseif ($type === 'months') {
        return ($diff->y * 12) + $diff->m;
    } elseif ($type === 'years') {
        return $diff->y;
    } else {
        // Return Kurdish string
        if ($diff->y > 0) {
            return $diff->y . ' ساڵ و ' . $diff->m . ' مانگ';
        } elseif ($diff->m > 0) {
            return $diff->m . ' مانگ و ' . $diff->d . ' ڕۆژ';
        } else {
            return $diff->d . ' ڕۆژ';
        }
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

function getStatusBadge($status) {
    $badges = [
        'healthy' => '<span class="badge bg-success">ساغ</span>',
        'sick' => '<span class="badge bg-danger">نەخۆش</span>',
        'recovering' => '<span class="badge bg-warning">چاکبوونەوە</span>',
        'dead' => '<span class="badge bg-dark">مردوو</span>',
        'active' => '<span class="badge bg-success">چالاک</span>',
        'sold' => '<span class="badge bg-info">فرۆشراو</span>',
        'laying' => '<span class="badge bg-warning">هێلکەدان</span>',
        'available' => '<span class="badge bg-success">بەردەست</span>',
        'expired' => '<span class="badge bg-danger">بەسەرچوو</span>',
        'hatching' => '<span class="badge bg-warning">لە خورکەدایە</span>',
        'growing' => '<span class="badge bg-info">گەورەبوون</span>',
        'matured' => '<span class="badge bg-success">گەورەبوو</span>',
        'paid' => '<span class="badge bg-success">پارەدراو</span>',
        'pending' => '<span class="badge bg-warning">چاوەڕوان</span>',
        'partial' => '<span class="badge bg-info">بەشێک</span>',
        'grade_a' => '<span class="badge bg-success">پۆلی A</span>',
        'grade_b' => '<span class="badge bg-info">پۆلی B</span>',
        'grade_c' => '<span class="badge bg-warning">پۆلی C</span>',
        'damaged' => '<span class="badge bg-danger">زیانمەند</span>'
    ];
    
    return isset($badges[$status]) ? $badges[$status] : '<span class="badge bg-secondary">' . $status . '</span>';
}

function getCategoryName($category) {
    $categories = [
        'feed' => 'خواردن',
        'medicine' => 'دەرمان',
        'equipment' => 'ئامێر',
        'other' => 'هیتر'
    ];
    
    return isset($categories[$category]) ? $categories[$category] : $category;
}

function getItemTypeName($type) {
    $types = [
        'male_bird' => 'هەوێردەی نێر',
        'female_bird' => 'هەوێردەی مێ',
        'egg' => 'هێلکە',
        'eggs' => 'هێلکە',
        'chick' => 'جوجکە',
        'chicks' => 'جوجکە',
        'warehouse' => 'مەخزەن',
        'feed' => 'خواردن',
        'medicine' => 'دەرمان',
        'equipment' => 'ئامێر',
        'other' => 'هیتر'
    ];
    
    return isset($types[$type]) ? $types[$type] : $type;
}
?>
