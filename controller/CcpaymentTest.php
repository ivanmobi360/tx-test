<?php
namespace controller;
use Utils;
abstract class CcpaymentTest extends \DatabaseBaseTest{
  
  //works, but slow
  public function xtestCreditCardPayment(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Quebec CES' , $seller->id, $this->createLocation()->id, date('Y-m-d', strtotime("+1 day"))  /* 1, '2012-01-01', '9:00', '2014-01-10', '18:00' */);
    $this->setEventId($evt, 'aaa');
    $cat = $this->createCategory('Verde', $evt->id, 10.00);
    
    
    
    //Transaction setup
    $foo = $this->createUser('foo');
    
    //let's buy
    $buyer = new \WebUser($this->db);
    $buyer->login($foo->username);
    $buyer->addToCart($cat->id, 3); //cart in session
    //$buyer->placeOrder()
    
    //let's pay
    Utils::clearLog();
    
    $_POST = $this->getCCPurchaseData();
    
    $page = new Ccpayment();
    
    $this->assertRows(1, 'processor_transactions');
    
  }
 
}

