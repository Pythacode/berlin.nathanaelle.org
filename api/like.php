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

    $postId = $data["post_id"];
    $action = $data["action"];
    $userId = $_SESSION['id'];

    if ($action == "add") {
        $stmt = $conn->prepare("SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();

        if (!$stmt->fetch()) {
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $postId, $userId);
            $stmt->execute();
            $stmt->close();
        } else {$stmt->close();}
    } else if ($action == "remove") {
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("ii", $userId, $postId);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM likes WHERE post_id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $countLike = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    echo json_encode(['likes_count' => $countLike]);

}

?>
