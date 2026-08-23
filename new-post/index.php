<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/res/php/mail.php";

function autoOrient(Imagick $image, string $filepath): void {
    $exif = @exif_read_data($filepath);
    if (!$exif || !isset($exif['Orientation'])) {
        return;
    }

    switch ($exif['Orientation']) {
        case 2:
            $image->flipImage();
            break;
        case 3:
            $image->rotateImage('#000000', 180);
            break;
        case 4:
            $image->flopImage();
            break;
        case 5:
            $image->flipImage();
            $image->rotateImage('#000000', 90);
            break;
        case 6:
            $image->rotateImage('#000000', 90);
            break;
        case 7:
            $image->flopImage();
            $image->rotateImage('#000000', 270);
            break;
        case 8:
            $image->rotateImage('#000000', 270);
            break;
    }
    // Ne pas oublier de reset l'orientation EXIF sinon certains viewers re-tournent l'image
    $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
}

function resizeImageToBase64($path, $maxWidth = 600, $maxHeight = 600, $quality = 80) {
    if (!extension_loaded('imagick')) {
        throw new Exception("Extension Imagick non disponible.");
    }

    $img = new Imagick($path);

    // Corrige l'orientation EXIF si présente (gère aussi le cas où webp n'en a pas)
    //$img->autoOrientImage();
    autoOrient($img,$path);

    // Aplati les frames (webp animé) sur la première frame
    if ($img->getNumberImages() > 1) {
        $img = $img->coalesceImages();
        $img = $img->getImage(); // ne garde que la première frame
    }

    $origWidth  = $img->getImageWidth();
    $origHeight = $img->getImageHeight();

    // Ratio de redimensionnement (ne pas agrandir si déjà petite)
    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1);
    $newWidth  = (int) round($origWidth * $ratio);
    $newHeight = (int) round($origHeight * $ratio);

    $img->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);

    // Préserve la transparence si présente
    $hasAlpha = $img->getImageAlphaChannel();

    if ($hasAlpha) {
        $img->setImageFormat('png');
        $img->setImageCompressionQuality(80); // ignoré par PNG mais inoffensif
        $outMime = 'image/png';
    } else {
        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality($quality);
        $img->setImageBackgroundColor('white');
        $img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $outMime = 'image/jpeg';
    }

    // Nettoie les métadonnées EXIF restantes (poids + vie privée)
    $img->stripImage();

    $data = $img->getImageBlob();
    $img->destroy();

    $base64 = base64_encode($data);
    return "data:{$outMime};base64,{$base64}";
}

function resizeAndSave($srcPath, $destPath, $maxWidth = 800, $quality = 75) {
    $mime = mime_content_type($srcPath);
    
    $img = new Imagick($srcPath);
    //$img->autoOrientImage();
    autoOrient($img,$srcPath);
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
    $img->writeImage($img_path);

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
                
                send_mail($template, $variables, "Nouveau post sur 1 an à Berlin !", $row["email"], "newsletter", "Newsletter - 1 an à Berlin", $unsubscribe_link);
                
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
                    
                send_mail($template, $variables, "Nouveau post sur 1 an à Berlin !", $row["email"], "newsletter", "Newsletter - 1 an à Berlin", $unsubscribe_link);
            }
            
        }
        $conn->close();
        header('Location: /?post=' . $postId);
    }
}
?>