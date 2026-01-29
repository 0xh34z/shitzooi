<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Group.php';
require_once __DIR__ . '/../classes/GroupMember.php';
require_once __DIR__ . '/../classes/Appointment.php';
require_once __DIR__ . '/../classes/AppointmentResponse.php';

// Check of gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$groupClass = new Group($db);
$groupMemberClass = new GroupMember($db);
$appointmentClass = new Appointment($db);
$responseClass = new AppointmentResponse($db);
$userId = $_SESSION['user_id'];

// Check groep ID
if (!isset($_GET['id'])) {
    header('Location: groups.php');
    exit;
}

$groupId = (int)$_GET['id'];
$group = $groupClass->getGroupById($groupId);

// Check of groep bestaat
if (!$group) {
    header('Location: groups.php');
    exit;
}

// Check of gebruiker lid is
if (!$groupMemberClass->isMember($groupId, $userId)) {
    header('Location: groups.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_appointment':
                $result = $appointmentClass->create(
                    $groupId,
                    $userId,
                    $_POST['title'] ?? '',
                    $_POST['appointment_date'] ?? '',
                    $_POST['appointment_time'] ?? '',
                    $_POST['description'] ?? null,
                    $_POST['location'] ?? null
                );
                if ($result) {
                    $message = 'Afspraak succesvol aangemaakt!';
                } else {
                    $error = 'Fout bij aanmaken afspraak.';
                }
                break;

            case 'respond':
                $appointmentId = (int)$_POST['appointment_id'];
                $response = $_POST['response'];
                if ($responseClass->respond($appointmentId, $userId, $response)) {
                    $message = 'Je reactie is opgeslagen!';
                } else {
                    $error = 'Fout bij opslaan reactie.';
                }
                break;

            case 'delete_appointment':
                $appointmentId = (int)$_POST['appointment_id'];
                if ($appointmentClass->delete($appointmentId, $userId)) {
                    $message = 'Afspraak verwijderd.';
                } else {
                    $error = 'Kon afspraak niet verwijderen.';
                }
                break;
        }
    }
}

// Haal data op
$members = $groupClass->getMembers($groupId);
$appointments = $appointmentClass->getAppointmentsByGroup($groupId);
$isOwner = ($group['owner_id'] == $userId);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($group['name']) ?> - <?= APP_NAME ?></title>
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
        <p><a href="groups.php">← Terug naar groepen</a></p>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h1><?= htmlspecialchars($group['name']) ?></h1>
            <?php if ($group['description']): ?>
                <p><?= nl2br(htmlspecialchars($group['description'])) ?></p>
            <?php endif; ?>
            <p>
                Leden: <?= $group['member_count'] ?> | 
                Afspraken: <?= count($appointments) ?>
                <?php if ($isOwner): ?>
                    | Code: <strong><?= $group['invite_code'] ?></strong>
                <?php endif; ?>
            </p>
        </div>

        <div class="card">
            <h2>Leden (<?= count($members) ?>)</h2>
            <table>
                <tr>
                    <th>Naam</th>
                    <th>Rol</th>
                </tr>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= htmlspecialchars($member['name']) ?></td>
                        <td><?= $member['is_owner'] ? 'Eigenaar' : 'Lid' ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card">
            <h2>Afspraken</h2>
            <?php if (empty($appointments)): ?>
                <p>Nog geen afspraken gepland.</p>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): 
                    $isPast = strtotime($appointment['appointment_date']) < strtotime('today');
                    $userResponse = $responseClass->getResponse($appointment['id'], $userId);
                ?>
                    <div class="card" style="background: #f8f8f8;">
                        <h3><?= htmlspecialchars($appointment['title']) ?></h3>
                        <p>
                            Datum: <?= date('d-m-Y', strtotime($appointment['appointment_date'])) ?> om <?= date('H:i', strtotime($appointment['appointment_time'])) ?>
                            <?php if ($appointment['location']): ?>
                                | Locatie: <?= htmlspecialchars($appointment['location']) ?>
                            <?php endif; ?>
                        </p>
                        <?php if ($appointment['description']): ?>
                            <p><?= nl2br(htmlspecialchars($appointment['description'])) ?></p>
                        <?php endif; ?>
                        
                        <p>
                            Reacties: 
                            ✅ <?= $appointment['response_yes'] ?? 0 ?> ja | 
                            ❓ <?= $appointment['response_maybe'] ?? 0 ?> misschien | 
                            ❌ <?= $appointment['response_no'] ?? 0 ?> nee
                        </p>
                        
                        <?php if (!$isPast): ?>
                            <p><strong>Jouw reactie:</strong></p>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="respond">
                                <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                <input type="hidden" name="response" value="erbij">
                                <button type="submit">
                                    <?= $userResponse && $userResponse['response'] === 'erbij' ? '✓ ' : '' ?>Erbij
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="respond">
                                <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                <input type="hidden" name="response" value="misschien">
                                <button type="submit">
                                    <?= $userResponse && $userResponse['response'] === 'misschien' ? '✓ ' : '' ?>Misschien
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="respond">
                                <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                <input type="hidden" name="response" value="niet">
                                <button type="submit">
                                    <?= $userResponse && $userResponse['response'] === 'niet' ? '✓ ' : '' ?>Niet
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($appointment['created_by'] == $userId || $isOwner): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je deze afspraak wilt verwijderen?')">
                                <input type="hidden" name="action" value="delete_appointment">
                                <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                <button type="submit">Verwijderen</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Nieuwe Afspraak</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_appointment">
                
                <div class="form-group">
                    <label>Titel *</label>
                    <input type="text" name="title" required>
                </div>
                
                <div class="form-group">
                    <label>Beschrijving</label>
                    <textarea name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Datum *</label>
                    <input type="date" name="appointment_date" required>
                </div>
                
                <div class="form-group">
                    <label>Tijd *</label>
                    <input type="time" name="appointment_time" required>
                </div>
                
                <div class="form-group">
                    <label>Locatie</label>
                    <input type="text" name="location">
                </div>
                
                <button type="submit">Afspraak Toevoegen</button>
            </form>
        </div>
    </div>
</body>
</html>
