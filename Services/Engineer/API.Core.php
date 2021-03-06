<?php

require "./Services/Guarder/Guarder.php";
require "./Services/Logging/IpDetector.php";
require "./Services/AutoLoader/AutoLoader.php";
require "./Services/Engineer/Decorators.Module.php";
require "./Services/URLRequest/URLRequest.Module.php";

$Router = $CALL[COUNT($CALL)-1];
$Request = $CALL[COUNT($CALL)];

define('GUARDIAN', (new Guard())->checkToken());
echo json_encode( ( new $Router() )->$Request() );