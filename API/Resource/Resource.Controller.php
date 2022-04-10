<?php

    class Auth extends Database
    {
   
        public function Login()
        {
            $path = $_POST["path"];
            header('Location: $path');
        }

       

    }