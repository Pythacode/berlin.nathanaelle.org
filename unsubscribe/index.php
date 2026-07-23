<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";
?>

<!DOCTYPE html>
<html lang="fr-FR">

    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/res/css/index.css" type="text/css" />
        <link rel="stylesheet" href="/res/css/legals-mentions.css" type="text/css" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script defer src="https://statistiques.nathanaelle.org/script.js" data-website-id="5ad832be-2b05-4147-ac62-b9978d41105a"></script>
        <title>Désinscription newsletter - 1 an à Berlin</title>
        <style>
            .newsletter {
                border-radius: 50px;
                border: 1px solid white;
                display: flex;
                align-items: center;
                color: white;
                width: 250px;

                & input[type=email] {
                    border: 0;
                    flex: 1;
                    background-color: inherit;
                    color: white;
                    margin-left: 5px;
                    &:focus {
                        outline: 0;
                    }
                }
                & input[type=submit] {
                    background-color: inherit;
                    border-top-right-radius: 50px;
                    border-bottom-right-radius: 50px;
                    color: white;
                    border: none;
                }
            }
            main {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
        </style>
    </head>
    <body>
        <header>
            <h1>Désinscription newsletter</h1>
        </header>
        <main>
            <?php
            if (isset($_GET["mail"]) || $_SERVER["REQUEST_METHOD"] == "POST") {
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    $mail = $_POST["mail"];
                } else {
                    $mail = $_GET["mail"];
                }

                $stmt = $conn->prepare("DELETE FROM `newsletter` WHERE `email` = ?");
                $stmt->bind_param("s", $mail);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("UPDATE `users` SET `news`=0 WHERE `email`= ?");
                $stmt->bind_param("s", $mail);
                $stmt->execute();
                $stmt->close();

                $conn->close();

                echo "Désinscription éffectué";                

            } else {
                ?>
        
                <form class="newsletter" id="newsletter-form" method="post" action="/unsubscribe/">
                    <input type="email" name="mail" required placeholder="newsletter@berlin.nathanaelle.org">
                    <input type="submit" value="Valider">
                </form>
                <?php
            }
            ?>
            <a href="/" class="button">Accueil</a>
        </main>
    </body>

</html>

