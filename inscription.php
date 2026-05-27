<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "config.php";

echo "<h2>Test inscription.php</h2>";

echo "<p>Base connectée : ";
echo mysqli_get_server_info($conn);
echo "</p>";

echo "<pre>";
print_r($_POST);
echo "</pre>";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    echo "<p>Le formulaire arrive bien dans PHP.</p>";
} else {
    echo "<p>Tu es arrivée ici sans envoyer le formulaire.</p>";
}

exit;
?>