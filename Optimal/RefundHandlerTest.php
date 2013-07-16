<?php
/**
 * I didn't code the RefundHandler so this is just a runner to inspect and generate data for reports - Ivan 
 * @author Ivan
 *
 */
namespace Optimal;
use Utils;

class RefundHandlerTest extends \DatabaseBaseTest{
  
  function getResponse($filename){
    return file_get_contents(__DIR__ . "/responses/" . $filename);
  }
  
  function createInstance($user_id){
    return new MockPaymentHandler($user_id);
  }
  
  function fixture(){
    $this->clearAll();
    
    $date = date('Y-m-d', strtotime("+1 day"));
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Quebec CES' , $seller->id, $this->createLocation()->id, $date );
    $this->setEventId($evt, 'aaa');
    $this->setEventPaymentMethodId($evt, self::OUR_CREDIT_CARD);
    $this->catA = $this->createCategory('Verde', $evt->id, 10.00, 500);
    $this->catB = $this->createCategory('Azul', $evt->id, 10.00, 500);
    
    
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('EMPIRE' , $seller->id, $this->createLocation()->id, $date );
    $this->setEventId($evt, 'bbb');
    $this->setEventPaymentMethodId($evt, self::OUR_CREDIT_CARD);
    $this->catC = $this->createCategory('Jedi', $evt->id, 10.00, 500);
    $this->catD = $this->createCategory('Klingon', $evt->id, 10.00, 500);
    
    
    $this->createUser('foo');
    $this->createUser('bar');
    $this->createUser('baz');

    //let's pay
    Utils::clearLog();
  }
  
  function testSingle(){
    $this->fixture();
    
    $txn_id = $this->buyTicketsWithCC('foo', $this->catA->id, 3);
    Utils::clearLog();
    $this->doRefund('foo', $txn_id);
  }
  
  //it works, it is just too long, use it to generate report data
  function xtestReportData(){
    
    $this->fixture();
    
    $txn_id = $this->buyTicketsWithCC('foo', $this->catA->id, 3);
    $this->doRefund(/*'seller'*/'foo', $txn_id); //apparently now it takes the buyer userid lol
    
    $users = array('foo', 'bar', 'baz');
    $cats1 = array(1, 2);
    $cats2 = array(3, 4);
    
    $this->db->beginTransaction();
    
    for ($i= 1; $i <=30; $i++){
      $user = $users[array_rand($users)];
      $txn_id = $this->buyTicketsWithCC( $user , $cats1[array_rand($cats1)], rand(1, 5));
      $this->doRefund(/*'seller'*/ $user , $txn_id);  
    }
    
    
    for ($i= 1; $i <=35; $i++){
      $user = $users[array_rand($users)];
      $txn_id = $this->buyTicketsWithCC($user, $cats2[array_rand($cats2)], rand(1, 5) );
      $this->doRefund(/*'seller2'*/$user, $txn_id);  
    }
    
    $this->db->commit();
    
  }
  
  

 
}