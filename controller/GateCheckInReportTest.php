<?php


use model\DeliveryMethod;
use reports\ReportLib;
class GateCheckInReportTest extends DatabaseBaseTest{
  
  //fixture to create activity for report
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();

    //Events
    $seller = $this->createUser('seller');
    $loc = $this->createLocation();
    $evt = $this->createEvent('First', $seller->id, $loc->id, '2012-01-01', '9:00', '2012-01-05', '18:00' );
    $catA = $this->createCategory('VIP', $evt->id, 4.50, 500);
    $catB = $this->createCategory('General Admission', $evt->id, 14.99);
    
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Second', $seller->id, $loc->id, '2012-02-01', '9:00', '2012-02-05', '18:00' );
    $cat2 = $this->createCategory('Ferrari', $evt->id, 15.00);
    
    $seller = $this->createUser('seller3');
    $evt = $this->createEvent('Third', $seller->id, $loc->id, '2012-03-01', '9:00', '2012-03-05', '18:00' );
    $cat3 = $this->createCategory('Uva', $evt->id, 20.00, 500);
    
    
    $foo = $this->createUser('foo');
    $this->buyTickets($foo->id, $catA->id, 10);
    
    //Flag last 5 as used
    $this->flagTickets(5);
    
    //$this->assertEquals(5, $this->db->get_one("SELECT COUNT(id) FROM ticket WHERE used=1"));
    
    /*$client = new WebUser($this->db);
    $client->login($foo->username);*/

    $this->buyTickets($foo->id, $catB->id, 7);
    $this->flagTickets(3);
    
    
    
    
    //some other seller ticket activity
    $this->buyTickets($foo->id, $cat2->id, 4);
    $this->flagTickets(3);
    
    //flag last transaction as PayAtdoor
    $id = $this->db->get_one("SELECT id FROM ticket_transaction ORDER BY id DESC");
    $this->db->update('ticket_transaction', array('delivery_method' => DeliveryMethod::PAY_AT_THE_DOOR) , "id=?", $id);
     //$this->flagTickets(3, array('delivery_method' => DeliveryMethod::PAY_AT_THE_DOOR));
    
    
  }
  
  protected function flagTickets($n, $data=false){
    $data = $data ?: array( 'used'=>1  ); //deatuls to 'used'
    $id = $this->db->get_one("SELECT id FROM ticket ORDER BY id DESC");
    for ($i=$id; $i>$id-$n; $i--){
      $this->db->update('ticket', $data , "id=?", $i);
      
    }
  }
 
}


