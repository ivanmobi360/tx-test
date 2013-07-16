<?php
use model\Tickettransactionmanager;
use model\Tickettransaction;
use model\Transaction;
use model\Payment;
use tool\PaymentProcessor\Paypal;
use reports\ErrorTrackHelper;
use reports\ProcessorReturnParser;
use reports\ReportLib;
class CustomerDetailsTest extends \DatabaseBaseTest{
  
  
  public function testCreate(){
    $this->clearAll();
    
    // -------------------- event setup --------------------------------------------
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Quebec CES' , $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Green', $evt->id, 10.00);
    //$cat->tax_group = 2; //state id will force tax calculation
    $cat->update();
    
    $this->createPromocode('DERP', $cat);
    
    $yellow = $this->createCategory('Yellow', $evt->id, 3.50);
    $red = $this->createCategory('Red', $evt->id, 6.50);
    
    $shakira = $this->createUser('shakira');
    $evt = $this->createEvent('Pies Descalzos' , $shakira->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $general = $this->createCategory('General', $evt->id, 25.00);
    

    //---------------- transactions ---------------------------------
    //Transaction setup
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    
    

    $client->addToCart($cat->id,5, 'DERP');
    $txn_id = $client->placeOrder();
    $this->completeTransaction($txn_id);
    
    
    
    //another purchase
    $client->addToCart($cat->id, 2);
    $txn_id = $client->placeOrder(); //incomplete
    
    
    
    //another buyer
    $bar = $this->createUser('bar');
    $client = new WebUser($this->db);
    $client->login($bar->username);
    $client->addToCart($yellow->id, 3);
    $this->completeTransaction($client->placeOrder());
    
    
    
    //another baz
    $bar = $this->createUser('baz');
    $client = new WebUser($this->db);
    $client->login($bar->username);
    $client->addToCart($general->id, 4);
    $this->completeTransaction($client->placeOrder());
    
    //return;
    
    //some dood who should not show up
    $d00d = $this->createUser('d00d');
    $client = new WebUser($this->db);
    $client->login($d00d->username);
    $client->addToCart($cat->id, 1);
    $txn_id = $client->placeOrder(); //incomplete
    //$this->completeTransaction($txn_id);
  
   }
  
  public function tearDown(){
    $_GET = array();
    $_SESSION = array();
    parent::tearDown();
  }
 
}

