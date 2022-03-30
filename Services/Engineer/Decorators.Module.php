<?php
$raw = json_decode(file_get_contents('php://input'), TRUE);

if($raw != null) {
    if($_SERVER["REQUEST_METHOD"] == 'POST')
        $_POST = array_merge($_POST, $raw);
    
    
    if($_SERVER["REQUEST_METHOD"] == 'GET')
        $_GET = array_merge($_GET, $raw);
}