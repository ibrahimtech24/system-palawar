<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Get sale record first to restore inventory
    $db->query("SELECT * FROM sales WHERE id = :id");
    $db->bind(':id', $id);
    $sale = $db->single();
    
    if ($sale) {
        // Restore inventory based on item type
        $item_id = $sale['item_id'] ?? 0;
        $quantity = $sale['quantity'];
        
        if ($item_id > 0 && $quantity > 0) {
            switch ($sale['item_type']) {
                case 'egg':
                    $db->query("UPDATE eggs SET quantity = quantity + :qty WHERE id = :id");
                    $db->bind(':qty', $quantity);
                    $db->bind(':id', $item_id);
                    $db->execute();
                    break;
                case 'chick':
                    $db->query("UPDATE chicks SET quantity = quantity + :qty WHERE id = :id");
                    $db->bind(':qty', $quantity);
                    $db->bind(':id', $item_id);
                    $db->execute();
                    break;
                case 'male_bird':
                    $db->query("UPDATE male_birds SET quantity = quantity + :qty WHERE id = :id");
                    $db->bind(':qty', $quantity);
                    $db->bind(':id', $item_id);
                    $db->execute();
                    break;
                case 'female_bird':
                    $db->query("UPDATE female_birds SET quantity = quantity + :qty WHERE id = :id");
                    $db->bind(':qty', $quantity);
                    $db->bind(':id', $item_id);
                    $db->execute();
                    break;
            }
        }
        
        // Delete related transaction
        $db->query("DELETE FROM transactions WHERE reference_type = 'sale' AND reference_id = :id");
        $db->bind(':id', $id);
        $db->execute();
        
        // Delete sale
        $db->query("DELETE FROM sales WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
    }
}

header('Location: list.php?deleted=1');
exit;
