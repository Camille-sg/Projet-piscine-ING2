<?php
require 'config.php';

$action = $_GET['action'] ?? '';
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ── Connexion ──────────────────────────────────────────
    case 'login':
        $email = trim($data['email'] ?? '');
        $mdp   = $data['mdp'] ?? '';

        if (!$email || !$mdp) {
            http_response_code(400);
            echo json_encode(['succes' => false, 'erreur' => 'Champs manquants.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM comptes WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($mdp, $user['mdp'])) {
            $_SESSION['user'] = [
                'id'     => (int)$user['id'],
                'prenom' => $user['prenom'],
                'nom'    => $user['nom'],
                'email'  => $user['email'],
                'role'   => $user['role'],
            ];
            echo json_encode(['succes' => true, 'user' => $_SESSION['user']]);
        } else {
            http_response_code(401);
            echo json_encode(['succes' => false, 'erreur' => 'Email ou mot de passe incorrect.']);
        }
        break;

    // ── Inscription ────────────────────────────────────────
    case 'inscription':
        $prenom = trim($data['prenom'] ?? '');
        $nom    = trim($data['nom']    ?? '');
        $email  = trim($data['email']  ?? '');
        $mdp    = $data['mdp'] ?? '';

        if (!$prenom || !$nom || !$email || !$mdp) {
            http_response_code(400);
            echo json_encode(['succes' => false, 'erreur' => 'Tous les champs sont requis.']);
            exit;
        }
        if (strlen($mdp) < 6) {
            echo json_encode(['succes' => false, 'erreur' => 'Le mot de passe doit contenir au moins 6 caractères.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM comptes WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['succes' => false, 'erreur' => 'Cet email est déjà utilisé.']);
            exit;
        }

        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO comptes (prenom, nom, email, mdp) VALUES (?, ?, ?, ?)");
        $stmt->execute([$prenom, $nom, $email, $hash]);
        $id = (int)$pdo->lastInsertId();

        $_SESSION['user'] = [
            'id' => $id, 'prenom' => $prenom, 'nom' => $nom,
            'email' => $email, 'role' => 'etudiant',
        ];
        echo json_encode(['succes' => true, 'user' => $_SESSION['user']]);
        break;

    // ── Déconnexion ────────────────────────────────────────
    case 'logout':
        session_destroy();
        echo json_encode(['succes' => true]);
        break;

    // ── Vérification session ───────────────────────────────
    case 'session':
        if (!empty($_SESSION['user'])) {
            echo json_encode(['connecte' => true, 'user' => $_SESSION['user']]);
        } else {
            echo json_encode(['connecte' => false]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['erreur' => 'Action inconnue.']);
}