<?php


use reports\ReportLib;
class PendingTransactionsTest extends DatabaseBaseTest{
  
  //fixture to create activity for report
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();

    

    //Events
    $seller = $this->createUser('seller');
    $loc = $this->createLocation();
    $evt = $this->createEvent('First', $seller->id, $loc->id, '2012-01-01', '9:00', '2012-01-05', '18:00' );
    $catA = $this->createCategory('CatA', $evt->id, 10.00, 500);
    $catB = $this->createCategory('CatB', $evt->id, 4.00);
    
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Second', $seller->id, $loc->id, '2012-02-01', '9:00', '2012-02-05', '18:00' );
    $cat2 = $this->createCategory('Cat2', $evt->id, 15.00);
    
    $seller = $this->createUser('seller3');
    $evt = $this->createEvent('Third', $seller->id, $loc->id, '2012-03-01', '9:00', '2012-03-05', '18:00' );
    $cat3 = $this->createCategory('Cat3', $evt->id, 20.00, 500);
    
    
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    
    $i = 100;
    $this->db->beginTransaction();
    while($i--){
      $client->addToCart($catA->id, 2);
      $client->placeOrder();
    
    }
    $this->db->commit();

    
  }
 
}


