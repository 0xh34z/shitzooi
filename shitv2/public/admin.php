<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

// Check of gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check of gebruiker admin is
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$userClass = new User($db);
$userId = $_SESSION['user_id'];

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken()) {
        $error = 'Beveiligingstoken ongeldig. Probeer opnieuw.';
    } elseif (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'block':
                $targetUserId = (int)$_POST['user_id'];
                if ($targetUserId !== $userId) { // Kan jezelf niet blokkeren
                    if ($userClass->blockUser($targetUserId)) {
                        $message = 'Gebruiker succesvol geblokkeerd.';
                    } else {
                        $error = 'Fout bij blokkeren gebruiker.';
                    }
                } else {
                    $error = 'Je kunt jezelf niet blokkeren.';
                }
                break;

            case 'unblock':
                $targetUserId = (int)$_POST['user_id'];
                if ($userClass->unblockUser($targetUserId)) {
                    $message = 'Gebruiker succesvol gedeblokkeerd.';
                } else {
                    $error = 'Fout bij deblokkeren gebruiker.';
                }
                break;

            case 'change_role':
                $targetUserId = (int)$_POST['user_id'];
                $newRole = $_POST['role'];
                if ($targetUserId !== $userId) { // Kan eigen rol niet wijzigen
                    if ($userClass->changeRole($targetUserId, $newRole)) {
                        $message = 'Rol succesvol gewijzigd.';
                    } else {
                        $error = 'Fout bij wijzigen rol.';
                    }
                } else {
                    $error = 'Je kunt je eigen rol niet wijzigen.';
                }
                break;

            case 'delete':
                $targetUserId = (int)$_POST['user_id'];
                if ($targetUserId !== $userId) { // Kan jezelf niet verwijderen
                    if ($userClass->deleteUser($targetUserId)) {
                        $message = 'Gebruiker succesvol verwijderd.';
                    } else {
                        $error = 'Fout bij verwijderen gebruiker.';
                    }
                } else {
                    $error = 'Je kunt jezelf niet verwijderen.';
                }
                break;
        }
    }
}

// Haal alle gebruikers op
$users = $userClass->getAllUsers();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .blocked-user {
            background-color: #ffe0e0;
        }
        .admin-user {
            background-color: #e0f0ff;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1><?= APP_NAME ?></h1>
            <div>
                <a href="dashboard.php">Dashboard</a>
                <a href="tasks.php">Taken</a>
                <a href="groups.php">Groepen</a>
                <a href="admin.php">Admin Panel</a>
                <span style="margin-left: 15px;"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <span style="margin-left: 5px;">[ADMIN]</span>
                <a href="logout.php">Uitloggen</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <h1>Admin Panel - Gebruikersbeheer</h1>
            <p>Welkom in het admin panel. Hier kun je alle gebruikers beheren.</p>
        </div>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Statistieken</h2>
            <table>
                <tr>
                    <td><strong>Totaal Gebruikers</strong></td>
                    <td><?= count($users) ?></td>
                    <td><strong>Studenten</strong></td>
                    <td><?= count(array_filter($users, fn($u) => $u['role'] === 'student')) ?></td>
                    <td><strong>Admins</strong></td>
                    <td><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></td>
                    <td><strong>Geblokkeerd</strong></td>
                    <td><?= count(array_filter($users, fn($u) => $u['is_blocked'])) ?></td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h2>Alle Gebruikers (<?= count($users) ?>)</h2>
            <p style="font-size: 0.9em; color: #666;">
                Tip: Lichtblauwe rijen = admins, Lichtrode rijen = geblokkeerde accounts
            </p>
            
            <?php if (empty($users)): ?>
                <p>Geen gebruikers gevonden.</p>
            <?php else: ?>
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Naam</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Status</th>
                            <th>Aangemaakt</th>
                            <th>Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="<?= $user['is_blocked'] ? 'blocked-user' : ($user['role'] === 'admin' ? 'admin-user' : '') ?>">
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                                    <?php if ($user['id'] == $userId): ?>
                                        <span style="color: green;">(jij)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php if ($user['id'] !== $userId): ?>
                                        <form method="POST" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <select name="role" onchange="this.form.submit()">
                                                <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </form>
                                    <?php else: ?>
                                        <?= ucfirst($user['role']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_blocked']): ?>
                                        <span style="color: red;">Geblokkeerd</span>
                                    <?php else: ?>
                                        <span style="color: green;">Actief</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d-m-Y H:i', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?php if ($user['id'] !== $userId): ?>
                                        <?php if ($user['is_blocked']): ?>
                                            <form method="POST" style="display: inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="unblock">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" style="background-color: #28a745;">Deblokkeren</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="block">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" style="background-color: #ffc107;">Blokkeren</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen? Deze actie kan niet ongedaan gemaakt worden.')">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" style="background-color: #dc3545;">Verwijderen</button>
                                        </form>
                                    <?php else: ?>
                                        <em style="color: #666;">Geen acties beschikbaar</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Admin Richtlijnen</h2>
            <ul>
                <li><strong>Blokkeren:</strong> Geblokkeerde gebruikers kunnen niet inloggen. Gebruik dit bij misbruik of als tijdelijke maatregel.</li>
                <li><strong>Rol Wijzigen:</strong> Promoveer studenten tot admin voor extra rechten, of degradeer admins terug naar student.</li>
                <li><strong>Verwijderen:</strong> Dit verwijdert de gebruiker permanent inclusief al hun taken en groepslidmaatschappen. Gebruik met voorzichtigheid!</li>
                <li><strong>Jezelf:</strong> Je kunt jezelf niet blokkeren, verwijderen of je eigen rol wijzigen voor veiligheid.</li>
            </ul>
        </div>
    </div>
</body>
</html>
