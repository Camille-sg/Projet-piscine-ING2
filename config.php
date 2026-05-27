<?php
$servername = "localhost";
$username = "root";
$password = "root"; // sur MAMP souvent c'est root, sur XAMPP c'est souvent vide
$dbname = "campusplay_db";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Erreur de connexion : " . mysqli_connect_error());
}
?>