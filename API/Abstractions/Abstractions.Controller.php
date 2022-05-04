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

            $result = parent::GET("  SELECT  email,
                                            salutation,
                                            CONCAT(firstname, ' ' , middlename , ' ' , lastname) AS fullname

                                    FROM    users 
                                    WHERE   token = :token ; ", 
                                    [ 'token' => $_POST["token"] ]
                                );

            if(!parent::Exists()) return [ 'error' => true, 'msg'=> 'Token is Wrong' ];

            $_POST["mainEmail"] = $result[0]["email"];
            $_POST["fullname"] = $result[0]["fullname"];
            $_POST["salutation"] = $result[0]["salutation"];

            parent::SET("   INSERT INTO Abstractions SET    title = :title,
                                                            topics = :topics,
                                                            preference = :preference,
                                                            introduction = :introduction,
                                                            objectives = :objectives,
                                                            methods = :methods,
                                                            results = :results,
                                                            conclusion = :conclusion,
                                                            abstract_file = :abstract_file,
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
                                                        'conclusion' => $_POST["conclusion"],
                                                        'abstract_file' => $_POST["abstract_file"],
                                                        'eposter_file' => $_POST["eposter_file"],
                                                        'eposter_audio' => $_POST["eposter_audio"]
                                                    ]
                                                );
            $Abstraction_id = parent::GetLastId();
            $index = 1;

            $authors = "";
            $keywords = "";
            $affiliations = "";

            foreach ($_POST['affiliations'] as $key => $value) {
                $affiliations .= "  <tr>
                                            <td>Affiliation" . $value["affiliation_number"] . "</td>
                                            <td>" . $value["institute_company"] . "</td>
                                            <td>" . $value["city"] . "</td>
                                            <td>" . $value["country"] . "</td>
                                        </tr>";
                        
                parent::SET("   INSERT INTO Abstractions_affiliations SET   abstractions_id = :abstractions_id, 
                                                                            institute_company = :institute_company,
                                                                            affiliation_number = :affiliation_number,
                                                                            city = :city,
                                                                            country = :country; ", 
                                                                        [
                                                                            'abstractions_id' => $Abstraction_id,
                                                                            'affiliation_number' => $value["affiliation_number"],
                                                                            'institute_company' => $value["institute_company"],
                                                                            'city' => $value["city"],
                                                                            'country' => $value["country"],
                                                                        ]);

            }

            foreach ($_POST['authors'] as $value) {
                
                $authors .= "   <tr>
                                    <td>Author $index</td>
                                    <td>$value[initials]</td>
                                    <td>$value[last_name]</td>
                                    <td>
                                        $value[affiliation_1] 
                                        $value[affiliation_2] 
                                        $value[affiliation_3]
                                    </td>
                                </tr>";
                $index++;

                parent::SET("   INSERT INTO Abstractions_authors SET    abstraction_id = :abstraction_id, 
                                                                        initials = :initials,
                                                                        last_name = :last_name,
                                                                        affiliation_1 = :affiliation_1,
                                                                        affiliation_2 = :affiliation_2,
                                                                        affiliation_3 = :affiliation_3; ", 
                                                                [
                                                                    'abstraction_id' => $Abstraction_id,
                                                                    'initials' => $value["initials"],
                                                                    'last_name' => $value["last_name"],
                                                                    'affiliation_1' => $value["affiliation_1"],
                                                                    'affiliation_2' => $value["affiliation_2"],
                                                                    'affiliation_3' => $value["affiliation_3"],
                                                                ]);
                $abstractions_authors_id = parent::GetLastId();

                
            }

            foreach ($_POST['keywords'] as $value) {
                foreach ($value as $Templateval) 
                    $_POST["keyword_$index"] = $Templateval;
                
                $index++;
                $keywords .= "<p> $value </p>";

                parent::SET("   INSERT INTO Abstractions_keywords SET abstraction_id = :abstraction_id, name = :name; ", 
                                                                [
                                                                    'abstraction_id' => $Abstraction_id,
                                                                    'name' => $value
                                                                ]);
            }

            $_POST["uploadFiles"] = "";

            if($_POST["eposter_file"] != "") {
                $file = explode('/',$_POST["eposter_file"]);
                $_POST["uploadFiles"] .= "  <h2><span>Eposter</span></h2>
                                            <a href='" . "https://wpatbilisicongress.com/Sources/save.php?dlink=" . str_replace("../", "", $_POST["eposter_file"]) . "' target='_blank' download='" . $file[COUNT($file)-1] . "'>
                                                Download
                                            </a>
                                            <br />";
            }

            if($_POST["abstract_file"] != "") {
                $file = explode('/',$_POST["abstract_file"]);
                $_POST["uploadFiles"] .= "  <h2><span>Abstraction</span></h2>
                                            <a href='" . "https://wpatbilisicongress.com/Sources/save.php?dlink=" . str_replace("../", "", $_POST["abstract_file"]) . "' target='_blank' download='" .  $file[COUNT($file)-1] . "'>
                                                download
                                            </a>
                                            <br />";
            }

            if($_POST["eposter_audio"] != "") {
                $file = explode('/',$_POST["eposter_audio"]);
                $_POST["uploadFiles"] .= "  <h2><span>Eposter Audio</span></h2>
                                            <a href='" . "https://wpatbilisicongress.com/Sources/save.php?dlink=" . str_replace("../", "", $_POST["eposter_audio"]) . "' target='_blank' download='" .  $file[COUNT($file)-1] . "'>
                                                Download
                                            </a>
                                            <br />";
            }

            $_POST["authors"] = $authors;
            $_POST["keywords"] = $keywords;
            $_POST["affiliations"] = $affiliations;

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

        public function UploadFile()
        {

            $info = parent::GET(" SELECT id FROM users WHERE token = :token ; ", [ 'token' => $_POST["token"] ]);
            if(!parent::Exists()) return [ 'error' => true, 'msg' => 'Token is Wrong' ];

            $id = $info[0]["id"];
            $file = $_FILES["file"];

            $sep = DIRECTORY_SEPARATOR;
            $directory = "..".$sep."Sources".$sep."Uploads".$sep."" . date('y-m-d');
            
            if(!file_exists($directory)) mkdir($directory);
            if(!file_exists($directory . "".$sep."$id")) mkdir($directory . "".$sep."$id");

            $directory .= "".$sep."$id".$sep."";

            $chmod = "0777";
            chmod($directory, octdec($chmod));
            $fname = explode(".",$file["name"]);
            $uniqueName = $fname[0] . "-" . date('y_m_d-h_m_s') . $id . "." . $fname[1];
            
            $target = $directory . $uniqueName;
            $valid = (new AbstractionsValidation)::CheckFileToUpload();

            if($valid["error"]) 
                return [ 'error' => true, 'msg' => $valid["msg"] ];

            return (!move_uploaded_file($_FILES["file"]["tmp_name"], $target)) ?
                [
                    'error' => true, 
                    'msg' => 'File Upload Has Been Faild. Unknown Error, Please Check Permissions',
                    'directory' => $_FILES["file"]["tmp_name"],
                ] : 
                [
                    'error' => false,
                    'file' => $target,
                    'msg' => 'File Has Been Uploaded Successfully'
                ];
            
        }

    }