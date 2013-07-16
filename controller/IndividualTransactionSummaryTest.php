<?php

class IndividualTransactionSummaryTest extends \DatabaseBaseTest{
  
  
  public function testListener(){
    $this->clearAll();
    
    //Create event
    Utils::log(__METHOD__ . "\nFees: \\model\\Fee::getByName('percentage'): " . \model\Fee::getByName('percentage') 
    . "\n\\model\\Fee::getByName('fixed'):" . \model\Fee::getByName('fixed')
    ); //It is returning the id as fee value?
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Quebec CES' , $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Verde', $evt->id, 10.00);
    
    

    //$txn_id = 'TX-ABC';
    //Transaction setup
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    
    $this->createPromocode('DERP', $evt);
    

    $client->addToCart($cat->id,5, 'DERP');
    $txn_id = $client->placeOrder();
    $this->completeTransaction($txn_id);

    
    //another purchase (incomplete)
    $client->addToCart($cat->id, 2);
    $txn_id = $client->placeOrder();
    
    
    
    //activity in another merchant
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Film', $seller->id, $this->createLocation()->id);
    $cat = $this->createCategory('Die Hard', $evt->id, 15.00);
    $this->buyTickets($foo->id, $cat->id);
    
   
    
  }
  
  public function tearDown(){
    $_GET = array();
    $_SESSION = array();
    parent::tearDown();
  }
 
}

