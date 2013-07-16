<?php
require_once '../website/global_credit_card_report.php';

class GlobalRemoteCreditCardReportTest extends \DatabaseBaseTest{
  
   
  //fixture only
  function testLocal(){
    $this->clearAll();
    
    $this->db->beginTransaction();
    // Event Organizer A *********************************************************************************************
    $seller = $this->createUser('seller', 'Event Organizer A');
    $evt = $this->createEvent('Event A1', $seller->id, $this->createLocation()->id); 
    $this->setEventId($evt, 'aa1');
    $this->setPaymentMethod($evt, self::OUR_CREDIT_CARD); //required to force the charge of cc fees - ASSERTED LATER!
    $cat1 = $this->createCategory('Adult', $evt->id, 100.00, 510, 255, array('tax_inc'=>1/*, 'cc_fee_id'=>11*/) );
    $cat2 = $this->createCategory('Kid'  , $evt->id,  50.00, 510, 255, array('tax_inc'=>1/*, 'cc_fee_id'=>11*/) );
    
    $buyer = $this->createUser('foo');
    $txn_id = $this->buyTickets($buyer->id, $cat1->id, 2);
    $txn_list[] = $txn_id;
    $this->setDateOfTransaction($txn_id, '2012-07-25');
    
    $buyer = $this->createUser('bar');
    $txn_id = $this->buyTicketsWithCC($buyer->id
    , $cat1->id);
    $txn_list[] = $txn_id;
    $this->setDateOfTransaction($txn_id, '2012-07-27');
    
    $buyer = $this->createUser('baz');
    $txn_id = $this->buyTicketsWithCC($buyer->id, $cat1->id);
    $txn_list[] = $txn_id;
    $this->setDateOfTransaction($txn_id, '2012-07-27');
    
    
    //a transaction with multiple categories
    $buyer = $this->createUser('paz');
    $web = new WebUser($this->db);
    $web->addToCart($cat1->id, 1);
    $web->addToCart($cat2->id, 2);
    $txn_id = $this->payCartByCreditCard($buyer->id, $web->getCart());
    $txn_list[] = $txn_id;
    $this->setDateOfTransaction($txn_id, '2012-07-27');
    
    
    $evt = $this->createEvent('Event A2', $seller->id, $this->createLocation()->id); 
    $this->setEventId($evt, 'aa2');
    $this->setPaymentMethod($evt, self::OUR_CREDIT_CARD); //required to force the charge of cc fees - ASSERTED LATER!
    $cat1 = $this->createCategory('Adult', $evt->id, 100.00, 510, 255, array('tax_inc'=>1/*, 'cc_fee_id'=>11*/) );
    
    $txn_id = $this->buyTickets('foo', $cat1->id, 3);
    $txn_list[] = $txn_id;
    $this->setDateOfTransaction($txn_id, '2012-07-24');
    $txn_id = $this->buyTickets('bar', $cat1->id, 2);
    $txn_list[] = $txn_id;
    $this->setDateOfTransaction($txn_id, '2012-07-24');
    
    $txn_id = $this->buyTickets('baz', $cat1->id, 6);
    $txn_list[] = $txn_id;
    $this->setDateOfTransaction($txn_id, '2012-08-05');
    
    // Event Organizer B *********************************************************************************************
    $seller = $this->createUser('seller2', 'Event Organizer B');
    $evt = $this->createEvent('Event B1', $seller->id, $this->createLocation()->id); 
    $this->setEventId($evt, 'bb1');
    $this->setPaymentMethod($evt, self::OUR_CREDIT_CARD); //required to force the charge of cc fees - ASSERTED LATER!
    $cat1 = $this->createCategory('Adult', $evt->id, 100.00, 510, 255, array('tax_inc'=>1/*, 'cc_fee_id'=>11*/) );
    $cat2 = $this->createCategory('Kid'  , $evt->id,  50.00, 510, 255, array('tax_inc'=>1/*, 'cc_fee_id'=>11*/) );
    
    $txn_id = $this->buyTickets('foo', $cat1->id, 2);
    $txn_list[] = $txn_id;
    $this->setDateOfTransaction($txn_id, '2012-07-24');
    $txn_id = $this->buyTicketsWithCC('bar', $cat1->id);
    $txn_list[] = $txn_id;
    $this->setDateOfTransaction($txn_id, '2012-07-25');
    
    
    $evt = $this->createEvent('Event B2', $seller->id, $this->createLocation()->id); 
    $this->setEventId($evt, 'bb2');
    $this->setPaymentMethod($evt, self::OUR_CREDIT_CARD); //required to force the charge of cc fees - ASSERTED LATER!
    $cat1 = $this->createCategory('Adult', $evt->id, 100.00, 510, 255, array('tax_inc'=>1/*, 'cc_fee_id'=>11*/) );
    
    $txn_id = $this->buyTickets('foo', $cat1->id, 3);
    $txn_list[] = $txn_id;
    $this->setDateOfTransaction($txn_id, '2012-07-27');
    $txn_id = $this->buyTickets('bar', $cat1->id, 2);
    $txn_list[] = $txn_id;
    $this->setDateOfTransaction($txn_id, '2012-08-05', 2);
    
    $this->db->commit();
    // Main Test *********************************************************************************************    
    $main = new MockMainProcessor();
    $main->txn_list = $txn_list; //skip csv parsing, just use a hardcoded list of txn_ids
    $row = $this->db->auto_array("SELECT * FROM optimal_transactions LIMIT 1");
    $main->data = $this->getCCData();
    Utils::clearLog("\n\n###########################################");
    $main->process();
    

  }
  
  function getCCData(){
    $data = array();
    $rows = $this->db->getIterator("SELECT * FROM optimal_transactions");
    foreach($rows as $row){
      $data[] = array(
        	'amount' => $row['amount']
        , 'auth_code' => $row['auth_code']
        , 'txn_id' => $row['txn_id']
      ); 
    }
    return $data;
  }
  
  //For this test to work, we must have transactions on the test database of the remote site (just run test/GlobalCreditCardReportApiTest on Ticketing
  //Make sure remote site's config.php is pointing to the db the data is stored at
  function testMGIntegration(){
    $this->clearAll();

    //Hack to retrieve the remote list of txn ids.
    $txn_list = $this->db->get_col("SELECT txn_id FROM mgevents_ticketing_test.optimal_transactions");
    
    $main = new MockMainProcessor();
    $main->txn_list = $txn_list;
    $main->data = $this->getCCData();
    Utils::clearLog();
    $main->process();
  }
  
  //same a previous report
  function testVisalusIntegration(){
    $this->clearAll();

    //Hack to retrieve the remote list of txn ids.
    $txn_list = $this->db->get_col("SELECT txn_id FROM ticketing_vis.optimal_transactions");
    
    $main = new MockMainProcessor();
    $main->txn_list = $txn_list;
    $main->data = $this->getCCData();
    Utils::clearLog();
    $main->process();
  }
  
  //same a previous report
  function testTixproCaribbeanIntegration(){
    $this->clearAll();
    
    //Hack to retrieve the remote list of txn ids.
    $txn_list = $this->db->get_col("SELECT txn_id FROM tixpro_caribbean.transactions_optimal");

    $main = new MockMainProcessor();
    $main->txn_list = $txn_list;
    $main->data = $this->getCCData();
    Utils::clearLog();
    $main->process();

  }
  

 
}

class MockMainProcessor extends MainProcessor{
   function processUpload(){
     //do nothing
   }
   
  function sendReport(ReportBuilder $report){
    $report->save(__DIR__ . '/a-global-report.xls');
  }
   
}