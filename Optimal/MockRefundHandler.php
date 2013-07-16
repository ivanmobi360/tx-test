<?php
namespace Optimal;
use Utils;
class MockRefundHandler extends RefundHandler {

  public $response=false; //allows to hardcode a desired response
  public $amount_override=false;
  
  function doCreditCardRefund(){
    if($this->response!== false){
      $this->service_response = $this->response;
      Utils::log(__METHOD__ . "local request: \n" . $this->createRequest()->getXml());
      Utils::log(__METHOD__ . "parsing HARDCODED response: \n" . $this->response);
      $resp = $this->processResponse();
      
      
        $failed = $resp['failed'];
        if ($failed){
          throw new \Exception($resp['response']->decision);
        }else{
          //log success
          $this->logSuccess($resp['response']);
          $this->completeTransaction();
        }
      
      
      
      return;
    }
    
    throw new \Exception( __METHOD__ . " not implemented");
    
    //parent::doCreditCardTransaction();
  }
  
  function findAmount(){
    if($this->amount_override!==false){
      $this->amount = $this->amount_override;
      return;  
    }
    
    parent::findAmount();
  }
  

}