<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h2>Test connexion MySQL</h2><pre>";

$combos = [
    ['127.0.0.1', 3306, 'root', '',     'projet_info'],
    ['127.0.0.1', 3306, 'root', 'root', 'projet_info'],
    ['localhost',  3306, 'root', '',     'projet_info'],
    ['localhost',  3306, 'root', 'root', 'projet_info'],
    ['127.0.0.1', 8889, 'root', 'root', 'projet_info'],
    ['127.0.0.1', 8889, 'root', '',     'projet_info'],
];

foreach ($combos as $c) {
    [$host, $port, $user, $pass, $db] = $c;
    $label = "host=$host port=$port user=$user pass=" . ($pass === '' ? '(vide)' : $pass);
    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✅ OK      — $label\n";
    } catch (PDOException $e) {
        echo "❌ ECHEC   — $label  → " . $e->getMessage() . "\n";
    }
}

echo "</pre>";
echo "<p>Utilise les valeurs de la ligne ✅ dans ton config.php</p>";
