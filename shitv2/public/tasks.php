<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Task.php';

// Check of gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$taskClass = new Task($db);
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
                $result = $taskClass->create(
                    $userId,
                    $_POST['title'] ?? '',
                    $_POST['description'] ?? '',
                    $_POST['deadline'] ?? '',
                    $_POST['priority'] ?? 'normaal',
                    $_POST['status'] ?? 'te_doen'
                );
                if ($result) {
                    $message = 'Taak succesvol aangemaakt!';
                } else {
                    $error = 'Fout bij aanmaken taak.';
                }
                break;

            case 'update':
                $taskId = (int)$_POST['task_id'];
                $data = [
                    'title' => $_POST['title'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'deadline' => $_POST['deadline'] ?? '',
                    'priority' => $_POST['priority'] ?? 'normaal',
                    'status' => $_POST['status'] ?? 'te_doen'
                ];
                if ($taskClass->update($taskId, $userId, $data)) {
                    $message = 'Taak succesvol bijgewerkt!';
                } else {
                    $error = 'Fout bij bijwerken taak.';
                }
                break;

            case 'delete':
                $taskId = (int)$_POST['task_id'];
                if ($taskClass->delete($taskId, $userId)) {
                    $message = 'Taak succesvol verwijderd!';
                } else {
                    $error = 'Fout bij verwijderen taak.';
                }
                break;
        }
    }
}

// Filter op status
$statusFilter = $_GET['status'] ?? null;
$tasks = $taskClass->getTasksByUser($userId, $statusFilter);
$stats = $taskClass->getStats($userId);

// Edit mode
$editTask = null;
if (isset($_GET['edit'])) {
    $editTaskId = (int)$_GET['edit'];
    $editTask = $taskClass->getTaskById($editTaskId, $userId);
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Taken - <?= APP_NAME ?></title>
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
            <h2>Statistieken</h2>
            <table>
                <tr>
                    <td>Openstaand</td>
                    <td><?= $stats['openstaand'] ?></td>
                    <td>Afgerond</td>
                    <td><?= $stats['completed'] ?></td>
                    <td>Totaal</td>
                    <td><?= $stats['total'] ?></td>
                    <td>% Klaar</td>
                    <td><?= $stats['percentage_completed'] ?>%</td>
                </tr>
            </table>
        </div>

        <div class="card">
            <p>
                <strong>Filter:</strong>
                <a href="tasks.php">Alle</a> | 
                <a href="tasks.php?status=te_doen">Te doen</a> | 
                <a href="tasks.php?status=bezig">Bezig</a> | 
                <a href="tasks.php?status=afgerond">Afgerond</a>
            </p>
        </div>

        <div class="card">
            <h2><?= $editTask ? 'Taak Bewerken' : 'Nieuwe Taak' ?></h2>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $editTask ? 'update' : 'create' ?>">
                <?php if ($editTask): ?>
                    <input type="hidden" name="task_id" value="<?= $editTask['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Titel *</label>
                    <input type="text" name="title" required value="<?= $editTask ? htmlspecialchars($editTask['title']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label>Beschrijving</label>
                    <textarea name="description"><?= $editTask ? htmlspecialchars($editTask['description']) : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Deadline *</label>
                    <input type="date" name="deadline" required value="<?= $editTask ? $editTask['deadline'] : '' ?>">
                </div>
                
                <div class="form-group">
                    <label>Prioriteit</label>
                    <select name="priority">
                        <option value="laag" <?= ($editTask && $editTask['priority'] === 'laag') ? 'selected' : '' ?>>Laag</option>
                        <option value="normaal" <?= ($editTask && $editTask['priority'] === 'normaal') || !$editTask ? 'selected' : '' ?>>Normaal</option>
                        <option value="hoog" <?= ($editTask && $editTask['priority'] === 'hoog') ? 'selected' : '' ?>>Hoog</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="te_doen" <?= ($editTask && $editTask['status'] === 'te_doen') || !$editTask ? 'selected' : '' ?>>Te doen</option>
                        <option value="bezig" <?= ($editTask && $editTask['status'] === 'bezig') ? 'selected' : '' ?>>Bezig</option>
                        <option value="afgerond" <?= ($editTask && $editTask['status'] === 'afgerond') ? 'selected' : '' ?>>Afgerond</option>
                    </select>
                </div>
                
                <button type="submit"><?= $editTask ? 'Bijwerken' : 'Toevoegen' ?></button>
                <?php if ($editTask): ?>
                    <a href="tasks.php" class="btn btn-secondary">Annuleren</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2>Mijn Taken (<?= count($tasks) ?>)</h2>
            
            <?php if (empty($tasks)): ?>
                <p>Geen taken gevonden.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Titel</th>
                        <th>Deadline</th>
                        <th>Prioriteit</th>
                        <th>Status</th>
                        <th>Acties</th>
                    </tr>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($task['title']) ?></strong>
                                <?php if ($task['description']): ?>
                                    <br><small><?= nl2br(htmlspecialchars($task['description'])) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d-m-Y', strtotime($task['deadline'])) ?></td>
                            <td><?= ucfirst($task['priority']) ?></td>
                            <td><?= str_replace('_', ' ', ucfirst($task['status'])) ?></td>
                            <td>
                                <a href="tasks.php?edit=<?= $task['id'] ?>">Bewerken</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je deze taak wilt verwijderen?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <button type="submit">Verwijderen</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
