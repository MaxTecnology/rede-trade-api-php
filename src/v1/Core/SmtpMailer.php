<?php

namespace Core;

use App\Library\PHPMailer\PHPMailer;
use App\Library\PHPMailer\SMTP;
use App\Library\PHPMailer\Exception;

class SmtpMailer
{
  private $smtpHost;
  private $smtpPort;
  private $smtpUser;
  private $smtpPass;
  private $smtpEncryption;

  public function __construct($host, $port, $user, $pass, $encryption = 'tls')
  {
    $this->smtpHost = $host;
    $this->smtpPort = $port;
    $this->smtpUser = $user;
    $this->smtpPass = $pass;
    $this->smtpEncryption = $encryption;
  }

  public function sendMailer($to, $subject, $htmlContent, $from)
  {


    try {
      ob_start();
      $mail = new PHPMailer(true);
      $mail->SMTPDebug = SMTP::DEBUG_SERVER;
      $mail->isSMTP();
      $mail->Host       = $this->smtpHost;
      $mail->SMTPAuth   = true;
      $mail->Username   = $this->smtpUser;
      $mail->Password   = $this->smtpPass;
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
      $mail->Port       = $this->smtpPort;

      $mail->CharSet = 'UTF-8';

      $mail->setFrom($this->smtpUser, 'G7 BankPay');
      $mail->addAddress($to);

      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body    = $htmlContent;
      $mail->AltBody = strip_tags($htmlContent);

      $send = $mail->send();
      ob_end_clean();
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  public function sendMail($to, $subject, $htmlContent, $from)
  {
    return $this->sendMailer($to, $subject, $htmlContent, $from);
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $from" . "\r\n";
    $message = $htmlContent;


    $contextOptions = [
      'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
      ],
    ];

    $context = stream_context_create($contextOptions);
    $protocol = strtolower($this->smtpEncryption) === 'ssl' ? 'ssl://' : 'tls://';
    $smtp = stream_socket_client($protocol . $this->smtpHost . ':' . $this->smtpPort, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

    if (!$smtp) {
      $this->logError("Falha ao conectar ao servidor SMTP: $errstr ($errno)");
      return $this->response(false, "Falha ao conectar ao servidor SMTP: $errstr ($errno)");
    }

    try {
      $this->sendCommand($smtp, "EHLO " . $this->smtpHost);
      $this->sendCommand($smtp, "AUTH LOGIN");
      $this->sendCommand($smtp, base64_encode($this->smtpUser));
      $this->sendCommand($smtp, base64_encode($this->smtpPass));
      $this->sendCommand($smtp, "MAIL FROM: <$from>");
      $this->sendCommand($smtp, "RCPT TO: <$to>");
      $this->sendCommand($smtp, "DATA");
      $this->sendCommand($smtp, "To: <$to>\r\nSubject: $subject\r\n$headers\r\n\r\n$message\r\n.");
      $this->sendCommand($smtp, "QUIT");

      fclose($smtp);
      return $this->response(true, "Email enviado com sucesso");
    } catch (\Exception $e) {
      fclose($smtp);
      $this->logError($e->getMessage());
      return $this->response(false, "Falha ao enviar email: " . $e->getMessage());
    }
  }

  private function sendCommand($smtp, $cmd)
  {
    fputs($smtp, $cmd . "\r\n");
    $response = fgets($smtp, 512);

    // Log the response for debugging purposes
    $this->logDebug("Command: $cmd - Response: $response");

    // Check if the response is not an error
    if (substr($response, 0, 3) >= '400') {
      throw new \Exception("Falha no comando SMTP: $cmd - Resposta: $response");
    }

    return $response;
  }

  private function logError($message)
  {
    // Implement your error logging logic here (e.g., file logging, database logging)
    error_log($message);
  }

  private function logDebug($message)
  {
    // Implement your debug logging logic here (e.g., file logging, database logging)
    // For now, just log to the error log
    error_log($message);
  }

  private function response($success, $message)
  {
    return json_encode([
      "success" => $success,
      "message" => $message
    ]);
  }
}
