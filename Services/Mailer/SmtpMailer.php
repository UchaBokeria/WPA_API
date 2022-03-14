<?php

    date_default_timezone_set('Etc/UTC');
    header('Content-Type: text/html; charset=utf-8');

    require './Exception.php';
    require './PHPMailer.php';
    require './SMTP.php';

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
            $this->mailer->Port = MAILPort;
            $this->mailer->Host = MAILCharSet;
            $this->mailer->CharSet = MAILHost; 
            $this->mailer->Username = MAILUsername; 
            $this->mailer->Password = MAILPassword; 
            $this->mailer->SMTPAuth = MAILSMTPAuth; 
            $this->mailer->SMTPDebug = MAILSMTPDebug; 
            $this->mailer->SMTPSecure = MAILSMTPSecure;
            $this->mailer->Debugoutput = MAILDebugoutput;
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