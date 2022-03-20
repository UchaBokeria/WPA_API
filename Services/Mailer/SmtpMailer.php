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
            $mail->Body = '<img src="https://doc-14-a0-docs.googleusercontent.com/docs/securesc/jagmva5ufaire31mv4rfvapg01jf1r2c/9rb8hhg75mo8hvuvedhdp1ivpr7tli8n/1647777675000/17593894767674285516/17593894767674285516/16fVr0AeqN4Mrw575YSthG4ITmv8U3oQS?e=download&ax=ACxEAsa9ys-DT7jTRUNyxtEnvAcRnQB3FWNaxH2VWqP0lYNqWMdjVMLYE7ElilLZrZEKMJtgdQKzPzzoZB7UjA7AJqbvycAaKm5RC2qr3-kYgh5TE24ElAlb2nOFj0S99DBkNvPzZzBa6mZGyWtsMFhfIsxAwN0F_01OlpwvuUHlx1DcPiOIWwxVakQxK0qE9gkFGBsxm1JxArqQEVCW5CaSCHqerlZ84ERX_86gA90AtC5amUkhm6koHR9Zz_ggfnYhTnQz_JVTxW5I-9mXMg3UQeB11eqgILUY4GZ4kGlt2arZMvN1aVgqqGEFjKWSiCJ1FXurJyv1o1Ce6NNM-_nkOElgfReE1j8mJPMB6cQTUO-4nI1hs8Yx8l-Y5w84VW12iNv7iL7BO5c3zS-RUAK-IHuG9dN8WKkh-5A5SoYoJW5_N782dVlvgg445tc1zpIU1Ymq65iFqwUaLuhfCFYbtx7KjkW7x_gQn0DX_TztvxGTGCOOVjvZb8ZI0S3bsemNgO_3DvGwKe_F5OkOK_CcexNZ4_x8zJp432wSBrmggQQX_m02-LWZy0TfRn7trSjBCnKc2wR9GvvOZpFLynOIWFdNNTM7hrBrG6kVocyXrSXf7wu1vJJobl__cjcLviFVDHW7uc6eLRizcW-dc0_TK2Juor5krMziUVd2QcSN94roUYF4TqzK-cfSSnPx0-ryc_flR6qfIpZjOy6YTN5KfZ-RnyfGKguT17y2OEVUW69UQJ-JloOd-xL5BvE2MDoj3q-r-6WnIMFX5lRx3x4hQu8&authuser=2&nonce=c6rlalk4jdnha&user=17593894767674285516&hash=u7gfqmp87pkk2qd2n7rttenrtargmhv9"> X';
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