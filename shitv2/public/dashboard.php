<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/DashboardHelper.php';

// Check of gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

// Haal dashboard data op
$db = Database::getInstance()->getConnection();
$dashboard = new DashboardHelper($db, $userId);
$data = $dashboard->getDashboardData();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
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
                <?php if ($userRole === 'admin'): ?>
                    <a href="admin.php">Admin Panel</a>
                <?php endif; ?>
                <span style="margin-left: 15px;"><?= htmlspecialchars($userName) ?></span>
                <?php if ($userRole === 'admin'): ?>
                    <span style="margin-left: 5px;">[ADMIN]</span>
                <?php endif; ?>
                <a href="logout.php">Uitloggen</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="card">
            <h2>Welkom, <?= htmlspecialchars($userName) ?></h2>
            <p>Dit is je persoonlijke dashboard.</p>
        </div>
        
        <div class="card">
            <h2>Statistieken</h2>
            <table>
                <tr>
                    <th>Categorie</th>
                    <th>Aantal</th>
                </tr>
                <tr>
                    <td>Openstaande Taken</td>
                    <td><?= $data['tasks']['openstaand'] ?></td>
                </tr>
                <tr>
                    <td>Voortgang</td>
                    <td><?= $data['tasks']['percentage_completed'] ?>%</td>
                </tr>
                <tr>
                    <td>Groepen</td>
                    <td><?= $data['groups']['total'] ?></td>
                </tr>
                <tr>
                    <td>Aankomende Afspraken</td>
                    <td><?= $data['appointments']['upcoming'] ?></td>
                </tr>
            </table>
        </div>
        
        <?php if ($data['next_appointment']): ?>
        <div class="card">
            <h2>Eerstvolgende Afspraak</h2>
            <p><strong><?= htmlspecialchars($data['next_appointment']['title']) ?></strong></p>
            <p>Groep: <?= htmlspecialchars($data['next_appointment']['group_name']) ?></p>
            <p>Datum: <?= date('d-m-Y H:i', strtotime($data['next_appointment']['appointment_date'] . ' ' . $data['next_appointment']['appointment_time'])) ?></p>
            <?php if ($data['next_appointment']['location']): ?>
                <p>Locatie: <?= htmlspecialchars($data['next_appointment']['location']) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($data['upcoming_tasks'])): ?>
        <div class="card">
            <h2>Komende Taken</h2>
            <?php foreach (array_slice($data['upcoming_tasks'], 0, 5) as $task): ?>
                <div style="padding: 10px; border-bottom: 1px solid black;">
                    <p><strong><?= htmlspecialchars($task['title']) ?></strong></p>
                    <p>Deadline: <?= date('d-m-Y', strtotime($task['deadline'])) ?> | 
                       Prioriteit: <?= ucfirst($task['priority']) ?> | 
                       Status: <?= str_replace('_', ' ', ucfirst($task['status'])) ?></p>
                </div>
            <?php endforeach; ?>
            <p style="margin-top: 10px;"><a href="tasks.php">Bekijk alle taken</a></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
