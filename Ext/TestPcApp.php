<?php
/**
  * @author Ivan Rodriguez
 */
class Ext_TestPcApp extends Ext_TestApp{
  protected $merchantid, $operator, $staff_code=false;
  function __construct($appid, $merchantid, $operator=false){
    parent::__construct($appid);
    $this->merchantid = $merchantid;
    $this->operator = !$operator?$merchantid:$operator;
  }
  
  public function getUserId(){
    return $this->operator;
  }
  
  public function setStaffCode($staff_code){
    $this->staff_code = $staff_code;
  }
  
  public function getStaffCode(){
    return $this->staff_code? $this->staff_code: $this->operator;
  }
  
  public function getBalance(){
    //Even if staffer, api must always determine that operator is staffer, and retrieve the merchantid
    return Ext_Ewallet::getBalance($this->merchantid, $this->appid, $this->merchantid );
  }
  
  
  public function loadToBuyer($buyer_nick, $amount){
    $this->quickCommand('load', array( 
                            'userid' => $this->operator //merchant logs in
                          , 'amount' => $amount
                          , 'code' => $buyer_nick //nickname or phonetr
                          , 't_type' => 'request' //horrible way to tell a load
                          , 'appid' => $this->appid
                          , 'merchantid' => $this->merchantid
                          , 'staff_code' => $this->getStaffCode()
                          )); //this horrible mix does a load?
    
   return $this->lastCommand->getGiftedCardId(); //a load generates a giftcardowned row for the buyer under some rule                       
  }
  
  public function requestMoney($to, $amount){
    $this->quickCommand('payment', array(
                                    'userid' => $this->operator
                                  , 'code' => $to //nickname or phonetr
                                  , 'amount' => $amount
                                  , 't_type' => 'request'
                                  , 'appid' => $this->appid
                                  , 'merchantid' => $this->merchantid
                                  , 'staff_code' => $this->getStaffCode()
                                  ));
    return $this->lastCommand->getInsertedPendingSpecificId(); //casted
  }
  
  public function useCard($giftcard_id, $charge_amount){
    //Some logic is done on the pcapp side to calculate the amount parameter
    $giftcard_value = $GLOBALS['db']->get_one("SELECT price FROM giftcard INNER JOIN giftcardowned ON giftcardowned.giftcard_id=giftcard.id AND giftcardowned.id=$giftcard_id; ");
    $amount_due = $charge_amount -  $giftcard_value;
    Utils::log(__METHOD__ . " charge amount: $charge_amount, giftcard value: $giftcard_value, amount due: $amount_due ");
    
    $this->quickCommand('payment', array(
                                    'userid' => $this->operator
                                  //, 'amount' => -1*abs($amount) //wth??
                                  , 'amount' => $amount_due == 0? '0.00': $amount_due
                                  , 'giftcard_id' => $giftcard_id
                                  , 'appid' => $this->appid
                                  , 'merchantid' => $this->merchantid
                                  , 'staff_code' => $this->getStaffCode()
                                  ));
                                  
    //return $this->lastCommand->getInsertedPendingSpecificId(); //casted
  }
  
  public function doRefund($transid, $password_override=false){
    
    $request = array(
                    'userid' => $this->operator
                  , 'transid' => $transid//5 //hard guess based on table layout
                  , 'appid' => $this->appid
                  , 'merchantid' => $this->merchantid
                  //, 'staff_code'=> $this->getStaffCode() //Apparently not sent from pcapp?
                  );
    if ($password_override===true){
      $request['password'] = str_repeat($this->merchantid, 4);
    }              
    else if ($password_override){
      $request['password'] = $password_override;
    }
                  
    $this->quickCommand('refund', $request );
  }
  
  public function generateExcelReport($begin_date=false, $end_date=false){
    $this->quickCommand('exceldailyreport', array(
                                    'userid' => $this->operator
                                  , 'appid' => $this->appid
                                  , 'merchantid' => $this->merchantid)
                        );
  }
  
  public function generateUserBalanceReport(){
    $this->quickCommand('exceldailyreport', array(
                                    'users' => 'blah' //? 
                                  , 'userid' => $this->operator
                                  , 'appid' => $this->appid
                                  , 'merchantid' => $this->merchantid)
                        );
  }
  
  public function setReportRecipients($emails){
    $this->quickCommand('setreportrecipients', array(
                                    'userid' => $this->operator
                                  , 'appid' => $this->appid
                                  , 'merchantid' => $this->merchantid
                                  , 'recipients' => implode(',', $emails)
                                  , 'selected_merchant' => $this->merchantid
                                  )
                        );
    return $this->lastCommand->getTotalInserted(); //casted                    
  }
  
  
  
  public function getSalesMinusRefunds(){
    return $this->getBalance();
  }
  
}