<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";


if (isset($_SESSION['id'])) {
    header('Location: /logout/');
    exit;
}

function display_error($error) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/res/css/index.css">
        <link rel="stylesheet" href="/res/css/signAndLogIn.css">
        <script defer src="https://statistiques.nathanaelle.org/script.js" data-website-id="5ad832be-2b05-4147-ac62-b9978d41105a"></script>
        <title>1 an à Berlin - Réinitialisation du mot de passe</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <header>
        <h1>Réinitialisation du mot de passe</h1>
        </header>
        <main>
        <p><?php echo $error;?></p>  
        <a href="/login/">Connection</a>      
        </main>
        <footer></footer>
    </body>
    </html>
    <?php
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $token = urldecode($_GET["token"]);

    $stmt = $conn->prepare("SELECT * FROM `reset_password_requests` WHERE `token` = ?");
    $stmt->bind_param("s", $token);

    $stmt->execute();   

    $result = $stmt->get_result();
    $requete = $result->fetch_assoc();
    $stmt->close();
    
    if ($requete) {
        $date = new DateTime($requete['created_at']);

        if ($date->getTimestamp() < time() - 900) {
            display_error("Token expiré");
            exit;
        } else {
            $stmt = $conn->prepare("SELECT * FROM `users` WHERE `id` = ?");
            $stmt->bind_param("i", $requete["user_id"]);

            $stmt->execute();  
            
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            ?>
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="utf-8">
                <link rel="stylesheet" href="/res/css/index.css">
                <link rel="stylesheet" href="/res/css/signAndLogIn.css">
                <title>Réinitialiser son mdp - 1 an à Berlin</title>
                <script src="/res/js/signAndLogin.js"></script>
                <script src="/res/js/new-password.js" defer></script>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <script defer src="https://statistiques.nathanaelle.org/script.js" data-website-id="5ad832be-2b05-4147-ac62-b9978d41105a"></script>
            </head>
            <body>
                <header>
                <h1>Réinitialiser son mot de passe</h1>
                </header>
                <main>
                <span id="error" style="display: none;"></span>
                <form action="/reset-password/" method="post" id="form">
                    <p>
                        Nom d'utilisateur :
                        <input type="text" id="username" name="username" autocomplete="username" value="<?php echo $user["username"]; ?>" readonly>
                    </p>

                    <label for="password">Nouveau mot de passe :</label>
                    <div class="input-password">
                    <input type="password" name="password" id="password">
                    <span type="button" onclick="change_type(this)" data-input-id="password">Voir</span>
                    </div>

                    <label for="password-confirm">Confirmation du nouveau mot de passe :</label>
                            <div class="input-password">
                    <input type="password" name="password-confirm" id="password-confirm">
                    <span type="button" onclick="change_type(this)" data-input-id="password-confirm">Voir</span>
                    </div>

                    <input type="submit" value="Valider">
                    <input type="hidden" name="token" value="<?php echo $_GET['token'] ?>">
                    
                </form>
                </main>
                <footer></footer>
            </body>
            </html> 
        <?php
        }
    } else {
        display_error("Token invalide");
        exit;
    }


} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {

    $stmt = $conn->prepare("SELECT * FROM `reset_password_requests` WHERE `token` = ?");
    $stmt->bind_param("s", $_POST["token"]);

    
    $stmt->execute();   
    
    $result = $stmt->get_result();
    $requete = $result->fetch_assoc();
    $stmt->close();

    $hash_pass = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE `users` SET `password`=? WHERE id=?");
    $stmt->bind_param("si", $hash_pass, $requete["user_id"]);

    $stmt->execute();

    $stmt->close();

    # Supression
    $stmt = $conn->prepare("DELETE FROM `reset_password_requests` WHERE `token`=?");
    $stmt->bind_param("s", $_POST["token"]);

    $stmt->execute();

    $stmt->close();

    $conn->close();

    display_error("Mot de passe modifié !");
    exit;
}
?>
