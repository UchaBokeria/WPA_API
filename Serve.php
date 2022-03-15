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

// define('MAILSMTPDEBUG', 3);
// define('MAILSMTPAUTH', true);
// define('MAILUSERNAME', 'wpatbilisicongress@gmail.com');
// define('MAILFORMNAME', 'wpatbilisicongress@gmail.com');
// define('MAILPASSWORD', 'wpatbilisi2022');
// define('MAILSMTPSECURE', 'SSL');
// define('MAILDEBUGOUTPUT', 'html');
// define('MAILHOST', 'smtp.gmail.com');
// define('MAILCHARSET', 'UTF-8');
// define('MAILPORT', 465);

// require "./Config/Mailer.Config.php";
// require "./Config/MySQL.Config.php";

// require "./Services/Database/Database.php";
// require "./Services/Mailer/SmtpMailer.php";


// var_dump( (new SmtpMailer([
//     'address' => 'ucha1bokeria@gmail.com',
//     'subject' => 'TEST subject',
//     'body' => 'TEST body'
// ]))->Send()
// );

ini_set('display_errors',1);
ini_set('display_errors', 'On');
set_error_handler("var_dump");

$address =  "ucha1bokeria@gmail.com";
$msg =      'TEST body';
$fullname = "TEST subject";

require "./Services/Mailer/PHPMailer/src/PHPMailer.php";
require "./Services/Mailer/PHPMailer/src/SMTP.php";
require "./Services/Mailer/PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer();
$mail->SMTPDebug = 4;
$mail->isSMTP();
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
$mail->addAddress("ucha1bokeria@gmail.com");
if(!$mail->send()){
    echo 'Mailer Error: ' . $mail->ErrorInfo;
}
else{
    echo "good";
}
$mail->smtpClose();