<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM ressources ORDER BY nom ASC");
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['id']                  = (int)$row['id'];
        $row['quantite_totale']     = (int)$row['quantite_totale'];
        $row['quantite_disponible'] = (int)$row['quantite_disponible'];
    }
    echo json_encode($rows);
    exit;
}

if ($method === 'POST') {
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $authUser = getAuthUser($pdo, $body);

    if (!$authUser || !in_array($authUser['role'], ['admin', 'manager'])) {
        http_response_code(403);
        echo json_encode(['succes' => false, 'erreur' => 'Accès refusé.']);
        exit;
    }

    $action = $body['action'] ?? '';

    if ($action === 'creer') {
        $nom = trim($body['nom'] ?? '');
        $qte = max(1, (int)($body['quantite_totale'] ?? 1));
        if (!$nom) {
            echo json_encode(['succes' => false, 'erreur' => 'Nom requis.']);
            exit;
        }
        $stmt = $pdo->prepare(
            "INSERT INTO ressources (nom, categorie, quantite_totale, quantite_disponible, description)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $nom,
            $body['categorie']    ?? '',
            $qte,
            $qte,
            $body['description']  ?? ''
        ]);
        echo json_encode(['succes' => true, 'id' => (int)$pdo->lastInsertId()]);

    } else if ($action === 'supprimer') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) {
            echo json_encode(['succes' => false, 'erreur' => 'ID manquant.']);
            exit;
        }
        $pdo->prepare("DELETE FROM ressources WHERE id = ?")->execute([$id]);
        echo json_encode(['succes' => true]);

    } else {
        echo json_encode(['succes' => false, 'erreur' => 'Action inconnue.']);
    }
}