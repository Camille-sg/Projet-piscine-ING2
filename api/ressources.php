<?php
require 'config.php';

$stmt = $pdo->query("SELECT * FROM ressources ORDER BY nom ASC");
echo json_encode($stmt->fetchAll());