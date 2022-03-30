<?php

global $Controllers;
$Controllers = [];

define('ABSOLUTEPATH', str_replace( '\Services\Engineer', '' , __DIR__ ));
define('URI', str_replace('WPA/', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));

$CALL = explode("/", URI);
$empty = array_count_values($CALL)[""];

for ($i=0; $i < $empty; $i++) 
    unset($CALL[array_search("",$CALL)]);


function LearnControllers($dir){
    global $Controllers;
    $DirMembers = scandir($dir);

    unset($DirMembers[array_search('.', $DirMembers, true)]);
    unset($DirMembers[array_search('..', $DirMembers, true)]);
    if (count($DirMembers) < 1) return;

    foreach($DirMembers as $ff){
        if(is_dir($dir.'/'.$ff)) LearnControllers($dir.'/'.$ff);

        else if(strpos($ff,'.Controller.php')) {
            $name = str_replace('.Controller.php', '', $ff);

            $Controllers[$name] = str_replace( ABSOLUTEPATH, '' , str_replace(
                '/home/u609332810/domains/wpatbilisicongress.com/public_html/Server','',
                realpath($dir.DIRECTORY_SEPARATOR.$ff)) 
            );
        }
    }

}

LearnControllers('API');
define('CONTROLLERS_SCHEME', $Controllers);