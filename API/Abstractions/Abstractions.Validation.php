<?php

    class AbstractionsValidation
    {
        
        public static function CheckFileToUpload()
        {

            if(!isset($_POST["rule"]) || !method_exists(new self,$_POST["rule"]) )
                return [ 'error' => true, 'msg' => 'validation rule is not defined' ];

            $rule = $_POST["rule"];
            return self::$rule();

        }

        public static function abstractionFile()
        {
            return true;
        }

        public static function audioFile()
        {
            return true;
        }

        public static function eposterFile()
        {
            return true;
        }


    }