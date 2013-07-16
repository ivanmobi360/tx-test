<?php
use model\Payment;
use tool\PaymentProcessor\Paypal;
use controller\Ipnlistener;
use reports\ErrorTrackHelper;
use reports\ProcessorReturnParser;
use reports\ReportLib;
class ErrorTrackTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();

    $pp_status = 'failed';
    $pp_error = 'The payment failed';
    $returnPP =  serialize(array('payment_status'=> $pp_status, 'pending_reason'=> $pp_error ) );
    
    $ap_status = 'Subscription-Payment-Failed';
    $returnAP = serialize(array('ap_status'=> $ap_status ));

    //assume full xml response is stored
    $returnGoog = <<<eot
<?xml version="1.0" encoding="UTF-8"?>
<msg>
  <foo>    
  	<financial-order-state>CANCELLED</financial-order-state>
  </foo>
  <bar>
    <baz>
    	<reason>Failed risk check</reason>
    </baz>
  </bar>
</msg>    
eot;
    
    $return_st1 = serialize( array('status' => 'PENDING') );
    $return_st2 = serialize( array('card_transact_status' => 'DECLINED', 'gateway_result'=> '!ERROR!Server IP does not match posted IP') );
    $return_st3 = serialize( array('stp_transact_status' => 'FAILED', 'gateway_result'=> '!ERROR!Your STP account must be set to accept this interface - please contact our Helpdesk') );
    
    $user_id = $this->addUser('mega');
    $evt = $this->createEvent('CIESPAL' , $user_id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Normal', $evt->id, 100.00);
    $txn_id = 'TX-'. $this->getSerial();
    $this->createTransaction($cat->id, $user_id, 100.00, 2, $txn_id /*, 1, false, 0,  $returnPP*/ );
    $this->flagAsPaid($txn_id, 2, $returnPP);
    
    $user_id = $this->addUser('fire');
    $txn_id = 'TX-'. $this->getSerial();
    $this->createTransaction($cat->id, $user_id, 50.00, 1, $txn_id);
    $this->flagAsPaid($txn_id, 1, $returnAP);
    
    $user_id = $this->addUser('jinx');
    $txn_id = 'TX-'. $this->getSerial();
    $this->createTransaction($cat->id, $user_id, 50.00, 1, $txn_id/* 'TX-'. $this->getSerial(), 3, false, 0, $returnGoog*/);
    $this->flagAsPaid($txn_id, 3, $returnGoog);
    
    $user_id = $this->addUser('dotcom');
    $txn_id = 'TX-'. $this->getSerial();
    $this->createTransaction($cat->id, $user_id, 50.00, 1, $txn_id/*, 4, false, 0, $return_st1*/);
    $this->flagAsPaid($txn_id, 4, $return_st1);
    
    $txn_id = 'TX-'. $this->getSerial();
    $this->createTransaction($cat->id, $user_id, 22.00, 1, $txn_id /*'TX-'. $this->getSerial(), 4, false, 0, $return_st2*/);
    $this->flagAsPaid($txn_id, 4, $return_st2);
    
    $txn_id = 'TX-'. $this->getSerial();
    $this->createTransaction($cat->id, $user_id, 33.00, 1, $txn_id /*, 'TX-'. $this->getSerial(), 4, false, 0, $return_st3*/);
    $this->flagAsPaid($txn_id, 4, $return_st3);
    /*
    //Tests disabled since now error track is inserted on db by listeners
    $parser = new ProcessorReturnParser();
    
    //try to parse
    $lib = new ReportLib();
    $rows = $this->db->getAll($lib->getRecentOrdersSql());
    
    $row = $rows[0];
    Utils::log( __METHOD__ . print_r($row, true));
    
    $this->assertEquals('PayPal', $row['payment_type']  );
    $this->assertEquals($pp_status, $parser->getStatus($row));
    $this->assertEquals($pp_error, $parser->getError($row));
    
    //Alert Pay
    $row = $rows[1];
    Utils::log( print_r($row, true));
    $this->assertEquals($ap_status, $parser->getStatus($row));
    $this->assertEquals($ap_status, $parser->getError($row));
    
    //Google Checkout
    $row = $rows[2];
    Utils::log( print_r($row, true));
    $this->assertEquals('CANCELLED', $parser->getStatus($row));
    $this->assertEquals('Failed risk check', $parser->getError($row));
    
    $parser->getStatus($row);
    $parser->getStatus($row);
    $parser->getStatus($row);
    */
    
    /*
    $serial =  'TX-aaaa';//'TX-'. $this->getSerial();
    //Apparently there can be multiple rows with the same txn_id;
    $user_id = $this->createUser('wolf');
    $evt = $this->createEvent('Worldcup' , $user_id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Tribuna', $evt->id, 100.00);
    $this->createTransaction($cat->id, $user_id, 200.00, 2, $serial, 1, false, 0,  $returnPP );
    $cat = $this->createCategory('General', $evt->id, 50.00);
    $this->createTransaction($cat->id, $user_id, 150.00, 3, $serial, 1, false, 0,  $returnPP );
    $cat = $this->createCategory('Palco', $evt->id, 150.00);
    $this->createTransaction($cat->id, $user_id, 150.00, 1, $serial, 1, false, 0,  $returnPP );
    
    //Flag a test to alert of schema changes
    $lib = new ReportLib();
    $rows = $this->db->getIterator($lib->getOrderLinesSql(), $serial);
    $this->assertEquals(3, count($rows)); 
    */
    
  }
  
  //Deprecated. error track is logged directly on db by listener
  /*
  public function testMove(){
    
    //let's create some events
    $this->clearAll();

    $pp_status = 'failed';
    $pp_error = 'The payment failed';
    $returnPP =  serialize(array('payment_status'=> $pp_status, 'pending_reason'=> $pp_error ) );
    
    $txn_id = 'TX-PPP';
    
    //The different error states of a transaction are recorded in an error_track table. The
    $user_id = $this->createUser('mega');
    $evt = $this->createEvent('PIPA' , $user_id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Luneta', $evt->id, 100.00);
    $id = $this->createTransaction($cat->id, $user_id, 100.00, 2, $txn_id, 1, false, 0,  $returnPP );

    //'watch' error track page
    $helper = new ErrorTrackHelper();
    $helper->fetchNewRecords();
    
    $lib = new ReportLib();
    
    $this->assertEquals(1, count($this->db->getIterator($lib->getErrorTrackSql())) );
    
    //getway updates order status
    $returnPP =  serialize(array('payment_status'=> 'Checking', 'pending_reason'=> 'We are checking the funds' ) );
    $this->db->update('transaction', array('processor_return'=> $returnPP ), array('id'=>$id)  );
    
    $this->assertEquals(1, count($this->db->getIterator($lib->getErrorTrackSql())) );
    $helper->fetchNewRecords();
    $this->assertEquals(2, count($this->db->getIterator($lib->getErrorTrackSql())) );
  }*/
  
  public function testListener(){
    $this->clearAll();
    
    //Transaction setup
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Trial' , $seller->id, $this->createLocation()->id, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Luneta', $evt->id, 100.00);
    
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($cat->id, 2);
    $txn_id = $client->placeOrder();
    Utils::clearLog();
    
    $_GET['pt'] = 'p';
    parse_str($this->getIpnString(), $data);
    $data['notify_url'] = 'http://blah.com';
    $data['mc_gross'] = 10.00; //error in purpose
    //$data['payment_status'] = 'pending';
    //$data['pending_reason'] = 'some reason';
    $data['custom'] = Payment::createCustom(array(
                       'tixpro_customerid' => 1
                      , 'tixpro_txnid' => $txn_id
                      , 'tixpro_merchantid' => 1
                      , 'currency' => 'CAD'
                      ));
    $_POST = $data;
    $listen = new Ipnlistener();
    
    //a simple check
    $lib = new ReportLib();
    $rows = $this->db->getIterator($lib->getErrorTrackSql());
    $this->assertEquals(1, count($rows));
    $this->assertEquals(1, (int)$this->db->get_one("SELECT * FROM error_track WHERE status<>''") );
    
  }
  
  //1 row error track fixture to test change to viewed
  public function testSimpleFixture(){
    $this->clearAll();
    
    $txn_id = 'TX-ABC';
    //Transaction setup
    $user_id = $this->addUser('mega');
    $evt = $this->createEvent('Trial', $user_id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Luneta', $evt->id, 100.00);
    $id = $this->createTransaction($cat->id, $user_id, 100.00, 2, $txn_id, 2, false, 0 );
    
    $status = array(
                    'txn_id' => $txn_id
                     , 'status' => 'pending'
                     , 'error' => 'some error'
                     , 'processor_return' => 'some gibberish'
                     , 'payment_method_id' => 2
                     , 'created_at' => '2012-01-01' 
                    );
    
    $this->db->insert('error_track', $status );
    
    $status['status'] = 'checking';
    $status['created_at'] = '2012-01-03';
    $this->db->insert('error_track', $status );
    
    $status['status'] = 'denied';
    $status['created_at'] = '2012-01-05';
    $this->db->insert('error_track', $status );
  }
  
  
  
  
  public function tearDown(){
    $_GET = array();
  }
  
  
  
  
  
  
  
 
  
 

  
}