<?php


use model\Transaction;
use reports\ReportLib;
class DailyReportTest extends DatabaseBaseTest{
  
  //fixture to create activity for report
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();

    
    $this->db->beginTransaction();

    //Events
    //$seller_id = $this->addUser('seller');
    $seller = $this->createUser('seller');
    $loc = $this->createLocation();
    //$loc->
    $evt = $this->createEvent('First', $seller->id, $loc->id, '2012-01-01', '9:00', '2012-01-05', '18:00' );
    $this->setEventId($evt, 'first');
    $catA = $this->createCategory('CatA', $evt->id, 10.00);
    $catB = $this->createCategory('CatB', $evt->id, 4.00);
    
    //$seller_id = $this->addUser('seller2');
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Second', $seller->id, $loc->id, '2012-02-01', '9:00', '2012-02-05', '18:00' );
    $this->setEventId($evt, 'second');
    $cat2 = $this->createCategory('Cat2', $evt->id, 15.00);
    
    //$seller_id = $this->addUser('seller3');
    $seller = $this->createUser('seller3');
    $evt = $this->createEvent('Third', $seller->id, $loc->id, '2012-03-01', '9:00', '2012-03-05', '18:00' );
    $this->setEventId($evt, 'third');
    $cat3 = $this->createCategory('Cat3', $evt->id, 20.00);
    
    // ----------------- Transactions ------------------------------------------------------------------------------------------
    
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($catA->id, 10);
    $this->completeTransaction($client->placeOrder(false, '2012-02-14' ), self::PAYPAL, '2012-02-14');
    //$client->placeOrder(false, '2012-02-14' ); //pending
    
    $client->addToCart($catB->id, 5);
    $this->completeTransaction($client->placeOrder(false, '2012-02-20' ), self::PAYPAL, '2012-02-20');
    //$client->placeOrder(false, '2012-02-20' ); //pending
    
    //activity from another buyer to another seller
    $bar = $this->createUser('bar');
    $client = new WebUser($this->db);
    $client->login($bar->username);
    $client->addToCart($cat2->id, 3);
    $this->completeTransaction($client->placeOrder(false, '2012-03-15' ), self::PAYPAL, '2012-03-15');
    //$client->placeOrder(false, '2012-03-15' ); //pending
    
    $silas = $this->createUser('silas');
    $client = new WebUser($this->db);
    $client->login($silas->username);
    $client->addToCart($cat3->id, 4);
    $this->completeTransaction($client->placeOrder(false, '2012-03-17' ), self::OUR_CREDIT_CARD, '2012-03-17');
    
    $volk = $this->createUser('volk');
    $client = new WebUser($this->db);
    $client->login($volk->username);
    $client->addToCart($catB->id, 1);
    $this->completeTransaction($client->placeOrder(false, '2012-03-19' ), self::OUR_CREDIT_CARD, '2012-03-19');
    
    $magnus = $this->createUser('magnus');
    $client = new WebUser($this->db);
    $client->login($magnus->username);
    $client->addToCart($catA->id, 3);
    $this->completeTransaction($client->placeOrder(false, '2012-04-01' ), self::OUR_CREDIT_CARD, '2012-04-01');
 

    $this->db->commit();

    
  }
 
}
