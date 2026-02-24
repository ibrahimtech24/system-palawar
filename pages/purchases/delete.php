<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Get purchase record first
    $db->query("SELECT * FROM purchases WHERE id = :id");
    $db->bind(':id', $id);
    $purchase = $db->single();
    
    if ($purchase) {
        // Delete related transaction
        $db->query("DELETE FROM transactions WHERE reference_type = 'purchase' AND reference_id = :id");
        $db->bind(':id', $id);
        $db->execute();
        
        // Delete purchase record
        $db->query("DELETE FROM purchases WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
    }
}

header('Location: list.php?deleted=1');
exit;
