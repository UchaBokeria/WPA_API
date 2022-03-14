<?php

global $Configs;
$Configs = [];

define('ABSOLUTEPATH', str_replace( '\Services\Engineer', '' , __DIR__ ));
define('URI', str_replace('WPA/', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));

$CALL = explode("/", URI);
$empty = array_count_values($CALL)[""];

for ($i=0; $i < $empty; $i++) 
    unset($CALL[array_search("",$CALL)]);


function LearnConfigs($dir){
    global $Configs;
    $DirMembers = scandir($dir);

    unset($DirMembers[array_search('.', $DirMembers, true)]);
    unset($DirMembers[array_search('..', $DirMembers, true)]);
    if (count($DirMembers) < 1) return;

    foreach($DirMembers as $ff){
        if(is_dir($dir.'/'.$ff)) LearnConfigs($dir.'/'.$ff);

        else if(strpos($ff,'.Config.php')) {
            $name = str_replace('.Config.php', '', $ff);
            $Configs[$name] = "./Config/$ff";
            include_once $Configs[$name];
        }
    }

}

LearnConfigs('Config');
define('CONFIGS_SCHEME', $Configs);