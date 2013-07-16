<?php


namespace controller;
use tool\TableRemover;

use tool\TableAssigner;

use model\Categoriesmanager;

use \WebUser, \Utils;
class TableassignmentTest extends \DatabaseBaseTest{
  
  
  function testAssign(){
    //our assign fixture
    $this->clearAll();
    
    $this->db->beginTransaction();
    
    //A merchant creates some event with 3 categories. (Basic->Open, Premium->Table As seats, VIP -> Table )
    $seller = $this->createUser('seller');
    
    $client = new WebUser($this->db);
    $client->login($seller->username);
    $_POST = $this->createSonyData();
    
    Utils::clearLog();
    
    $cont = new Newevent(); //all the logic in the constructor haha
    
    $id = $this->getLastEventId();
    $this->changeEventId($id, 'sony');
    
    //$this->db->commit(); return;
    
    $this->insertTables(); //risky, hardcoded category ids
    
    
    
    $this->clearRequest();
    
    $BASIC = 5;
    $PREMIUM_SEAT = 2;
    $PREMIUM_TABLE = 4;
    $VIP = 3;
    
    //Some buyers activity. This will create tickets to assingn
    $foo = $this->createUser('john');
    $this->buyTickets('john', $BASIC, 3); // 3 BASIC tickets 
    
    $bar = $this->createUser('mary');
    $this->buyTickets('mary', $BASIC, 1); // 1 BASIC ticket
    
    $bar = $this->createUser('cuero');
    $this->buyTickets('cuero', $PREMIUM_SEAT, 2); // - 2 PREMIUM seats
    
    $this->createUser('rolindo');
    $this->buyTickets('rolindo', $PREMIUM_SEAT, 2); // - 2 PREMIUM seats
    
    $this->createUser('tiban');
    $this->buyTickets('tiban', $PREMIUM_SEAT, 1); // - 1 PREMIUM SEAT
    
    $foo = $this->createUser('fabi'); //figuretti
    $buyer = new WebUser($this->db);
    $buyer->login($foo->username);
    $buyer->addToCart($PREMIUM_TABLE, 2);
    $buyer->addToCart($BASIC, 1); //1 basic ticket
    $buyer->addToCart($PREMIUM_SEAT, 1); //- 1 PREMIUM just to isolate somebody
    $this->completeTransaction($buyer->placeOrder());
    
    
    $this->createUser('obama');
    $this->buyTickets('obama', $VIP, 1); //una mesa - A Full VIP table
    
    
    //Add another event just to fill the select
    $evt = $this->createEvent('Birthday Party', 'seller', $this->createLocation('puerta'));
    $cat = $this->createCategory('Sala', $evt->id, 11.25, 60);
    
    $this->db->commit();
    // *******************************************************************************************************
    Utils::clearLog();
    
    
    //lets inspect the category 2
    $cat = Categoriesmanager::loadCategoryFromID(2);
    //Utils::dump($cat);
    $this->assertEquals(3, $cat->category_seats);
    $this->assertTrue($cat->is_table);
    $this->assertEquals(4, $cat->single_values['cat_id']);
    
    $ajax = new \ajax\Tables();
    $this->assertEquals(2, count($ajax->findSoldTables($PREMIUM_SEAT)));
    
    $this->assertEquals(6, count($ajax->findSoldTicketsAsSeat($PREMIUM_SEAT)));
    
    //For VIP we should find 2 tables
    Utils::clearLog();
    $tables = $ajax->findSoldTables(/*$VIP*/1); //unfortunatelly the table_def are associated with the null category
    $this->assertEquals(1, count($tables));
    //return;
    //let's try to assign a table
    //return;
    
    $form = new TableAssigner('seller');
    $form->process();
    $this->assertFalse($form->success());
    
    $form = new TableAssigner('seller');
    $data = $this->okTableData();
    unset($data['t_0']);
    $form->setData($data);
    $form->process();
    $this->assertFalse($form->success());

    //This does the insert
    $form = new TableAssigner('seller');
    $data = $this->okTableData();
    $form->setData($data);
    $form->process();
    $this->assertTrue($form->success());
    $this->assertEquals(3, $this->db->get_one("SELECT COUNT(*) FROM ticket_table WHERE as_table=1") );
    
    //Fail if inserting the same tickets
    $form = new TableAssigner('seller');
    $data = $this->okTableData();
    $form->setData($data);
    $form->process();
    $this->assertFalse($form->success());
    
    //Fail if inserting new tickets in filled table
    $form = new TableAssigner('seller');
    $data = $this->okTableData();
    $data['t_0'] = array(12,13,14); //these are not in the db
    $form->setData($data);
    $form->process();
    $this->assertFalse($form->success());
    
    //Assign seats to table 2
    $form = new TableAssigner('seller');
    $data = $this->okSeatData();
    $form->setData($data);
    $form->process();
    $this->assertTrue($form->success());
    $this->assertEquals(2, $this->db->get_one("SELECT COUNT(*) FROM ticket_table WHERE as_table=0") );
    $this->assertRows(5, 'ticket_table');
    
    
    // ------------------- DELETE -----------------------
    
    $form = new TableRemover('seller');
    $data = $this->okDeleteData();
    $data['tickets'] = array(10);
    $form->setData($data);
    $form->process();
    $this->assertFalse($form->success()); //it must fail because the ticket belongs to a table purchase
    
    //but a full table should success
    $form = new TableRemover('seller');
    $data = $this->okDeleteData();
    $form->setData($data);
    $form->process();
    $this->assertTrue($form->success());
    $this->assertRows(2, 'ticket_table'); //table is gone, seats remain
    
    //A single ticket is fine too
    $form = new TableRemover('seller');
    $data = $this->okDeleteSeat();
    $form->setData($data);
    $form->process();
    $this->assertTrue($form->success());
    $this->assertRows(1, 'ticket_table');
    
    // --------- are pay at the door tickets fine too?, yes, they are listed
    Utils::clearLog();
    $this->clearRequest();
    $buyer = new WebUser($this->db);
    $buyer->login('tiban@blah.com');
    $buyer->addToCart($BASIC, 4); //1 basic ticket
    $txn_id = $buyer->placeOrder();
    $buyer->payByCash($txn_id);
    
   
    
    //$this->creditCardTest();
  }
  
  function creditCardTest(){
    $this->clearRequest();
    
    
    $buyer = new WebUser($this->db);
    $buyer->login('cuero@blah.com');
    $buyer->addToCart(/*$VIP*/3, 1);
    
    Utils::clearLog();
    $req = $this->getCCPurchaseData();
    $req['cc_type'] = 'MasterCard';
    $_POST = $req;
    $cnt = new Ccpayment(); //this one fails
    
    Utils::log( "\n\n ------ ---------------------------------------- NEW RUN ----------------------------------------------- ");
    $this->clearRequest();
    $_POST = $this->getCCPurchaseData();
    $cnt = new Ccpayment();
    
  }
  
    
  function createSonyData(){
    $data = array(
      'MAX_FILE_SIZE' => 3000000
    , 'is_logged_in' => 1
    , 'copy_event' => 'aaa'
    , 'e_name' => 'SONY'
    , 'e_date_from' => '2012-05-15'
    , 'e_time_from' => ''
    , 'e_date_to' => ''
    , 'e_time_to' => ''
    , 'e_description' => '<p>asd</p>'
    , 'reminder_email' => ''
    , 'reminder_sms' => ''
    , 'c_id' => 1
    , 'l_latitude' => '52.9399159'
    , 'l_longitude' => '-73.5491361'
    , 'l_id' => 1
    , 'dialog_video_title' =>'' 
    , 'dialog_video_content' =>'' 
    , 'id_ticket_template' => 1
    , 'e_currency_id' => 1
    , 'payment_method' => 3
    , 'paypal_account' => ''
    , 'tax_ref_hst' => ''
    , 'tax_ref_pst' => ''
    , 'tax_other_name' =>'' 
    , 'tax_other_percentage' => 0
    , 'tax_ref_other' => ''
    , 'ticket_type' => 'table'
    , 'cat_all' => array
        (
              '0' => '2'
            , '1' => '1'
            , '2' => '0'
        )

    , 'cat_2_type' => 'table'
    , 'cat_2_name' => 'VIP'
    , 'cat_2_description' => 'This is the VIP room'
    , 'cat_2_capa' => 2
    , 'cat_2_over' => 0
    , 'cat_2_tcapa' => 3
    , 'cat_2_price' => 300.00
    , 'cat_2_ticket_price' => 0.00
    , 'cat_2_seat_name' => ''
    , 'cat_2_seat_desc' =>'' 
    
    , 'cat_1_type' => 'table'
    , 'cat_1_name' => 'Premium'
    , 'cat_1_description' => 'The premium tables'
    , 'cat_1_capa' => 4 //nb of tables
    , 'cat_1_over' => 0
    , 'cat_1_tcapa' => 3 //seats per table
    , 'cat_1_price' => '100.00'
    , 'cat_1_single_ticket' => 'true'
    , 'cat_1_ticket_price' => '25.00'
    , 'cat_1_seat_name' => 'Premium seat'
    , 'cat_1_seat_desc' => 'A premium seat'
    
    , 'cat_0_type' => 'open'
    , 'cat_0_name' => 'Basic'
    , 'cat_0_description' => 'The basic category'
    , 'cat_0_capa' => 10
    , 'cat_0_over' => 0
    , 'cat_0_price' => 15.25
    , 'create' => 'do'
    );
    return $data;
  }
  
  function insertTables(){
    /*$sql = "INSERT INTO `room_designer` (`id`, `name`, `pos_left`, `pos_top`, `category`, `seats_number`, `chair_chosen`, `counter`, `event_id`) VALUES
(1, 'Basic ?', 861.5, 197.5, 5, 10, 3, 1, 'sony'),
(2, 'Premium 2', 788.5, 281.5, 2, 4, 2, 2, 'sony'),
(3, 'Premium 3', 922.5, 281.5, 2, 4, 2, 3, 'sony'),
(4, 'Premium 4', 1018.5, 279.5, 2, 4, 2, 4, 'sony'),
(5, 'Premium 1', 692.5, 279.5, 2, 4, 2, 5, 'sony'),
(6, 'Vip 1', 821.5, 395.5, 1, 3, 4, 6, 'sony'),
(7, 'Vip 2', 921.5, 397.5, 1, 3, 4, 7, 'sony');
    ";*/
    $sql = "
INSERT INTO `room_designer` (`id`, `name`, `pos_left`, `pos_top`, `category`, `seats_number`, `chair_chosen`, `counter`, `event_id`) VALUES
(1, 'Basic?', 857.5, 200.5, 5, 10, 2, 2, 'sony'),
(2, 'Premium 3', 957.5, 282.5, 2, 3, 3, 3, 'sony'),
(3, 'Premium 4', 1075.5, 281.5, 2, 3, 3, 4, 'sony'),
(4, 'Premium 2', 831.5, 284.5, 2, 3, 3, 5, 'sony'),
(5, 'Premium 1', 684.5, 284.5, 2, 3, 3, 6, 'sony'),
(6, 'VIP 2', 961.5, 419.5, 1, 3, 2, 8, 'sony'),
(7, 'VIP 1', 774.5, 421.5, 1, 3, 2, 9, 'sony');
    ";
    $this->db->executeBlock($sql);
  }

  function okTableData(){
    return array(
      	'event_id' => 'sony'
      , 'counter' => '4'
      , 'table' => 't_0'
      , 't_0' => array(10,15,11) //only this group will be validated
      , 't_1' => array(12,13,14)
      , 'tickets' => array(99,100,101)
    
    );
  }
  
  function okSeatData(){
    return array(
      	'event_id' => 'sony'
      , 'counter' => '5'
      , 'table' => 'tickets'
      , 't_0' => array(10,13,14) //meh
      , 'tickets' => array(5,6)//only this group will be validated
    
    );
  }
  
  function okDeleteData(){
    return array(
      	'event_id' => 'sony'
      , 'counter' => '4'
      , 'tickets' => array(10,15,11) 
    );
  }
  
  function okDeleteSeat(){
    return array(
      	'event_id' => 'sony'
      , 'counter' => '5'
      , 'tickets' => array(6) 
    );
  }
  
}


