<?php

namespace SCFL\App\Controller;

use PHPMailer\PHPMailer\PHPMailer;


/**
 * Class Email
 * @package SCFL\App\Controller
 */
class Email
{
    /**
     * @param $recipientEmail
     * @param $recipientName
     * @param $subject
     * @param $message
     * @param array $cc
     * @param array $bcc
     * @return bool
     */
    function sendEmail($recipientEmail, $recipientName, $subject, $message, $cc = [], $bcc = [])
    {

        $emailBody = [
            'message' => [
                'from_name' => 'Satkhira Consulting Firm Limited',
                'from_email_address' => 'shantaex81@gmail.com',
                'from_password' => 'lxsgubclfoajiken',
                'recipientEmail' => $recipientEmail,
                'recipientName' => $recipientName,
                'subject' => $subject,
                'html' => $message
            ]
        ];

        try {
            $mail = new PHPMailer();

            $mail->IsSMTP(); // send via SMTP
            $mail->Host = "ssl://smtp.gmail.com";
            $mail->SMTPAuth = true;  // turn on SMTP authentication
            $mail->Username = $emailBody['message']['from_email_address']; // SMTP username
            $mail->Password = $emailBody['message']['from_password']; // SMTP password
            $webmaster_email = $emailBody['message']['from_email_address']; //Reply to this email ID
            $email = $recipientEmail; // Recipients email ID
            $name = $recipientName; // Recipient's name
            $mail->From = $webmaster_email;
            $mail->Port = 465;
            $mail->FromName = "Sharmin Shanta";
            $mail->AddAddress($email, $name);
            $mail->AddReplyTo($webmaster_email, "Sharmin Shanta");
            $mail->WordWrap = 50; // set word wrap
            $mail->IsHTML(true); // send as HTML
            $mail->Subject = $subject;
            $mail->Body = $message; //HTML Body
            $mail->AltBody = $message; //Text Body
            $mail->SMTPDebug = 3;
            //$mail->Debugoutput = $this->getLogger();

            if (!$mail->Send()) {
                return false;
            } else {
                return true;
            }

        } catch (\Exception $exception) {
            $this->getLogger()->info($exception->getMessage(), ['recipients' => $recipientEmail, 'cc' => $cc, 'bcc' => $bcc]);
            $this->getLogger()->info($exception->getTraceAsString(), ['recipients' => $recipientEmail, 'cc' => $cc, 'bcc' => $bcc]);
        }

        return false;
    }
}