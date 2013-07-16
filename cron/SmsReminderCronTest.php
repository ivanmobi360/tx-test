<?php
namespace cron;
use model\ReminderType, WebUser;
class SmsReminderCronTest extends ReminderCronTest{
  
  protected $type = ReminderType::SMS;
  
  protected function createInstance(){
    return new \cron\SmsReminderCron;
    }
    
  //Both cron run in sequence one after the other should operate seamlessly  
  public function testMixed(){
    
    $this->clearAll();
    
    //event
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('SOAT', $seller->id, 1, '2012-05-16');
    $cat = $this->createCategory('Prime', $evt->id, 10.00, 50);
    $this->createReminder($evt->id, '2012-05-10', ReminderType::EMAIL);
    $this->createReminder($evt->id, '2012-05-12', ReminderType::SMS);
    
    //purchase
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($cat->id, 10);
    $this->completeTransaction($client->placeOrder());
    
    //purchase
    $foo = $this->createUser('bar');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($cat->id, 15);
    $this->completeTransaction($client->placeOrder());
    
    $foo = $this->createUser('baz');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($cat->id, 7);
    $this->completeTransaction($client->placeOrder());
    
    // ******************************************************************************
    
    $this->runCrons('2012-05-09 09:05:00');
    $this->assertRows(0, 'reminder_sent' );
    
    $this->runCrons('2012-05-10 09:05:00');
    $this->assertRows(3, 'reminder_sent' ); //3 emails
    
    $this->runCrons('2012-05-11 09:05:00');
    $this->assertRows(3, 'reminder_sent' ); //3 emails
    
    $this->runCrons('2012-05-12 09:05:00');
    $this->assertRows(6, 'reminder_sent' ); //3 emails + 3 sms
    /*
    $cron = $this->createInstance();
    $cron->setDate('2012-05-14 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    
    $this->assertRows(2, 'reminder_sent' );
    
    
    $cron = $this->createInstance();
    $cron->setDate('2012-05-15 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    
    $this->assertRows(2, 'reminder_sent' ); //no change*/
    
    
  }
  
  protected function runCrons($at){
    $cron = new EmailReminderCron();
    $cron->setDate($at);
    $cron->execute();
    
    $cron = new SmsReminderCron();
    $cron->setDate($at);
    $cron->execute();
  }
   
}