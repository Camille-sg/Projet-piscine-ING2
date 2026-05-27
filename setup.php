<?php
// setup.php — Créer les comptes de démonstration (une seule fois)
// URL : http://localhost:8888/campusplay/setup.php

require 'api/config.php';

$comptes = [
    ['Admin',  'CampusPlay', 'admin@campusplay.fr',    'admin123',    'admin'],
    ['Jean',   'Manager',    'manager@campusplay.fr',  'manager123',  'manager'],
    ['Sophie', 'Étudiant',   'etudiant@campusplay.fr', 'etudiant123', 'etudiant'],
];

$inseres = 0;
$stmt = $pdo->prepare(
    "INSERT IGNORE INTO comptes (prenom, nom, email, mdp, role) VALUES (?, ?, ?, ?, ?)"
);

foreach ($comptes as $c) {
    $hash = password_hash($c[3], PASSWORD_DEFAULT);
    $stmt->execute([$c[0], $c[1], $c[2], $hash, $c[4]]);
    $inseres += $stmt->rowCount();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Setup CampusPlay</title>
<style>body{font-family:sans-serif;max-width:500px;margin:3rem auto;background:#f8fafc;padding:2rem;border-radius:1rem;box-shadow:0 4px 24px rgba(0,0,0,.08)}</style>
</head>
<body>
<h2>🎵 CampusPlay — Setup BDD</h2>
<?php if ($inseres > 0): ?>
  <p style="color:#16a34a;font-weight:700">✅ <?= $inseres ?> compte(s) créé(s) !</p>
<?php else: ?>
  <p style="color:#d97706">⚠️ Comptes déjà existants (aucun doublon inséré).</p>
<?php endif; ?>
<h3>Comptes disponibles :</h3>
<table style="width:100%;border-collapse:collapse">
  <tr style="background:#e0e7ff"><th style="padding:.4rem;text-align:left">Email</th><th>Mot de passe</th><th>Rôle</th></tr>
  <tr><td>admin@campusplay.fr</td><td>admin123</td><td>admin</td></tr>
  <tr style="background:#f8fafc"><td>manager@campusplay.fr</td><td>manager123</td><td>manager</td></tr>
  <tr><td>etudiant@campusplay.fr</td><td>etudiant123</td><td>etudiant</td></tr>
</table>
<br>
<a href="index.html" style="background:#4f46e5;color:white;padding:.7rem 1.5rem;border-radius:.5rem;text-decoration:none;font-weight:700">→ Aller à la page de connexion</a>
</body>
</html>