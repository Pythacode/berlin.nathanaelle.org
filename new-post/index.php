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
    if (!file_exists($path)) {
        throw new Exception("Fichier introuvable.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);

    /*
     * =========================
     * VIDEO WEBM
     * =========================
     */
    if ($mime === 'video/webm') {

        $tmpImage = tempnam(sys_get_temp_dir(), 'webm_preview_') . '.jpg';

        $cmd = sprintf(
            'ffmpeg -y -i %s -frames:v 1 -q:v 2 %s 2>&1',
            escapeshellarg($path),
            escapeshellarg($tmpImage)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($tmpImage)) {
            @unlink($tmpImage);

            throw new Exception(
                "Impossible d'extraire la miniature du WebM."
            );
        }

        $path = $tmpImage;
        $deleteTmp = true;

    } else {
        $deleteTmp = false;

        if (!str_starts_with($mime, 'image/')) {
            throw new Exception("Type de fichier non supporté : " . $mime);
        }
    }

    /*
     * =========================
     * IMAGE / FRAME WEBM
     * =========================
     */

    if (!extension_loaded('imagick')) {
        if ($deleteTmp) {
            @unlink($path);
        }

        throw new Exception("Extension Imagick non disponible.");
    }

    $img = new Imagick($path);

    autoOrient($img, $path);

    // Première frame pour les images animées
    if ($img->getNumberImages() > 1) {
        $img = $img->coalesceImages();
        $img->setIteratorIndex(0);
    }

    $origWidth  = $img->getImageWidth();
    $origHeight = $img->getImageHeight();

    $ratio = min(
        $maxWidth / $origWidth,
        $maxHeight / $origHeight,
        1
    );

    $newWidth  = (int) round($origWidth * $ratio);
    $newHeight = (int) round($origHeight * $ratio);

    $img->resizeImage(
        $newWidth,
        $newHeight,
        Imagick::FILTER_LANCZOS,
        1
    );

    /*
     * Transparence
     */
    $hasAlpha = $img->getImageAlphaChannel();

    if ($hasAlpha) {
        $img->setImageFormat('png');
        $outMime = 'image/png';
    } else {
        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality($quality);
        $img->setImageBackgroundColor('white');

        $img = $img->mergeImageLayers(
            Imagick::LAYERMETHOD_FLATTEN
        );

        $outMime = 'image/jpeg';
    }

    $img->stripImage();

    $data = $img->getImageBlob();

    $img->clear();
    $img->destroy();

    if ($deleteTmp) {
        @unlink($path);
    }

    return "data:{$outMime};base64," . base64_encode($data);
}

function resizeAndSave($srcPath, $destPath, $maxWidth = 800, $quality = 75) {
    $mime = mime_content_type($srcPath);

    // Retirer l'ancienne extension du chemin
    $destPath = pathinfo($destPath, PATHINFO_DIRNAME) . '/' .
                pathinfo($destPath, PATHINFO_FILENAME);

    /*
     * =========================
     * IMAGE
     * =========================
     */
    if (str_starts_with($mime, 'image/')) {

        $destPath .= '.webp';

        $img = new Imagick($srcPath);

        autoOrient($img, $srcPath);

        $origWidth  = $img->getImageWidth();
        $origHeight = $img->getImageHeight();

        if ($origWidth <= $maxWidth) {
            $newWidth  = $origWidth;
            $newHeight = $origHeight;
        } else {
            $newWidth  = $maxWidth;
            $newHeight = (int) round(
                $origHeight * ($maxWidth / $origWidth)
            );
        }

        $img->thumbnailImage($newWidth, $newHeight);

        $img->setImageFormat('webp');
        $img->setImageCompressionQuality($quality);
        $img->writeImage($destPath);

        $img->clear();
        $img->destroy();

        return $destPath;
    }

    /*
     * =========================
     * VIDEO
     * =========================
     */
    if (str_starts_with($mime, 'video/')) {

        $destPath .= '.webm';

        $cmd = sprintf(
            'ffprobe -v error -select_streams v:0 ' .
            '-show_entries stream=width,height ' .
            '-of csv=p=0:s=x %s 2>&1',
            escapeshellarg($srcPath)
        );

        $dimensions = trim(shell_exec($cmd));

        if (!preg_match('/^(\d+)x(\d+)$/', $dimensions, $matches)) {
            throw new Exception("Impossible de récupérer les dimensions de la vidéo.");
        }

        $origWidth  = (int) $matches[1];
        $origHeight = (int) $matches[2];

        if ($origWidth <= $maxWidth) {
            $newWidth  = $origWidth;
            $newHeight = $origHeight;
        } else {
            $newWidth  = $maxWidth;
            $newHeight = (int) round(
                $origHeight * ($maxWidth / $origWidth)
            );
        }

        // Dimensions paires
        $newWidth  -= $newWidth % 2;
        $newHeight -= $newHeight % 2;

        // 75 => CRF ~25
        $crf = (int) round(40 - ($quality * 0.2));
        $crf = max(10, min(40, $crf));

        $cmd = sprintf(
            'ffmpeg -y -i %s ' .
            '-vf %s ' .
            '-c:v libvpx-vp9 ' .
            '-crf %d -b:v 0 ' .
            '-c:a libopus -b:a 128k ' .
            '%s 2>&1',
            escapeshellarg($srcPath),
            escapeshellarg("scale={$newWidth}:{$newHeight}"),
            $crf,
            escapeshellarg($destPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception(
                "Erreur FFmpeg :\n" . implode("\n", $output)
            );
        }

        return $destPath;
    }

    throw new Exception("Type de fichier non supporté : " . $mime);
}


function rotate($filePath, $rotate) {
    $mime = mime_content_type($filePath);

    /*
     * =========================
     * IMAGE
     * =========================
     */
    if (str_starts_with($mime, 'image/')) {

        $img = new Imagick($filePath);

        $img->rotateImage('none', $rotate);

        $img->setImageFormat('webp');

        $img->writeImage($filePath);

        $img->clear();
        $img->destroy();

        return $filePath;
    }

    /*
     * =========================
     * VIDEO
     * =========================
     */
    if (str_starts_with($mime, 'video/')) {

        $tempPath = $filePath . '.tmp.webm';

        /*
         * FFmpeg :
         * 90°  -> transpose=1
         * 180° -> hflip,vflip
         * 270° -> transpose=2
         */
        $angle = (($rotate % 360) + 360) % 360;

        switch ($angle) {
            case 90:
                $filter = 'transpose=1';
                break;

            case 180:
                $filter = 'hflip,vflip';
                break;

            case 270:
                $filter = 'transpose=2';
                break;

            case 0:
                return $filePath;

            default:
                // Rotation arbitraire
                $filter = 'rotate=' . deg2rad($angle) .
                          ':fillcolor=black';
                break;
        }

        $cmd = sprintf(
            'ffmpeg -y -i %s ' .
            '-vf %s ' .
            '-c:v libvpx-vp9 ' .
            '-crf 25 -b:v 0 ' .
            '-c:a libopus -b:a 128k ' .
            '%s 2>&1',
            escapeshellarg($filePath),
            escapeshellarg($filter),
            escapeshellarg($tempPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            @unlink($tempPath);

            throw new Exception(
                "Erreur FFmpeg :\n" . implode("\n", $output)
            );
        }

        // Remplacer l'original
        rename($tempPath, $filePath);

        return $filePath;
    }

    throw new Exception("Type de fichier non supporté : " . $mime);
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

        $RelativeUploadDir = "/res/data/";

        $AbsoluteUploadDir = $_SERVER['DOCUMENT_ROOT'] . $RelativeUploadDir;

        if (!is_dir($AbsoluteUploadDir)) {
            mkdir($AbsoluteUploadDir, 0755, true);
        }


        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['photo']['tmp_name']);

        if (str_starts_with($mime, 'image/')) {
            $type = 'img';
            $AbsoluteUploadDir .= "pictures";
        } elseif (str_starts_with($mime, 'video/')) {
            $type = 'video';
            $AbsoluteUploadDir .= "videos";
        }

        $infos = pathinfo($_FILES['photo']['name']);
        $filename = $infos['filename'] . '_';

        $file = uniqid($filename);

        $path = resizeAndSave(
            $_FILES['photo']['tmp_name'],
            $AbsoluteUploadDir . $file,
            800,
            75
        );

        rotate(
            $path,
            (int)($_POST["rotate"] ?? 0)
        );


        $new = $_POST['new'] == "on" ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO `posts` (`picture_name`, `width`, `height`, `description`, `user_id`, `type`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisis", basename($path), $_POST['width'], $_POST['height'], $_POST['description'], $_SESSION["id"], $type);

        $stmt->execute();

        $postId = $conn->insert_id;

        $stmt->close();

        if ($new) {
        
            $img_data = resizeImageToBase64($path, 600, 600, 80);
            
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