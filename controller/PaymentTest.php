<?php
namespace controller;
use Utils;
class PaymentTest extends \DatabaseBaseTest{
  
  
  public function testCreate(){
    
    Utils::log(__METHOD__ . "using db: " . $this->db->getDbName() );
    
    $this->clearAll();
    
    
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Quebec CES' , $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $this->setEventId($evt, 'aaa');
    $cat = $this->createCategory('Verde', $evt->id, 10.00);
    
    
    
    //Transaction setup
    $foo = $this->createUser('foo');
    //return;
    $client = new \WebUser($this->db);
    $client->login($foo->username);
    
    $client->addToCart($cat->id, 5);
    $txn_id = $client->placeOrder();
    
    //$this->completeTransaction($txn_id); return;
    
    $this->assertEquals(0, $this->db->get_one("SELECT count(id) FROM ticket"));
    
    
    $data = array(
      'txn_id' => $txn_id,
      'type_pay' => \model\DeliveryMethod::PAY_BY_CASH //'paybycash'
    );
    
    $_POST = $data;
    
    //Now see if controller reacts properly
    $cnt = new Payment();
        
    $this->assertEquals(5, $this->db->get_one("SELECT count(id) FROM ticket"));
    
    $this->assertEquals(0, $this->db->get_one("SELECT completed FROM ticket_transaction WHERE txn_id=?", $txn_id));
    
  }
  
  public function testCreditCardPayment(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Quebec CES' , $seller->id, $this->createLocation()->id, date('Y-m-d', strtotime("+1 day"))  /* 1, '2012-01-01', '9:00', '2014-01-10', '18:00' */);
    $this->setEventId($evt, 'aaa');
    $cat = $this->createCategory('Verde', $evt->id, 10.00);
    
    
    
    //Transaction setup
    $foo = $this->createUser('foo');
    
  }
  
  public function testPaypal(){
    $this->clearAll();
    
    
    
    $seller = $this->createUser('seller', 'Seller', array('paypal_account'=>'seller_1337720202_biz@yahoo.com'));
    $evt = $this->createEvent('Quebec CES' , $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $this->setEventId($evt, 'aaa');
    $cat = $this->createCategory('Verde', $evt->id, 10.00);
    
    //Transaction setup
    $foo = $this->createUser('foo');

    return; //just setup
    
    
    
    //return;
    $client = new \WebUser($this->db);
    $client->login($foo->username);
    
    $client->addToCart($cat->id, 5);
    $txn_id = $client->placeOrder();
    
    
    $this->assertEquals(0, $this->db->get_one("SELECT count(id) FROM ticket"));
    
    
    $data = array(
      'txn_id' => $txn_id,
      'type_pay' => \model\DeliveryMethod::PAY_BY_CASH
    );
    
    $_POST = $data;
    
    //Now see if controller reacts properly
    $cnt = new Payment();
        
    $this->assertEquals(5, $this->db->get_one("SELECT count(id) FROM ticket"));
    
    $this->assertEquals(0, $this->db->get_one("SELECT completed FROM ticket_transaction WHERE txn_id=?", $txn_id));
    
  }

 
}

