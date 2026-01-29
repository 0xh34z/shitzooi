<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Check of gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Logout
session_destroy();
header('Location: login.php');
exit;
