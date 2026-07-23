<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);

    $postId = $data["post_id"];

    $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $postDesc = $post["description"];
    $postDate = new DateTime($post["created_at"]);
    $postUser_id = $post["user_id"];

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM likes WHERE post_id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $countLike = $stmt->get_result()->fetch_assoc()['total'];

    if (isset($_SESSION['id'])) {
        $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("ii", $_SESSION['id'], $postId);
        $stmt->execute();
        $stmt->store_result();
        $hasLiked = $stmt->num_rows > 0;
    } else {
        $hasLiked = false;
    }

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

    $stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $postUser_id);
    $stmt_user->execute();

    $result = $stmt_user->get_result();
    $user = $result->fetch_assoc();

    $stmt_user->close();

    if ($user) {
        $username = $user["username"];
    } else {
        $username = "Utilisateur inconus";
    }

    echo json_encode([
        'user' => $username,
        'date' => $postDate->getTimestamp() * 1000,
        'desc' => $postDesc,
        'likes_count' => $countLike,
        'has_like' => $hasLiked,
        'comments' => $comments
    ]);
}

?>
