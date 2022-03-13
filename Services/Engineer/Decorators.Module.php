<?php

if($_SERVER["REQUEST_METHOD"] == 'POST')
    $_POST = array_merge($_POST, json_decode(file_get_contents('php://input'), TRUE));


if($_SERVER["REQUEST_METHOD"] == 'GET')
    $_GET = array_merge($_GET, json_decode(file_get_contents('php://input'), TRUE));