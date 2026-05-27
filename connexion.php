<?php
session_start();
include "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim(strtolower($_POST["email"]));
    $mdp = $_POST["mdp"];

    if (empty($email) || empty($mdp)) {
        die("Email et mot de passe obligatoires.");
    }

    $sql = "SELECT * FROM utilisateurs WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $resultat = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultat) === 1) {
        $user = mysqli_fetch_assoc($resultat);

        if (password_verify($mdp, $user["mot_de_passe"])) {

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["nom"] = $user["nom"];
            $_SESSION["prenom"] = $user["prenom"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            header("Location: accueil.php");
            exit;

        } else {
            echo "Mot de passe incorrect.";
        }

    } else {
        echo "Aucun compte trouvé avec cet email.";
    }
}
?>