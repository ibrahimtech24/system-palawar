<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Check if bird is parent of eggs
    $db->query("SELECT COUNT(*) as count FROM eggs WHERE male_bird_id = :id");
    $db->bind(':id', $id);
    $hasEggs = $db->single()['count'] ?? 0;
    
    if ($hasEggs > 0) {
        // Clear reference instead of blocking delete
        $db->query("UPDATE eggs SET male_bird_id = NULL WHERE male_bird_id = :id");
        $db->bind(':id', $id);
        $db->execute();
    }
    
    // Check if bird is referenced in sales
    $db->query("SELECT COUNT(*) as count FROM sales WHERE item_type = 'male_bird' AND item_id = :id");
    $db->bind(':id', $id);
    $inSales = $db->single()['count'] ?? 0;
    
    if ($inSales > 0) {
        header('Location: male_list.php?error=sales');
        exit;
    }
    
    $db->query("DELETE FROM male_birds WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();
}

header('Location: male_list.php?deleted=1');
exit;
