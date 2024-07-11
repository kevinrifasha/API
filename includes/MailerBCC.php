<?php

use PHPMailer\PHPMailer;
use PHPMailer\Exceptions;

class MailerBcc
{
    function __construct()
    {
        require_once dirname(__FILE__) . '/Constants.php';
        /* Exception class. */
        require_once dirname(__FILE__).'/php-mailer/src/Exception.php';

        /* The main PHPMailer class. */
        require_once dirname(__FILE__).'/php-mailer/src/PHPMailer.php';

        /* SMTP class, needed if you want to use SMTP. */
        require_once dirname(__FILE__).'/php-mailer/src/SMTP.php';
    }

    public function sendMessage($to, $subject, $msgBody)
    {
        $mail = new PHPMailer\PHPMailer();
        $mail->SMTPAutoTLS = true;
        $mail->IsSMTP();
        $mail->SMTPSecure = 'tls';
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->Host = "srv107.niagahoster.com"; //host masing2 provider email
        $mail->SMTPDebug = 0;
        $mail->IsHTML(true);
        $mail->Port = 465;
        $mail->SMTPAuth = true;
        $mail->Username = "noreply@ur-hub.com"; //user email
        $mail->Password = "Opengbangsat20!"; //password email
        $mail->SetFrom("noreply@ur-hub.com","no-reply"); //set email pengirim
        $mail->Subject = $subject; //subyek email
        // $mail->AddBcc("harrytanaka420@gmail.com");
        // $mail->AddBcc("flinctchristian@gmail.com");
        $mail->AddAddress($to);  //tujuan email
        $mail->MsgHTML($msgBody);

        if($mail->Send())
            return TRANSAKSI_CREATED;
        else
            return FAILED_TO_CREATE_TRANSAKSI;
    }

}
