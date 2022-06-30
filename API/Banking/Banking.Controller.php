<?php

class Banking extends Database
{

    private $TBC;
    private $url;

    private $TOKEN     = '';

    private $baseURL   = 'https://api.tbcbank.ge';
    private $testURL   = 'tpay';
    private $version   = 'v1';

    private $clientID  = '7000753';
    private $SECRET    = 'GYcPcZyGUJKiV9As';

    private $APIKEY    = 'cJDsjKJn4JFs9F0PD7e0ps3XB4YBOeiF';
    private $APPID     = '755084f6-b71a-4964-bd3b-a071a34d498c';
    private $CallBack  = 'https://wpatbilisicongress.com/callback.php';

    private $production = false;

    public function __construct()
    {

        parent::__construct();
        $this->TBC = new URLRequest();

        $this->url = $this->baseURL."/".$this->version."/".$this->testURL."";
        if ($this->production) $this->url = $this->baseURL;
        
    }

    public function GetToken() 
    {

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "$this->url/access-token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "client_Id=$this->clientID&client_secret=$this->SECRET",
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            'apikey: '.$this->APIKEY,
            ),
        ]);
        
        $this->TOKEN = json_decode(curl_exec($curl),true)["access_token"];
        return $this->TOKEN;

    }

    public function CheckPayment()
    {

        if (GUARDIAN['error']) return GUARDIAN;

        $this->GetToken();
        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => 'https://api.tbcbank.ge/v1/tpay/payments/' . $_POST["payment_id"],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'apikey: ' . $this->APIKEY,
            'Authorization: Bearer ' . $this->TOKEN
          ],
        ]);
        
        $response = curl_exec($curl);
        $res = json_decode($response, true);
        curl_close($curl);
        return ['error' => ($res["status"] == "Failed"), 'msg' => $res["status"]];
    }

    public function Create()
    {

        if (GUARDIAN['error']) return GUARDIAN;

        $this->GetToken();
        $curl = curl_init();
        
        $id = $_POST["product_id"];
        $Product = parent::GET("    SELECT * FROM product WHERE id = :id; ", ['id'=>$id] )[0];
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.tbcbank.ge/v1/tpay/payments',
          CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'
            {
                "amount": {
                    "currency": "GEL",
                    "total": ' . $Product["price"] . '
                },
                "installmentProducts": [' . json_encode($Product) . '],
                "preAuth": true,
                "language":"EN",
                "saveCard": false,
                "returnurl": "https://wpatbilisicongress.com/registration/getPaymentResult",
                "callbackUrl": "' . $this->CallBack . '", 
                "expirationMinutes" : "5",
                "userIpAddress" : "127.0.0.1",
                "methods" : [5, 7, 8]
            }
        ', CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'apikey: '.$this->APIKEY,
            'Authorization: Bearer '. $this->TOKEN
          ),
        ));

        $jsonResult = curl_exec($curl);
        $Result = json_decode($jsonResult,true);

        $fname = date('y-m-d');
        file_put_contents('./Sources/Logs/'. $fname, "$jsonResult," ,FILE_APPEND);
        
        parent::SET("INSERT INTO `payments` SET `user_id` = :user_id, 
                                                `datetime` = NOW(), 
                                                `price` = :price, 
                                                `product_id` = :product_id, 
                                                `payId` = :payId, 
                                                `merchantPaymentId` = :merchantPaymentId, 
                                                `status` = :status, 
                                                `currency` = :currency, 
                                                `amount` = :amount, 
                                                `links` = :links,
                                                `transactionId` = :transactionId, 
                                                `preAuth` = :preAuth, 
                                                `recId` = :recId, 
                                                `expirationMinutes` = :expirationMinutes, 
                                                `httpStatusCode` = :httpStatusCode, 
                                                `developerMessage` = :developerMessage, 
                                                `userMessage` = :userMessage, 
                                                `ipAddress` = :ipAddress, 
                                                `rawJson` = :rawJson ; ",
                                            [
                                                "user_id"           => $_SESSION["USERID"],
                                                "price"             => $Product["price"],
                                                "product_id"        => $Product["id"],
                                                "payId"             => $Result["payId"],
                                                "merchantPaymentId" => $Result["merchantPaymentId"],
                                                "status"            => $Result["status"],
                                                "currency"          => $Result["currency"],
                                                "amount"            => $Result["amount"],
                                                "links"             => json_encode($Result["links"]),
                                                "transactionId"     => $Result["transactionId"],
                                                "preAuth"           => $Result["preAuth"],
                                                "recId"             => $Result["recId"],
                                                "expirationMinutes" => $Result["expirationMinutes"],
                                                "httpStatusCode"    => $Result["httpStatusCode"],
                                                "developerMessage"  => $Result["developerMessage"],
                                                "userMessage"       => $Result["userMessage"],
                                                "ipAddress"         => IP_ADDRESS,
                                                "rawJson"           => $jsonResult
                                            ]);
        return ['error' => $Result["httpStatusCode"] == 200 , 'msg' => $Result["links"] ];
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
            'subject' => " Abstract submission confirmation /WPA Thematic Congress Tbilisi 2022",
            'body' => $SMTPMAILER->TemplateBuild($_POST, "./Sources/Doc/Abstractions.Template.html")
        ]);

        $AdminResponse = $SMTPMAILER->Send([
            'address' => 'wpatbilisicongress@gmail.com',
            'subject' => "Abstraction  By: " .  $_POST["mainEmail"],
            'body' => $SMTPMAILER->TemplateBuild($_POST, "./Sources/Doc/Abstractions.Template.html")
        ]);

        return [
            'error' => ($CustomerResponse["error"] || $AdminResponse["error"]),
            'msg' => "  Abstraction  Has Been Created. " .
                $CustomerResponse["msg"] . " To The Customer, " .
                $AdminResponse["msg"] . " To The Administrator "
        ];
    }

}
