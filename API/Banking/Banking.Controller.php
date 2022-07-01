<?php

class Banking extends Database
{

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

    public function __construct()
    {
        parent::__construct();
        $this->url = $this->baseURL."/".$this->version."/".$this->testURL."";
    }

    public function GetToken() 
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "$this->url/access-token", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "client_Id=$this->clientID&client_secret=$this->SECRET",
            CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded','apikey: '.$this->APIKEY),
        ]);
        
        $this->TOKEN = json_decode(curl_exec($curl),true)["access_token"];
        return $this->TOKEN;
    }

    public function CheckPayment()
    {
        if (GUARDIAN['error']) return GUARDIAN;
        $user_id = parent::GET(" SELECT id FROM users WHERE token = :token ; ", [ 'token' => $_POST["token"] ])[0]["id"];
        $payment = parent::GET(" SELECT payId FROM payments WHERE user_id = :user_id ORDER BY id DESC LIMIT 1;", [ "user_id" => $user_id ]);

        if(!parent::Exists())
            return ['error' => true, 'msg' => 'no info on payment_id'];
        
        $this->GetToken();
        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => 'https://api.tbcbank.ge/v1/tpay/payments/' . $payment[0]["payId"],
          CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '', CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded','apikey: ' . $this->APIKEY,'Authorization: Bearer ' . $this->TOKEN],
        ]);
        
        $res = json_decode(curl_exec($curl), true);
        curl_close($curl);

        return ['error' => ($res["status"] == "Failed"), 'msg' => $res["status"]];
    }

    public function Create()
    {
        if (GUARDIAN['error']) return GUARDIAN;
        $this->GetToken();
        $curl = curl_init();
        $id = $_POST["product_id"];
        $Product = parent::GET(" SELECT * FROM product WHERE id = 7; ", ['id'=>$id] )[0];
        
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.tbcbank.ge/v1/tpay/payments',
            CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'
                {
                    "amount": {
                        "currency": "EUR",
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
                }', 
            CURLOPT_HTTPHEADER => [ 'Content-Type: application/json', 'apikey: '.$this->APIKEY, 'Authorization: Bearer '. $this->TOKEN ],
        ]);

        $jsonResult = curl_exec($curl);
        $Result = json_decode($jsonResult,true);
        file_put_contents('./Sources/Logs/'. date('y-m-d'), "$jsonResult," ,FILE_APPEND);
        
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
}
