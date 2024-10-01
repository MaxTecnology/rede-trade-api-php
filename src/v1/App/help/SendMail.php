<?php

namespace App\help;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../../vendor/autoload.php';

class SendMail
{

    static function go($to, $subject, $message)
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = getenv('SMTP_HOST');            // Host SMTP
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USER');            // Email do remetente
            $mail->Password   = getenv('SMTP_PASSWORD');        // Senha ou App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
            $mail->Port       = 587;                            // Porta SMTP

            $mail->setFrom(getenv('SMTP_USER'), 'Teste de Envio');   // Email do remetente
            $mail->addAddress($to);           // Email do destinatário

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = $message;

            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
}
