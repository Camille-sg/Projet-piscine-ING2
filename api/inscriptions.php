<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

$user = getAuthUser($pdo, $data);

if ($method === 'GET') {

    if (!$user) {
        echo json_encode([]);
        exit;
    }

    if (in_array($user['role'], ['admin', 'manager'])) {
        $stmt = $pdo->query(
            "SELECT i.*, e.titre AS evenement_titre
             FROM inscriptions i
             LEFT JOIN evenements e ON i.evenement_id = e.id
             ORDER BY i.inscrit_le DESC"
        );
    } else {
        $stmt = $pdo->prepare(
            "SELECT i.*, e.titre AS evenement_titre
             FROM inscriptions i
             LEFT JOIN evenements e ON i.evenement_id = e.id
             WHERE i.user_email = ?
             ORDER BY i.inscrit_le DESC"
        );
        $stmt->execute([$user['email']]);
    }

    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['id'] = (int)$row['id'];
        $row['evenement_id'] = (int)$row['evenement_id'];
    }

    echo json_encode($rows);
    exit;
}

if ($method === 'POST') {

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'succes' => false,
            'erreur' => 'Tu dois être connecté(e).'
        ]);
        exit;
    }

    $action = $data['action'] ?? '';
    $ev_id  = (int)($data['evenement_id'] ?? 0);

    if ($ev_id <= 0) {
        echo json_encode([
            'succes' => false,
            'erreur' => 'Événement invalide.'
        ]);
        exit;
    }

    if ($action === 'inscrire') {

        $stmt = $pdo->prepare(
            "SELECT * FROM evenements
             WHERE id = ? AND statut = 'approuve'"
        );
        $stmt->execute([$ev_id]);
        $ev = $stmt->fetch();

        if (!$ev) {
            echo json_encode([
                'succes' => false,
                'erreur' => 'Événement introuvable.'
            ]);
            exit;
        }

        $stmt = $pdo->prepare(
            "SELECT id FROM inscriptions
             WHERE user_email = ? AND evenement_id = ?"
        );
        $stmt->execute([$user['email'], $ev_id]);

        if ($stmt->fetch()) {
            echo json_encode([
                'succes' => false,
                'erreur' => 'Tu es déjà inscrit(e) ou en liste d’attente.'
            ]);
            exit;
        }

        $places_prises = (int)$ev['places_prises'];
        $places_max    = (int)$ev['places_max'];

        if ($places_prises >= $places_max) {
            $statut = 'liste_attente';
        } else {
            $statut = 'inscrit';
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO inscriptions (user_email, evenement_id, statut)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$user['email'], $ev_id, $statut]);

            if ($statut === 'inscrit') {
                $stmt = $pdo->prepare(
                    "UPDATE evenements
                     SET places_prises = places_prises + 1
                     WHERE id = ?"
                );
                $stmt->execute([$ev_id]);
            }

            $pdo->commit();

            echo json_encode([
                'succes' => true,
                'statut' => $statut,
                'message' => $statut === 'inscrit'
                    ? 'Inscription réussie.'
                    : 'Événement complet : tu as été ajouté(e) à la liste d’attente.'
            ]);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();

            echo json_encode([
                'succes' => false,
                'erreur' => 'Erreur pendant l’inscription : ' . $e->getMessage()
            ]);
            exit;
        }
    }

    if ($action === 'desinscrire') {

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT * FROM inscriptions
                 WHERE user_email = ? AND evenement_id = ?"
            );
            $stmt->execute([$user['email'], $ev_id]);
            $inscription = $stmt->fetch();

            if (!$inscription) {
                $pdo->rollBack();
                echo json_encode([
                    'succes' => false,
                    'erreur' => 'Inscription introuvable.'
                ]);
                exit;
            }

            $ancienStatut = $inscription['statut'] ?? 'inscrit';

            $stmt = $pdo->prepare(
                "DELETE FROM inscriptions
                 WHERE user_email = ? AND evenement_id = ?"
            );
            $stmt->execute([$user['email'], $ev_id]);

            if ($ancienStatut === 'inscrit') {

                $stmt = $pdo->prepare(
                    "UPDATE evenements
                     SET places_prises = GREATEST(0, places_prises - 1)
                     WHERE id = ?"
                );
                $stmt->execute([$ev_id]);

                $stmt = $pdo->prepare(
                    "SELECT * FROM inscriptions
                     WHERE evenement_id = ? AND statut = 'liste_attente'
                     ORDER BY inscrit_le ASC
                     LIMIT 1"
                );
                $stmt->execute([$ev_id]);
                $premierAttente = $stmt->fetch();

                if ($premierAttente) {
                    $stmt = $pdo->prepare(
                        "UPDATE inscriptions
                         SET statut = 'inscrit'
                         WHERE id = ?"
                    );
                    $stmt->execute([$premierAttente['id']]);

                    $stmt = $pdo->prepare(
                        "UPDATE evenements
                         SET places_prises = places_prises + 1
                         WHERE id = ?"
                    );
                    $stmt->execute([$ev_id]);
                }
            }

            $pdo->commit();

            echo json_encode([
                'succes' => true,
                'message' => 'Désinscription réussie.'
            ]);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();

            echo json_encode([
                'succes' => false,
                'erreur' => 'Erreur pendant la désinscription : ' . $e->getMessage()
            ]);
            exit;
        }
    }

    echo json_encode([
        'succes' => false,
        'erreur' => 'Action inconnue.'
    ]);
    exit;
}

http_response_code(405);
echo json_encode([
    'succes' => false,
    'erreur' => 'Méthode non autorisée.'
]);
?>