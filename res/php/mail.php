<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_mail($template, $variables, $subject, $to, $from, $unsubscribe_link) {
    $corps = str_replace(array_keys($variables), array_values($variables), $template);
    $fromEmail = $from . "@berlin.nathanaelle.org";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'mail.infomaniak.com';
        $mail->SMTPAuth = true;
        $mail->Username = $fromEmail;
        $mail->Password = $_ENV["MAIL_PASS"];
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($fromEmail, "1 an à Berlin newsletter");
        $mail->addReplyTo('contact@nathanaelle.org');
        $mail->addAddress($to);

        if ($unsubscribe_link) {
            $mail->addCustomHeader('List-Unsubscribe', "<{$unsubscribe_link}>, <mailto:contact@nathanaelle.org?subject=unsubscribe>");
        }
        $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $corps;

        $result = $mail->send();
        return $result;

    } catch (Exception $e) {
        error_log("[SEND_ERROR] à $to : " . $mail->ErrorInfo);
        return false;
    }
}
?>