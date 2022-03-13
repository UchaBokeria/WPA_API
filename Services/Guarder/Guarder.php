<?php

class Guard extends Database
{

    public function checkToken()
    {

        if($_POST["token"] == "") 
            return [ 'error' => true , 'msg' => 'Empty Token' ];

        $UserInfo = parent::GET(" SELECT id FROM users WHERE token = :token ;", [ 'token' => $_POST["token"] ]);
        $Result = parent::Exists();

        
        return [ 'error' => !$Result , 'msg' => ($Result) ? 'Access granted' : 'Wrong Token' ];

    }

}
