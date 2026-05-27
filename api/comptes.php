<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = $_SESSION['user'] ?? null;

if ($method === 'GET') {
    if (!$user || !in_array($user['role'], ['admin', 'manager'])) { echo json_encode([]); exit; }
    $stmt = $pdo->query("SELECT id, prenom, nom, email, role, cree_le FROM comptes ORDER BY cree_le DESC");
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    if (!$user || !in_array($user['role'], ['admin', 'manager'])) {
        http_response_code(403); echo json_encode(['succes' => false]); exit;
    }
    $data   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'role':
            $roles_ok = ['etudiant', 'manager', 'admin'];
            $role = in_array($data['role'] ?? '', $roles_ok) ? $data['role'] : 'etudiant';
            $pdo->prepare("UPDATE comptes SET role = ? WHERE id = ?")->execute([$role, (int)$data['id']]);
            echo json_encode(['succes' => true]);
            break;

        case 'supprimer':
            $id = (int)($data['id'] ?? 0);
            if ($id === $user['id']) {
                echo json_encode(['succes' => false, 'erreur' => 'Impossible de supprimer son propre compte.']); exit;
            }
            $pdo->prepare("DELETE FROM comptes WHERE id = ?")->execute([$id]);
            echo json_encode(['succes' => true]);
            break;
    }
}