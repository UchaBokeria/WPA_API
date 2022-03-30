<?php 

    class Abstractions extends Database
    {
         
        public function Read()
        {
            return ['commingsoon' => true];
        }

        public function Create()
        {
            
            if(GUARDIAN['error']) return GUARDIAN;

            $email = parent::GET("SELECT email FROM users WHERE token = :token ; ", [ 'token' => $_POST["token"] ]);
            if(!parent::Exists()) return [ 'error' => true, 'msg'=> 'Token is Wrong' ];

            $_POST["mainEmail"] = $email[0]["email"];

            parent::SET("   INSERT INTO Abstractions SET    title = :title,
                                                            topics = :topics,
                                                            preference = :preference,
                                                            introduction = :introduction,
                                                            objectives = :objectives,
                                                            methods = :methods,
                                                            results = :results,
                                                            conclution = :conclution,
                                                            abstract_file = :abstract_file,
                                                            type = :type,
                                                            eposter_file = :eposter_file,
                                                            eposter_audio = :eposter_audio,
                                                            createdAt = NOW(); ",
                                                    [
                                                        'title' => $_POST["title"],
                                                        'topics' => $_POST["topics"],
                                                        'preference' => $_POST["preference"],
                                                        'introduction' => $_POST["introduction"],
                                                        'objectives' => $_POST["objectives"],
                                                        'methods' => $_POST["methods"],
                                                        'results' => $_POST["results"],
                                                        'conclution' => $_POST["conclution"],
                                                        'abstract_file' => $_POST["abstract_file"],
                                                        'type' => $_POST["type"],
                                                        'eposter_file' => $_POST["eposter_file"],
                                                        'eposter_audio' => $_POST["eposter_audio"]
                                                    ]
                                                );
            $Abstraction_id = parent::GetLastId();

            $index = 1;
            foreach ($_POST['authors'] as $value) {
                foreach ($value as $key => $Templateval) 
                    $_POST["author_$key"."_"."$index"] = $Templateval;
                
                $index++;

                parent::SET("   INSERT INTO Abstraction_authors SET abstraction_id = :abstraction_id, name = :name; ", 
                                                                [
                                                                    'abstraction_id' => $Abstraction_id,
                                                                    'name' => $value["name"]
                                                                ]);
            }
            foreach ($_POST['keywords'] as $value) {
                foreach ($value as $key => $Templateval) 
                    $_POST["keyword_$key"."_"."$index"] = $Templateval;
                
                $index++;

                parent::SET("   INSERT INTO Abstraction_keywords SET abstraction_id = :abstraction_id, name = :name; ", 
                                                                [
                                                                    'abstraction_id' => $Abstraction_id,
                                                                    'name' => $value["name"]
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
                'subject' => "Proposal Abstraction Confirmation / WPA Thematic Congress Tbilisi 2022",
                'body' => $SMTPMAILER->TemplateBuild($_POST, "./Sources/Doc/Abstractions.Template.html")
            ]);

            $AdminResponse = $SMTPMAILER->Send([
                'address' => 'wpatbilisicongress@gmail.com',
                'subject' => "Abstraction  By: " .  $_POST["mainEmail"],
                'body' => $SMTPMAILER->TemplateBuild($_POST, "./Sources/Doc/Abstractions.Template.html")
            ]);

            return [
                'error' => ($CustomerResponse["error"] || $AdminResponse["error"]) , 
                'msg' => "  Abstraction  Has Been Created. " . 
                            $CustomerResponse["msg"] . " To The Customer, " . 
                            $AdminResponse["msg"] . " To The Administrator "
            ];

        }
    }