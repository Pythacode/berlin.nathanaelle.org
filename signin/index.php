<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";

if (isset($_SESSION['id'])) {
    header('Location: /logout/');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $mail = $_POST["mail"];
    $news = $_POST['news'] == "on" ? 1 : 0;


    $stmt = $conn->prepare("SELECT `id` FROM `users` WHERE `email` = ?");
    $stmt->bind_param("s", $mail);
    $stmt->execute();
    $stmt->store_result(); 

    $stmt->bind_result($id);
    $stmt->fetch();
    
    if ($stmt->num_rows > 0) {
        $error = "Mail déjà utilisé";
        require 'signin.html';
        exit;
    }


    $stmt = $conn->prepare("SELECT `id` FROM `users` WHERE `username` = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result(); 

    $stmt->bind_result($id);
    $stmt->fetch();
    
    if ($stmt->num_rows > 0) {
        $error = "Nom d'utilisateur déjà utilisé";
        require 'signin.html';
        exit;
    }


    $stmt = $conn->prepare("SELECT `id` FROM `newsletter` WHERE `email` = ?");
    $stmt->bind_param("s", $mail);
    $stmt->execute();
    $stmt->store_result(); 

    $stmt->bind_result($id);
    $stmt->fetch();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();

        $news = 1;

        $stmt = $conn->prepare("DELETE * FROM `newsletter` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

    } else {
        $stmt->close();
    }

    $hash_pass = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO `users` (`username`, `password`, `email`, `news`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $username, $hash_pass, $mail, $news);

    $stmt->execute();

    $id = $conn->insert_id;
    
    $stmt->close();

    $conn->close();

    $redirect = $_POST['redirect'] ?? '/';

    if (!str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
        $redirect = '/';
    }

    session_regenerate_id(true);
    $_SESSION['id'] = $id;
    $_SESSION['mail'] = $mail;
    $_SESSION['username'] = $username;
    $_SESSION['admin'] = 0;
    $_SESSION['news'] = $news;

    header("Location: $redirect");
    exit;

} else {
    require_once 'signin.html';
}
?>
