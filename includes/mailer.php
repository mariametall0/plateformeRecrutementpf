<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../mail/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/../mail/PHPMailer/src/SMTP.php";
require_once __DIR__ . "/../mail/PHPMailer/src/Exception.php";

function exec_send_email($to, $subject, $body) {
    try {
        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8";
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'a42004001@smtp-brevo.com';
        $mail->Password = 'xsmtpsib-21006202d92c7b745921dd1dd20a8dd52a763ca863fdcd93caf812b3a0a2687c-fBfDGKk8BvRm3OpQ';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('mariametall06@gmail.com', 'Plateforme Admissio');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        return $mail->send();
    } catch (Exception $e) {
        // Enregistrez l'erreur dans un log si nécessaire
        return false;
    }
}
