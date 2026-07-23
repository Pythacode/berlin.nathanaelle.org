<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";

if (!isset($_SESSION['id'])) {
    header('Location: /');
    exit;
}

session_unset();
session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconection - 1 an à Berlin</title>
    <script defer src="https://statistiques.nathanaelle.org/script.js" data-website-id="5ad832be-2b05-4147-ac62-b9978d41105a"></script>
    <link rel="stylesheet" href="/res/css/index.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <header>
        Vous avez été déconnecté
    </header>
    <a href="/login/" class="button">Se connecter</a>
    <a href="/signin/" class="button">S'inscrire</a>
    <a href="/" class="button">Accueil</a>
    <footer></footer>    
</body>
</html>