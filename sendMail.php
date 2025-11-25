<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

function sendMail($to, $subject, $body, $isHTML = true, $attachments = [])
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'vadodariyashivam3@gmail.com';
        $mail->Password = 'hqhgnpylmxzlafqq';

        $mail->SMTPSecure =  'tls';
        $mail->Port = 587;

        $mail->setFrom('vadodariyashivam3@gmail.com', 'osp_platform');
        $mail->addAddress($to);

        if (!empty($attachments)) {
            foreach ($attachments as $file) {
                $mail->addAttachment($file);
            }
        }

        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    } catch (Exception $e) {
        return "Error: {$mail->ErrorInfo}";
    }
}
