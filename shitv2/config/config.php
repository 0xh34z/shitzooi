<?php
// Load .env file
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("ERROR: .env file not found. Please copy .env.example to .env and configure it.");
}

$envVars = parse_ini_file($envFile);
if ($envVars === false) {
    die("ERROR: Failed to parse .env file.");
}

// Database configuratie
define('DB_HOST', $envVars['DB_HOST'] ?? 'localhost');
define('DB_NAME', $envVars['DB_NAME'] ?? 'studybuddy');
define('DB_USER', $envVars['DB_USER'] ?? 'root');
define('DB_PASS', $envVars['DB_PASS'] ?? '');
define('DB_CHARSET', $envVars['DB_CHARSET'] ?? 'utf8mb4');

// Applicatie configuratie
define('BASE_URL', $envVars['BASE_URL'] ?? 'http://localhost');
define('APP_NAME', $envVars['APP_NAME'] ?? 'StudyBuddy');

// Sessie configuratie (NB: session_start is called before this config is loaded in each file)
// These ini_set calls can't be used here because session is already active
// Instead, configure these in php.ini or .user.ini if needed

// Error reporting (development)
error_reporting(E_ALL);
ini_set('display_errors', $envVars['DISPLAY_ERRORS'] ?? 1);

// Timezone
date_default_timezone_set('Europe/Amsterdam');

// Autoloader voor classes
spl_autoload_register(function ($className) {
    $classPath = __DIR__ . '/../classes/' . $className . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

// CSRF Token functies
/**
 * Genereer CSRF token en sla op in sessie
 * @return string
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valideer CSRF token van POST request
 * @return bool
 */
function validateCSRFToken(): bool {
    if (!isset($_POST['csrf_token'])) {
        return false;
    }
    
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Rendereer hidden CSRF input field
 * @return string
 */
function csrfField(): string {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
