<?php

class Guard extends Database
{

    public function checkToken()
    {

        if($_SERVER["token"] == "") $Result = false;
        else {
            $UserInfo = parent::GET(" SELECT id FROM users WHERE token = :token ;", [ 'token' => $_SERVER["token"] ]);
            $Result = parent::Exists();
        }
        
        return [ 'error' => $Result , '' => ($Result) ? 'Access granted' : 'Wrong Token' ];

    }

}
