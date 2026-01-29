<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Group.php';
require_once __DIR__ . '/../classes/GroupMember.php';

// Check of gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$groupClass = new Group($db);
$groupMemberClass = new GroupMember($db);
$userId = $_SESSION['user_id'];

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken()) {
        $error = 'Beveiligingstoken ongeldig. Probeer opnieuw.';
    } elseif (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $result = $groupClass->create(
                    $_POST['name'] ?? '',
                    $userId,
                    $_POST['description'] ?? null
                );
                if ($result) {
                    $message = 'Groep succesvol aangemaakt!';
                } else {
                    $error = 'Fout bij aanmaken groep.';
                }
                break;

            case 'join':
                $inviteCode = strtoupper(trim($_POST['invite_code'] ?? ''));
                $result = $groupClass->joinByInviteCode($inviteCode, $userId);
                if ($result) {
                    $message = 'Je bent lid geworden van de groep!';
                } else {
                    $error = 'Ongeldige code of je bent al lid van deze groep.';
                }
                break;

            case 'leave':
                $groupId = (int)$_POST['group_id'];
                if ($groupClass->leave($groupId, $userId)) {
                    $message = 'Je hebt de groep verlaten.';
                } else {
                    $error = 'Kon groep niet verlaten. Ben je de eigenaar?';
                }
                break;

            case 'delete':
                $groupId = (int)$_POST['group_id'];
                if ($groupClass->delete($groupId, $userId)) {
                    $message = 'Groep succesvol verwijderd.';
                } else {
                    $error = 'Kon groep niet verwijderen. Ben je de eigenaar?';
                }
                break;
        }
    }
    }
}

// Haal groepen op
$groups = $groupMemberClass->getGroupsWithStats($userId);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Groepen - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1><?= APP_NAME ?></h1>
            <div>
                <a href="dashboard.php">Dashboard</a>
                <a href="tasks.php">Taken</a>
                <a href="groups.php">Groepen</a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin.php">Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php">Uitloggen</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Nieuwe Groep Aanmaken</h2>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Groepsnaam *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Beschrijving</label>
                    <textarea name="description"></textarea>
                </div>
                
                <button type="submit">Groep Aanmaken</button>
            </form>
        </div>

        <div class="card">
            <h2>Lid Worden van Groep</h2>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="join">
                
                <div class="form-group">
                    <label>Uitnodigingscode *</label>
                    <input type="text" name="invite_code" required style="text-transform: uppercase;">
                </div>
                
                <button type="submit">Groep Joinen</button>
            </form>
        </div>

        <div class="card">
            <h2>Mijn Groepen (<?= count($groups) ?>)</h2>
            
            <?php if (empty($groups)): ?>
                <p>Je bent nog geen lid van een groep.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Naam</th>
                        <th>Beschrijving</th>
                        <th>Leden</th>
                        <th>Afspraken</th>
                        <th>Rol</th>
                        <th>Acties</th>
                    </tr>
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($group['name']) ?></strong></td>
                            <td><?= $group['description'] ? nl2br(htmlspecialchars($group['description'])) : '-' ?></td>
                            <td><?= $group['member_count'] ?></td>
                            <td><?= $group['appointment_count'] ?></td>
                            <td><?= $group['is_owner'] ? 'Eigenaar' : 'Lid' ?></td>
                            <td>
                                <a href="group_detail.php?id=<?= $group['id'] ?>">Bekijken</a>
                                <?php if ($group['is_owner']): ?>
                                    <br>Code: <strong><?= htmlspecialchars($group['invite_code']) ?></strong>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je deze groep wilt verwijderen?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                        <button type="submit">Verwijderen</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je deze groep wilt verlaten?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="leave">
                                        <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                        <button type="submit">Verlaten</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
