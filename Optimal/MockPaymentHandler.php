<?php
namespace Optimal;
use Utils;
class MockPaymentHandler extends PaymentHandler {

  public $response=false; //allows to hardcode a desired response
  public $amount_override=false;
  
  function doCreditCardTransaction(){
    if($this->response!== false){
      $this->service_response = $this->response;
      Utils::log(__METHOD__ . "local request: \n" . $this->createRequest()->getXml());
      Utils::log(__METHOD__ . "parsing HARDCODED response: \n" . $this->response);
      $this->processResponse();
      return;
    }
    
    parent::doCreditCardTransaction();
  }
  
  function findAmount(){
    if($this->amount_override!==false){
      $this->amount = $this->amount_override;
      return;  
    }
    
    parent::findAmount();
  }
  

}