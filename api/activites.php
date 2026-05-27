<?php
require 'config.php';

$stmt = $pdo->query("SELECT * FROM activites ORDER BY titre ASC");
echo json_encode($stmt->fetchAll());