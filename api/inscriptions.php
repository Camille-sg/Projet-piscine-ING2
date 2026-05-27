<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = $_SESSION['user'] ?? null;

if ($method === 'GET') {
    if (!$user) { echo json_encode([]); exit; }
    if (in_array($user['role'], ['admin', 'manager'])) {
        $stmt = $pdo->query("SELECT * FROM inscriptions ORDER BY inscrit_le DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM inscriptions WHERE user_email = ?");
        $stmt->execute([$user['email']]);
    }
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    if (!$user) { http_response_code(401); echo json_encode(['succes' => false]); exit; }
    $data   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $data['action'] ?? '';
    $ev_id  = (int)($data['evenement_id'] ?? 0);

    if ($action === 'inscrire') {
        $stmt = $pdo->prepare("SELECT * FROM evenements WHERE id = ? AND statut = 'approuve'");
        $stmt->execute([$ev_id]);
        $ev = $stmt->fetch();

        if (!$ev) { echo json_encode(['succes' => false, 'erreur' => 'Événement introuvable.']); exit; }
        if ($ev['places_prises'] >= $ev['places_max']) {
            echo json_encode(['succes' => false, 'erreur' => 'Plus de places disponibles.']); exit;
        }

        try {
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO inscriptions (user_email, evenement_id) VALUES (?, ?)")
                ->execute([$user['email'], $ev_id]);
            $pdo->prepare("UPDATE evenements SET places_prises = places_prises + 1 WHERE id = ?")
                ->execute([$ev_id]);
            $pdo->commit();
            echo json_encode(['succes' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['succes' => false, 'erreur' => 'Déjà inscrit(e) à cet événement.']);
        }

    } elseif ($action === 'desinscrire') {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM inscriptions WHERE user_email = ? AND evenement_id = ?");
        $stmt->execute([$user['email'], $ev_id]);
        if ($stmt->rowCount() > 0) {
            $pdo->prepare("UPDATE evenements SET places_prises = GREATEST(0, places_prises - 1) WHERE id = ?")
                ->execute([$ev_id]);
        }
        $pdo->commit();
        echo json_encode(['succes' => true]);
    }
}