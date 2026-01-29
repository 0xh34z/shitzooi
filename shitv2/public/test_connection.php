<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

echo "<h1>Database Connection Test</h1>";

try {
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: #2a7d2e;'>Database connectie succesvol!</p>";
    
    // Check users in database
    echo "<h2>Users in database:</h2>";
    $stmt = $db->query("SELECT id, name, email, role FROM users");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p style='color: red;'>Geen users gevonden! Database is leeg?</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Naam</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test password
    echo "<h2>Password Test:</h2>";
    require_once __DIR__ . '/../classes/User.php';
    $userObj = new User($db);
    
    $testPassword = "admin123";
    $testHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    echo "<p>Test password: " . htmlspecialchars($testPassword) . "</p>";
    echo "<p>Test hash: " . htmlspecialchars($testHash) . "</p>";
    
    if ($userObj::verifyPassword($testPassword, $testHash)) {
        echo "<p style='color: #2a7d2e;'>Password verify werkt!</p>";
    } else {
        echo "<p style='color: #c33;'>Password verify faalt!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: #c33;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
