<?php

class Guard extends Database
{

    public function checkToken()
    {

        if($_SERVER["HTTP_TOKEN"]== "") $Result = false;
        else {
            $UserInfo = parent::GET(" SELECT id FROM users WHERE token = :token ;", [ 'token' => $_SERVER["HTTP_TOKEN"] ]);
            $Result = parent::Exists();
        }
        
        return [ 'error' => !$Result , 'msg' => ($Result) ? 'Access granted' : 'Wrong Token' ];

    }

}
