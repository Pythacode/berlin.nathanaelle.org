<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mail = json_decode(file_get_contents('php://input'), true)['email'];

    $stmt = $conn->prepare("SELECT `id` FROM `users` WHERE `email` = ?");
    $stmt->bind_param("s", $mail);
    $stmt->execute();
    $stmt->store_result(); 
    $stmt->bind_result($id);
    $stmt->fetch();

    if ($stmt->num_rows > 0) {

        $stmt->close();


        $news = 1;

        $stmt = $conn->prepare("UPDATE `users` SET `news`=1 WHERE `id`= ?;");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

    } else {

        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO `newsletter` (`email`) VALUES (?)");
        $stmt->bind_param("s", $mail);

        $stmt->execute();
        $stmt->close();

        $conn->close();
    }

    echo json_encode(['result' => 'success']);
}

?>