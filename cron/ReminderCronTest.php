<?php
namespace cron;
use model\ReminderType;
use WebUser;

abstract class ReminderCronTest extends \DatabaseBaseTest{
  
  protected $type;
  
  abstract protected function createInstance();
  
  public function testDoNothing(){
    $this->clearAll();
    
    $files = glob(PATH_PDF.'*.pdf'); foreach($files as $file) unlink($file); 
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('SOAT', $seller->id, 1, '2012-03-15');
    $cat1 = $this->createCategory('Alfa', $evt->id, 10.00);
    //buyer
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($cat1->id, 3);
    $this->completeTransaction($client->placeOrder());
    
    
    $cron = $this->createInstance();
    $cron->execute();
    $this->assertRows(0, 'reminder_sent' );
    
    //create reminder
    $this->createReminder($evt->id, '2012-03-12 09:00:00', $this->type, 'No te duermas' );
    
    //return;
    
    $cron->setDate('2012-03-11');
    $cron->execute();
    $this->assertRows(0, 'reminder_sent' );
    
    $cron->setDate('2012-03-12 08:30:00');
    $cron->execute();
    $this->assertRows(0, 'reminder_sent' );
    
    $cron->setDate('2012-03-12 08:30:00'); //it is 30 minutes early
    $cron->execute();
    $this->assertRows(0, 'reminder_sent' );
    
    $cron->setDate('2012-03-12 09:01:00'); //it is past 1 minute delivery date
    $cron->execute();
    $this->assertRows(1, 'reminder_sent' );
    
    //another cron
    $cron = $this->createInstance();
    $cron->setDate('2012-03-12 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    $this->assertRows(1, 'reminder_sent' ); //already sent - no change
    
  }
  
  function testManyUsers(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('SOAT', $seller->id, 1, '2012-03-15');
    $cat1 = $this->createCategory('Alfa', $evt->id, 10.00);
    //buyer
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($cat1->id, 3);
    $this->completeTransaction($client->placeOrder());
    
    $bar = $this->createUser('bar');
    $client = new WebUser($this->db);
    $client->login($bar->username);
    $client->addToCart($cat1->id, 2);
    $this->completeTransaction($client->placeOrder());
    
    //create reminder
    $this->createReminder($evt->id, '2012-03-12 09:00:00', $this->type );
    
    $cron = $this->createInstance();
    $cron->setDate('2012-03-12 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    
    $this->assertRows(2, 'reminder_sent' );
    
  }
  
  function testIgnoreOthers(){
    $this->clearAll();
    
    //event
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('SOAT', $seller->id, 1, '2012-05-16');
    $cat = $this->createCategory('Alfa', $evt->id, 10.00);
    $this->createReminder($evt->id, '2012-05-10', $this->type);
    
    //purchase
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($cat->id, 3);
    $this->completeTransaction($client->placeOrder());
    
    //event
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('GOAT', $seller->id, 1, '2012-05-16');
    $cat = $this->createCategory('GOAT-1', $evt->id, 10.00);
    $this->createReminder($evt->id, '2012-05-14', $this->type);
    //purchase
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($cat->id, 3);
    $this->completeTransaction($client->placeOrder());
    
    // ******************************************************************************
    
    $cron = $this->createInstance();
    $cron->setDate('2012-05-12 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    
    $this->assertRows(1, 'reminder_sent' );
    
    $cron = $this->createInstance();
    $cron->setDate('2012-05-14 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    
    $this->assertRows(2, 'reminder_sent' );
    
    
    $cron = $this->createInstance();
    $cron->setDate('2012-05-15 09:05:00'); //it is past 1 minute delivery date
    $cron->execute();
    
    $this->assertRows(2, 'reminder_sent' ); //no change
    
  }
  
  function testInactive(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('SOAT', $seller->id, 1, '2012-03-15');
    $cat = $this->createCategory('Alfa', $evt->id, 10.00);
    //buyer
    $foo = $this->createUser('foo');
    $this->buyTickets($foo->id, $cat->id, 3);
    
    
    $this->createReminder($evt->id, '2012-03-12 09:00:00', $this->type, 'No te duermas' );
    
    //make it inactive
    $this->db->update('reminder', array('active'=>0), " 1");
    
    
    $cron = $this->createInstance();
    $cron->setDate('2012-03-12 09:01:00'); //it is past 1 minute delivery date
    $cron->execute();
    $this->assertRows(0, 'reminder_sent' );
    
  }
  
  
  
  
  
  
 
}