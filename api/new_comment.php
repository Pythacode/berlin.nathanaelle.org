<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    $comment = $data['comment'];
    $postId = $data['post_id'];

    $stmt = $conn->prepare("INSERT INTO `comments` (`id_post`, `id_user`, `comment`) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $postId, $_SESSION['id'], $comment);

    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM comments WHERE id_post = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();

    $result = $stmt->get_result();

    $comments = [];
    
    while ($row = $result->fetch_assoc()) {
        $date = new DateTime($row['created_at']);

        $stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt_user->bind_param("i", $row["id_user"]);
        $stmt_user->execute();

        $result_user = $stmt_user->get_result();
        $user = $result_user->fetch_assoc();

        $stmt_user->close();

        if ($user) {
            $username = $user["username"];
        } else {
            $username = "Utilisateur inconus";
        }

        array_push($comments, [
            "user" => $username,
            "date" => $date->getTimestamp() * 1000,
            "comment" => $row["comment"]
        ]);
    }

    $stmt->close();
    $conn->close();

    echo json_encode(['result' => 'success', "comments" => $comments]);
}

?>