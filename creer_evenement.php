<?php
require 'api/config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre       = trim($_POST['titre']       ?? '');
    $activite_id = (int)($_POST['activite_id'] ?? 0);
    $date_debut  = $_POST['date_debut']        ?? '';
    $date_fin    = $_POST['date_fin']           ?? '';
    $lieu        = trim($_POST['lieu']         ?? '');
    $places_max  = max(1, (int)($_POST['places_max'] ?? 20));
    $description = trim($_POST['description'] ?? '');

    if ($titre === '') {
        $message = '❌ Le titre est obligatoire.';
    } elseif ($date_debut === '' || $date_fin === '') {
        $message = '❌ Les dates sont obligatoires.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO evenements
                 (titre, activite_id, date_debut, date_fin, lieu, places_max, description, statut)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'approuve')"
            );
            $stmt->execute([
                $titre,
                $activite_id,
                $date_debut,
                $date_fin,
                $lieu,
                $places_max,
                $description,
            ]);
            $newId   = (int)$pdo->lastInsertId();
            $message = '✅ Événement "' . htmlspecialchars($titre) . '" créé avec succès (ID : ' . $newId . ') !';
            $success = true;
        } catch (Exception $ex) {
            $message = '❌ Erreur base de données : ' . $ex->getMessage();
        }
    }
}

$activites = $pdo->query("SELECT id, titre FROM activites ORDER BY titre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Créer un événement — CampusPlay</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body        { font-family: system-ui, sans-serif; background: #f1f5f9; min-height: 100vh; display: flex; align-items: flex-start; justify-content: center; padding: 2rem 1rem; }
    .card       { background: white; border-radius: 1rem; padding: 2rem; width: 100%; max-width: 620px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
    h1          { font-size: 1.4rem; font-weight: 800; color: #1e293b; margin-bottom: 1.5rem; }
    .msg-ok     { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: .9rem 1.1rem; border-radius: .6rem; margin-bottom: 1.25rem; font-weight: 700; font-size: .95rem; }
    .msg-err    { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: .9rem 1.1rem; border-radius: .6rem; margin-bottom: 1.25rem; font-weight: 700; font-size: .95rem; }
    .fg         { margin-bottom: 1rem; }
    label       { display: block; font-size: .84rem; font-weight: 700; color: #374151; margin-bottom: .3rem; }
    input, select, textarea {
      width: 100%; padding: .6rem .85rem;
      border: 1.5px solid #e2e8f0; border-radius: .5rem;
      font-size: .9rem; font-family: inherit;
      transition: border .15s;
    }
    input:focus, select:focus, textarea:focus { outline: none; border-color: #4f46e5; }
    .grid2      { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .btn-submit {
      width: 100%; padding: .8rem; background: #4f46e5; color: white;
      border: none; border-radius: .6rem; font-size: 1rem; font-weight: 700;
      cursor: pointer; margin-top: .5rem; transition: background .15s;
    }
    .btn-submit:hover { background: #3730a3; }
    .retour     { display: inline-block; margin-top: 1.25rem; color: #4f46e5; font-weight: 600; text-decoration: none; font-size: .9rem; }
    .retour:hover { text-decoration: underline; }
  </style>
</head>
<body>
<div class="card">
  <h1>➕ Créer un événement</h1>

  <?php if ($message !== ''): ?>
    <div class="<?= $success ? 'msg-ok' : 'msg-err' ?>"><?= $message ?></div>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="POST" action="creer_evenement.php">

    <div class="fg">
      <label>Titre *</label>
      <input type="text" name="titre" required
             placeholder="Concert de printemps…"
             value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>">
    </div>

    <div class="fg">
      <label>Activité</label>
      <select name="activite_id">
        <option value="0">-- Choisir une activité --</option>
        <?php foreach ($activites as $a): ?>
          <option value="<?= $a['id'] ?>"
            <?= (isset($_POST['activite_id']) && $_POST['activite_id'] == $a['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($a['titre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="grid2">
      <div class="fg">
        <label>Date début *</label>
        <input type="datetime-local" name="date_debut" required
               value="<?= htmlspecialchars($_POST['date_debut'] ?? '') ?>">
      </div>
      <div class="fg">
        <label>Date fin *</label>
        <input type="datetime-local" name="date_fin" required
               value="<?= htmlspecialchars($_POST['date_fin'] ?? '') ?>">
      </div>
    </div>

    <div class="grid2">
      <div class="fg">
        <label>Lieu</label>
        <input type="text" name="lieu"
               placeholder="Salle B204, Grande Salle…"
               value="<?= htmlspecialchars($_POST['lieu'] ?? '') ?>">
      </div>
      <div class="fg">
        <label>Places max *</label>
        <input type="number" name="places_max" min="1" required
               value="<?= htmlspecialchars($_POST['places_max'] ?? '20') ?>">
      </div>
    </div>

    <div class="fg">
      <label>Description</label>
      <textarea name="description" rows="3"
                placeholder="Décrivez l'événement…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn-submit">✅ Créer et approuver l'événement</button>
  </form>
  <?php endif; ?>

  <a href="admin.html" class="retour">← Retour à l'administration</a>
</div>
</body>
</html>