function change_type(button) {
    const input = document.getElementById(button.dataset.inputId)
    button.innerText = button.innerText == "Voir" ? "Masquer" : "Voir";
    input.type = input.type == "password" ? "text" : "password";
}