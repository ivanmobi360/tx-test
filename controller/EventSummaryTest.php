<?php


use reports\ReportLib;
class EventSummaryTest extends DatabaseBaseTest{
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();

    

    //Events
    $user_id = $this->addUser('seller');
    $evt = $this->createEvent('First', $user_id, 1, '2013-01-01', '9:00', '2013-01-05', '18:00' );
    $catA = $this->createCategory('CatA', $evt->id, 10.00);
    $catB = $this->createCategory('CatB', $evt->id, 4.00);
    
    $user_id = $this->addUser('seller2');
    $evt = $this->createEvent('Second', $user_id, 1, '2013-02-01', '9:00', '2013-02-05', '18:00' );
    $cat2 = $this->createCategory('Cat2', $evt->id, 15.00);
    
    $user_id = $this->addUser('seller3');
    $evt = $this->createEvent('Third', $user_id, 1, '2013-03-01', '9:00', '2013-03-05', '18:00' );
    $cat3 = $this->createCategory('Cat3', $evt->id, 20.00);
    
    $evt = $this->createEvent('Cuarto', 'seller', 1, '2013-04-01');
    $this->setEventId($evt, 'cuatro');
    $cat4 = $this->createCategory('Cat4', $evt->id, 10.00);
    $cat41 = $this->createCategory('Cat41', $evt->id, 12.00);
    
    
    $foo = $this->createUser('foo');
    $this->buyTickets($foo->id, $catA->id, 1);
    $this->buyTickets($foo->id, $catB->id, 2);
    $this->buyTickets($foo->id, $cat2->id, 5);
    
    
    
    $bar = $this->createUser('bar');
    $this->buyTickets($bar->id, $cat3->id, 3);

    
    $this->buyTickets($foo->id, $cat4->id, 7);
    $this->buyTickets($bar->id, $cat4->id, 2);
    
    $baz = $this->createUser('baz');
    $this->buyTickets($baz->id, $cat41->id, 2);
    
    //multiple categories
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($catA->id, 1);
    $client->addToCart($catB->id, 1);
    $this->completeTransaction($client->placeOrder());
    
  }
 
}


