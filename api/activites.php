<?php
require 'config.php';

$stmt = $pdo->query("SELECT * FROM activites ORDER BY titre ASC");
$rows = $stmt->fetchAll();
foreach ($rows as &$row) {
    $row['id'] = (int)$row['id'];
}
echo json_encode($rows);