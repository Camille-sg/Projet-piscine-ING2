<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = $_SESSION['user'] ?? null;

if ($method === 'GET') {
    if (!$user) { echo json_encode([]); exit; }
    if (in_array($user['role'], ['admin', 'manager'])) {
        $stmt = $pdo->query(
            "SELECT r.*, rs.nom AS ressource_nom FROM reservations r
             LEFT JOIN ressources rs ON r.ressource_id = rs.id ORDER BY r.cree_le DESC"
        );
    } else {
        $stmt = $pdo->prepare(
            "SELECT r.*, rs.nom AS ressource_nom FROM reservations r
             LEFT JOIN ressources rs ON r.ressource_id = rs.id
             WHERE r.user_email = ? ORDER BY r.cree_le DESC"
        );
        $stmt->execute([$user['email']]);
    }
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    if (!$user) { http_response_code(401); echo json_encode(['succes' => false]); exit; }
    $data   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'creer':
            $res_id  = (int)($data['ressource_id'] ?? 0);
            $qte     = max(1, (int)($data['quantite'] ?? 1));

            $stmt = $pdo->prepare("SELECT * FROM ressources WHERE id = ?");
            $stmt->execute([$res_id]);
            $res = $stmt->fetch();

            if (!$res || $res['quantite_disponible'] < $qte) {
                echo json_encode(['succes' => false, 'erreur' => 'Quantité insuffisante ou ressource introuvable.']); exit;
            }

            $pdo->beginTransaction();
            $pdo->prepare(
                "INSERT INTO reservations (user_email, ressource_id, date_debut, date_fin, quantite) VALUES (?, ?, ?, ?, ?)"
            )->execute([$user['email'], $res_id, $data['date_debut'], $data['date_fin'], $qte]);
            $pdo->prepare(
                "UPDATE ressources SET quantite_disponible = quantite_disponible - ? WHERE id = ?"
            )->execute([$qte, $res_id]);
            $pdo->commit();
            echo json_encode(['succes' => true]);
            break;

        case 'statut':
            if (!in_array($user['role'], ['admin', 'manager'])) {
                http_response_code(403); echo json_encode(['succes' => false]); exit;
            }
            $id     = (int)($data['id'] ?? 0);
            $ok     = ['approuve', 'refuse', 'annule', 'en_attente'];
            $statut = in_array($data['statut'] ?? '', $ok) ? $data['statut'] : 'en_attente';

            // Si refus/annulation → restituer la quantité
            if (in_array($statut, ['refuse', 'annule'])) {
                $stmt = $pdo->prepare("SELECT ressource_id, quantite, statut FROM reservations WHERE id = ?");
                $stmt->execute([$id]);
                $r = $stmt->fetch();
                if ($r && !in_array($r['statut'], ['refuse', 'annule'])) {
                    $pdo->prepare(
                        "UPDATE ressources SET quantite_disponible = quantite_disponible + ? WHERE id = ?"
                    )->execute([$r['quantite'], $r['ressource_id']]);
                }
            }
            $pdo->prepare("UPDATE reservations SET statut = ? WHERE id = ?")->execute([$statut, $id]);
            echo json_encode(['succes' => true]);
            break;
    }
}