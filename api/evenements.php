<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'GET') {
    $stmt = $pdo->query(
        "SELECT e.*,
                a.titre  AS activite_titre,
                a.couleur AS activite_couleur
         FROM evenements e
         LEFT JOIN activites a ON e.activite_id = a.id
         ORDER BY e.date_debut ASC"
    );
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['id']            = (int)$row['id'];
        $row['activite_id']   = (int)$row['activite_id'];
        $row['places_max']    = (int)$row['places_max'];
        $row['places_prises'] = (int)$row['places_prises'];
    }
    echo json_encode($rows);
    exit;
}

if ($method === 'POST') {
    $authUser = getAuthUser($pdo, $body);

    if (!$authUser) {
        http_response_code(401);
        echo json_encode(['succes' => false, 'erreur' => 'Session expirée. Reconnecte-toi.']);
        exit;
    }
    if (!in_array($authUser['role'], ['admin', 'manager'])) {
        http_response_code(403);
        echo json_encode(['succes' => false, 'erreur' => 'Accès refusé.']);
        exit;
    }

    $action = $body['action'] ?? '';

    switch ($action) {
        case 'creer':
            if (empty($body['titre'])) {
                echo json_encode(['succes' => false, 'erreur' => 'Le titre est obligatoire.']); exit;
            }
            if (empty($body['date_debut']) || empty($body['date_fin'])) {
                echo json_encode(['succes' => false, 'erreur' => 'Les dates sont obligatoires.']); exit;
            }
            $stmt = $pdo->prepare(
                "INSERT INTO evenements
                 (titre, activite_id, date_debut, date_fin, lieu, places_max, description, statut)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'approuve')"
            );
            $stmt->execute([
                $body['titre'],
                (int)($body['activite_id'] ?? 0),
                $body['date_debut'],
                $body['date_fin'],
                $body['lieu']        ?? '',
                (int)($body['places_max'] ?? 20),
                $body['description'] ?? '',
            ]);
            echo json_encode(['succes' => true, 'id' => (int)$pdo->lastInsertId()]);
            break;

        case 'statut':
            $ok     = ['approuve', 'refuse', 'en_attente'];
            $statut = in_array($body['statut'] ?? '', $ok) ? $body['statut'] : 'en_attente';
            $pdo->prepare("UPDATE evenements SET statut = ? WHERE id = ?")
                ->execute([$statut, (int)($body['id'] ?? 0)]);
            echo json_encode(['succes' => true]);
            break;

        case 'supprimer':
            $pdo->prepare("DELETE FROM evenements WHERE id = ?")
                ->execute([(int)($body['id'] ?? 0)]);
            echo json_encode(['succes' => true]);
            break;

        default:
            echo json_encode(['succes' => false, 'erreur' => "Action inconnue : $action"]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['erreur' => 'Méthode non autorisée']);