<?php

    class Resource extends Database
    {
   
        public function GetFile()
        {
            $path = $_POST["path"];
            header("Location: $path");
        }

       

    }