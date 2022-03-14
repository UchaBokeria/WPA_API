<?php

// /* Configs */
// require "./Services/Engineer/LearnConfigs.Scheme.php";
// require "./Services/Engineer/LearnControllers.Scheme.php";

// /* Services */
// require "./Services/Database/Database.php";
// require "./Services/Mailer/SmtpMailer.php";
// require "./Services/Engineer/API.Core.php";


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

define('MAILSMTPDEBUG', 3);
define('MAILSMTPAUTH', true);
define('MAILUSERNAME', 'wpatbilisicongress@gmail.com');
define('MAILFORMNAME', 'wpatbilisicongress@gmail.com');
define('MAILPASSWORD', 'wpatbilisi2022');
define('MAILSMTPSECURE', 'SSL');
define('MAILDEBUGOUTPUT', 'html');
define('MAILHOST', 'smtp.gmail.com');
define('MAILCHARSET', 'UTF-8');
define('MAILPORT', 465);

require "./Config/Mailer.Config.php";
require "./Config/MySQL.Config.php";

require "./Services/Database/Database.php";
require "./Services/Mailer/SmtpMailer.php";


var_dump( (new SmtpMailer([
    'address' => 'ucha1bokeria@gmail.com',
    'subject' => 'TEST subject',
    'body' => 'TEST body'
]))->Send()
);