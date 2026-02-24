<?php
/**
 * AJAX endpoint for adding new customer from sales form
 */
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'نەتوانرای چوونەژوورەوە']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_customer') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($name)) {
        $response['message'] = 'تکایە ناوی کڕیار بنووسە';
        echo json_encode($response);
        exit;
    }
    
    try {
        $db->query("INSERT INTO customers (name, phone, address, created_at) VALUES (:name, :phone, :address, NOW())");
        $db->bind(':name', $name);
        $db->bind(':phone', $phone);
        $db->bind(':address', $address);
        
        if ($db->execute()) {
            $response['success'] = true;
            $response['customer_id'] = $db->lastInsertId();
            $response['message'] = 'کڕیار زیاد کرا';
        } else {
            $response['message'] = 'هەڵە لە زیادکردنی کڕیار';
        }
    } catch (Exception $e) {
        $response['message'] = 'هەڵەیەک ڕوویدا';
    }
} else {
    $response['message'] = 'داواکاری نادروست';
}

echo json_encode($response);
