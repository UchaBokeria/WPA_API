<?php 

    class Simposyums extends Database
    {
         
        public function FixUnsends()
        {
            
            $Result = [];

            $Reserve = parent::GET("SELECT  salutation AS salutation,
                                            fullname AS fullname,
                                            fullname AS user,
                                            Simposyums.email AS email,
                                            Simposyums.email AS mainEmail,
                                            Simposyums.title AS title,
                                            text AS description,
                                            chair_name AS chair_name,
                                            chair_country AS chair_country,
                                            chair_email AS chair_email,
                                            cochair_name AS cochair_name,
                                            cochair_country AS cochair_country,
                                            cochair_email AS cochair_email,
                                            CONCAT('[',
                                                GROUP_CONCAT(JSON_OBJECT(
                                                    'title',   Simposyum_presentators.title,
                                                    'name',    Simposyum_presentators.`name`,
                                                    'email',   Simposyum_presentators.email,
                                                    'country', Simposyum_presentators.country
                                                ))
                                            ,']') AS presentator
                                        
                                    FROM Simposyums
                                    LEFT JOIN users ON users.email = Simposyums.email
                                    LEFT JOIN Simposyum_presentators ON Simposyum_presentators.simposyum_id = Simposyums.id
                                    WHERE Simposyums.id IN( 123,
                                                            124,
                                                            125,
                                                            126,
                                                            132,
                                                            133,
                                                            134,
                                                            136 )
                                    GROUP BY Simposyums.id	; ");

            foreach ($Reserve as $key => $value) {

                $index = 1;
                foreach (json_decode(stripcslashes($value['presentator']),true) as $prese) {
                    foreach ($prese as $key => $Templateval) 
                        $value["presentator_$key"."_"."$index"] = $Templateval;
                    $index++;
                }

                global $SMTPMAILER;
            
                $CustomerResponse = $SMTPMAILER->Send([
                    'address' => $value["mainEmail"],
                    'subject' => "Proposal Submission Confirmation / WPA Thematic Congress Tbilisi 2022",
                    'body' => $SMTPMAILER->TemplateBuild($value, "./Sources/Doc/Simposyums.Template.html")
                ]);

                $AdminResponse = $SMTPMAILER->Send([
                    'address' => 'wpatbilisicongress@gmail.com',
                    'subject' => "Symposium  By: " .  $value["mainEmail"],
                    'body' => $SMTPMAILER->TemplateBuild($value, "./Sources/Doc/Simposyums.Template.html")
                ]);

                array_push($Result, [
                    'params' => $value,
                    'log' => [
                        'CustomerResponse' => $CustomerResponse["error"],
                        'AdminResponse' => $AdminResponse["error"]
                    ],
                    'error' => ($CustomerResponse["error"] || $AdminResponse["error"]) , 
                    'msg' => "  Symposium  Has Been Created. " . 
                                $CustomerResponse["msg"] . " To The Customer, " . 
                                $AdminResponse["msg"] . " To The Administrator "
                ]);

            }
            

            return $Result;

        }

        public function Create()
        {
            
            if(GUARDIAN['error']) return GUARDIAN;

            $email = parent::GET("SELECT id,email FROM users WHERE token = :token ; ", [ 'token' => $_POST["token"] ]);
            if(!parent::Exists()) return [ 'error' => true, 'msg'=> 'Token is Wrong' ];

            $_POST["mainEmail"] = $email[0]["email"];

            parent::SET("   INSERT INTO Simposyums SET  fullname = :fullname,
                                                        email = :email,
                                                        user_id = :user_id,
                                                        title = :title,
                                                        text = :text,
                                                        chair_name = :chair_name,
                                                        chair_country = :chair_country,
                                                        chair_email = :chair_email,
                                                        cochair_name = :cochair_name,
                                                        cochair_country = :cochair_country,
                                                        cochair_email = :cochair_email,
                                                        created_datetime = NOW(); ",
                                                    [
                                                        'fullname' => $_POST["fullname"],
                                                        'user_id' => $email[0]["id"],
                                                        'email' => $_POST["email"],
                                                        'text' => $_POST["description"],
                                                        'title' => $_POST["title"],
                                                        'chair_name' => $_POST["chair_name"],
                                                        'chair_country' => $_POST["chair_country"],
                                                        'chair_email' => $_POST["chair_email"],
                                                        'cochair_name' => $_POST["cochair_name"],
                                                        'cochair_country' => $_POST["cochair_country"],
                                                        'cochair_email' => $_POST["cochair_email"]
                                                    ]
                                                );
            $simposyum_id = parent::GetLastId();

            $index = 1;
            foreach ($_POST['presentator'] as $value) {
                foreach ($value as $key => $Templateval) 
                    $_POST["presentator_$key"."_"."$index"] = $Templateval;
                
                $index++;

                parent::SET("   INSERT INTO Simposyum_presentators SET  simposyum_id = :simposyum_id,
                                                                        title = :title,
                                                                        name = :name,
                                                                        email = :email,
                                                                        country = :country,
                                                                        create_datetime = NOW() ; ", 
                                                                    [
                                                                        'simposyum_id' => $simposyum_id,
                                                                        'title' => $value["title"],
                                                                        'name' => $value["name"],
                                                                        'email' => $value["email"],
                                                                        'country' => $value["country"]
                                                                    ]);
            }

            /* Send Symposyum To The Mail */
            return $Response = $this->SendMail();

        }

        public function Update()
        {
            return ['commingsoon' => true];
        }

        public function Delete()
        {
            return ['commingsoon' => true];
        }
        
        private function SendMail()
        {

            global $SMTPMAILER;
            
            $CustomerResponse = $SMTPMAILER->Send([
                'address' => $_POST["mainEmail"],
                'subject' => "Proposal Submission Confirmation / WPA Thematic Congress Tbilisi 2022",
                'body' => $SMTPMAILER->TemplateBuild($_POST, "./Sources/Doc/Simposyums.Template.html")
            ]);

            $AdminResponse = $SMTPMAILER->Send([
                'address' => 'wpatbilisicongress@gmail.com',
                'subject' => "Symposium  By: " .  $_POST["mainEmail"],
                'body' => $SMTPMAILER->TemplateBuild($_POST, "./Sources/Doc/Simposyums.Template.html")
            ]);

            return [
                'error' => ($CustomerResponse["error"] || $AdminResponse["error"]) , 
                'msg' => "  Symposium  Has Been Created. " . 
                            $CustomerResponse["msg"] . " To The Customer, " . 
                            $AdminResponse["msg"] . " To The Administrator "
            ];

        }
    }