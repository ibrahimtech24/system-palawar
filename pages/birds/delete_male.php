<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    $db->query("DELETE FROM male_birds WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();
}

header('Location: male_list.php?deleted=1');
exit;
