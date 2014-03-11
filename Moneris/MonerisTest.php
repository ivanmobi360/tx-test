<?php
namespace Moneris;

use tool\CurlHelper;

use controller\Checkout;

use tool\Request;

use Utils;

/**
 * This is actually a functional/integration test, since it is a multi step payment process
 * @author Ivan Rodriguez
 *
 */
class MonerisTest extends \DatabaseBaseTest{
  
    
  protected function prePayState(){
    $this->clearAll();
    
    
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Autoshow' , $seller->id, $this->createLocation()->id, date('Y-m-d', strtotime("+1 day")) );
    $this->setEventParams($evt, array('has_tax'=>0, 'fee_id'=>9, 'cc_fee_id'=>2)); //With this configuration, the category price becomes the final price, as is.
    $this->setEventId($evt, 'aaa');
    $this->setEventPaymentMethodId($evt, self::MONERIS);
    $this->cat = $this->createCategory('Verde', $evt->id, 6.00, 100, 0);
    
    
    
    //Transaction setup
    $this->foo = $this->createUser('foo');
    
    //let's buy
    $this->buyer = new \WebUser($this->db);
    $this->buyer->login($this->foo->username);

    //let's pay
    Utils::clearLog();
  }
  
  
  
  function testSuccess(){
    
    $this->prePayState();
    
    $this->buyer->addToCart($this->cat->id, 1); //cart in session
    
    //$this->buyer->placeMonerisTransaction();
    $this->buyer->payWithMoneris();
    
    $this->assertEquals(self::MONERIS, $this->db->get_one("SELECT payment_method_id FROM processor_transactions LIMIT 1"));
    $this->assertRows(1, 'moneris_transactions');
    $this->assertRows(1, 'ticket');
    
  }
  

  
  function testListener(){

      $this->prePayState();
      
      $buyer = $this->buyer;
      $buyer->addToCart($this->cat->id, 1); //cart in session
      $total = $buyer->getCart()->getTotal(); //for some reason we have to store the total first
      $txn_id = $this->buyer->placeMonerisTransaction();
      
      $xml = MonerisTestTools::createXml($buyer->id, $txn_id, $total);
      MonerisTestTools::processIpnMessage($xml);
      

      $this->assertEquals(self::MONERIS, $this->db->get_one("SELECT payment_method_id FROM processor_transactions LIMIT 1"));
      $this->assertRows(1, 'moneris_transactions');
      $this->assertRows(1, 'ticket');
      
      
      
      //If the same message is sent again, nothing happens
      MonerisTestTools::processIpnMessage($xml);

      
      $this->assertRows(1, 'moneris_transactions');
      $this->assertRows(1, 'ticket');
      
  }
  
  //when we cancel, we are both redirect, and sent a cancelled ipn
  function testCancel(){
  
      $this->prePayState();
  
      $buyer = $this->buyer;
      $buyer->addToCart($this->cat->id, 1); //cart in session
      $total = $buyer->getCart()->getTotal();
      $txn_id = $buyer->placeMonerisTransaction();
      
      $this->assertRows(1, 'ticket_transaction', "completed=0 AND cancelled=0");
      //return;
      
      MonerisTestTools::processIpnMessage(MonerisTestTools::createCancelXml($buyer->id, $txn_id));
  
      $this->assertRows(1, 'ticket_transaction', "completed=0 AND cancelled=1");
      $this->assertRows(0, 'moneris_transactions');
      $this->assertRows(0, 'ticket');
  
  }
  
  /**
   * This test fails if $this->handlePurchaseResponse() is commented out in \controller\Moneris
   * 
   * If redirect data from Moneris were processed, this process would run. But at the moment to avoid
   * race conditions, we just await for the ipn message, so this test models a process that is not happening
   * in production at the moment
   */
  function testApprovedUrl(){
      //Moneris is setup to point to this url
      $this->prePayState();
      
      $buyer = $this->buyer;
      $buyer->addToCart($this->cat->id, 1); //cart in session
      $total = $buyer->getCart()->getTotal();
      $txn_id = $this->buyer->placeMonerisTransaction();
      //return;
      
      Utils::clearLog();
      Request::clear();
      
      $xml = MonerisTestTools::createXml($buyer->id, $txn_id, $total);
      
      //Mimic a post response;
      $_POST['xml_response'] = $xml;
      $cnt = new \Moneris\MockMonerisController(); //new \controller\Moneris();
      
      $this->assertEquals(self::MONERIS, $this->db->get_one("SELECT payment_method_id FROM processor_transactions LIMIT 1"));
      $this->assertRows(1, 'moneris_transactions');
      $this->assertRows(1, 'ticket');
  }

 
}