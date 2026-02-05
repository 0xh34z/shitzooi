<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

// Als al ingelogd, redirect naar dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken()) {
        $error = 'Beveiligingstoken ongeldig. Probeer opnieuw.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($name) || empty($email) || empty($password) || empty($password_confirm)) {
            $error = 'Vul alle velden in';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ongeldig email adres';
    } elseif (strlen($password) < 10) {
        $error = 'Wachtwoord moet minimaal 10 karakters zijn';
    } elseif ($password !== $password_confirm) {
        $error = 'Wachtwoorden komen niet overeen';
    } else {
        $db = Database::getInstance()->getConnection();
        $userObj = new User($db);
        
        $userId = $userObj->register($name, $email, $password);
        
        if ($userId) {
            $success = 'Account aangemaakt! Je kunt nu inloggen.';
        } else {
            $error = 'Registratie mislukt. Email bestaat mogelijk al.';
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registreren - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <h1><?= APP_NAME ?></h1>
        <p class="subtitle">Maak een nieuw account aan</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message">
                <?= htmlspecialchars($success) ?>
                <br><a href="login.php">→ Ga naar login</a>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="name">Naam</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Wachtwoord (min. 10 karakters)</label>
                <input type="password" id="password" name="password" required minlength="10">
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Bevestig wachtwoord</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            
            <button type="submit">Registreren</button>
        </form>
        
        <div class="links">
            Al een account? <a href="login.php">Log hier in</a>
        </div>
    </div>
</body>
</html>
