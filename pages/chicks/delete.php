<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Get chick record
    $db->query("SELECT * FROM chicks WHERE id = :id");
    $db->bind(':id', $id);
    $chick = $db->single();
    
    if ($chick) {
        // Check if chicks are referenced in sales
        $db->query("SELECT COUNT(*) as count FROM sales WHERE item_type = 'chick' AND item_id = :id");
        $db->bind(':id', $id);
        $inSales = $db->single()['count'] ?? 0;
        
        if ($inSales > 0) {
            header('Location: list.php?error=sales');
            exit;
        }
        
        // If chick came from incubator, update incubator hatched_count
        if (!empty($chick['incubator_id'])) {
            $db->query("UPDATE incubator SET hatched_count = hatched_count - :qty WHERE id = :inc_id AND hatched_count >= :qty");
            $db->bind(':qty', $chick['quantity']);
            $db->bind(':inc_id', $chick['incubator_id']);
            $db->execute();
        }
        
        // Delete chick record
        $db->query("DELETE FROM chicks WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
    }
}

header('Location: list.php?deleted=1');
exit;
