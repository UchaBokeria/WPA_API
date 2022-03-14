<?php

    date_default_timezone_set('Etc/UTC');
    header('Content-Type: text/html; charset=utf-8');

    require './Services/Mailer/Exception.php';
    require './Services/Mailer/PHPMailer.php';
    require './Services/Mailer/SMTP.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;


    class SmtpMailer extends Database
    {

        public $Options = null;

        public function __construct($OPTIONS = null) 
        {
            if($OPTIONS != null)
                $this->Options = $OPTIONS;
        }

        public function Send()
        {

            $this->mailer = new PHPMailer;
            $this->mailer->isSMTP();
            $this->mailer->Port = MAILPORT;
            $this->mailer->Host = MAILCHARSET;
            $this->mailer->CharSet = MAILHOST; 
            $this->mailer->Username = MAILUSERNAME; 
            $this->mailer->Password = MAILPASSWORD; 
            $this->mailer->SMTPAuth = MAILSMTPAUTH; 
            $this->mailer->SMTPDebug = MAILSMTPDEBUG; 
            $this->mailer->SMTPSecure = MAILSMTPSECURE;
            $this->mailer->Debugoutput = MAILDEBUGOUTPUT;
            $this->mailer->setFrom(MAILFORMNAME);
            //$this->mailer->AuthType = 'PLAIN';

            if ($this->Options['cc_address'] != '')
                foreach (explode(';', $this->Options['cc_address']) as $val) $this->mailer->AddCC($val);
            
            if ($this->Options['bcc_address'] != '')
                foreach (explode(';', $this->Options['bcc_address']) as $val) $this->mailer->AddBCC($val);

            if ($this->Options['address'] != '')
                foreach (explode(";", $this->Options['address']) as $val) $this->mailer->addAddress($val);
            
            $this->mailer->Subject = $this->Options['subject'];
            //$this->mailer->addAttachment('http://localhost/TDG/mepacallapp/media/uploads/documents/'.$attachmet);
            $this->mailer->msgHTML(($this->Options['body'] == '') ? ' ' : $this->Options['body']);

            $this->Result = $this->mailer->send();
            return [ 
                'error' => $this->Result , 
                'msg' => (!$this->Result ) ? 
                    'Mail Has Been Failed' : 
                    'Mail Has Been Sent'
            ];
            
        }

    }