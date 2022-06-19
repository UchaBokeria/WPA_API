<?php

/**
 * @author Ucha
 * @version 1.1.0
 * @package Curl
 */

// (parent::__construct())->request([
//   'Body' => '',
//   'Method' => 'GET',
//   'URL' => $this->URL . "/$ID",
//   'Headers' => [ 
//       'Authorization:  Bearer ' . $this->Token,
//       'Content-Type: application/json'
//   ],
//   'return' => 'decode',
// ]);



class URLRequest {

  public function __construct() {}

  /**
   * CONSTRUCT REQUEST
   * Parameters:
   *    returnData Set False to return Data From Request    
   *    RawOptions  Array Of Options For Request 
   *    Method Request Type (GET,POST,PUT,DELETE)     
   *    Authorization Authorization As array    
   *    Headers Headers AS array  
   *    Body Body Parameters As array     
   *    URL Request URL     
   */


  public $Curl;
  public $error;
  public $response;

  public $Options;
  public $RawOptions;


  public function initialize()
  {
    $this->Curl = curl_init();
  }

  public function request( $RawOptions = null )
  {

    $this->RawOptions = $RawOptions;

    $this->Options = [
      'Method' => 'GET',
      'returnData' => true,
      'Headers' => ['Content-Type: application/json'],
      'Body' => [],
      'URL' => '',
      'return' => 'encode'
    ];

    foreach($this->RawOptions AS $key => $value) 
      $this->Options[$key] = ($RawOptions[$key] == '' || $RawOptions[$key] == null) ? $this->Options[$key] : $RawOptions[$key];
        $this->initialize();

    $this->setParameters();

    $this->response = curl_exec($this->Curl);
    
    if(curl_errno($this->Curl)){
      $this->error = curl_error($this->Curl);
      new Exception($this->error);
    }

    $this->close();

    if($this->Options["return"] == "decode")
      return json_decode($this->response,true);

    return $this->response;

  }

  public function getError()
  {
    return $this->error;
  }

  public function close() 
  {
    curl_close($this->Curl);
  }

  public function setParameters() 
  {
    
    // METHOD TYPE
    curl_setopt($this->Curl, CURLOPT_URL, $this->Options["URL"]);

    // REQUEST DATA
    curl_setopt($this->Curl, CURLOPT_POSTFIELDS, $this->Options["Body"]);

    // REQUEST CONTENT TYPE
    curl_setopt($this->Curl, CURLOPT_HTTPHEADER, $this->Options["Headers"]);

    // METHOD TYPE
    curl_setopt($this->Curl, CURLOPT_CUSTOMREQUEST, $this->Options["Method"]);

    // IF REQUEST HAVE A RESPONSE
    curl_setopt($this->Curl, CURLOPT_RETURNTRANSFER, $this->Options["returnData"]);

  }

}