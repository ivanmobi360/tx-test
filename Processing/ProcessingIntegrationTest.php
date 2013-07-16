<?php
namespace Optimal;
use tool\CurlHelper;

use Utils;

class ProcessingIntegrationTest extends \DatabaseBaseTest{
  

  function fixture(){
    $this->clearAll();
    
    
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Quebec CES' , $seller->id, $this->createLocation()->id, date('Y-m-d', strtotime("+1 day")) );
    $this->setEventId($evt, 'aaa');
    $this->setEventPaymentMethodId($evt, self::PROCESING);
    $this->cat = $this->createCategory('Verde', $evt->id, 10.00, 100, 0, array('tax_inc'=>1) );
    
    
    
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
    
    //some hardcoded transactions
    $this->db->Query("INSERT INTO `ticket_transaction` (`id`, `category_id`, `user_id`, `price_paid`, `currency_id`, `date_processed`, `completed`, `ticket_count`, `txn_id`, `promocode_id`, `discount`, `taxe1`, `taxe2`, `fee`, `cancelled`, `delivery_method`, `reminder`, `promoter_paid`, `pcheck_date`, `pcheck_number`, `bo_id`) VALUES
(875000000000907, 1, 'foo', 11.96, 1, '2013-04-16 15:23:14', 0, 1, 'TX-PVXL2-KLIK8-NCWA1', 0, 0, 0.44, 0.87, 1.37, 0, 'mailed', 0, 0, NULL, 0, NULL),
(875000000000908, 1, 'foo', 23.92, 1, '2013-04-16 15:36:56', 0, 2, 'TX-AP5Q3-3BMW7-7ATA7', 0, 0, 0.87, 1.74, 2.74, 0, 'mailed', 0, 0, NULL, 0, NULL);
    ");
    
    $this->db->Query("TRUNCATE TABLE processing_sql.ipn_message");
    $this->db->Query("INSERT INTO processing_sql.`ipn_message` (`id`, `message_id`, `site_id`, `created_at`, `delivery_status`, `server_response`, `txn_id`, `resent`, `last_delivery_attempt_date`, `no_of_retries`, `notification_url`, `txn_type`, `body`) VALUES
(1, 'MBQ67A8BEHNL2LDG', 2, '2013-04-16 11:34:55', 'q', NULL, 'CC1366126495', 0, NULL, 0, 'http://www.tixpro.local/index.php?action=ipnlistener&pt=proc', 'payment', 'txn_type=payment&first_name=Foo&last_name=Pira%C3%B1a+Salada&receiver_email=info%40tixpro.com&reference_id=TX-PVXL2-KLIK8-NCWA1&custom=&txn_id=CC1366126495&amount=11.96&currency=CAD&payment_status=completed&pending_reason='),
(2, 'MZ5KWYRHXBM4QWNW', 2, '2013-04-16 11:38:01', 'q', NULL, 'CC1366126681', 0, NULL, 0, 'http://www.tixpro.local/index.php?action=ipnlistener&pt=proc', 'payment', 'txn_type=payment&first_name=Foo&last_name=Pira%C3%B1a+Salada&receiver_email=info%40tixpro.com&reference_id=TX-AP5Q3-3BMW7-7ATA7&custom=&txn_id=CC1366126681&amount=23.92&currency=CAD&payment_status=completed&pending_reason=');
    ");
    
    //manually run the cron
    $curl = new CurlHelper();
    $curl->get("http://www.processing.local/send_ipn.php");
    $this->assertEquals(2, $this->db->get_one("SELECT COUNT(id) FROM ticket_transaction WHERE completed=1")); //Problem was that the curl obj was being reused on the cron on processing.
    
  }
  
  

 
}