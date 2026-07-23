<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/res/php/mail.php";

if (!isset($_SESSION['id'])) {
    header('Location: /login/?redirect=/new-post/');
    exit;
}

if (!$_SESSION["admin"]) {
    header('Location: /');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {

    require_once 'post.html';

} else if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_FILES['photo']['tmp_name'])) {

        $RelativeUploadDir = "/res/pictures/";

        $AbsoluteUploadDir = $_SERVER['DOCUMENT_ROOT'] . $RelativeUploadDir;

        if (!is_dir($AbsoluteUploadDir)) {
            mkdir($AbsoluteUploadDir, 0755, true);
        }

        $infos = pathinfo($_FILES['photo']['name']);
        $filename = $infos['filename'] . '_';
        $extension = $infos['extension'];
        
        $file = uniqid($filename) . '.' . $extension;

        $retour = copy($_FILES['photo']['tmp_name'], $AbsoluteUploadDir . $file);

        $stmt = $conn->prepare("INSERT INTO `posts` (`picture_name`, `width`, `height`, `description`, `user_id`) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siisi", $file, $_POST['width'], $_POST['height'], $_POST['description'], $_SESSION["id"]);

        $stmt->execute();

        $postId = $conn->insert_id;

        $stmt->close();
        
        if($retour) {
            
            $data = file_get_contents($AbsoluteUploadDir . $file);
            $mime = mime_content_type($AbsoluteUploadDir . $file);
            $base64 = base64_encode($data);
            
            $img_data = "data:{$mime};base64,{$base64}";
            
            $template = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/res/mail-templates/news.html');
            
            $result = $conn->query("SELECT `username`, `email` FROM `users` WHERE `news` = 1");
            foreach ($result as $row) {
                
                $unsubscribe_link = "https://berlin.nathanaelle.org/unsubscribe/?mail=" . htmlspecialchars($row['email']);
                
                $variables = [
                    '{{NAME}}' => htmlspecialchars($row["username"]) . ' ',
                    '{{USER}}' => $_SESSION['username'],
                    '{{POST_ID}}' => $postId,
                    '{{UNSUBSRIBE_LINK}}' => $unsubscribe_link,
                    '{{IMG_DATA}}' => $img_data,
                ];
                
                send_mail($template, $variables, "Nouveau post sur 1 an à Berlin !", $row["email"], "newsletter", $unsubscribe_link);
                
            }

            $result = $conn->query("SELECT `email` FROM `newsletter`");
            foreach ($result as $row) {
                
                $unsubscribe_link = "https://berlin.nathanaelle.org/unsubscribe/?mail=" . htmlspecialchars($row['email']);
                
                $variables = [
                    '{{NAME}}' => '',
                    '{{USER}}' => $_SESSION['username'],
                    '{{POST_ID}}' => $postId,
                    '{{UNSUBSRIBE_LINK}}' => $unsubscribe_link,
                    '{{IMG_DATA}}' => $img_data,
                    ];
                    
                send_mail($template, $variables, "Nouveau post sur 1 an à Berlin !", $row["email"], "newsletter", $unsubscribe_link);
            }
            
            $conn->close();
            header('Location: /?post=' . $postId);
        }
    }
}
?>