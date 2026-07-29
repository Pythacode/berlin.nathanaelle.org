<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/res/php/mail.php";

function resizeImageToBase64($path, $maxWidth = 600, $maxHeight = 600, $quality = 80) {
    $mime = mime_content_type($path);

    switch ($mime) {
        case 'image/jpeg':
            $src = imagecreatefromjpeg($path);
            break;
        case 'image/png':
            $src = imagecreatefrompng($path);
            break;
        case 'image/webp':
            $src = imagecreatefromwebp($path);
            break;
        case 'image/gif':
            $src = imagecreatefromgif($path);
            break;
        default:
            throw new Exception("Format non supporté : $mime");
    }

    $origWidth = imagesx($src);
    $origHeight = imagesy($src);

    // Ratio de redimensionnement (ne pas agrandir si déjà petite)
    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1);
    $newWidth = (int) round($origWidth * $ratio);
    $newHeight = (int) round($origHeight * $ratio);

    $dst = imagecreatetruecolor($newWidth, $newHeight);

    // Préserver la transparence pour PNG/GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

    // Encoder en JPEG pour réduire le poids (sauf si transparence nécessaire → PNG)
    ob_start();
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagepng($dst, null, 6);
        $outMime = 'image/png';
    } else {
        imagejpeg($dst, null, $quality);
        $outMime = 'image/jpeg';
    }
    $data = ob_get_clean();

    imagedestroy($src);
    imagedestroy($dst);

    $base64 = base64_encode($data);
    return "data:{$outMime};base64,{$base64}";
}

function resizeAndSave($srcPath, $destPath, $maxWidth = 800, $quality = 75) {
    $mime = mime_content_type($srcPath);
    
    $img = new Imagick($srcPath);
    $img->autoOrientImage();
    
    // Calcul des nouvelles dimensions
    $origWidth = $img->getImageWidth();
    $origHeight = $img->getImageHeight();
    
    if ($origWidth <= $maxWidth) {
        $newWidth = $origWidth;
        $newHeight = $origHeight;
    } else {
        $newWidth = $maxWidth;
        $newHeight = (int) round($origHeight * ($maxWidth / $origWidth));
    }
    
    // Redimensionnement avec filtrage de haute qualité
    $img->thumbnailImage($newWidth, $newHeight);
    
    // Conversion explicite en WebP pour la sortie
    $img->setImageFormat('webp');
    $img->setImageCompressionQuality($quality);
    
    // Sauvegarde
    $img->writeImage($destPath);
    $img->clear();
    $img->destroy();
    
    return $destPath;
}

function rotate($img_path, $rotate) {
    $img = new Imagick($img_path);

    $img->rotateImage('none', $rotate);
    
    $img->resetIterator();

    // 5. Définir le format de sortie sur WebP
    $img->setImageFormat('webp');

    // 6. Sauvegarder le résultat
    $img->writeImage($img);

    // 7. Nettoyage mémoire
    $img->clear();
    $img->destroy();
}

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
        
        $file = uniqid($filename) . ".webp";

        resizeAndSave($_FILES['photo']['tmp_name'], $AbsoluteUploadDir . $file, 800, 75);
        
        rotate($AbsoluteUploadDir . $file, $_POST["rotate"]);

        $new = $_POST['new'] == "on" ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO `posts` (`picture_name`, `width`, `height`, `description`, `user_id`) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siisi", $file, $_POST['width'], $_POST['height'], $_POST['description'], $_SESSION["id"]);

        $stmt->execute();

        $postId = $conn->insert_id;

        $stmt->close();

        if ($new) {
        
            $img_data = resizeImageToBase64($AbsoluteUploadDir . $file, 600, 600, 80);
            
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
            
        }
        $conn->close();
        header('Location: /?post=' . $postId);
    }
}
?>