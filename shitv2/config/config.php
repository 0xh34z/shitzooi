<?php
// Database configuratie
define('DB_HOST', 'localhost');
define('DB_NAME', 'studybuddy');
define('DB_USER', 'kasper');
define('DB_PASS', '2dCnbSf4j5');
define('DB_CHARSET', 'utf8mb4');

// Applicatie configuratie
define('BASE_URL', 'http://localhost');
define('APP_NAME', 'StudyBuddy');

// Sessie configuratie
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Zet op 1 als je HTTPS gebruikt

// Error reporting (development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Europe/Amsterdam');

// Autoloader voor classes
spl_autoload_register(function ($className) {
    $classPath = __DIR__ . '/../classes/' . $className . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});
