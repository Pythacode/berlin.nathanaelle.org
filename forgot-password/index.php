<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/res/php/mail.php";

if (isset($_SESSION['id'])) {
    header('Location: /logout/');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/res/css/index.css">
        <link rel="stylesheet" href="/res/css/signAndLogIn.css">
        <script defer src="https://statistiques.nathanaelle.org/script.js" data-website-id="5ad832be-2b05-4147-ac62-b9978d41105a"></script>
        <title>1 an à Berlin - Mot de passe / Nom d'utilisateur oublié</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <header>
        <h1>Mot de passe / Nom d'utilisateur oublié</h1>
        </header>
        <main>
        <span id="error" <?php echo isset($error) ? "" : "style=\"display: none;\""; echo ">"; echo isset($error) ? $error : ""; echo "</span>"; ?>
        <form action="/forgot-password/" method="post" id="form">
            Veuillez entrez l'email avec lequel vous vous êtes inscrit.
            <br>
            <label for="mail">Email :</label>
            <input type="mail" name="mail" id="mail" required>

            <input type="submit" value="Valider">
        </form>
        </main>
        <footer></footer>
    </body>
    </html>
    <?php
} if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `email` = ?");
    $stmt->bind_param("s", $_POST['mail']);

    $stmt->execute();   

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        $token = uniqid();

        $stmt = $conn->prepare("INSERT INTO `reset_password_requests` (`user_id`, `token`) VALUES (?, ?)");
        $stmt->bind_param("is", $user["id"], $token);

        $stmt->execute();
        
        $template = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/res/mail-templates/new_password.html');
        // (`username`, `password`, `email`, `news`)
        $variables = [
            '{{USERNAME}}' => $row["username"],
            '{{MDP_LINK}}' => '/'.$token,
        ];
            
        send_mail($template, $variables, "Réinitialisation du mdp.", $row["email"], "newsletter", $unsubscribe_link);
    }
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/res/css/index.css">
        <link rel="stylesheet" href="/res/css/signAndLogIn.css">
        <script defer src="https://statistiques.nathanaelle.org/script.js" data-website-id="5ad832be-2b05-4147-ac62-b9978d41105a"></script>
        <title>1 an à Berlin - Mot de passe / Nom d'utilisateur oublié</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <header>
        <h1>Mot de passe / Nom d'utilisateur oublié</h1>
        </header>
        <main>
            <p>Si l'email fourni est rataché à un conte, vous recevrez dans quelques instant un mail avec la procédure à suivre.</p>
        </main>
        <footer></footer>
    </body>
    </html>
    <?php
}
?>