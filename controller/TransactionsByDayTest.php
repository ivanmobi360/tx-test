<?php
use model\Tickettransactionmanager;
use model\Tickettransaction;
use model\Transaction;
use model\Payment;
use tool\PaymentProcessor\Paypal;
use reports\ErrorTrackHelper;
use reports\ProcessorReturnParser;
use reports\ReportLib;
class TransactionsByDayTest extends \DatabaseBaseTest{
  
  
  public function testCreate(){
    $this->clearAll();
    
    // -------------------- event setup --------------------------------------------
    $seller = $this->createUser('seller');
    $loc = $this->createLocation('Panamá y Roca');
    $evt = $this->createEvent('Quebec CES' , $seller->id, $loc->id, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Green', $evt->id, 10.00);
    $cat->update();
    
    $this->createPromocode('DERP', $cat);
    
    $yellow = $this->createCategory('Yellow', $evt->id, 3.50);
    $red = $this->createCategory('Red', $evt->id, 6.50, 100);
    
    
    // ---- table setup -----
    
    // ---- 
    
    $shakira = $this->createUser('shakira');
    $loc = $this->createLocation('Estadio Modelo');
    $evt = $this->createEvent('Pies Descalzos' , $shakira->id, $loc->id, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $general = $this->createCategory('General', $evt->id, 25.00);
    

    //---------------- transactions ---------------------------------
    //Transaction setup
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    //$this->login($foo);
    
    

    $client->addToCart($cat->id,5, 'DERP');
    
    //now create the tickets
    $txn_id = $client->placeOrder(false, '2012-02-15');
    $this->completeTransaction($txn_id);
    
    
    //another purchase
    $client->addToCart($cat->id, 2);
    $txn_id = $client->placeOrder(false, '2012-02-17'); //This order is incomplete. This order should not show in Customer_details report
    
    //return;
    
    //another buyer
    $bar = $this->createUser('bar');
    $client = new WebUser($this->db);
    $client->login($bar->username);
    $client->addToCart($yellow->id, 3);
    $this->completeTransaction($client->placeOrder(false, '2012-02-21'));
    
    //another baz
    $bar = $this->createUser('baz');
    $client = new WebUser($this->db);
    $client->login($bar->username);
    $client->addToCart($general->id, 4);
    $this->completeTransaction($client->placeOrder(false, '2012-03-05'));
    
    
    $elmer = $this->createUser('elmer');
    $client = new WebUser($this->db);
    $client->login($elmer->username);
    $client->addToCart($red->id, 6);
    $this->completeTransaction($client->placeOrder(false, '2012-03-05'));
    
    
    $yuu = $this->createUser('yuu');
    $client = new WebUser($this->db);
    $client->login($yuu->username);
    $client->addToCart($red->id, 15);
    $txn_id = $client->placeOrder(false, '2012-03-05'); //yuu should not be shown in Email Reports
    //$this->completeTransaction($txn_id);
    
    //flag all as completed
    //$this->flagTransactionsAsCompleted();
  }
    
  public function tearDown(){
    $_GET = array();
    $_SESSION = array();
    parent::tearDown();
  }
 
}

