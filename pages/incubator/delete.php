<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: list.php');
    exit;
}

// Get incubator record
$db->query("SELECT * FROM incubator WHERE id = :id");
$db->bind(':id', $id);
$item = $db->single();

if (!$item) {
    header('Location: list.php');
    exit;
}

// If still incubating, return eggs to egg stock
if ($item['status'] === 'incubating' && $item['egg_id']) {
    $db->query("UPDATE eggs SET quantity = quantity + :qty WHERE id = :egg_id");
    $db->bind(':qty', $item['egg_quantity']);
    $db->bind(':egg_id', $item['egg_id']);
    $db->execute();
}

// If hatched, clean up related chicks
if ($item['status'] === 'hatched') {
    $db->query("UPDATE chicks SET incubator_id = NULL WHERE incubator_id = :id");
    $db->bind(':id', $id);
    $db->execute();
}

// Delete incubator record
$db->query("DELETE FROM incubator WHERE id = :id");
$db->bind(':id', $id);
$db->execute();

header('Location: list.php?deleted=1');
exit;
?>
