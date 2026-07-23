<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";

if (isset($_SESSION['id'])) {
    header('Location: /logout/');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `username` = ?");
    $stmt->bind_param("s", $username);

    $stmt->execute();   

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        if (password_verify($password, $user["password"])) {

            session_regenerate_id(true);
            $_SESSION['id'] = $user["id"];
            $_SESSION['mail'] = $user["email"];
            $_SESSION['username'] = $user["username"];
            $_SESSION['admin'] = $user["admin"];
            $_SESSION['news'] = $user["news"];

            $redirect = $_POST['redirect'] ?? '/';

            if (!str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
                $redirect = '/';
            }

            header("Location: $redirect");
            exit;

        } else  {
            $error = "Mot de passe incorrect";
            require_once 'login.html';
            exit;
        }
    } else {
        $error = "Nom d'utilisateur incorrect";
        require_once 'login.html';
        exit;;
    }

} else {
    require_once 'login.html';
}
?>
