<?php
use ajax\Ccpay;
use tool\Session;
use model\Eventcontact;
use model\Users;
use model\Tickettransactionmanager;
use model\Tickettransaction;
use tool\Request;
use model\Events;use model\TicketBuilder;
use model\Locations;
use model\Categories;
use model\Transaction;

abstract class DatabaseBaseTest extends BaseTest{
  
  /* @var TestDatabase */
  public $db;
  
  protected $database_name = 'tixpro_demo';
  
  //payment methods id
  const PAYPAL = 1;
  const OUR_PAYPAL = 2;
  const OUR_CREDIT_CARD = 3;
  const PROCESING = 6;
  const MONERIS = 7;
  
  //apparently some code was written for this once in a lifetime event - Reserve Tickets, Move Seats
  const STRANGER_IN_THE_NIGHT_8_ID = 'a20f69f3';
  
  const STRANGERS_IN_THE_NIGHT_10_ID = '28d26a2d'; // Strangers in the Night 10
  
  /**
  * Generic coupon code used in 
  * @see CouponCodeHandlerTest
  */
  protected $coupon_code = 'some-code';
  
  protected $test_email = 'tester_buyer@test.com';
  protected $testUserid = '99';
  
  protected $merchantid = 'mg3v3nt5';
  
  protected $serial;
  
  public function setUp(){
    Utils::log("setUp");
    \Database::init(DB_HOSTNAME, $this->database_name, DB_USERNAME, DB_PASSWORD);
    $this->db = new TestDatabase();
    
    $this->resetSerial();
    Request::clear();
  }
  
  public function tearDown(){
    Utils::log("tearDown");
    /*$this->db->close();
    unset($GLOBALS['db']);*/
    unset($this->db);
    parent::tearDown();
  }
  
  protected function clearAll(){
  	
  	$tables = ['ticket_transaction', 'location', 'contact', 'user', 'category', 'ticket', 'event', 'event_contact', 'event_email',
'room_designer', 'ticket_table', 'error_track', 'processor_transactions', 'promocode', 'bo_user', 
  	'optimal_transactions', 'myvirtual_transactions', 'moneris_transactions',
'merchant_invoice', 'merchant_invoice_line', 'merchant_invoice_taxe', 'email_processor', 'banner',    ];

  	$this->clearTables($tables);
  	
    $this->db->Query("ALTER TABLE `ticket_transaction` AUTO_INCREMENT = 875000000000903;");
    $this->db->Query("ALTER TABLE `category` AUTO_INCREMENT = 330;");
    $this->db->Query("ALTER TABLE `ticket` AUTO_INCREMENT = 777;");
    
    /*
    $this->db->Query("TRUNCATE TABLE ticket_transaction");
    $this->db->Query("TRUNCATE TABLE location");
    $this->db->Query("TRUNCATE TABLE contact");
    $this->db->Query("TRUNCATE TABLE user");
    $this->db->Query("TRUNCATE TABLE category");
    $this->db->Query("TRUNCATE TABLE ticket");
    
    $this->db->Query("TRUNCATE TABLE event");
    $this->db->Query("TRUNCATE TABLE event_contact");
    $this->db->Query("TRUNCATE TABLE event_email");
    //mesas
    $this->db->Query("TRUNCATE TABLE room_designer");
    $this->db->Query("TRUNCATE TABLE ticket_table");
    
    
    $this->db->Query("TRUNCATE TABLE error_track");
    $this->db->Query("TRUNCATE TABLE processor_transactions");
    $this->db->Query("TRUNCATE TABLE promocode");
    
    $this->db->Query("TRUNCATE TABLE bo_user");
    
    $this->db->Query("TRUNCATE TABLE optimal_transactions");
    $this->db->Query("TRUNCATE TABLE myvirtual_transactions");
    $this->db->Query("TRUNCATE TABLE moneris_transactions");
    
    
    $this->db->Query("TRUNCATE TABLE merchant_invoice");
    $this->db->Query("TRUNCATE TABLE merchant_invoice_line");
    $this->db->Query("TRUNCATE TABLE merchant_invoice_taxe");
    
    $this->db->Query("TRUNCATE TABLE email_processor");
    
    
    $this->db->Query("TRUNCATE TABLE banner");*/
    
    $this->db->Query(file_get_contents(__DIR__ . "/fixture/banner.sql"));
    $this->clearReminders();
    $this->resetFees();
    $this->insertJohnDoe();
    
  }
  
  function clearTables($tables){
  	$tables = array_map(function($x){return trim($x);}, $tables);
  	
  	$sql = '';
  	foreach ($tables as $table){
  		$cnt = (int) $this->db->get_one("SELECT COUNT(*) FROM $table");
  		Utils::log("cnt of $table is $cnt");
  		if ( $cnt >0 ){
  			//Utils::log("will truncate $table");
  			$sql .= "TRUNCATE TABLE $table;\n";
  			}
  		}
  	$sql  = trim($sql);
  	if (!empty($sql)){
  		$this->db->executeBlock($sql);
  	}
  }
  
  function resetFees(){
      $this->db->executeBlock(file_get_contents(__DIR__ . "/fixture/fee-reset.sql"));
  }
  
  
  
  function getCCPurchaseData(){
    return array(
        'sent' => 1
      , 'cc_num' => '4715320629000001'
      , 'cc_name_on_card' => 'JOHN DOE'
      , 'exp_month' => '08'
      , 'exp_year' => date('Y', strtotime('+5 year'))
      , 'cc_cvd' => '1234'
      , 'cc_type' => 'Visa'
      , 'street' => 'Calle 1'
      , 'country' => '124'
      , 'city' => 'Ontario'
      , 'state' => 'QC'
      , 'zipcode' => 'HR54'
      , 'username' => 'foo@blah.com'
      
      
    );
  }
  
  protected function insertJohnDoe(){
      $sql = "INSERT INTO `contact` (`id`, `user_id`, `name`, `email`, `phone`, `home_phone`, `company_name`, `position`) VALUES
(1, 'johndoe1', 'POS Sale', 'johndoe1@tixpro.com', '5145555555', '5145555555', NULL, NULL);
INSERT INTO `location` (`id`, `user_id`, `name`, `street`, `street2`, `country_id`, `state_id`, `state`, `city`, `zipcode`, `longitude`, `latitude`) VALUES (1, 'johndoe1', 'My location', '8600 Decarie', 'Suite 100', 124, 2, 'Quebec', 'Montreal', 'H4P2N2', -73.6641, 45.5013);
              ";
      $this->db->executeBlock($sql);
    $this->db->Query("INSERT INTO `user` (`id`, `username`, `password`, `created_at`, `active`, `contact_id`, `location_id`, `billing_location_id`, `language_id`, `paypal_account`, `tax_ref_hst`, `tax_ref_gst`, `tax_ref_pst`, `tax_ref_other`, `tax_other_name`, `tax_other_percentage`, `fee_id`, `cc_fee_id`, `code`) VALUES ('johndoe1', 'johndoe1@blah.com', 'e10adc3949ba59abbe56e057f20f883e', '2012-03-23 12:38:59', '1', '1', '1', NULL, 'en', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '');");
  }
  

  
  protected function clearTestTable(){
    $this->db->Query("TRUNCATE TABLE test");
  }
  
  protected function clearReminders(){
    $this->db->Query("TRUNCATE TABLE reminder");
    $this->db->Query("TRUNCATE TABLE reminder_sent");
  }
  
  protected function insertTestRows($total){
    $this->db->beginTransaction();
    for($i = 1; $i<=$total; $i++){
      $this->db->insert('test', array('title'=>"foo$i", 'content'=>"Some Content $i", 'hits'=>10+$i), true);
     }
    $this->db->commit();
  }
  
  function createModuleFee($name, $fixed, $percentage, $fee_max, $module_id, $is_default=1){
      return $this->createSpecificFee($name, $fixed, $percentage, $fee_max, $module_id);
  }
  
  //function createSpecificFee($item_id, $item_type, $fixed, $percentage, $fee_max, $module_id ){
  function createSpecificFee($name, $fixed, $percentage, $fee_max, $module_id, $user_id=null, $event_id=null, $category_id=null ){
      return \model\SpecificFee::create($name, $fixed, $percentage, $fee_max, $module_id, $user_id, $event_id, $category_id);
  }
  
  //they seem to be changed these values, so we can't hardcore them in the long term. let's try to pick them up from the db
  protected function currentGlobalFee(){
      return \model\Fee::getGlobalFee();
  }

  
 public function getCreateCustomerData(){
    return array(
                  'email' => $this->test_email
                , 'new_password' => '123456'
                , 'confirm_password' => '123456'
                ,	'name' => 'Some'
                , 'surname' => 'Customer'
                , 'street' => 'Some Street'
                , 'city' => 'Some city'
                , 'state' => 'Some State'
                , 'country' => 'Canada'
                , 'zipcode' => 'ab0000'
                
                , 'cellphone' => "1234567890"
                , 'profession' => 'some profession'
                , 'language' => 'en'
                , 'team_id' => '99'
                , 'age' => 18
                , 'promcode' => 'foobarbaz'
                , 'position' => 'QTT'
                
                //??
                , 'company_id' => 'c0mp4ny1'
                , 'team_name' => 'blah'
                
                );
  }
  
  public function clearCustomers(){
    $this->db->Query("TRUNCATE TABLE customer");
    $this->db->Query("TRUNCATE TABLE master_cc");
  }
  
  public function clearPromoCodeTable(){
    $this->db->Query("TRUNCATE TABLE promo_codes");
  }
  
  
  protected function clearOrders(){
    $this->db->Query("TRUNCATE table `order`");
    $this->db->Query("TRUNCATE table `order_line`");
  }
  
  protected function clearTickets(){
    $this->db->Query("TRUNCATE table `ticket`");
  }
  
  protected function insertCustomer(){
    $this->db->Query("INSERT INTO `customer` (`id`, `username`, `rpassword`, `name`, `surname`, `street`, `city`, `state`, `country`, `zipcode`, `cellphone`, `profession`, `language`, `team_id`, `age`, `promcode`, `created_at`, `deleted`, `locked`, `active`) VALUES
(NULL, '$this->test_email', 'e10adc3949ba59abbe56e057f20f883e', 'Bill Gates', 'Dood', 'Pantalla', 'Somewhere2', 'Salta', 'Canada', '123456', '1234567890', 'Arquero', 'es', 98, 99, '', '2011-07-06 12:26:35', 0, 0, 1);
    ");
    return $this->db->insert_id();
  }
  // *********************************************************************************
  
 public function getCreditCardInsertData(){
        return array( 
                  //Credit Card data
                    'fname' => 'JOHN DOE'
                  , 'ccnum' => '4111111111111111'
                  , 'month' => 12
                  , 'year' => date('Y', strtotime("+ 1 year"))
                  , 'ccname' => 'visa'
                  , 'ccidnumber' => '111' //cvvv2, cvc2 or cid code 
                   );
  }
  
  //Credit card helpers
  protected function insertCustomerAndPartialCreditCard(){
    $this->db->Query("INSERT INTO `customer` (`id`, `username`, `rpassword`, `name`, `surname`, `street`, `city`, `state`, `country`, `zipcode`, `cellphone`, `profession`, `language`, `team_id`, `age`, `promcode`, `created_at`, `deleted`, `locked`, `active`) VALUES
(NULL, '$this->test_email', 'e10adc3949ba59abbe56e057f20f883e', 'Bill Gates', 'Dood', 'Pantalla', 'Somewhere2', 'Salta', 'Canada', '123456', '1234567890', 'Arquero', 'es', 98, 99, '', '2011-07-06 12:26:35', 0, 0, 1);
    ");
      
    //and partial credit card - no psiAccount at the moment
    $this->db->Query("INSERT INTO `master_cc` (`id`, `customer_id`, `psigate_id`, `psi_serial_no`, `holder`, `number`, `exp_month`, `exp_year`, `type`, `created_at`, `updated_at`, `ip`, `status`, `deleted`, `verified`) VALUES
(NULL, '{$this->testUserid}', '', '', 'CREDIT CARD TESTER', '', '12', '11', 'visa', '2010-12-23 10:19:55', '2010-12-23 10:19:55', '0.0.0.0', '1', 0, 0);");
    $this->ccid = $this->db->insert_id();
  }

  protected function createEvent($name, $user_id, $location_id, $date_from=false, $time_from='', $date_to='', $time_to=''){
    if(empty($location_id)){ throw new Exception(__METHOD__ . " location id is required");}
    $event = new Events();
    $event->name = $name;
    
    $event->date_from = $date_from?:date('Y-m-d H:i:s');
    $event->time_from = $time_from;
    
    $event->user_id = $user_id;
    $event->location_id = $location_id;
    
    $event->date_to = $date_to;
    $event->time_to = $time_to;
    $event->active=1;
    
    //cant be null :/
    $event->description = 'blah';
    
    $event->currency_id = 1;// 7;
    $event->private = 0;
    
    $event->ticket_template_id = '1';
    
    $event->payment_method_id = self::PAYPAL;// 1; //???
    $event->has_tax = 1;
    $event->fee_id = 1; //magic??
    
    $event->event_theme_id = 1;
    
    $event->insert();
    
    //contact?
    $user = new \model\Users($user_id);
    $ec = new \model\Eventcontact($event->id, $user->contact_id);
    $ec->insert();
    
    //further flag that contact with userid?
    $this->db->update('contact', array('user_id'=>$user_id), 'id=?', $user->contact_id);
    $this->db->update('location', array('user_id'=>$user_id), 'id=?', $location_id);
    

    return $event;
  }
  
  function setEventId($evt, $new_event_id){
    $this->changeEventId($evt->id, $new_event_id);
    $evt->id = $new_event_id;
  }
  
  function setEventParams($evt, $data){
    $this->db->update('event', $data, "id=?", $evt->id);
    foreach ($data as $prop => $val ){
      $evt->$prop = $val;
    }
  }
  
  function setCategoryId($cat, $new_category_id){
    $this->db->update('category', array( 'id' => $new_category_id), "id=?", $cat->id);
    $cat->id = $new_category_id;
  }
  
  function setPaymentMethod($evt, $payment_method_id){
    $this->db->update('event', array( 'payment_method_id' => $payment_method_id ), "id=?", $evt->id);
  }
  
  function changeEventId($event_id, $new_event_id){
    $this->db->update('event', array( 'id' => $new_event_id ), "id=?", $event_id);
    $this->db->update('event_contact', array( 'event_id' => $new_event_id ), "event_id=?", $event_id);
    $this->db->update('event_email', array( 'event_id' => $new_event_id ), "event_id=?", $event_id);
    $this->db->update('category', array( 'event_id' => $new_event_id ), "event_id=?", $event_id);
    return $new_event_id;
  }
  
  function setEventPaymentMethodId($evt, $payment_method_id){
    $this->db->update('event', array('payment_method_id'=>$payment_method_id), " id=?", $evt->id);
  }
  
  protected function addUser($id, $name=false ){
    return $this->createUser($id, $name)->id;
  }
  
  function createUser($id, $name=false, $params=array()){
    $name = $name?$name:ucfirst($id);
    $location_id = $this->createLocation()->id;
    $contact_id = $this->createContact($name, "$name@gmail.com", rand(5000000000, 9000000000));
    
    $this->db->update('contact',  array('user_id' => $id), "id=?", $contact_id);
    $this->db->update('location',  array('user_id' => $id), "id=?", $location_id);
     
    $username = "$id@blah.com";
    $data = array( 'id'=> $id, 'username'=> $username , 'location_id' => $location_id, 'contact_id'=>$contact_id
        , 'password' => md5('123456')
        , 'code' => $id
    );
    
    if (strstr($id, 'seller')){
      $data['promoter'] = 1;
      //$data['tax_other_name'] = 'VAT'; //not sure if needed in tp
      //$data['tax_ref_other'] = 'b4rb4d0s';
    }
    
    
    $this->db->insert('user', array_merge($data, $params) );
    
    $user = new MockUser($this->db, $name);
    $user->id = $id;
    $user->location_id = $location_id;
    $user->contact_id = $contact_id;
    $user->username =$username ;
    
    return $user;
  }
  
  function setUserHomePhone($user, $home_phone){
      $this->db->update('contact', array('home_phone'=>$home_phone), "id=?", $user->contact_id);
  }
  
  protected function createContact($name, $email='', $phone=''){
    $this->db->insert('contact', array( 'name'=> $name, 'email'=> $email, 'phone'=> $phone
                                        , 'home_phone' => $phone //assignseating needs this 
                                      ));
    return $this->db->insert_id();
  }
  
  protected function createLocation($name='myLoc'){
    $o = new Locations();
    $o->name = $name;
    $o->street = 'Calle 1';
    $o->country_id = '124';
    $o->state = 'AOHA';
    $o->city = 'Quebec';
    $o->zipcode = 'BB';
    $o->state_id = 2 ; //hardcoded to quebec?
    $o->latitude = 45.30;
    $o->longitude = -73.35;
    $o->insert();
    return $o;
  }
  
  function createBanner($evt, $banner_type, $price_id, $approved=true){
    
    //Hmmmmmm
    \tool\Session::setUser($this->db->auto_array("SELECT * FROM user WHERE id=?", array($evt->user_id)));
    
    \tool\Banner::setInactive ($evt->id, $banner_type);
    \tool\Banner::insert ($evt->id, $banner_type, $price_id, $evt->getEndDateTime());
    
    if ($approved){
      \tool\Banner::setNotPendind($evt->id, $banner_type);  
    }
    
    return $this->db->insert_id();
    
  }
  
  protected function createTransaction($category_id, $user_id, $price_paid, $ticket_count, $txn_id, $completed = 1, $date_processed=false){
    //$date_processed = $date_processed? $date_processed: date('Y-m-d H:i:s');
    Utils::log(__METHOD__ . " date_processed: $date_processed");
    
    
    $date = new DateTime($date_processed);
    try{
      $date->setTimestamp(strtotime($date_processed));
    }catch (Exception $e){}
    
    $data = array( 'category_id'=>$category_id
                                          , 'user_id'=> $user_id
                                          , 'price_paid'=>$price_paid
                                          , 'currency_id' => 1
                                          , 'ticket_count' => $ticket_count
                                          , 'txn_id'=> $txn_id
                                          , 'completed' => $completed
                                          , 'date_processed' => $date->format('Y-m-d H:i:s')//  $date_processed 
                                          );

    $this->db->insert('ticket_transaction', $data );
    
    return $this->db->insert_id();
  }
  
  protected function flagAsPaid($txn_id, $payment_method_id=2, $returned='', $date_processed=false){

    //$payment_method_id = $payment_method_id ?: self::PAYPAL;
    
    $date_processed = $date_processed ? $date_processed: date('Y-m-d H:i:s');
    
    $date = new DateTime($date_processed);
    try{
      $date->setTimestamp(strtotime($date_processed));
    }catch (Exception $e){}
    
    $this->db->update('ticket_transaction', array('completed'=>1), " txn_id=?", $txn_id );
    
    if(is_numeric($txn_id)){
      $txn_id = $this->db->get_one("SELECT txn_id FROM ticket_transaction WHERE id=?", $txn_id);
    }
    
    $trans = Tickettransactionmanager::load($txn_id);
    
    $payment_method_id = $this->db->get_one("SELECT payment_method_id FROM event 
    																				Inner JOin category ON category.event_id = event.id
    																				Inner Join ticket_transaction ON ticket_transaction.category_id = category.id 
    																				WHERE ticket_transaction.txn_id=?", $txn_id);
    
    $data = array(  'txn_id'=>$txn_id
                  , 'payment_method_id' => $payment_method_id
                  , 'returned' => $returned
                  , 'amount' => $trans->getTotalAmount()
                  , 'userid' => $trans->getUserid()
                  , 'date' => $date->format('Y-m-d H:i:s')
                  );
    $this->db->insert('processor_transactions', $data );                                      
  }
  
  function buyTickets($user_id, $category_id, $quantity=1, $payment_method_id=false){
    
    //Inspect event
    $is_cc = self::OUR_CREDIT_CARD == $this->db->get_one("SELECT payment_method_id FROM event Inner Join category ON event.id=category.event_id AND category.id=?", $category_id);
    if ($is_cc){
      return $this->buyTicketsWithCC($user_id, $category_id, $quantity);
      return;
    }
    
    
    $user = new \model\Users($user_id);
    $client = new WebUser($this->db);
    $client->login($user->username);
    
    $client->addToCart($category_id, $quantity);
    $txn_id = $client->placeOrder();
    $this->completeTransaction($txn_id, $payment_method_id);
    
    //return the tickets created
    $tickets = $this->db->getAll("SELECT * FROM ticket
    LEFT JOIN ticket_transaction ON ticket_transaction.id=ticket.transaction_id
    AND ticket_transaction.txn_id=?", array($txn_id)
     
    );
    
    return $txn_id;// $tickets;
    
  }
  
  function buyTicketsWithCC($user_id, $cat_id, $quantity=1){
    Utils::log(__METHOD__ . " user_id: $user_id, cat id: $cat_id, qty: $quantity "); 
    $user = new \model\Users($user_id);
    
    //let's buy
    $client = new \WebUser($this->db);
    $client->login($user->username);
    
    $client->addToCart($cat_id, $quantity); //cart in session
    return $this->payCartByCreditCard($user_id, $client->getCart());
  }
  
  function payCartByCreditCard($user_id, $cart){
    $payer = new \Optimal\MockPaymentHandler($user_id);
    $payer->setData($this->getCCPurchaseData());
    $payer->setCart($cart);
    $payer->response = $this->getOptimalResponse('success.xml');
    $payer->process();

    $cart->clean();
    $_SESSION = array();
    return $payer->getTxnId();
  }
  
  function setDateOfTransaction($txn_id, $date){
    $this->db->update('ticket_transaction', array('date_processed'=> $date), "txn_id=?", $txn_id );
    $this->db->update('optimal_transactions', array('date_processed'=> $date), "txn_id=?", $txn_id );
    $sql = "UPDATE `ticket` JOIN ticket_transaction ON ticket_transaction.txn_id=? AND ticket.transaction_id=ticket_transaction.id  SET `date_creation` = ? ";
    $this->db->Query($sql, array($txn_id, $date));
  }
  
  function getOptimalResponse($filename){
    return file_get_contents(__DIR__ . "/Optimal/responses/" . $filename);
  }
  
  /**
   * @param $buyer_id It does need the buyer id
   */
  function doRefund($buyer_id, $txn_id){
    //now let's refund it
    $data = array('txnid'=>$txn_id);
    $ref = new \Optimal\MockRefundHandler($buyer_id); //refund is done by the merchant
    $ref->setData($data);
    $ref->response = $this->getOptimalResponse('refund_success_cancel.xml');
    $ref->process();
  }
  
  function buyCart($user_id, $cart, $payment_method_id, $date=false){
    
    //for now, to avoid instrospection, provie the payment_method_id
    if ($payment_method_id == self::OUR_CREDIT_CARD){
      $this->payCartByCreditCard($user_id, $cart);
      return;
    }
    
    
    //Utils::log(__METHOD__ . " date: $date");
    $txn_id = $this->nextSerial('TX');
    $price_paid = 0;
    foreach ($cart->items as $line){
      $category_id = $line->item_id;
      $quantity = $line->quantity;
      $price = $line->price;
      $price_paid = $quantity * $price;
      $this->createTransaction($category_id, $user_id, $price_paid , $quantity, $txn_id, 1, $date );
    }

    $this->completeTransaction($txn_id, $payment_method_id, '', $date);
  }
  
  function completeTransaction($txn_id, $payment_method_id=self::PAYPAL, $date=false){
    $this->flagAsPaid($txn_id, $payment_method_id, '', $date);
    $builder = new TestTicketBuilder();
    $builder->setPaid(1);
    $builder->createFromTransaction(Tickettransactionmanager::load($txn_id));
  }
  
  function createBoxoffice($name, $merchant_id, $options=array()){
      $form = new \Forms\BoxOffice();
      $data = array_merge(array( 'username'=> strtolower(str_replace(' ', '', $name))
              , 'name'=>$name
              , 'password'=>'123456'
              , 'user_id' => $merchant_id
  
              //for boxoffice, pretend there's at least an options setup
  
      )
              , $options);
      $form->setData($data);
      $form->process();
      if(!$form->success()){
          throw new Exception(__METHOD__ . " " . implode( "\n",  $form->getErrors()) );
      }
      return $form->getInsertedId();
  }
  
  
  
  
  protected function createCategory($name, $event_id, $price, $capacity=20, $overbooking=0, $params=false){
    $cat = new Categories();
    $cat->name = $name;
    $cat->event_id = $event_id;
    $cat->price = $price;
    //$cat->taxe_id = $taxe_id;
    
    $cat->capacity = $capacity;
    $cat->capacity_max = $capacity;
    $cat->capacity_min = 0;//?? $capacity;
    
    //$cat->sub_capacity = 1;
    $cat->overbooking = $overbooking;
    $cat->cc_fee_inc = 0;
    
    //$cat->tax_group = 2; //default?
    $cat->tax_inc = 0;
    $cat->fee_inc = 0;
    
    $cat->as_seats = '0';
    $cat->hidden = '0';
    
    $cat->category_id = 0;
    
    $cat->assign = '';//?
    $cat->order = '';//?
    
    $cat->sold_out = '0';
    
    $cat->insert();
    
    if ($params){
      $this->db->update('category', $params, "id=?", $cat->id);
      $cat = new Categories($cat->id);
    }
    
    //hack
    //$cat->user_id //it seems that now user_id is required
    
    return $cat;
  }
  
  protected function createTicket($user_id, $category_id, $name, $price_fee){
    $this->db->insert('ticket', array(  'user_id'=>$user_id
                                      , 'category_id' => $category_id
                                      , 'name' => $name
                                      , 'price_fee' => $price_fee
                                      , 'code' => 'CODE-'. $this->getSerial()
                                      ));
  }
  
  protected function createPromocode($code, $cat){
    $event_id = $cat->id;
    if($cat instanceof \model\Categories){
      $event_id = $cat->event_id;
    }
    
    
    $this->db->insert('promocode', array( 'code' => $code
                                      //, 'category_id' => $cat->id
                                      , 'event_id' => $event_id// $cat->event_id
                                      , 'reduction' => 1.5
                                      , 'reduction_type' => 'f' 
                                      , 'capacity' => 100
                                      , 'valid_from' => date('Y-m-d', strtotime("-1 month"))
                                      , 'valid_to' => date('Y-m-d', strtotime("+1 month"))
                                      ));
    $id = $this->db->insert_id();
    //$this->db->insert('promocode_category', array('promocode_id'=>$id, 'category_id'=>$cat->id));                                  
    return $id;                                  
  }
  
  protected function resetSerial(){
    $this->serial = rand(1000, 9999);;
  }
  
  protected function getSerial($pre=''){
    $n = ++$this->serial;
    return $pre . $n;
  }
  protected function nextSerial($pre=''){
    return $this->getSerial($pre);
  }
  
  protected function clearRequest(){
    Request::clear();
  }
  
  protected function login($user, $password='123456'){
    $resUser = \model\Usersmanager::login($user->username, $password);
    $resUser = \model\Usersmanager::exists($resUser['id']);
    \tool\Session::setUser($resUser);
  }
  
  protected function flagTransactionsAsCompleted(){
    $this->db->update('ticket_transaction', array("completed"=>1), " 1 ");
  }
  
   protected function createTickets($txn_id){
    
    //just in case, flag transaction as completed
    $this->db->update('ticket_transaction', array('completed'=>1), "txn_id=?", $txn_id);
    
    $bld = new TestTicketBuilder();
    $bld->setPaid(1);
    $bld->createFromTransaction(Tickettransactionmanager::load($txn_id));
  }
  
  protected function createReminder($event_id, $send_at, $type=\model\ReminderType::EMAIL, $content=''){
      $this->db->insert('reminder', array( 'event_id' => $event_id
                                         , 'send_at' => $send_at
                                         , 'type' => $type
                                         , 'content' => $content 
     ));
  }
  
  protected function assertRows($total, $table){
    $this->assertEquals($total, $this->db->get_one("SELECT COUNT(*) FROM $table" ));
  }
  
  function dateAt($offset){
    return date('Y-m-d H:i:s', strtotime($offset) );
  }
  
  function getLastEventId(){
    return $this->db->get_one("SELECT event_id FROM event_email ORDER BY id DESC ");
  }
  
  protected function getIpnString(){
    //paypal
    return "mc_gross=113.93&protection_eligibility=Eligible&address_status=confirmed&payer_id=J9YZSMMYVD3UQ&tax=0.00&address_street=1 Maire-Victorin&payment_date=10:07:39 Aug 05, 2011 PDT&payment_status=Completed&charset=windows-1252&address_zip=M5A 1E1&first_name=Test&mc_fee=3.60&address_country_code=CA&address_name=Test User&notify_version=3.2&custom=eJxLtDKyqi62srBSyi9KSS2Kz0xRsi4GiimZWYAYhoZWSiX5JYk58XmJuanFStaZVobWtQCzZxBa&payer_status=verified&business=acn_1312402113_biz@yahoo.com&address_country=Canada&address_city=Toronto&quantity=1&verify_sign=AFcWxV21C7fd0v3bYYYRCpSSRl31AxeUax.JFWs3tO6a5onB3CDeTRHv&payer_email=gates_1312402289_per@yahoo.com&txn_id=7LM02875RB145043G&payment_type=instant&last_name=User&address_state=Ontario&receiver_email=acn_1312402113_biz@yahoo.com&payment_fee=&receiver_id=CVYPQJ2YRD2JW&txn_type=web_accept&item_name=ACN-PURCHASE&mc_currency=CAD&item_number=68&residence_country=CA&test_ipn=1&handling_amount=0.00&transaction_subject=eJxLtDKyqi62srBSyi9KSS2Kz0xRsi4GiimZWYAYhoZWSiX5JYk58XmJuanFStaZVobWtQCzZxBa&payment_gross=&shipping=0.00&ipn_track_id=-GCFMBpohvqr6iVPhtY9OA";
  }
  
  function createPrintedTickets($nb, $evtid, $cat_id, $cat_name, $fee_fixed=0.6, $fee_percent=0){
  	$ajax = new \ajax\TicketPrinting();
  	$data = array(
  			'eventid' => array($evtid, ''),
  			'categoryId' => array($cat_id, ''),
  			'categoryName' => array($cat_name, ''),
  			'ticketAmount' => array($nb, ''),
  			'tixproFees' => array(1, ''),
  			'promocode' => array('', ''),
  			'tixpro_fee_fix' => $fee_fixed,
  			'tixpro_fee_percent'=> $fee_percent,
  			'preActivated' => '0'
  	);
  	$_POST = array('tickets' => serialize($data));
  	$ajax->Process();
  	Request::clear();
  }
  
  protected function getTicket($code){
  	return $this->db->auto_array("SELECT * FROM ticket WHERE code=?", $code);
  }
  
  
}




// **************************************************************************************

class MockUser{
  public $db, $id, $name, $location_id, $contact_id, $username;
  function __construct($db, $name){
    $this->db = $db;
    $this->name = $name;
  }

}

class TestTicketBuilder extends TicketBuilder{
  protected function sendTicketEmail($ticketid){
    Utils::log(__METHOD__ . " do nothing");
  }
}


// ************************ Operator *****************************************

class WebUser{
  public $db, $username, $id
  , $date = false //date override
  ;
  
  function __construct($db){
    $this->db = $db;
  }
  
  function login($username, $password='123456'){
    \model\Usersmanager::clear();
    //session_unset();
    $resUser = \model\Usersmanager::login($username, $password);
    $resUser = \model\Usersmanager::exists($resUser['id']);
    \tool\Session::setUser($resUser);
    
    $this->username = $username;
    $this->id = $resUser['id'];
    
  }
  
  function addToCart($category_id, $quantity, $promocode=''){
    $data = array( 'page'=>'Event', 'method'=>'add-cart', 'category_id'=> $category_id, 'quantity'=> $quantity, 'promocode'=> '' );
    $p = new ajax\Event();
    $p->setData($data);
    $p->addCart();
    //$p->Process();
    
    if ($p->res && 'failed' == Utils::getArrayParam('result', $p->res) ){
      throw new Exception(__METHOD__ . "failure : " . $p->res['msg']  );
    }
    
    Utils::log( __METHOD__ . " session so far: " .   print_r($_SESSION, true));
    
    $this->clearRequest();
    
    if(!empty($promocode)){
      $cat = new \model\Categories($category_id);
      $this->applyPromoCode($cat->event_id, $promocode);
    }
    
  }
  
  //Each row on the cart has a promocode input
  function applyPromoCode($event_id, $promocode){
    $_POST = array( 'page'=>'Cart', 'method'=>'verify-code', 'event_id'=> $event_id, 'code'=>$promocode);
    $p = new ajax\Cart();
    $p->Process();
    
    Utils::log(print_r($_SESSION, true));
    
    $this->clearRequest();
  }
  
  
  function placeOrder($gateway=false, $date=false){
    $gateway = $gateway? $gateway: 'paypal';
    $_POST = array( 'method'=>'cart-payment', 'page'=>'Cart', 'name_pay'=>$gateway);
    $p = new \ajax\Cart();
    $p->Process();
    $this->clearRequest();
    $res = $p->res;
    $txn_id = $res['txn_id'];
    //$txn_id = $this->db->get_one("SELECT txn_id FROM ticket_transaction ORDER BY id DESC LIMIT 1");
    if($date){
      $this->db->update('ticket_transaction', array('date_processed'=>$date), 'txn_id=?', $txn_id);
    }
    return $txn_id;
  }
  
  function payByCash($txn_id){
    
    $data = array(
      'txn_id' => $txn_id,
      'type_pay' => \model\DeliveryMethod::PAY_BY_CASH //'paybycash'
    );
    
    $_POST = $data;
    
    //Now see if controller reacts properly
    $cnt = new \controller\Payment();
    $this->clearRequest();  
  }
  
  function getCart(){
    $cart = new \tool\Cart();
    $cart->load();
    return $cart;
  }
  
  function posAddItem($category_id, $qty=1){
    
    $_POST = array( 'page'=>'Cart', 'method'=>'add-item', 'category_id'=> $category_id, 'quantity'=> $qty);
    $p = new ajax\Cart();
    $p->Process();
    
    Utils::log(print_r($_SESSION, true));
    
    $this->clearRequest();
  }
  
  function posPay(){
    $_POST = array( 'page'=>'Cart', 'method'=>'pos-pay');
    $p = new ajax\Cart();
    $p->Process();
    
    Utils::log(print_r($_SESSION, true));
    
    $this->clearRequest();
  }
  
  function posPayWithCC($params){
    //apparently it does a pos-currency first?
    $data = array( 'page'=>'Cart', 'method'=>'pos-currency' );
    $p = new ajax\Cart();
    $res = $p->posGetCurrency();
    $amount = $res['total'];
    $currency = $res['row'];
    
    
    //do the cc call
    $data = array_merge(array('amount'=>$amount, 'currency'=>$currency  ), $params);
    $ajax = new ajax\Ccpay($data);
    $ajax->Process();
  }
  
  protected function clearRequest(){
    tool\Request::clear();
  }
  
}
