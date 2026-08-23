const form = document.getElementById("form")
const password = document.getElementById('password')
const confirmPassword = document.getElementById('password-confirm')
const errorSpan = document.getElementById('error')
const inputs = form.querySelectorAll('input');  

function showerror(error) {
    errorSpan.innerText = error;
    errorSpan.style.display = 'block';
}

function hideerror() {
    errorSpan.style.display = 'none';
}

inputs.forEach((input) => {
  input.addEventListener('input', (e) => {
    hideerror();
  });
});

form.addEventListener('submit', function(event) {
    event.preventDefault();
    hideerror()


    if (password.value == "") {
        showerror("Le mot de passe ne peut être vide.");
        return false;
    }

    if (password.value != confirmPassword.value) {
        showerror("Les mots de passe ne correspondent pas.");
        return false;
    }
    
    form.submit();
})