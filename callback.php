<?php

$content = [];

$raw = json_decode(file_get_contents('php://input'), TRUE);
$content = array_merge($raw, $_REQUEST);

$fname = date('y-m-d');
chmod("./Logs",0777);

file_put_contents("./Logs/$fname",$content,FILE_APPEND);

die();