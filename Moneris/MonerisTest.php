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
    
    $this->doTransaction();
    
  }
  
  protected function doTransaction(){
      //post to check out to see what happens.
      Request::clear();
      
      $_POST = array(
              'sms-aaa-to' => '618994576'
              ,'sms-aaa-date' => '2013-06-03'
              ,'sms-aaa-time' => '09:00'
              ,'ema-aaa-to' => 'Foo@gmail.com'
              ,'ema-aaa-date' => '2013-06-01'
              ,'ema-aaa-time' => '09:00'
              ,'x' => '77'
              ,'y' => '41'
              ,'pay_mhp' => 'on'
      );
      
      $cnt = new Checkout(); //used just to inspect output js in log
      return $this->db->get_one("SELECT txn_id FROM ticket_transaction ORDER BY id DESC LIMIT 1");
  }
  
  function testListener(){

      $this->prePayState();
      
      $buyer = $this->buyer;
      $buyer->addToCart($this->cat->id, 1); //cart in session
      $total = $buyer->getCart()->getTotal();
      $txn_id = $this->doTransaction();
      //return;
      
      Utils::clearLog();
      Request::clear();
      $_POST = $_GET = array();
      
      $xml = MonerisTestTools::createXml($buyer->id, $txn_id, $total);
      
      //Mimic a post response;
      Utils::clearLog();
      $_GET['pt'] = 'm';
      $_POST['xml_response'] = $xml;
      $cnt = new \controller\Ipnlistener();
      
      $this->assertEquals(self::MONERIS, $this->db->get_one("SELECT payment_method_id FROM processor_transactions LIMIT 1"));
      $this->assertRows(1, 'moneris_transactions');
      $this->assertRows(1, 'ticket');
      
      
      
      //If the same message is sent again, nothing happens
      Request::clear();
      $_GET['pt'] = 'm';
      $_POST['xml_response'] = $xml;
      $cnt = new \controller\Ipnlistener();
      
      $this->assertRows(1, 'moneris_transactions');
      $this->assertRows(1, 'ticket');
      
  }
  
  //when we cancel, we are both redirect, and sent a cancelled ipn
  function testCancel(){
  
      $this->prePayState();
  
      $buyer = $this->buyer;
      $buyer->addToCart($this->cat->id, 1); //cart in session
      $total = $buyer->getCart()->getTotal();
      $txn_id = $this->doTransaction();
      
      $this->assertRows(1, 'ticket_transaction', "completed=0 AND cancelled=0");
      //return;
  
      Utils::clearLog();
      $this->clearRequest();
      $_GET['pt'] = 'm';
      $_POST['xml_response'] = MonerisTestTools::createCancelXml($buyer->id, $txn_id);
      $cnt = new \controller\Ipnlistener();
  
      $this->assertRows(1, 'ticket_transaction', "completed=0 AND cancelled=1");
      $this->assertRows(0, 'moneris_transactions');
      $this->assertRows(0, 'ticket');
  
  }
  
  /**
   * This test fails if $this->handlePurchaseResponse() is commented out in \controller\Moneris
   */
  function testApprovedUrl(){
      //Moneris is setup to point to this url
      $this->prePayState();
      
      $buyer = $this->buyer;
      $buyer->addToCart($this->cat->id, 1); //cart in session
      $total = $buyer->getCart()->getTotal();
      $txn_id = $this->doTransaction();
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