<?php
/**
  * @author Ivan Rodriguez
  */
abstract class Ext_TestApp {
  protected $appid;
  /** @var Ext_Actions_Action */
  protected $lastCommand;
  function __construct($appid){
    $this->appid = $appid;
  }
  
  public function quickCommand($action, $params){
    Utils::log( "#####> " . __METHOD__ . " action: $action, userid: {$this->getUserId()}");
    $cmd = false;
    $cmd = Ext_Main::stringToAction($action);
    $cmd->setData($params)  ;
    $cmd->execute();
    if (!$cmd->success()){
      throw new Exception($cmd->getErrorMsg() );
      }
    $this->lastCommand = $cmd;
  }
  
  /*public function getBalance($merchantid=false){
    return Ext_Ewallet::getBalance($this->getUserId(), $this->appid, $merchantid );
  }*/
  
  abstract function getUserId();
  
  public function getLastResponse(){
    return '<MIOAPP>' . $this->lastCommand->getResponse()  . '</MIOAPP>';
  }
  
  public function getLastCommand(){
    return $this->lastCommand;
  }
  
}