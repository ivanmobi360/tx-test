<?php
/**
 * Simple wrapper to be able to inspect and test the website/assignXMLconstructor.php test
 * 
 */
class AssignXMLconstructorTest extends DatabaseBaseTest{
  
  function setUp(){
    parent::setUp();
    
    $this->db->Query("TRUNCATE TABLE ticket_pool");
    
  }

  //simple test runner
  function testRun(){
    $this->create_Stranger_10_Tickets();
  }
  
  function test_THE_FIRM(){
  	
	$this->clearAll();
	$seller = $this->createUser('seller');
	$foo = $this->createUser('foo');

	$evt = $this->createEvent('Strangers 10', $seller->id, $this->createLocation()->id, $this->dateAt("+5 day"));
	$this->setEventId($evt, self::STRANGERS_IN_THE_NIGHT_10_ID);
	
	$cat = $this->createCategory('Normal', $evt->id, 45);
	$this->setCategoryId($cat, 380);
	
	$cat = $this->createCategory('VIP', $evt->id, 45);
	$this->setCategoryId($cat, 381);
	
	//fill up ticket pool
	$this->create_Stranger_10_Tickets();
	
	$event_id = $this->createTheFirm($seller);
	
	//fix some malformed data for now
	$this->db->update('category', array('tax_inc'=>1, 'fee_inc'=>1, 'cc_fee_inc'=>1), "event_id=?", $event_id);
	$this->db->update('category', array('hidden'=>1), "event_id=? AND category_id IS NULL", $event_id);
	
  }
  
  function create_Stranger_10_Tickets(){
  	//this really heavily on some external file that changes every year. don't expect consistent results. Last time it generated 2060 tickets/rows
  	require_once PATH_INCLUDES .'../website/assignXMLconstructor.php';
  }
  
  protected function createTheFirm($seller){
  	//we need to log in to create
  	$client = new WebUser($this->db);
  	$client->login($seller->username);
  	
  	$_POST = $this->get_THE_FIRM_create_data();
  	
  	Utils::clearLog();
  	
  	$cont = new controller\Newevent(); //all the logic in the constructor haha
  	
  	$id = $this->getLastEventId();
  	$id = $this->changeEventId($id, 'thefirm1');
  	return $id;
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

  
}

