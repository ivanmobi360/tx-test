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
  
    
  function fixture(){
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
    
    $this->fixture();
    
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

      $this->fixture();
      
      $buyer = $this->buyer;
      $buyer->addToCart($this->cat->id, 1); //cart in session
      $total = $buyer->getCart()->getTotal();
      $txn_id = $this->doTransaction();
      //return;
      
      Utils::clearLog();
      Request::clear();
      $_POST = $_GET = array();
      
      $xml = $this->createXml($buyer->id, $txn_id, $total);
      
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
  
  /**
   * This test fails if $this->handlePurchaseResponse() is not uncommented in \controller\Moneris
   */
  function xtestApprovedUrl(){
      //Moneris is setup to point to this url
      $this->fixture();
      
      $buyer = $this->buyer;
      $buyer->addToCart($this->cat->id, 1); //cart in session
      $total = $buyer->getCart()->getTotal();
      $txn_id = $this->doTransaction();
      //return;
      
      Utils::clearLog();
      Request::clear();
      
      $xml = $this->createXml($buyer->id, $txn_id, $total);
      
      //Mimic a post response;
      $_POST['xml_response'] = $xml;
      $cnt = new \controller\Moneris();
      
      $this->assertEquals(self::MONERIS, $this->db->get_one("SELECT payment_method_id FROM processor_transactions LIMIT 1"));
      $this->assertRows(1, 'moneris_transactions');
      $this->assertRows(1, 'ticket');
  }
  
  function createXml($buyer_id, $txn_id, $total, $seller='seller' ){
      $xml = file_get_contents(__DIR__ . '/responses/post_response2.xml');
      
      //lets override the template response
      $data = json_decode(json_encode((array)simplexml_load_string($xml)),1);
      $data['rvarcustom'] = \model\Payment::createCustom(array(
              'tixpro_customerid' => $buyer_id
              , 'tixpro_txnid' => $txn_id //$this->db->get_one("SELECT txn_id FROM ticket_transaction LIMIT 1")
              , 'tixpro_merchantid' => $seller
              , 'currency' => 'CAD'
      ));
      $data['charge_total'] = $total;
      $new_xml = new \SimpleXMLElement("<?xml version=\"1.0\"?><response></response>");
      $this->array_to_xml($data, $new_xml);
      $xml = $new_xml->asXML();
      return $xml;
  }
  
  // function defination to convert array to xml
  function array_to_xml($student_info, &$xml_student_info) {
      foreach($student_info as $key => $value) {
          if(is_array($value)) {
              if(!is_numeric($key)){
                  $subnode = $xml_student_info->addChild("$key");
                  $this->array_to_xml($value, $subnode);
              }
              else{
                  $this->array_to_xml($value, $xml_student_info);
              }
          }
          else {
              $xml_student_info->addChild("$key","$value");
          }
      }
  }

 
}