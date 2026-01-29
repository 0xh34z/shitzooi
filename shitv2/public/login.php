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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vul alle velden in';
    } else {
        $db = Database::getInstance()->getConnection();
        $userObj = new User($db);
        
        $user = $userObj->login($email, $password);
        
        if ($user) {
            // Login succesvol - sla user data op in sessie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Ongeldige inloggegevens of account is geblokkeerd';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <h1><?= APP_NAME ?></h1>
        <p class="subtitle">Log in op je account</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Wachtwoord</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Inloggen</button>
        </form>
        
        <div class="links">
            Nog geen account? <a href="register.php">Registreer hier</a>
        </div>
    </div>
</body>
</html>
