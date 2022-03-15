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

        public function Send()
        {

            // $this->mailer = new PHPMailer();
            // $this->mailer->SMTPDebug = MAILSMTPDEBUG; 
            // $this->mailer->isSMTP();
            // $this->mailer->SMTPAuth = MAILSMTPAUTH; 
            // $this->mailer->Host = MAILCHARSET;
            // $this->mailer->SMTPSecure = MAILSMTPSECURE;
            // $this->mailer->Port = MAILPORT;
            // $this->mailer->Username = MAILUSERNAME; 
            // $this->mailer->Password = MAILPASSWORD; 
            // $this->mailer->isHTML(MAILISHTML);
            // $this->mailer->setFrom(MAILFORMNAME);
            // //$this->mailer->AuthType = 'PLAIN';

            $mail = new PHPMailer();
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->SMTPAuth = true; 
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = "true";
            $mail->SMTPSecure = "tls";
            $mail->Port = "587";
            $mail->Username = "wpatbilisicongress@gmail.com";
            $mail->Password = "wpatbilisi2022";
            $mail->Subject = "Support message FROM: " . $this->Options['address'] . ", Full Name: " . $this->Options['subject'];
            $mail->setFrom("wpatbilisicongress@gmail.com");
            $mail->isHTML(true);
            $mail->Body = $this->Options['body'];
            $mail->addAddress("wpatbilisicongress@gmail.com");
            $resp = $mail->send();
            $mail->smtpClose();

            // if ($this->Options['cc_address'] != '')
            //     foreach (explode(';', $this->Options['cc_address']) as $val) $this->mailer->AddCC($val);
            
            // if ($this->Options['bcc_address'] != '')
            //     foreach (explode(';', $this->Options['bcc_address']) as $val) $this->mailer->AddBCC($val);

            // if ($this->Options['address'] != '')
            //     foreach (explode(";", $this->Options['address']) as $val) $this->mailer->addAddress($val);
            
            // $this->mailer->Subject = $this->Options['subject'];
            // //$this->mailer->addAttachment('http://localhost/TDG/mepacallapp/media/uploads/documents/'.$attachmet);
            // $this->mailer->Body = ($this->Options['body'] == '') ? ' ' : $this->Options['body'];

            // $this->Result = $this->mailer->send();
            // $this->mailer->smtpClose();
            
            // return [
            //     'error' => !$this->Result , 
            //     'msg' => (!$this->Result ) ? 
            //         'Mail Has Been Failed' : 
            //         'Mail Has Been Sent'
            // ];

            return [
                'error' => !$resp , 
                'msg' => ($resp ) ? 
                    'Mail Has Been Failed' : 
                    'Mail Has Been Sent'
            ];
            
        }

    }
    
    global $SMTPMAILER;
    $SMTPMAILER = new SmtpMailer();