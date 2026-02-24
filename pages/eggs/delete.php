<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Check if eggs are used in incubator
    $db->query("SELECT COUNT(*) as count FROM incubator WHERE egg_id = :id AND status = 'incubating'");
    $db->bind(':id', $id);
    $inIncubator = $db->single()['count'] ?? 0;
    
    if ($inIncubator > 0) {
        // Cannot delete - eggs are in incubator
        header('Location: list.php?error=incubator');
        exit;
    }
    
    // Check if eggs are referenced in sales
    $db->query("SELECT COUNT(*) as count FROM sales WHERE item_type = 'egg' AND item_id = :id");
    $db->bind(':id', $id);
    $inSales = $db->single()['count'] ?? 0;
    
    if ($inSales > 0) {
        // Cannot delete - eggs are referenced in sales
        header('Location: list.php?error=sales');
        exit;
    }
    
    // Safe to delete
    $db->query("DELETE FROM eggs WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();
}

header('Location: list.php?deleted=1');
exit;
