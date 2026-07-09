<?php

namespace App\Libraries;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    protected $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true); // Pass `true` for exceptions
    }

    public function sendEmail($host, $username, $password, $name, $to, $subject, $message)
    {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $host;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $username;
            $this->mailer->Password = $password;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mailer->Port = 465;

            // Recipients
            $this->mailer->setFrom($username, $name);
            $this->mailer->addAddress($to);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;

            $this->mailer->send();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
