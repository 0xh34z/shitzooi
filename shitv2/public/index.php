<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Als al ingelogd, ga naar dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Anders ga naar login
header('Location: login.php');
exit;
?>
