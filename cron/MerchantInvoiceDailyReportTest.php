<?php
namespace cron;

use model\ReminderType;

use WebUser;
use Utils;
class MerchantInvoiceDailyReportTest extends \DatabaseBaseTest{
  
  protected function createInstance(){
    return new MerchantInvoiceCron();
  }
  
  function fixture(){
    //Create a good chunck of events/merchant invoices
    $this->clearAll();
    
    // ------------------
    //some finished event
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Marchas en Quito', $seller->id, 1, '2012-02-15');
    $this->setEventId($evt, 'aaa');
    $this->setPaymentMethod($evt,self::OUR_CREDIT_CARD);
    $cat1 = $this->createCategory('Conaie', $evt->id, 100.00);
    $cat2 = $this->createCategory('Ecuarurani', $evt->id, 25.00);
    
    
    
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Bailando 2012', $seller->id, 1, '2012-03-15');
    $this->setEventId($evt, 'bbb');
    $cat1 = $this->createCategory('Piscina', $evt->id, 100.00);
    
    
    $seller = $this->createUser('seller3');
    $evt = $this->createEvent('Occupy Wallstreet', $seller->id, 1, '2012-04-15', '', '2012-04-20');
    $this->setEventId($evt, 'ccc');
    $cat1 = $this->createCategory('Hipsters', $evt->id, 100.00);
    
    $evt = $this->createEvent('Dinner', $seller->id, 1, '2012-04-19', '10:00', '2012-04-20', '13:00');
    $this->setEventId($evt, 'ddd');
    $cat1 = $this->createCategory('Pals', $evt->id, 100.00);
    
    $foo = $this->createUser('foo');
    $this->buyTickets('foo', $cat1->id, 3);
    
    
    //Today fixture - run manually merchant_invoice_cron and mercant_invoice_daily_report_cron to verify emails are sent to your inbox
    $seller = $this->createUser('ronald');
    $evt = $this->createEvent('McDonal Race', $seller->id, 1, date('Y-m-d'), '07:00:00', date('Y-m-d'), '08:00:00');
    $this->setEventId($evt, 'mcrace');
    $cat1 = $this->createCategory('Clowns', $evt->id, 100.00);
    
   
    
    // **********************************************************
  }
  
  public function testCreate(){
    $this->fixture();

    $sender = $this->runInvoiceSender('2012-02-14 23:00:00');
    $this->assertEquals(0, $sender->total_sent);
    Utils::clearLog();
    $cron = new MerchantInvoiceDailyReportCron();
    $cron->setDate('2012-02-14 23:00:00');
    $cron->execute();
    $this->assertTrue($cron->empty);
    $this->assertEquals(0, $cron->rows_sent);
    //return;
    // ---------------------------------
    
    //This sends aaa
    Utils::clearLog();
    $sender = $this->runInvoiceSender('2012-02-15 23:59:59');
    $this->assertEquals(1, $sender->total_sent);
    
    $cron = new MerchantInvoiceDailyReportCron();
    $cron->setDate('2012-02-15 23:59:59');
    $cron->execute();
    $this->assertFalse($cron->empty);
    $this->assertEquals(1, $cron->total_sent);// sent to support@tixpro.com
    $this->assertEquals(1, $cron->rows_sent);
    
    
    // -------------------------------------------------
    //This sends bbb - aaa already sent
    Utils::clearLog();
    $sender = $this->runInvoiceSender('2012-03-15 23:59:59');
    $this->assertEquals(1, $sender->total_sent);
    
    $cron = new MerchantInvoiceDailyReportCron();
    $cron->setDate('2012-03-15 23:59:59');
    $cron->execute();
    $this->assertFalse($cron->empty);
    $this->assertEquals(1, $cron->total_sent);// sent to support@tixpro.com
    $this->assertEquals(1, $cron->rows_sent);
    
    
    // ---------------------------------
    //This sends ccc and ddd
    Utils::clearLog();
    $sender = $this->runInvoiceSender('2012-04-20 23:59:59');
    $this->assertEquals(2, $sender->total_sent);
    
    $cron = new MerchantInvoiceDailyReportCron();
    $cron->setDate('2012-04-20 23:59:59');
    $cron->execute();
    $this->assertFalse($cron->empty);
    $this->assertEquals(1, $cron->total_sent);// sent to support@tixpro.com
    $this->assertEquals(2, $cron->rows_sent);
    
  }
  
  function runInvoiceSender($date){
    $cron = new MerchantInvoiceCron();
    $cron->setDate($date);
    $cron->execute();
    return $cron;
  }
  
  
  
  
  
  
  
  
  
  
  
  
  
 
}