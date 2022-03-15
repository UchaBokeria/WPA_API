<?php

require "./Config/Mailer.Config.php";


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
$resp = !$mail->send();
$mail->smtpClose();

echo json_encode(['error' => !$resp, 'msg' => $mail->ErrorInfo]);
