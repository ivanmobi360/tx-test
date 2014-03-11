<?php
use model\Categories;

use tool\Request;

/**
 * Simple wrapper to be able to inspect and test the website/assignXMLconstructor.php test
 * 
 */
class AssignXMLconstructorTest extends DatabaseBaseTest{
    
   
  
  //simple test runner
  function xtestRun(){
    $this->create_Stranger_10_Tickets();
  }
  
  protected function createState(){
      global $argv;
      if (!in_array('no-clear', $argv)){
           
          $this->clearAll();
          //$this->db->Query("TRUNCATE TABLE ticket_pool");
          $seller = $this->createUser('seller');
          $foo = $this->createUser('foo');
           
          $evt = $this->createEvent('Simple Event', $seller->id, $this->createLocation()->id, $this->dateAt("+5 day"));
          $this->setEventId($evt, 's1mpl33v');
          $this->setEventPaymentMethodId($evt, self::MONERIS);
          $this->setEventParams($evt, array('has_tax'=>0, 'cc_fee_id'=>2));
          $cat = $this->createCategory('Normal', $evt->id, 45.00, 100, 0, array('fee_inc'=>1));
      
          $this->createStrangers10($seller, false);
          
          $event_id = $this->createTheFirm($seller, false);
          
          if ($this->db->get_one("SELECT id FROM ticket_pool LIMIT 1")){
              //just reset them
              $this->db->Query("UPDATE ticket_pool SET time_reserved=NULL, txn_id=NULL, reserved=0, ticket_id=NULL, name=''");
          }else{
              $this->db->Query("TRUNCATE TABLE ticket_pool");
              $this->db->beginTransaction();
              $this->db->executeBlock(file_get_contents(__DIR__ . "/fixture/ticket_pool.sql"));
              $this->db->commit();
          }
           
          
           
      }
  }
  
  function testTheFirm(){
      
    $this->createState();
	
	//Now we'll do some purchases. First we'll do a purchase of a map selected 4-8 table
	$web = new \WebUser($this->db); $web->login('foo@blah.com'); //login for laughs
	Request::clear();
	$_POST = $this->purchase_4_8_request();
	$_GET = array('page' => 'thefirmpay');
	$cont = new \controller\Assignseating();
	
	//Expect a transaction
	$this->assertRows(1, 'ticket_transaction', " ticket_count=8 AND completed=1 ");
	$this->assertRows(8, 'ticket');
	$this->assertRows(8, 'ticket_pool', " `table`=3 AND ticket_id IS NOT NULL AND txn_id IS NOT NULL");
	$this->assertEquals(8, $this->db->get_one("SELECT COUNT(ticket.id) FROM ticket
INNER JOIN ticket_pool on ticket.code = ticket_pool.code AND ticket.category_id = 388")); //tickets are synched
	
	//it should not be possible to buy the same table again
	Request::clear();
	$_POST = $this->purchase_4_8_request();
	$_GET = array('page' => 'thefirmpay');
	$cont = new \controller\Assignseating();
	$this->assertRows(1, 'ticket_transaction', " ticket_count=8 AND completed=1 ");
	
	//now let's buy 4 seats of the only 4-6 table
	Request::clear();
	$_POST = $this->purchase_4_6_request();
	$_GET = array('page' => 'thefirmpay');
	$cont = new \controller\Assignseating();
	$txn_id = $cont->txn_id;
	$this->assertRows(1, 'ticket_transaction', " ticket_count=4 AND completed=1 AND txn_id=?", $txn_id);
	$this->assertRows(12, 'ticket');
	$this->assertRows(4, 'ticket_pool', " ticket_id IS NOT NULL AND txn_id =?", $txn_id);
	
	//if we do the same purchase again, for the remainder 2 tickets, it should fail
	Request::clear();
	$_POST = $this->purchase_4_6_remainder();
	$_GET = array('page' => 'thefirmpay');
	$cont = new \controller\Assignseating();
	$this->assertFalse($cont->ok);
	
  }
  
  function testRegistration(){
      $this->createState();
      
      //force a logout
      $web = new \WebUser($this->db); $web->login('foo@blah.com'); $web->logout();
      
      Utils::clearLog();
      
      $this->clearRequest();
      $_POST = $this->purchase_register();
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      
      //Expect a transaction
      $this->assertRows(1, 'ticket_transaction');
      $this->assertRows(2, 'ticket');

      //return;
      
      //if some other user tries to purchase the same table, it should fail;
      $web = new \WebUser($this->db); $web->login('foo@blah.com'); Utils::clearLog();
      $_POST = $this->purchase_2_4_remainder();
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertFalse($cont->ok);
  }
  
  /**
   * There are two [6-10] tables
   * Buy iconmpletely the 2 tables (IT)
   * Do a third purchase from cart (AT and At).
   * Purchase should fail
   */
  function test_blocked(){
      $this->createState();
      
      $web = new \WebUser($this->db); $web->login('foo@blah.com');

      //first table (1)
      $this->clearRequest();
      $_POST = $this->purchase_6_10();
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
      
      //return;
      
      //second table (2)
      $this->clearRequest();
      $_POST = $this->purchase_6_10('2', 6);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
      
      //return;
      
      //third table
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_At();
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertFalse($cont->ok);
      
      //TODO: It should show single seats on cart as blocked too.
  }
  
  function test_min_policy(){
      $this->createState();
      
      //on At, min policy should be applied
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_At(5);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertFalse($cont->ok);
      
  }
  
  function test_max_policy(){
      $this->createState();
      
      //first table ok
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_At(7);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
      
      //we have one table left, but we should not exceed the superior limit
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_At(11);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertFalse($cont->ok);
  }
  
  /**
   * There are two [6-10] tables
   * Buy iconmpletely the 2 tables (At) Anonymous tickets
   * Do a third purchase from cart (AT and At).
   * Purchase should fail
   */
  function test_Aticket_exhaust(){
      $this->createState();

      
      //first table ok
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_At(7);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
      
      //second table ok
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_At(10);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
      
      //no more tables - this should fail
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_At(7);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertFalse($cont->ok);
      
      
  }
  
  /**
   * There are two [6-10] tables
   * Buy (AT) Anonymous tables
   * Do a third purchase from cart (AT and At).
   * Purchase should fail
   */
  function test_ATable_exhaust(){
      $this->createState();
      
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_ATables(2);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
      
      //a third table purchase should fail
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_ATables(1);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertFalse($cont->ok);
      
  }
  
  /**
   * There are two [6-10] tables
   * Buy (At) Anonymously 6t - blocks 1st table
   * Buy (At) Anonymously 6t - blocks 2nd table
   * Verify each table has exaclty 6 occupied seats
   */
  function test_Aticket_assign(){
      $this->createState();
  
  
      //first table ok
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_At(6);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
  
      //second table ok
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_At(6);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
  
      $this->assertRows(6, 'ticket_pool', "category_id=389 AND ticket_id IS NOT NULL AND `table`=1");
      $this->assertRows(6, 'ticket_pool', "category_id=389 AND ticket_id IS NOT NULL AND `table`=2");
  
  }
  
  /**
   * It should be possible to purchase two tables selected from the map
   * Update: Nope. "if a customer purchase a table it would be the only one who bought that table"
   */
  function test_two_tables(){
      $this->createState();
      
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_two_tables();
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
      
  }
  
  //Bug but dropped for now
  function test_two_DT_tables(){
      $this->createState();
  
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_two_DT_tables();
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
  
  }
  
  function test_bug01(){
      $this->createState();
  
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_bug01();
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
  
  }
  
  /**
   * When purchasing 15 tickets of [6-10]
   * It should fail because, while first table fills (10 tickets), 5 tickets are not enough to fill the second table
   */
  function test_bug02(){
      $this->createState();
      
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_At(15);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertFalse($cont->ok);
      
  }
  
  /**
   * When purchasing 20 tickets of [6-10]
   * It should succeed
   * Two tables should be allocated
   */
  function test_bug03(){
      $this->createState();
  
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_6_10_At(20);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
  
      $this->assertRows(20, "ticket_pool", "ticket_id IS NOT NULL and category_id=389");
      //$this->assertRows(20, "ticket_pool", "ticket_id IS NOT NULL and category_id=389");
  }
  
  /**
   * There are only 2 [6-10] tables
   * It should not allow to buy 3 ATables
   */
  function test_bug04(){
      $this->createState();
  
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->bug04();
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertFalse($cont->ok);
  
  }
  
  /**
   * There are 48 [2-4] free seats in 12 tables
   * First we buy 20 seats. That should fill up 5 tables. There should be 7 free tables.
   */
  function test_bug05(){
      $this->createState();
      
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_2_4_At(20);
      $_GET = array('page' => 'thefirmpay');
      $cont = new \controller\Assignseating();
      $this->assertTrue($cont->ok);
      
      $cat = new Categories(382);
      $this->assertEquals(7, count($cat->getEmptyTables()));
      
  }
  
  function testMonerisIntegration(){
      $this->createState();
      $web = new \WebUser($this->db); $web->login('foo@blah.com');      
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_2_4_ATable();
      $_GET = array('page' => 'thefirmpay');
      $cont = new MonerisHandledAssigSeatingController();
      //$this->assertTrue($cont->ok);
      
      $this->assertRows(0, 'ticket');
      
      //Now we call our listener
      Utils::clearLog(); 
      $this->clearRequest();
      $_GET['pt'] = 'm';
      $_POST['xml_response'] = \Moneris\MonerisTestTools::createXml('foo', $cont->txn_id, $cont->cart_total);
      $cnt = new \controller\Ipnlistener();
      
      $this->assertRows(4, 'ticket');
      $this->assertRows(4, 'ticket_pool', "ticket_id IS NOT NULL");
      $this->assertEquals(self::MONERIS, $this->db->get_one("SELECT payment_method_id FROM processor_transactions LIMIT 1"));
      $this->assertRows(1, 'moneris_transactions');
      
  }
  
  function testCancelMoneris(){
      $this->createState();
      $web = new \WebUser($this->db); $web->login('foo@blah.com');
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchase_2_4_ATable();
      $_GET = array('page' => 'thefirmpay');
      $cont = new MonerisHandledAssigSeatingController();
      //$this->assertTrue($cont->ok);
  
      $this->assertRows(0, 'ticket');
      $this->assertRows(4, 'ticket_pool', "txn_id IS NOT NULL");
  
      //Now we call our listener
      Utils::clearLog();
      $this->clearRequest();
      $_GET['pt'] = 'm';
      $_POST['xml_response'] = \Moneris\MonerisTestTools::createCancelXml('foo', $cont->txn_id);
      $cnt = new \controller\Ipnlistener();
  
      $this->assertRows(0, 'ticket');
      $this->assertRows(0, 'ticket_pool', "NOT(ticket_id IS NULL AND txn_id IS NULL AND time_reserved IS NULL AND reserved=0)");
      $this->assertRows(0, 'processor_transactions');
      $this->assertRows(0, 'moneris_transactions');
  
  }
  
  /**
   * A cancelled cc transaction should not clear any previous buyed ticket
   */
  function testNoSwipe(){
    $this->testMonerisIntegration(); //set up initial transaction

    $this->clearRequest(); Utils::clearLog();
    $_POST = $this->purchase_2_4_ATable();
    $_GET = array('page' => 'thefirmpay');
    $cont = new MonerisHandledAssigSeatingController();
    //$this->assertTrue($cont->ok);
    
    $this->assertRows(4, 'ticket'); //from previous transacation
    $this->assertRows(4, 'ticket_pool', "txn_id IS NOT NULL AND ticket_id IS NULL"); //pending tickets
    
    //Now we call our listener
    Utils::clearLog();
    $this->clearRequest();
    $_GET['pt'] = 'm';
    $_POST['xml_response'] = \Moneris\MonerisTestTools::createCancelXml('foo', $cont->txn_id);
    $cnt = new \controller\Ipnlistener();
    
    //no change in previous tate
    $this->assertRows(4, 'ticket');
    $this->assertRows(4, 'ticket_pool', "ticket_id IS NOT NULL");
    $this->assertEquals(self::MONERIS, $this->db->get_one("SELECT payment_method_id FROM processor_transactions LIMIT 1"));
    $this->assertRows(1, 'moneris_transactions');
    $this->assertRows(1, 'ticket_transaction', "cancelled=0 AND completed=1");
    $this->assertRows(1, 'ticket_transaction', "cancelled=1 AND completed=0");
  }
  
  function testXml(){
      //quick xml parser
      $path = 'C:/wamp/www/tixpro/website/resources/images/event/th/ef/ir/m1/assign/assign.xml';
      $xml = simplexml_load_file($path);
      //Utils::log($xml->asXML());
      foreach($xml->tables->table as $table){
          Utils::log($table->asXML());
      }
  }
  
  protected function createStrangers10($seller, $create_pool = true){
      $evt = $this->createEvent('Strangers 10', $seller->id, $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, self::STRANGERS_IN_THE_NIGHT_10_ID);
  
      //for the time being we'll just dump the live database contents to reproduce the table logic
      $this->db->Query("
INSERT INTO `category` (`id`, `name`, `description`, `event_id`, `category_id`, `price`, `capacity`, `capacity_max`, `capacity_min`, `overbooking`, `tax_inc`, `fee_inc`, `cc_fee_inc`, `fee_id`, `cc_fee_id`, `as_seats`, `hidden`, `locked_fee`, `assign`, `order`, `sold_out`) VALUES
(378, '', '', '28d26a2d', NULL, '500.00', 10, 10, 0, 0, 1, 1, 1, NULL, NULL, 0, 1, NULL, 0, 0, 0),
(379, 'Single Basic Seating', 'Open Bar from 6:00 PM – 1:00 AM </br> Single seating</br> Access to all restaurants and entertainment</br> ** Get upgrade to “ Preferred Table Seating “ by completing a table of ten(10) seats with friends and/or  family !!', '28d26a2d', NULL, '200.00', 10, 10, 0, 0, 1, 1, 1, NULL, NULL, 1, 0, NULL, 0, 0, 0),
(380, 'VIP Premium Seating', 'Premium Open Bar from 5:00 PM – 1:00 AM </br>VIP Valet Parking </br>Front Of The Line Drive Home Service </br>Private Preferred Table Seating for 10 </br>Dedicated servers </br>Access to VIP Lounge</br>Access to all restaurants and entertainment</br>Early entrance option available at 5 PM', '28d26a2d', 378, '5000.00', 100, 100, 0, 0, 1, 1, 1, NULL, NULL, 0, 0, NULL, 1, 0, 0),
(381, 'Standard Preferred Seating', 'Open Bar from 6:00 PM – 1:00 AM </br>Table seating for 10 </br>Access to all restaurants and entertainment', '28d26a2d', 379, '2000.00', 300, 300, 0, 0, 1, 1, 1, NULL, NULL, 1, 0, NULL, 1, 0, 0);
	        ");
  
      //fill up ticket pool
      if($create_pool)
          $this->create_Stranger_10_Tickets();
  }
  
  protected function create_Stranger_10_Tickets(){
      //this really heavily on some external file that changes every year. don't expect consistent results. Last time it generated 2060 tickets/rows
      require_once PATH_INCLUDES .'../website/assignXMLconstructor.php';
  }
  
  protected function createTheFirm($seller, $create_pool= true){
      //we need to log in to create
      $client = new WebUser($this->db);
      $client->login($seller->username);
       
      $_POST = $this->get_THE_FIRM_create_data();
       
      Utils::clearLog();
       
      $cont = new controller\Newevent(); //all the logic in the constructor haha
       
      $event_id = $this->getLastEventId();
      $event_id = $this->changeEventId($event_id, 'thefirm1');
       
      $this->setEventPaymentMethodId(new \model\Events($event_id), self::MONERIS);
       
      //fix some malformed data for now
      $this->db->update('category', array('tax_inc'=>1, 'fee_inc'=>1, 'cc_fee_inc'=>1), "event_id=?", $event_id);
      //make the tables assign=1
      $this->db->update('category', array('assign'=>1), "event_id=?", $event_id);
      //$this->db->update('category', array('hidden'=>1), "event_id=? AND category_id IS NULL", $event_id);
       
      //now we need to create the seats of the firm
      if($create_pool){
          $pool = new tool\TheFirmAssignXmlGenerator();
          $pool->build();
          \Utils::log(__METHOD__ . " xml: \n" . $pool->getAssignXml() );
      }
       
       
      return $event_id;
  }
  
  
  
  protected function get_THE_FIRM_create_data(){
  	return array (
	  'MAX_FILE_SIZE' => '3000000',
	  'is_logged_in' => '1',
	  'copy_event' => self::STRANGERS_IN_THE_NIGHT_10_ID,
	  'e_name' => 'THE FIRM',
	  'e_capacity' => '',
	  'e_date_from' => $this->dateAt("+5 day"), //'2014-02-26',
	  'e_time_from' => '',
	  'e_date_to' => '',
	  'e_time_to' => '',
	  'e_description' => '<p>blah</p>',
	  'reminder_email' => '',
	  'sms' => 
	  array (
	    'content' => '',
	  ),
	  'c_id' => '2',
	  'l_latitude' => '52.9399159',
	  'l_longitude' => '-73.5491361',
	  'l_id' => '4',
	  'dialog_video_title' => '',
	  'dialog_video_content' => '',
	  'id_ticket_template' => '1',
	  'e_currency_id' => '1',
	  'payment_method' => '7',
	  'paypal_account' => '',
	  'tax_ref_hst' => '',
	  'tax_ref_pst' => '',
	  'tax_other_name' => '',
	  'tax_other_percentage' => '',
	  'tax_ref_other' => '',
	  'ticket_type' => 'open',
	  'cat_all' => 
	  array (
	    0 => '3',
	    1 => '2',
	    2 => '1',
	    3 => '0',
	  ),
  			
	  'cat_3_type' => 'table',
	  'cat_3_name' => 'Table [2-4]',
	  'cat_3_description' => 'Table with 4 seats. Min 2 tickets',
	  'cat_3_capa' => '12', //nb of tables
	  'cat_3_over' => '0',
	  'cat_3_tcapa' => '4', //seats per table
	  'cat_3_price' => '100.00', //full table price
	  'cat_3_single_ticket' => 'true',
	  'cat_3_ticket_price' => '25.00', //single seat price
	  'cat_3_seat_name' => 'seat 2-4',
	  'cat_3_seat_desc' => '',
  			
	  'cat_2_type' => 'table',
	  'cat_2_name' => 'Table[4-6]',
	  'cat_2_description' => 'Buy between 4 and 6 seats.',
	  'cat_2_capa' => '1',
	  'cat_2_over' => '0',
	  'cat_2_tcapa' => '6',
	  'cat_2_price' => '180.00',
	  'cat_2_single_ticket' => 'true',
	  'cat_2_ticket_price' => '30.00',
	  'cat_2_seat_name' => 'seats 4-6',
	  'cat_2_seat_desc' => '',
  			
	  'cat_1_type' => 'table',
	  'cat_1_name' => 'Table[4-8]',
	  'cat_1_description' => 'Buy between 4 or 8 seats',
	  'cat_1_capa' => '1',
	  'cat_1_over' => '0',
	  'cat_1_tcapa' => '8',
	  'cat_1_price' => '240.00',
	  'cat_1_single_ticket' => 'true',
	  'cat_1_ticket_price' => '30.00',
	  'cat_1_seat_name' => 'seats 4-8',
	  'cat_1_seat_desc' => '',
  			
	  'cat_0_type' => 'table',
	  'cat_0_name' => 'Table[6-10]',
	  'cat_0_description' => 'Buy between 6 or 10 seats',
	  'cat_0_capa' => '2',
	  'cat_0_over' => '0',
	  'cat_0_tcapa' => '10',
	  'cat_0_price' => '300.00',
	  'cat_0_single_ticket' => 'true',
	  'cat_0_ticket_price' => '30.00',
	  'cat_0_seat_name' => 'seats 6-10',
	  'cat_0_seat_desc' => '',
  			
	  'create' => 'do',
	);
  }
  
  protected function purchase_4_8_request(){
      return $this->purchase_request( array (
  
      'total' => 'CAD 240.00',
      382 => '0',
      383 => '0',
      384 => '0',
      385 => '0',
      386 => '0',
      387 => '0',
      388 => '1',
      389 => '0',
      'table' => 
      array (
        0 => '388-3-1',
          )
        )
       );
  }
  
  //we pick up table 4 from the map, just 4 seats
  protected function purchase_4_6_request(){
      return $this->purchase_request( array (
  'total' => 'CAD 120.00',
  382 => '0',
  383 => '4',
  384 => 'undefined',
  385 => '0',
  386 => '0',
  387 => '0',
  388 => 'undefined',
  389 => '0',
  'table' => 
  array (
    0 => '383-4-4',
  ),
  )
      );
  }
  
  protected function purchase_4_6_remainder(){
      return $this->purchase_request( array (
  'total' => 'CAD 60.00',
  382 => '0',

  383 => '2',
  384 => 'undefined',
  385 => '0',
  386 => '0',
  387 => 'undefined',
  388 => 'undefined',
  389 => '0',
              )
);
  }
  
  //use this to simulate the test of purchasing the same 2_4 table (the one called '13') that was previously purchased. This should fail
  protected function purchase_2_4_remainder(){
      return $this->purchase_request(array(
          'total' => 'CAD 50.00',
          382 => '2',
          383 => '0',
          384 => '0',
          385 => '0',
          386 => '0',
          387 => '0',
          388 => '0',
          389 => '0',
          'table' =>
              array (
                      0 => '382-13-2',
              ),              
          ));
  }
  
  protected function purchase_request($data, $extra=array()){
      $base = array (
  'reg_new_username' => '',
  'reg_confirm_username' => '',
  'reg_new_password' => '',
  'reg_confirm_password' => '',
  'reg_language_id' => '',
  'reg_name' => '',
  'reg_home_phone' => '',
  'reg_phone' => '',
  'reg_l_street' => '',
  'reg_l_country_id' => '',
  'reg_l_state' => '',
  'reg_l_city' => '',
  'reg_l_zipcode' => '',
  'reg_l_street2' => '',
  'reg_l_state_id' => '',
              
  'user_id' => 'foo',
              
  'cat_list' =>
  array (
          0 => '382',
          1 => '383',
          2 => '384',
          3 => '385',
          4 => '386',
          5 => '387',
          6 => '388',
          7 => '389',
  ),              
              
  'cc_holder_name' => 'John Smith',
  'cc_number' => '5301250070000050',
  'cc_ccv' => '123',
  'cc_month' => '01',
  'cc_year' => '2030',
  'bil_name' => 'Calle 1',
  'bil_city' => 'Quebec',
  'bil_state' => 'Quebec',
  'bil_country' => 'Canada',
  'bil_zipcode' => 'BB',
  'mailing_list' => 'yes'
    );
      return $data + array_merge($base, $extra);
  }
  
  protected function purchase_register(){
      return array (
  'reg_new_username' => 'baz@blah.com',
  'reg_confirm_username' => 'baz@blah.com',
  'reg_new_password' => '123456',
  'reg_confirm_password' => '123456',
  'reg_language_id' => 'en',
  'reg_name' => 'Some baz',
  'reg_home_phone' => '123456',
  'reg_phone' => '',
  'reg_l_street' => 'Calle 1',
  'reg_l_country_id' => '124',
  'reg_l_state' => '',
  'reg_l_city' => 'Quebec',
  'reg_l_zipcode' => '75211',
  'reg_l_street2' => '',
  'reg_l_state_id' => '4',
  'total' => 'CAD 50.00',
  382 => '2',
  'cat_list' => 
  array (
    0 => '382',
    1 => '383',
    2 => '384',
    3 => '385',
    4 => '386',
    5 => '387',
    6 => '388',
    7 => '389',
  ),
  383 => '0',
  384 => 'undefined',
  385 => '0',
  386 => '0',
  387 => 'undefined',
  388 => 'undefined',
  389 => '0',
  'table' => 
  array (
    0 => '382-13-2',
  ),
  'cc_holder_name' => 'sdf asdf',
  'cc_number' => '5301250070000050',
  'cc_ccv' => '12345',
  'cc_month' => '01',
  'cc_year' => '2026',
  'bil_name' => 'Calle 1',
  'bil_city' => 'Quebec',
  'bil_state' => 'Alberta',
  'bil_country' => '124',
  'bil_zipcode' => '75211',
);
  }

  protected function purchase_6_10($table_name=1, $nb_seats=7){
      return $this->purchase_request(array(
              'total' => 'CAD ' . ($nb_seats * 30.00) ,
              382 => '0',
              383 => '0',
              384 => '0',
              385 => $nb_seats,// '7',
              386 => '0',
              387 => '0',
              388 => '0',
              389 => '0',
              'table' =>
              array (
                      0 => '385-' . $table_name . '-' . $nb_seats,
                  ),
              ));
  }
  
  //At: Anonymous ticket
  protected function purchase_6_10_At($nb=6){
      return $this->purchase_request(array(
              'total' => 'CAD ' . ($nb*30.00), //'CAD 180.00',
              382 => '0',
              383 => '0',
              384 => '0',
              385 => $nb,
              386 => '0',
              387 => '0',
              388 => '0',
              389 => 'undefined',
              ));
  }
  
  //protected function purchase_6_20_At
  protected function purchase_6_10_ATables($nb = 1){
      return $this->purchase_request(array(
              'total' => 'CAD ' . $nb*300,
              382 => '0',
              383 => '0',
              384 => '0',
              385 => '0',
              386 => '0',
              387 => '0',
              388 => '0',
              389 => $nb, //the full tables
              )); 
  }
  
  protected function purchase_two_tables(){
      return $this->purchase_request(array(
              'total' => 'CAD 125.00',
              382 => '5',
              383 => '0',
              384 => '0',
              385 => '0',
              386 => '0',
              387 => 'undefined',
              388 => '0',
              389 => 'undefined',
              'table' => 
              array (
                0 => '382-6-3',
                1 => '382-8-2',
              ),
              ));
  }
  
  protected function purchase_two_DT_tables(){
      return $this->purchase_request(array(
              'total' => 'CAD 150.00',
              382 => '2',
              383 => '0',
              384 => '0',
              385 => '0',
              386 => '1',
              387 => '0',
              388 => '0',
              389 => '0',
              'table' => 
              array (
                0 => '382-5-2',
                1 => '386-12-1',
              ),
      ));
  }
  
  protected function purchase_bug01(){
      return $this->purchase_request(array(
                'total' => 'CAD 300.00',
                  382 => '0',
                  383 => '0',
                  384 => '4',
                  385 => '0',
                  386 => '0',
                  387 => '1',
                  388 => '0',
                  389 => '0',
                  'table' => 
                  array (
                    0 => '384-3-4',
                    1 => '387-4-1',
                  ),
      ));
  }
  
  protected function purchase_2_4_ATable(){
      return $this->purchase_request(array(
                'total' => 'CAD 100.00',
                  382 => '0',
                  383 => '0',
                  384 => '0',
                  385 => '0',
                  386 => '1',
                  387 => '0',
                  388 => '0',
                  389 => '0',
      ));
  }
  
  protected function purchase_2_4_At($nb=1){
      return $this->purchase_request(array(
              'total' => 'CAD ' . (25*$nb),
              382 => $nb,
              383 => '0',
              384 => '0',
              385 => '0',
              386 => '0',
              387 => '0',
              388 => '0',
              389 => '0',
      ));
  }
  
  //therea are only 2 tables
  protected function bug04(){
      return $this->purchase_request(array(
              'total' => 'CAD 900.00',
              382 => '0',
              383 => '0',
              384 => '0',
              385 => '0',
              386 => '0',
              387 => '0',
              388 => '0',
              389 => '3',
      ));      
  }
  
  
  
  
}

class MonerisHandledAssigSeatingController extends \controller\Assignseating{
    function completeTransaction($txn_id){
        //do nothgin
    }
} 