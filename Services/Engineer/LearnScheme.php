<?php

global $Controllers;
$Controllers = [];

define('ABSOLUTEPATH', str_replace( '\Services\Engineer', '' , __DIR__ ));
echo die('ABSOLUTEPATH -> ' . str_replace( '\Services\Engineer', '' , __DIR__ ));
define('URI', str_replace('WPA/', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
$CALL = explode("/", URI);

$empty = array_count_values($CALL)[""];
for ($i=0; $i < $empty; $i++) { 
    unset($CALL[array_search("",$CALL)]);
}

function LearnScheme($dir){
    global $Controllers;
    $DirMembers = scandir($dir);

    unset($DirMembers[array_search('.', $DirMembers, true)]);
    unset($DirMembers[array_search('..', $DirMembers, true)]);
    if (count($DirMembers) < 1) return;

    foreach($DirMembers as $ff){
        if(is_dir($dir.'/'.$ff)) LearnScheme($dir.'/'.$ff);

        else if(strpos($ff,'.Controller.php')) {
            $name = str_replace('.Controller.php', '', $ff);
            $Controllers[$name] = str_replace( ABSOLUTEPATH, '' , realpath($dir.DIRECTORY_SEPARATOR.$ff) );
        }
    }

}

LearnScheme('API');
define('CONTROLLERS_SCHEME', $Controllers);