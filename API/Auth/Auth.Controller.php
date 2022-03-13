<?php

    class Auth extends Database
    {
   
        public function Login()
        {

            $email = $_POST["email"];
            $Password = $_POST["pass"];

            $User = parent::GET("   SELECT password, id FROM users 
                                    WHERE  email = :email", [ 'email' => $email ] );

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
            
            return [ 'error' => false, 'msg' => $NewToken ];

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
                                                last_login_datetime = NOW(); 
                                                ",
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

            return [ 'error' => false , 'msg' => 'Account Has Been Created' ];
            
        }

    }