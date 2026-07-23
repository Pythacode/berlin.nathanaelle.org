const images = document.querySelectorAll('img.picture');
const overlay = document.getElementById('overlay');
const fenetreModal = document.getElementById('fenetreModal');
const visualiseur = document.getElementById('visualiseur');
const quitIcon = document.getElementById('quitIcon');

const heart = document.getElementById('heart')
const share = document.getElementById('share')
const dateSpan = document.getElementById('date')
const likesSpan = document.getElementById('likes')
const usernameSpan = document.getElementById('username')
const descDiv = document.getElementById('desc')
const commentsDiv = document.getElementById('comments')

const post_login_link = document.getElementById('post-login')
const post_signin_link = document.getElementById('post-signin')

const unlogDiv = document.getElementById('unlog-comment')

let has_like = false;
let post_id = NaN

function show_comment(comments) {
  
  commentsDiv.innerHTML = ""

  if (comments.length == 0) {
    commentsDiv.innerText = "Aucun commentaire pour l'instant"
  } else {
    comments.forEach(comment => {
      const div = document.createElement("div");
      const dataP = document.createElement("p")
      const commentDateSpan = document.createElement('span')
      const commentP = document.createElement("p")
      commentDateSpan.className = "date"

      const Cdate = new Date(comment["date"])

      const Cformatted = new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }).format(Cdate);
      
      const username = document.createTextNode(comment["user"]);
      commentDateSpan.innerText = ` ⋅ ${Cformatted}`;
      commentP.innerText = comment["comment"]

      dataP.appendChild(username);
      dataP.appendChild(commentDateSpan);
      div.appendChild(dataP);
      div.appendChild(commentP);
      commentsDiv.appendChild(div)
    })
  }
}

async function display_post(id) {

  const img = document.getElementById(id)

  if (!img) {
    return
  }

  history.replaceState(null, '', '/?post=' + img.id);

  post_login_link.href = `/login/?redirect=${encodeURIComponent('/?post=' + img.id)}`;
  post_signin_link.href = `/signin/?redirect=${encodeURIComponent('/?post=' + img.id)}`;

  visualiseur.src = img.getAttribute('src');
  try {
      const response = await fetch('/api/post.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ post_id : img.id })
    });

    if (!response.ok) throw new Error('Erreur serveur');

    post_id = img.id;

    const data = await response.json();

    has_like = data["has_like"];
    heart.classList.toggle('has_like', has_like);

    const date = new Date(data["date"])

    const formatted = new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
    
    dateSpan.innerText = formatted;
    likesSpan.innerText = data["likes_count"];
    usernameSpan.innerText = data["user"];
    descDiv.innerHTML = data["desc"];

    show_comment(data["comments"])
    
    overlay.style.display = 'block';
    quitIcon.style.display = 'block';
    fenetreModal.style.display = 'flex';

  } catch (err) {
    console.error(err);
  }
}

images.forEach(img => {
    img.addEventListener('click', () => display_post(img.id));
});

heart.addEventListener('click', async function(e) {
  has_like = !has_like;
  heart.classList.toggle('has_like', has_like);
  try {
    const response = await fetch('/api/like.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ post_id: post_id, action: has_like ? 'add' : 'remove' })
    });

    if (response.status === 401) {
      window.location.assign(`/login/?redirect=/?post=${post_id}`);
      return;
    }

    if (!response.ok) {
      throw new Error(`Erreur HTTP: ${response.status}`);
    }

    const data = await response.json();
    console.log(data);
    likesSpan.innerText = data["likes_count"];

  } catch (error) {
    console.error('Erreur lors de la requête:', error);
  }
});

function closeOverlay() {
  history.replaceState(null, '', '/');
  overlay.style.display = 'none';
  quitIcon.style.display = 'none';
  fenetreModal.style.display = 'none';
}

overlay.addEventListener('click', closeOverlay);
quitIcon.addEventListener('click', closeOverlay);
window.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    closeOverlay();
  }
});

const news_form = document.querySelector('#newsletter-form');
const message = document.querySelector('#newsletter-message');

news_form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const email = news_form.email.value.trim();

  try {
    const response = await fetch('/api/newsletter.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });

    if (!response.ok) throw new Error('Erreur serveur');

    const data = await response.json();
    message.textContent = 'Merci, inscription confirmée !';
    news_form.reset();

  } catch (err) {
    message.textContent = 'Une erreur est survenue, réessaie.';
  }
});


const comment_form = document.getElementById('comment-form');

comment_form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const comment = comment_form.comment.value.trim();

  try {
    const response = await fetch('/api/new_comment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ comment, post_id })
    });

    if (!response.ok) throw new Error('Erreur serveur');

    const data = await response.json();
    comment_form.reset();
    show_comment(data["comments"])

  } catch (err) {
    message.textContent = 'Une erreur est survenue, réessaie.';
  }
});

const params = new URLSearchParams(window.location.search);

const post_id_ask = params.get("post");
if (post_id_ask) {
  display_post(post_id_ask);
}

if (!isLoggedIn) {
  comment_form.style.display = "none"
  unlogDiv.style.display = "block"
}

share.addEventListener('click', function(e) {
  navigator.clipboard.writeText(window.location.href)
    .then(function() {
      alert('L\'URL du post a été copiée dans le presse-papier !');
    })
    .catch(function(err) {
      console.error('Erreur lors de la copie :', err);
    });
})