<?php

spl_autoload_register( function($Controller) {
    $Path = str_replace(DIRECTORY_SEPARATOR , '/', CONTROLLERS_SCHEME[$Controller]);
    if(!file_exists(".$Path")) echo die(" >> `$Controller` In `$Path` Does Not Exist");
    else include_once ".$Path";
});