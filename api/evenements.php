<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = $_SESSION['user'] ?? null;

// ── GET : liste des événements ─────────────────────────────
if ($method === 'GET') {
    $stmt = $pdo->query(
        "SELECT e.*, a.titre AS activite_titre, a.couleur AS activite_couleur
         FROM evenements e
         LEFT JOIN activites a ON e.activite_id = a.id
         ORDER BY e.date_debut ASC"
    );
    echo json_encode($stmt->fetchAll());
    exit;
}

// ── POST : créer / changer statut / supprimer ──────────────
if ($method === 'POST') {
    if (!$user) { http_response_code(401); echo json_encode(['succes' => false, 'erreur' => 'Non connecté']); exit; }
    if (!in_array($user['role'], ['admin', 'manager'])) {
        http_response_code(403); echo json_encode(['succes' => false, 'erreur' => 'Accès refusé']); exit;
    }

    $data   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $data['action'] ?? '';

    switch ($action) {

        case 'creer':
            if (empty($data['titre']) || empty($data['date_debut']) || empty($data['date_fin'])) {
                echo json_encode(['succes' => false, 'erreur' => 'Champs obligatoires manquants.']); exit;
            }
            $stmt = $pdo->prepare(
                "INSERT INTO evenements
                 (titre, activite_id, date_debut, date_fin, lieu, places_max, description, statut)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'approuve')"
            );
            $stmt->execute([
                $data['titre'],
                (int)($data['activite_id'] ?? 0),
                $data['date_debut'],
                $data['date_fin'],
                $data['lieu']        ?? '',
                (int)($data['places_max'] ?? 20),
                $data['description'] ?? '',
                            ]);
            echo json_encode(['succes' => true, 'id' => (int)$pdo->lastInsertId()]);
            break;

        case 'statut':
            $statuts_ok = ['approuve', 'refuse', 'en_attente'];
            $statut = in_array($data['statut'] ?? '', $statuts_ok) ? $data['statut'] : 'en_attente';
            $pdo->prepare("UPDATE evenements SET statut = ? WHERE id = ?")->execute([$statut, (int)$data['id']]);
            echo json_encode(['succes' => true]);
            break;

        case 'supprimer':
            $pdo->prepare("DELETE FROM evenements WHERE id = ?")->execute([(int)$data['id']]);
            echo json_encode(['succes' => true]);
            break;

        default:
            echo json_encode(['succes' => false, 'erreur' => 'Action inconnue.']);
    }
}