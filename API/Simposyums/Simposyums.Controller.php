<?php 

    class Simposyums extends Database
    {
         
        public function Read()
        {
            return ['commingsoon' => true];
        }

        public function Create()
        {

            parent::SET("   INSERT INTO Simposyums SET  fullname = :fullname,
                                                        email = :email,
                                                        title = :title,
                                                        text = :text,
                                                        chair_name = :chair_name,
                                                        chair_country = :chair_country,
                                                        cochair_name = :cochair_name,
                                                        cochair_country = :cochair_country,
                                                        created_datetime = NOW(); ",
                                                    [
                                                        'fullname' => $_POST["fullname"],
                                                        'email' => $_POST["email"],
                                                        'text' => $_POST["text"],
                                                        'title' => $_POST["title"],
                                                        'chair_name' => $_POST["chair_name"],
                                                        'chair_country' => $_POST["chair_country"],
                                                        'cochair_name' => $_POST["cochair_name"],
                                                        'cochair_country' => $_POST["cochair_country"]
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

            return [ 'error' => false , 'msg' => 'Simposyums Has Been Created' ];

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