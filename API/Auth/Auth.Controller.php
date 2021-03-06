<?php

    class Auth extends Database
    {
   
        public function Login()
        {

            $email = $_POST["email"];
            $Password = $_POST["pass"];

            $User = parent::GET("   SELECT * FROM users 
                                    WHERE  email = :email LIMIT 1", [ 'email' => $email ] );

            $id = $User[0]["id"];
            $HashPassword = $User[0]["password"];

            if(!password_verify($Password, $HashPassword)) 
                return [ 'error' => true, 'code' => 401, 'msg' => 'Provided Password Or email Is Wrong'];

            
            /* Random hex length: 38 */
            $NewToken = bin2hex(openssl_random_pseudo_bytes(16) . date('y_m_d.hms') .  $email);
            $ip = IP_ADDRESS;

            parent::SET("   UPDATE  users SET   logged = 1,
                                                last_ip_address = :ip,
                                                last_login_datetime = NOW(),
                                                token = :token

                            WHERE   id = :id;",
                            [
                                "token" => $NewToken,
                                "ip" => $ip,
                                "id" => $id
                            ]);

            unset($User[0]['logged']);
            unset($User[0]['password']);
            unset($User[0]['last_ip_address']);
            unset($User[0]['last_login_datetime']);

            return [ 'error' => false, 'msg' => $NewToken, 'userData' => $User ];

        }

        public function Logout()
        {

            $info = parent::GET("   SELECT id FROM users WHERE token = :token AND email = :email ; ", 
                        [ 
                            'token' => $_POST["token"],
                            'email' => $_POST["email"]
                        ]);

            if(!parent::Exists())
                return ["error" => true, "msg" => "Provided Token Is Wrong"];

            parent::SET("   UPDATE users SET logged = 0, 
                                             token = '', 
                                             last_ip_address = :ip,
                                             last_login_datetime = NOW() 
                            WHERE id = :id ;",
                            [
                                'ip' => IP_ADDRESS,
                                'id' => $info[0]["id"]
                            ]);

            return ["error" => false, "msg" => "You Has Been Logged Out"];

        }

        public function SignUp()
        {
            
            $matches = parent::GET("SELECT id FROM users WHERE email = :email; ", [ 'email' => $_POST["email"] ]);
            if(COUNT($matches) >= 1) 
                return [ 'error' => true , 'msg' => 'Account With This Email Already exists' ];

            parent::SET(" INSERT INTO users SET firstname = :firstname,
                                                lastname = :lastname,
                                                middlename = :middlename,
                                                phone = :phone,
                                                email = :email,
                                                username = :username,
                                                password = :password,
                                                token = :token,
                                                gender = :gender,
                                                salutation = :salutation,
                                                profession = :profession,
                                                addressType = :addressType,
                                                institution = :institution,
                                                department = :department,
                                                country = :country,
                                                city = :city,
                                                last_ip_address = :last_ip_address,
                                                last_login_datetime = NOW(); ",
                                            [
                                                'firstname' => $_POST["firstname"],
                                                'lastname' => $_POST["lastname"],
                                                'middlename' => $_POST["middlename"],
                                                'phone' => $_POST["phone"],
                                                'email' => $_POST["email"],
                                                'username' => $_POST["username"],
                                                'password' => password_hash( $_POST["password"], PASSWORD_BCRYPT ),
                                                'token' => bin2hex(openssl_random_pseudo_bytes(16) . date('y_m_d.hms') . $_POST["email"]),
                                                'gender' => $_POST["gender"],
                                                'salutation' => $_POST["salutation"],
                                                'profession' => $_POST["profession"],
                                                'addressType' => $_POST["addressType"],
                                                'institution' => $_POST["institution"],
                                                'department' => $_POST["department"],
                                                'country' => $_POST["country"],
                                                'city' => $_POST["city"],
                                                'last_ip_address' => IP_ADDRESS
                                            ]
                                        );

            
            global $SMTPMAILER;

            $Object = [];
            $Object["fullname"] = $_POST["firstname"] . " " . $_POST["middlename"] . " " . $_POST["lastname"]; 
            $Object["salutation"] = $_POST["salutation"];

            $SignUpMail = $SMTPMAILER->Send([
                'address' => $_POST["email"],
                'subject' => "Sign up Confirmation / WPA Thematic Congress Tbilisi 2022",
                'body' => $SMTPMAILER->TemplateBuild($Object, "./Sources/Doc/SignUp.Template.html")
            ]);

            return [ 'error' => $SignUpMail , 'msg' => $SignUpMail["msg"] ];

        }

        public function Reset()
        {

            global $SMTPMAILER;
            $info = parent::GET("SELECT id, CONCAT(firstname,' ',lastname) AS fullname, salutation FROM users WHERE email = :email", [ 'email' => $_POST["email"] ]);

            if(!parent::Exists()) 
                return [ "error" => true, "msg" => "Provided Email Does Not Exist"];

            $ip = IP_ADDRESS;
            $key = bin2hex(openssl_random_pseudo_bytes(16) . date('y_m_d.hms') . openssl_random_pseudo_bytes(16) .  $_POST["email"] . "w3p2a");
            
            $Object = [];
            $Object["resetLink"] = $key;
            $Object["fullname"] = $info[0]["fullname"];
            $Object["salutation"] = $info[0]["salutation"];
            
            $ResetMail = $SMTPMAILER->Send([
                'address' => $_POST["email"],
                'subject' => "Reset your password / WPA Thematic Congress Tbilisi 2022",
                'body' => $SMTPMAILER->TemplateBuild($Object, "./Sources/Doc/Reset.Template.html")
            ]);
            
            parent::SET("   UPDATE users SET    reset_key = :key,
                                                last_ip_address = :ip,
                                                last_login_datetime = NOW(),
                                                reset_pendding = 1
                            WHERE id = :id ; ", 
                            [ 
                                'key' => $key,
                                'ip' => $ip,
                                'id' => $info[0]["id"]
                            ]);
            
            return [ "error" => $ResetMail["error"], "msg" => $ResetMail["msg"] ];

        }

        public function ResetPassword()
        {
            
            $info = parent::GET("SELECT id FROM users WHERE reset_key = :key", [ 'key' => $_POST["key"] ]);
            $ip = IP_ADDRESS;

            if(!parent::Exists()) 
                return [ "error" => true, "msg" => "Provided Reset Token Is Wrong "];

            parent::SET("   UPDATE users SET    password = :password,
                                                token = :token,
                                                last_ip_address = :ip,
                                                last_login_datetime = NOW(),
                                                reset_pendding = 0,
                                                logged = 0,
                                                reset_key = ''
                            WHERE id = :id ; ", 
                            [ 
                                'password' => password_hash($_POST["password"], PASSWORD_BCRYPT ),
                                'ip' => $ip,
                                'token' => bin2hex(openssl_random_pseudo_bytes(16) . date('y_m_d.hms') . "sad4312sa%$13"),
                                'id' => $info[0]["id"]
                            ]);

            return [ "error" => false, "msg" => 'Password Has Been Reseted' ];
                                            
        }

    }