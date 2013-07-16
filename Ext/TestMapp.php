<?php
/**
  * @author Ivan Rodriguez
 */
class Ext_TestMapp extends Ext_TestApp{
  protected $buyerid;
  
  //state
  protected $state;
  
  //protected $merchantid=false;
  function __construct($appid, $buyerid){
    parent::__construct($appid);
    $this->buyerid = $buyerid;
    $this->state = new KeepAliveState();
  }
  
  function getUserId() {
    return $this->buyerid;
  }
  
  /**
   * Mobile app may or may not care to specify a merchant
   * @param unknown_type $merchantid
   */
  public function getBalance($merchantid=false){
    return Ext_Ewallet::getBalance($this->getUserId(), $this->appid, $merchantid );
  }
  
  /**
   * @return int $pending_id refundable id
   */
  public function payRequest($pending_id){
    //buyer pays
    $this->quickCommand('acceptrequest', array(
    														'pendingid' => $pending_id
                              , 'userid' => $this->buyerid
                              , 'appid' => $this->appid
                              ));
    //It doesn't make much sense generate this from the mobile side, but the command is stored here.
    return $this->lastCommand->getRefundableId();
  }
  
  /**
   * Mobile may specify merchant for some requests
   */
  /*public function setMerchantId($merchantid){
    $this->merchantid = $merchantid;
  }*/
  
  /**
   * @deprecated
   */
  public function buyCard($catalog_cardid){
    throw new Exception(__METHOD__ . " deprecated for dpm");
    $this->quickCommand('buycard', array(
                                
                                'id' => $catalog_cardid
                              , 'userid' => $this->buyerid
                              , 'appid' => $this->appid
                              ));
    return $this->lastCommand->getInsertedId();
  }
  
  
  public function depositCard($my_cardid){
    $this->quickCommand('depositcard', array(
                                'id' => $my_cardid
                              , 'userid' => $this->buyerid
                              , 'appid' => $this->appid
                              ));
  }
  
  /**
   * @deprecated
   */
  public function sendCard($my_cardid, $to){
    throw new Exception(__METHOD__ . " deprecated for dpm");
    $this->quickCommand('sendcard', array(
                                'id' => $my_cardid
                              , 'nickname' => $to
                              , 'userid' => $this->buyerid
                              , 'appid' => $this->appid
                              ));
  }
  
  public function buyAndSendCard($catalog_cardid, $to){
    $this->quickCommand('buyandsendcard', array(
                                
                                'id' => $catalog_cardid
                              , 'userid' => $this->buyerid
                              , 'nickname' => $to
                              , 'appid' => $this->appid
                              ));
    return $this->lastCommand->getReceivedCardId();                          
  }
  
  public function keepAlive(){
    
    $this->quickCommand('keepalive', array(
                                'type' => Ext_AppType::MOBILE_APP
                              , 'userid' => $this->buyerid
                              , 'appid' => $this->appid
                              ));
    //do some parsing
    $xml = '<DOC>'. $this->lastCommand->getResponse(). '</DOC>';
    $this->state->setFromXml($xml);
  }
  
  public function getKeepAliveBalance(){
    return $this->ka_balance;
  }
  
  public function getState(){
    return $this->state;
  }
  

  


}

class KeepAliveState{
  public $ewaletlastid=0, $ewalletlasttype=0, $ewalletbalance=0, $cardlistid=0, $pendinglastid=0, $pendingname=0, $pendingamount=0;
  public function setFromXml($xml){
    $xml = simplexml_load_string($xml);
    foreach( array('ewalletlastid', 'ewalletlasttype', 'ewalletbalance', 'cardlistid', 'pendinglastid', 'pendingname', 'pendingamount') as $key){
      $ckey = strtoupper($key);
      $this->$key = (float) $xml->$ckey;
    }
  }
}