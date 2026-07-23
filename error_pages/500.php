<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";

?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <title>1 an à Berlin</title>
    <link rel="stylesheet" href="/res/css/index.css">
    <link rel="stylesheet" href="/res/css/error_pages.css">
    <script src="/res/js/home.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script defer src="https://statistiques.nathanaelle.org/script.js" data-website-id="5ad832be-2b05-4147-ac62-b9978d41105a"></script>
  </head>
  <body>
    <header>
      <h1>
        1 an à Berlin
      </h1>
      <h3>
        Ich bin ein Berliner
      </h3>
    </header>
    <nav>
      <a href="/" class="button">Accueil</a>
      <?php
      if (isset($_SESSION['id'])) {
        if ($_SESSION['admin']) {
          ?>
          <a href="/new-post/" class="button">Nouveau post</a>
          <?php
        }
        ?>
        <a href="/logout/" class="button">Se déconnecter</a>
        <?php
      } else {
        ?>
        <a href="/login/" class="button">Se connecter</a>
        <a href="/signin/" class="button">S'inscrire</a>
        <?php
      }
      ?>
    </nav>
    <main>
      <h2>Erreur</h2>
      <h1 class="error">500</h1>
      Cette page s'affiche après un bug du serveur. N'hesitez pas à m'indiquer ce qui s'est produit (même si j'ai déjà reçus un mail avec l'erreur) :
      <a href="mailto:contact@nathanaelle.org?subject=%5BBUG%20on%20berlin.nathanaelle.org%5D">Signaler un bug</a>
    </main>
    <footer>
        <a href="/mentions-legales/">Mentions légales</a>
        <a href="mailto:contact@nathanaelle.org?subject=%5BBUG%20on%20berlin.nathanaelle.org%5D">Signaler un bug</a>
    </footer>
  </body>
</html>

