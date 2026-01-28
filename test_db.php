<!DOCTYPE html>
<html>
<head>
    <title>PHP Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; }
        .info { background: #f0f0f0; padding: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>PHP is werkend!</h1>
    <div class="info">
        <h2>Server Informatie</h2>
        <p><strong>PHP Versie:</strong> <?php echo phpversion(); ?></p>
        <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
        <p><strong>Tijd:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>Timezone (PHP):</strong> <?php echo date_default_timezone_get(); ?></p>
        <p><strong>Timezone (Systeem):</strong> <?php echo shell_exec('tzutil /g'); ?></p>
        <p><strong>Huidige Unix timestamp:</strong> <?php echo time(); ?></p>
    </div>
    
    <?php
    // Test database connectie
    $host = 'localhost';
    $db = 'information_schema';
    $user = 'h34z';
    $pass = '2dCnbSf4j5';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        echo '<div class="info" style="background: #d4edda; margin-top: 20px;">';
        echo '<h2>✓ Database Connectie Succesvol</h2>';
        echo '<p>Verbonden met database: ' . htmlspecialchars($db) . '</p>';
        echo '</div>';
    } catch(PDOException $e) {
        echo '<div class="info" style="background: #f8d7da; margin-top: 20px;">';
        echo '<h2>✗ Database Connectie Mislukt</h2>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    ?>
</body>
</html>
