<?php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'projet_info');
define('DB_USER', 'root');
define('DB_PASS', 'root');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT
            . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode(['erreur' => 'Connexion BDD impossible : ' . $e->getMessage()]));
}

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getAuthUser(PDO $pdo, array $data = []): ?array {
    if (!empty($_SESSION['user'])) return $_SESSION['user'];
    $email = $data['_user'] ?? '';
    if (!$email) return null;
    $stmt = $pdo->prepare("SELECT id, prenom, nom, email, role FROM comptes WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}