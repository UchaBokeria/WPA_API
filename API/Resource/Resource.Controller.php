<?php

    class Resource extends Database
    {
   
        public function GetFile()
        {
            $path = $_GET["path"];
            header("Location: $path");
        }

       

    }