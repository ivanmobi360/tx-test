<?php
namespace cron;

use model\MerchantInvoices;

use model\ReminderType;

use WebUser;

class MerchantInvoiceCronTest extends \DatabaseBaseTest{
  
  protected $delivery_date, $date_start;
  
  protected function createInstance(){
    return new MerchantInvoiceCron();
  }
  
  function fixture(){
    $this->clearAll();
    
    $this->date_start = '2012-03-23';
    $this->delivery_date = '2012-03-27 15:00'; //4 days later
    
    //some finished event
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Marchas en Quito', $seller->id, 1, $this->date_start /*, date('Y-m-d', strtotime('+1 day'))*/  ); // use future to make it refundable
    $this->setEventId($evt, 'aaa');
    $this->setPaymentMethod($evt,self::OUR_CREDIT_CARD); //Apparently this still has no effect on writing price_ccfee values
    $cat1 = $this->createCategory('Conaie', $evt->id, 100.00);
    $cat2 = $this->createCategory('Ecuarurani', $evt->id, 25.00);
    $cat3 = $this->createCategory('Pachakutik', $evt->id, 45.00);
    
    //create reminder?
    //$this->createReminder($evt->id, '2012-03-21 10:00', ReminderType::SMS);
    //$this->createReminder($evt->id, '2012-03-21 9:00', ReminderType::EMAIL);
    
    //have some buyers
    $buyer = $this->createUser('foo');
    $txn_id = $this->buyTickets($buyer->id, $cat1->id);
    
    $buyer = $this->createUser('bar');
    $this->buyTickets($buyer->id, $cat3->id, 3);
    
    $buyer = $this->createUser('baz');
    $client = new WebUser($this->db);
    $client->login($buyer->username);
    $client->addToCart($cat1->id, 2);
    $client->addToCart($cat2->id, 1);
    $this->buyCart('baz', $client->getCart(), self::OUR_CREDIT_CARD);
    
    //refund first one
    //$this->doRefund('seller', $txn_id); //date based test will fail, just enable it to quicly check that refunded amounts were not collected
    
    //for now hardcode cc fees on tickets
    $this->db->update('ticket', array('price_ccfee'=> 0.25), " 1 "); // it still doesn't seem to be working with my data :/  - Ivan
    
    //Send reminders
    /*$cron = new SmsReminderCron();
    $cron->setDate('2012-03-22 12:00');
    $cron->execute();*/ //Broken - new logic in place by qt
    
    //Have some banners?
    
    $this->createBanner($evt, 1, 1);
    $this->createBanner($evt, 1, 1); //this override actually disables the previous one of the same price_id
    $this->createBanner($evt, 1, 1); //"So if for example a merchant uploaded 3 banner of type 1 and all were approved, he still only going to pay for one."
    // **********************************************************
  }
  
  public function testDoNothing(){
    $this->fixture();
    //return; //manual fixture to run cron manually from browser
    
    $cron = $this->createInstance();
    
    //Send only after the event has finished
    $cron->setDate('2012-03-23 15:00');
    $cron->execute();
    $this->assertRows(0, 'merchant_invoice' );
    
    $cron->setDate('2012-03-23 23:00');
    $cron->execute();
    $this->assertRows(0, 'merchant_invoice' );
    $this->assertEquals(0, $cron->total_sent);
    
    //After the event if finished, we run a cron job.
    $cron->setDate($this->delivery_date);
    $cron->execute();
    
    //there must be an invoice
    $this->assertRows(1, 'merchant_invoice' );
    
    //$this->assertEquals(2*SMS_PRICE, $this->db->get_one("SELECT total FROM merchant_invoice WHERE event_id=? AND user_id=?", array( $evt->id, 'seller' )  ));
    
    //the banner related invoice line can't be zero
    $this->assertTrue(
          $this->db->get_one('SELECT price FROM `merchant_invoice_line` WHERE name like "%Banner%"')
          >0, "Banner price can't be zero"
    );
    
    //sent
    $this->assertEquals(1, $cron->total_sent);
    
    //don't create it again
    $cron = $this->createInstance();
    $cron->setDate('2012-03-28 15:05');
    $cron->execute();
    
    //no new invoice created
    $this->assertRows(1, 'merchant_invoice' );
    //it was not sent again
    $this->assertEquals(0, $cron->total_sent);
    
  }
  
  function testNoSales(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Americas', $seller->id, 1, '2012-03-23'); 
    $this->setEventId($evt, 'ameri');
    //$this->setPaymentMethod($evt,self::OUR_CREDIT_CARD); //Apparently this still has no effect on writing price_ccfee values
    $cat = $this->createCategory('Negocios', $evt->id, 100.00);
    $cat = $this->createCategory('Turismo', $evt->id, 100.00);
    $cat = $this->createCategory('Educacion', $evt->id, 100.00);
    
    $cron = $this->createInstance();
    $cron->setDate('2012-03-27 15:05'); //4 days later
    $cron->execute();
    
    //There's a 0.00 invoice
    $this->assertRows(1, 'merchant_invoice' );
    $this->assertRows(0, 'merchant_invoice_line' ); //no transactions
    $this->assertEquals(1, $cron->total_sent);
    
    $data = \model\MerchantInvoices::getInvoiceData(1);
    //$this->assertFalse($data['credit']); //deprecated
    $this->assertFalse($data['debit']);
    $this->assertEquals(0.00, $data['total'], 0.00);
  
  }
  
  function testTaxUtility(){
    $this->fixture();
    
    
    //very hard coded layout but there's no other option
    $this->db->delete('ticket', " id>4");
    $this->db->update('ticket', array('price_taxe_1'=>10.00, 'price_taxe_2'=>5.00), " 1");
    $this->db->update('ticket', array(
                                  'tax_id_1' => 1 , 'tax_id_2' => 2,
    															'price_taxe_1'=>5.00, 'price_taxe_2'=>10.00), " id=4 ");
    /*
    $cron = $this->createInstance();
    $cron->setDate('2012-03-24 15:05');
    $cron->execute();
    */
    $data = MerchantInvoices::getTaxSummary('aaa');
    
    $this->assertEquals($data[2]['name'], 'GST');
    $this->assertEquals($data[2]['total'], 40.00);
    
    $this->assertEquals($data[1]['name'], 'PST');
    $this->assertEquals($data[1]['total'], 20.00);
    
  }
  
  function testNoBannerOrderLine(){
    $this->fixture();
    
    $this->db->Query("TRUNCATE TABLE banner");
    
    $cron = $this->createInstance();
    $cron->setDate($this->delivery_date);
    $cron->execute();
    
    $this->assertFalse(
          $this->db->get_one('SELECT price FROM `merchant_invoice_line` WHERE name like "%Banner%"')
          , "Banner line should not exist"
    );
    
  }
  
  //So if for example a merchant uploaded 3 banner of type 1 and all were approved, he still only going to pay for one.
  function test_only_one_banner_of_type_1_is_charged(){
    $this->fixture();
    $price = $this->db->get_one("SELECT price FROM banner_price WHERE banner_type=1");
    
    //make all approved
    $this->db->update('banner', array('pending'=>0, 'active'=>1), " 1 ");
    
    $cron = $this->createInstance();
    $cron->setDate($this->delivery_date);
    $cron->execute();
    
    $this->assertEquals(
          $price,  
          $this->db->get_one('SELECT price FROM `merchant_invoice_line` WHERE name like "%Banner%"')
          , 0.01
    );
    
  }
  
  function test_multiple_banners_invoiced(){
    $this->fixture();
    $this->createBanner(new \model\Events('aaa'), 3, 2);
    
    
    $cron = $this->createInstance();
    $cron->setDate($this->delivery_date);
    $cron->execute();
    
    $this->assertEquals(
          2,  
          $this->db->get_one('SELECT COUNT(id) FROM `merchant_invoice_line` WHERE name like "%Banner%"')
    );
  }
  
  /**
   * "If any banner is set approved, it means it was shown on the website for a period of time = they have to pay.
   * So even if all banners are active 0 and any of them is approved, they have to pay.
   * (if there really was a serious reason to remove a banner and we agreed for them not to pay, we'll have made the banner not-approved ourselves before the end of the event)" 
   */
  function test_any_banner_approved_is_billed(){
    $this->fixture();
    
    $price = $this->db->get_one("SELECT price FROM banner_price WHERE banner_type=1");
    
    //make all approved and inactive
    $this->db->update('banner', array('pending'=>0, 'active'=>0), " 1 ");
    
    $cron = $this->createInstance();
    $cron->setDate($this->delivery_date);
    $cron->execute();
    
    $this->assertEquals(
          $price,  
          $this->db->get_one('SELECT price FROM `merchant_invoice_line` WHERE name like "%Banner%"')
          , 0.01
    );
    
  }

  
  
  
  
  
  
  
  
  
  
  
 
}