<?php
use model\Transaction;
use model\Payment;
use tool\PaymentProcessor\Paypal;
use reports\ErrorTrackHelper;
use reports\ProcessorReturnParser;
use reports\ReportLib;
class EventOverviewTest extends \DatabaseBaseTest{
  
  
  public function testCreate(){
    $this->clearAll();
    
    // -------------------- event setup --------------------------------------------
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Quebec CES' , $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $this->setEventId($evt,'aaa');
    $cat = $this->createCategory('Green', $evt->id, 10.00, 30, 10);
    $cat->update();
    
    $this->createPromocode('DERP', $cat);
    
    //let's create some other codes
    $pid = $this->createPromocode('SAMSUNG', $cat);
    //let's make this one invalid
    $this->db->update('promocode', array('valid_from' =>date('Y-m-d', strtotime("-1 year"))
																				, 'valid_to' =>date('Y-m-d', strtotime("-1 month"))
                                        ), "id=$pid");
    
    $yellow = $this->createCategory('Yellow', $evt->id, 3.50);
    $red = $this->createCategory('Red', $evt->id, 6.50);
    
    /*
    $shakira = $this->createUser('shakira');
    $evt = $this->createEvent('Pies Descalzos' , $shakira->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $general = $this->createCategory('General', $evt->id, 25.00);
    */

    //---------------- transactions ---------------------------------
    //Transaction setup
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    
    

    $client->addToCart($cat->id, 1, 'DERP');
    
    //now create the tickets
    $this->completeTransaction($client->placeOrder(false, '2012-02-15'));
    
    //return;
    
    //flag as completed up to here
    //$this->flagTransactionsAsCompleted();
    
    //order other tickets, but don't buy them
    $client->addToCart($yellow->id, 2);
    $client->placeOrder(false, '2012-02-20');
    
    
    //another purchase
    $bar = $this->createUser('bar');
    $client = new WebUser($this->db);
    $client->login($bar->username);
    $client->addToCart($cat->id, 2, 'SAMSUNG');
    $this->completeTransaction($client->placeOrder(false, '2012-02-17'));
    
    
    //purchase with no promo codes
    $baz = $this->createUser('baz');
    $client = new WebUser($this->db);
    $client->login($baz->username);
    $client->addToCart($cat->id, 4);
    $this->completeTransaction($client->placeOrder(false, '2012-03-05'));
    
    
    //another buyer
    $elmer = $this->createUser('elmer');
    $client = new WebUser($this->db);
    $client->login($elmer->username);
    $client->addToCart($cat->id, 6, 'DERP');
    $this->completeTransaction($client->placeOrder(false, '2012-03-05'));
    

    $rukia = $this->createUser('rukia');
    $this->buyTickets('rukia', $yellow->id, 5);
    
  }
  
  //simple Fixture to create several events hold by the same merchant
  function xtestSeveralEvents(){
    $this->clearAll();
    $seller = $this->createUser('seller');
    
    $loc = $this->createLocation();
    $evt = $this->createEvent('Dinner' , $seller->id, $loc->id, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Green', $evt->id, 10.00);
    $cat = $this->createCategory('Red', $evt->id, 15.00);
    $cat = $this->createCategory('Yellow', $evt->id, 20.00);
    $this->createPromocode('DERP', $evt);
    
    $loc = $this->createLocation();
    $evt = $this->createEvent('Lunch' , $seller->id, $loc->id, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('LAN', $evt->id, 10.00);
    $cat = $this->createCategory('WAN', $evt->id, 15.00);
    $cat = $this->createCategory('MAN', $evt->id, 20.00);
    $this->createPromocode('HERP', $evt);
    
  }
  
 
  
  

  
  public function tearDown(){
    $_GET = array();
    $_SESSION = array();
    parent::tearDown();
  }
 
}

