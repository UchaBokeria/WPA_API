<?php

    date_default_timezone_set('Etc/UTC');
    header('Content-Type: text/html; charset=utf-8');

    require "./Services/Mailer/PHPMailer/src/PHPMailer.php";
    require "./Services/Mailer/PHPMailer/src/SMTP.php";
    require "./Services/Mailer/PHPMailer/src/Exception.php";

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    /*
        $mail = new PHPMailer();
        $mail->SMTPDebug = 2;
        $mail->isSMTP();
        $mail->SMTPAuth = true; 
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = "true";
        $mail->SMTPSecure = "tls";
        $mail->Port = "587";
        $mail->Username = "wpatbilisicongress@gmail.com";
        $mail->Password = "wpatbilisi2022";
        $mail->Subject = "Support message FROM: " . $address . ", Full Name: " . $fullname;
        $mail->setFrom("wpatbilisicongress@gmail.com");
        $mail->isHTML(true);
        $mail->Body = $msg;
        $mail->addAddress("wpatbilisicongress@gmail.com");
        if(!$mail->send()){
            echo 'Mailer Error: ' . $mail->ErrorInfo;
        }
        else{
            echo "good";
        }
        $mail->smtpClose();
    */

    class SmtpMailer extends Database
    {

        public $Options = null;
        public $mailer = null;

        public function __construct($OPTIONS = null) 
        {
            if($OPTIONS != null)
                $this->Options = $OPTIONS;
        }

        public function Send($OPTIONS = null)
        {
            if($OPTIONS != null)
                $this->Options = $OPTIONS;

            $mail = new PHPMailer();
            
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->SMTPAuth = MAILSMTPAUTH; 
            $mail->Host = MAILHOST;
            $mail->SMTPAuth = MAILSMTPAUTH;
            $mail->SMTPSecure = MAILSMTPSECURE;
            $mail->Port = MAILPORT;
            $mail->Username = MAILUSERNAME;
            $mail->Password = MAILPASSWORD;
            $mail->Subject = $this->Options['subject'];
            $mail->setFrom(MAILFORMNAME);
            $mail->addAddress($this->Options['address']);

            // $mail->SMTPOptions = array(
            //     'ssl' => array(
            //     'verify_peer' => false,
            //     'verify_peer_name' => false,
            //     'allow_self_signed' => true
            //     )
            // );

            $mail->isHTML(true);
            $mail->Body = '<iframe src="https://wpatbilisicongress.com/" title="W3Schools Free Online Web Tutorials">
                </iframe>';
            //$mail->addAttachment("./Sources/Doc/logosvg.svg","logosvg"); 

            $resp = $mail->send();
            $mail->smtpClose();

            return [
                'error' => !$resp , 
                'msg' => (!$resp) ? 
                    'Mail Has Been Failed To Sent' : 
                    'Mail Has Been Sent'
            ];
            
        }

        public function TemplateBuild($object, $template)
        {
            $template = file_get_contents($template);
            foreach ($object as $key => $value)
                $template = str_replace("{". $key . "}", $value, $template);
            
            return $template;
        }

    }
    
    global $SMTPMAILER;
    $SMTPMAILER = new SmtpMailer();