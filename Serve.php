<?php
header("./testmail.php");
/* Configs */
require "./Services/Engineer/LearnConfigs.Scheme.php";
require "./Services/Engineer/LearnControllers.Scheme.php";

/* Services */
require "./Services/Database/Database.php";
require "./Services/Mailer/SmtpMailer.php";
require "./Services/Engineer/API.Core.php";