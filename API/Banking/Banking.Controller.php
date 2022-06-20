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


    private $production = false;

    public function __construct()
    {

        parent::__construct();
        $this->CURL = curl_init();
        $this->TBC = new URLRequest();
        $this->url = $this->baseURL."/".$this->version."/".$this->testURL."/";

        if ($this->production) {
            $this->url = $this->baseURL;
        }

    }


    public function Read()
    {
        return ['commingsoon' => true];

    }

    public function GetToken() 
    {


        curl_setopt_array($this->CURL, array(
          CURLOPT_URL => '$this->baseURL/access-token',
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
        ));
        
        $response = curl_exec($this->CURL);
        $this->TOKEN = json_decode($response,true)["access_token"];
        return $this->TOKEN;

    }

    public function Create()
    {

        if (GUARDIAN['error']) return GUARDIAN;

        $this->GetToken();

        return $this->TBC->request([
          'Body' => '',
          'Method' => 'POST',
          'URL' => $this->URL . "payments",
          'postFields' => '{
            "amount": {
                "currency":"GEL",
                "total": 200,
                "subTotal": 0,
                "tax": 0,
                "shipping": 0
            },
            "returnurl":"shopping.ge/callback",
            "extra":"GE60TB7226145063300008",
            "userIpAddress" : "127.0.0.1",
            "expirationMinutes" : "5",
            "methods" : [5, 7, 8],
            "installmentProducts":
            [
               {"Name":"t1","Price":100,"Quantity":1},
               {"Name":"t1","Price":50,"Quantity":1},
               {"Name":"t1","Price":50,"Quantity":1}
            ],
            "callbackUrl":"https://google.com", 
            "preAuth":true,
            "language":"EN",
            "merchantPaymentId": "P123123",
            "saveCard": true,
            "saveCardToDate": "0321"
        }
        ',
          'Headers' => [ 
            'Content-Type: application/json',
            'apikey: ' . $this->APIKEY,
            'Authorization: Bearer ' . $this->TOKEN,
          ],
          'return' => 'decode',
        ])["access_token"];
        
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
