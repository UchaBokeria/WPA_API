<?php

    class Contact extends Database
    {
   
        public function SendMessage()
        {

            global $SMTPMAILER;

            $email = $_POST["email"];
            $firstname = $_POST["firstname"];
            $lastname = $_POST["lastname"];
            $subject = $_POST["subject"];
            $body = $_POST["body"];
            $ip = IP_ADDRESS;

            $Contact = $SMTPMAILER->Send([
                'address' => "wpatbilisicongress@gmail.com",
                'subject' => "Mail From Site By: " . 
                            $_POST["firstname"] . $_POST["lastname"] . 
                            $_POST["email"] . ", About" . $_POST["subject"],
                'body' => $_POST["body"]
            ]);
            
            if($Contact["error"]) 
                return [ 'error' => true, "msg" => $Contact["msg"]];
                

            parent::SET("   INSERT INTO  mail SET   sender = :sender,
                                                    firstname = :firstname,
                                                    lastname = :lastname,
                                                    subject = :subject,
                                                    body = :body,
                                                    ip = :ip ; ",
                            [
                                "sender" => $_POST["email"],
                                "firstname" => $_POST["firstname"],
                                "lastname" => $_POST["lastname"],
                                "subject" => $_POST["subject"],
                                "body" => $_POST["body"],
                                "ip" => $ip,
                            ]);

            return [ 'error' => $Contact["error"], 'msg' => $Contact["msg"] ];

        }


    }