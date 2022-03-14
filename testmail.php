<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('MAILSMTPDEBUG', 2);
define('MAILSMTPAUTH', true);
define('MAILUSERNAME', 'support@wpatbilisicongress.com');
define('MAILFORMNAME', 'support@wpatbilisicongress.com');
define('MAILPASSWORD', 'Wpatbilisi2022!');
define('MAILSMTPSECURE', 'tls');
define('MAILDEBUGOUTPUT', 'html');
define('MAILHOST', 'ssl://smtp.titan.support@wpatbilisicongress.com');
define('MAILCHARSET', 'UTF-8');
define('MAILPORT', 465);

require "./Config/Mailer.Confing";
require "./Config/Mailer.Confing";

require "./Services/Database/Database.php";
require "./Services/Mailer/SmtpMailer.php";


var_dump( (new SmtpMailer([
    'cc_address' => 'TEST cc_address',
    'bcc_address' => 'TEST bcc_address',
    'address' => 'ucha1bokeria@gmail.com',
    'subject' => 'TEST subject',
    'body' => 'TEST body'
]))->Send()
);