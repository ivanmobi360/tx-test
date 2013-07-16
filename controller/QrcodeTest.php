<?php
use model\Transaction;
use model\Payment;
use tool\PaymentProcessor\Paypal;
use reports\ErrorTrackHelper;
use reports\ProcessorReturnParser;
use reports\ReportLib;
class QrcodeTest extends \DatabaseBaseTest{
  
  
  public function testCreate(){
    $this->clearAll();
    
    // -------------------- event setup --------------------------------------------
    $seller = $this->createUser('seller');
    
    $event_id = 'a1a2a3';
    
    $evt = $this->createEvent('Quebec CES' , $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $this->setEventId($evt, $event_id);
    $cat = $this->createCategory('Green', $evt->id, 10.00, 30, 10);
    $cat->update();
   
    //---------------- transactions ---------------------------------
    //Transaction setup
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);

    $client->addToCart($cat->id, 1);
    
    //now create the tickets
    $this->completeTransaction($client->placeOrder(false));
    
    
    //retrieve ticket
    $ticket = $this->db->auto_array("SELECT * FROM ticket LIMIT 1");
    
    $this->assertEquals(strtoupper($event_id), substr($ticket['code'], 0, 6) );
    $this->assertEquals(16, strlen($ticket['code']));
  }
  
  
  
 
  
  

  
  public function tearDown(){
    $_GET = array();
    $_SESSION = array();
    parent::tearDown();
  }
 
}

