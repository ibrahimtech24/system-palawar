<?php
session_start();

// Destroy session
$_SESSION = array();
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
