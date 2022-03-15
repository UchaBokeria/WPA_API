<?php 

    class Simposyums extends Database
    {
         
        public function Read()
        {
            return ['commingsoon' => true];
        }

        public function Create()
        {
            
            if(GUARDIAN['error']) return GUARDIAN;

            parent::SET("   INSERT INTO Simposyums SET  fullname = :fullname,
                                                        email = :email,
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
                                                        'email' => $_POST["email"],
                                                        'text' => $_POST["text"],
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

            foreach ($_POST['presentator'] as $key => $value) {
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
            
            /* Send Symposyum To Mail */
            SMTPMAILER->Options =[
                'address' => 'ucha1bokeria@gmail.com',
                'subject' => 'TEST subject',
                'body' => 'TEST body' ];
                
            $Response = SMTPMAILER->Send();

            return [ 'error' => !$Response["error"] , 'msg' => 'Simposyums Has Been Created And ' . $Response["msg"] ];

        }

        public function Update()
        {
            return ['commingsoon' => true];
        }

        public function Delete()
        {
            return ['commingsoon' => true];
        }
        
    }