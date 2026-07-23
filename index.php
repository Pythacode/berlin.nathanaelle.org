<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";

$result = $conn->query("SELECT `id`, `picture_name`, `width`, `height` FROM `posts` ORDER BY `created_at` DESC");

?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <title>1 an à Berlin</title>
    <link rel="stylesheet" href="/res/css/home.css">
    <link rel="stylesheet" href="/res/css/index.css">
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
        <?php
          if ($result->num_rows == 0) {
            ?>
            <style>
              main {
                display: flex;
                flex-direction: column;
                align-items: center;
              }
              #count {
                display: flex;
                gap: 10px;
              }
              .count {
                padding: 10px;
                border: 1px solid white;
                border-radius: 5px;
                display: flex;
                flex-direction: column;
                text-align: center;
                background-color: var(--background-color-secondary);
                align-items: center;
              }

              .count span.timer {
                width: 2ch;
                font-size: 10vh;
              }

              .count span:not(.timer),
              main h3 {
                font-size: 3rem;
              }

              @media (max-width: 480px) {
                .count span.timer {
                  font-size: 10vw !important;
                }
                .count span:not(.timer),
                main h3 {
                  font-size: 5vw !important;
                }
              }
            </style>
            <h3>Plus que</h3>
            <div id="count" style="margin: 10px;">
              <div class="count">
                <span class="timer" id="days"></span>
                <span id="days-text"></span>
              </div>
              <div class="count">
                <span class="timer" id="hours"></span>
                <span id="hours-text"></span>
              </div>
              <div class="count">
                <span class="timer" id="minutes"></span>
                <span id="minutes-text"></span>
              </div>
              <div class="count">
                <span class="timer" id="secondes"></span>
                <span id="secondes-text"></span>
              </div>
            </div>
            <h3><span id="unit"></span><span id="data2"></span> avant le grand départ !</h3>
            <script>
              const depart = new Date(1785093840000)
              const count_days = document.getElementById('days')
              const count_hours = document.getElementById('hours')
              const count_minutes = document.getElementById('minutes')
              const count_secondes = document.getElementById('secondes')
              const text_days = document.getElementById('days-text')
              const text_hours = document.getElementById('hours-text')
              const text_minutes = document.getElementById('minutes-text')
              const text_secondes = document.getElementById('secondes-text')
              let now = new Date()
              let diff;
              let unit;

              function refresh() {
                now = new Date();
                diff = Math.floor((depart - now) / 1000)
                
                const days = Math.floor(diff / 86400);
                diff %= 86400;
                const hours = Math.floor(diff / 3600);
                diff %= 3600;
                const minutes = Math.floor(diff / 60);
                const secondes = Math.floor(diff % 60);
                
                if (diff <= 0) {
                  location.reload()
                }
                //unit + (diff > 1 ? 's' : '');

                count_days.innerText = days.toString().padStart(2, '0')
                count_hours.innerText = hours.toString().padStart(2, '0')
                count_minutes.innerText = minutes.toString().padStart(2, '0')
                count_secondes.innerText = secondes.toString().padStart(2, '0')

                text_days.innerText = "Jour" + (days > 1 ? 's' : '')
                text_hours.innerText = "Heure" + (hours > 1 ? 's' : '')
                text_minutes.innerText = "Minute" + (minutes > 1 ? 's' : '')
                text_secondes.innerText = "Seconde" + (secondes > 1 ? 's' : '')

              }

              refresh()

              setInterval(refresh, 1000);
            </script>
            <?php
          } else {
            foreach ($result as $row) {
                echo "<img id=\"" . $row["id"] . "\" src=\"/res/pictures/" . htmlspecialchars($row["picture_name"]) . "\" class=\"picture line-" . $row["width"] . " row-" . $row["height"] . "\">";
            }
          }
        ?>

        <!--overlay-->
        <div class="overlay" id="overlay"></div>
        <svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 -960 960 960" width="30px" fill="#FFFFFF" id="quitIcon"><path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"></path></svg>
        <div class="fenetreModal" id="fenetreModal">
          <img src="" id="visualiseur">
          <div class="img_info">
            <div class="info likes">
              <svg id="heart" width="53.448mm" height="47.716mm" height="30px" viewBox="0 0 53.448 47.716" xml:space="preserve" xmlns="http://www.w3.org/2000/svg"><g transform="translate(-104.53 -34.333)"><path d="m118.65 34.833c-2.0883 2e-3 -4.1644 0.54545-6.1015 1.6676-9.7787 6.6921-8.3244 15.448-5.0486 20.855 1.9282 4.0135 20.082 17.597 23.756 23.721 3.6737-6.1241 21.828-19.708 23.756-23.721 3.2758-5.407 4.7301-14.163-5.0486-20.855-6.1989-3.5909-13.822-1.256-18.708 5.7842-3.359-4.8402-8.0117-7.4562-12.606-7.4518z" fill="none" stroke="#fff"/></g></svg>
              <span id="likes"></span>
            </div>
            <svg id="share" width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15 6.75C15 5.50736 16.0074 4.5 17.25 4.5C18.4926 4.5 19.5 5.50736 19.5 6.75C19.5 7.99264 18.4926 9 17.25 9C16.0074 9 15 7.99264 15 6.75ZM17.25 3C15.1789 3 13.5 4.67893 13.5 6.75C13.5 7.00234 13.5249 7.24885 13.5724 7.48722L9.77578 9.78436C9.09337 8.85401 7.99222 8.25 6.75 8.25C4.67893 8.25 3 9.92893 3 12C3 14.0711 4.67893 15.75 6.75 15.75C8.10023 15.75 9.28379 15.0364 9.9441 13.9657L13.5866 16.4451C13.5299 16.7044 13.5 16.9737 13.5 17.25C13.5 19.3211 15.1789 21 17.25 21C19.3211 21 21 19.3211 21 17.25C21 15.1789 19.3211 13.5 17.25 13.5C15.9988 13.5 14.8907 14.1128 14.2095 15.0546L10.4661 12.5065C10.4884 12.3409 10.5 12.1718 10.5 12C10.5 11.7101 10.4671 11.4279 10.4049 11.1569L14.1647 8.88209C14.8415 9.85967 15.971 10.5 17.25 10.5C19.3211 10.5 21 8.82107 21 6.75C21 4.67893 19.3211 3 17.25 3ZM15 17.25C15 16.0074 16.0074 15 17.25 15C18.4926 15 19.5 16.0074 19.5 17.25C19.5 18.4926 18.4926 19.5 17.25 19.5C16.0074 19.5 15 18.4926 15 17.25ZM4.5 12C4.5 10.7574 5.50736 9.75 6.75 9.75C7.99264 9.75 9 10.7574 9 12C9 13.2426 7.99264 14.25 6.75 14.25C5.50736 14.25 4.5 13.2426 4.5 12Z" fill="#ffffff"/><script xmlns=""/></svg>
            <div class="info postInfo">
                <span id="username"></span>
                <span id="date"></span>
              </div>
            </div>
            <div class="desc" id="desc"></div>
            <hr>
            <div class="comments" id="comments"></div>
            <form action="/api/new_comment/" method="post" class="comment" id="comment-form">
              <input type="text" name="comment" id="comment" placeholder="Poster un commentaire" require>
              <input type="submit" value="Envoyer">
            </form>
            <div class="comment" style="display:none;margin:10px;" id="unlog-comment">
              <a href="/login/" id="post-login">Connectez-vous</a> ou <a href="/signin/" id="post-signin">Inscrivez-vous</a> pour poster un commentaire.
            </div>
        </div>
    </main>
    <footer>
      <h1>
          Abonnez-vous à la NewsLetter !
      </h1>
      <form class="newsletter" id="newsletter-form">
        <input type="email" name="email" required placeholder="newsletter@berlin.nathanaelle.org">
        <input type="submit" value="Valider">
      </form>
      <p id="newsletter-message"></p>
      <p>En vous inscrivant à la newsletter, vous acceptez les <a href="/mentions-legales/">mentions légales</a></p>
      <p>Ou <a href="/signin">créez-vous un compte</a></p>
      <a href="/mentions-legales/">Mentions légales</a>
      <a href="mailto:contact@nathanaelle.org?subject=%5BBUG%20on%20berlin.nathanaelle.org%5D">Signaler un bug</a>
      <script>
        const isLoggedIn = <?php echo isset($_SESSION['id']) ? 'true' : 'false'; ?>;
      </script>
    </footer>
  </body>
</html>

