<?php
/**
 * Ticket Multiplier logic 
 * @author Ivan Rodriguez
 * 
 * Notes: Original code done on TC. The portback to TX was done by Nicolas. We copied over TC's test to verify Nicolas' port
 *
 */
class MultiplierTest extends DatabaseBaseTest{
  
  function testPurchase(){
      
    $this->clearAll();
    
    $user = $this->createUser('foo');
    $seller = $this->createUser('seller');
    $bo_id = $this->createBoxoffice('111-xbox', $seller->id);
    
    // **********************************************
    // Eventually this test will break for the dates
    // **********************************************
    $evt = $this->createEvent('Multiplier Test', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
    $this->setEventId($evt, 'aaargh');
    $this->setEventParams($evt, ['has_tax'=>0]);
    $catA = $this->createCategory('CREATES FOUR', $evt->id, 20.00, 100, 0, ['ticket_multiplier'=>4, 'fee_inc'=>1]);
    $catB = $this->createCategory('NORMAL', $evt->id, 50.00, 100, 0, ['fee_inc'=>1]);
    
    
    //return; //manual test fixture
    
    $client = new \WebUser($this->db);
    $client->login($user->username);
    $client->addToCart($catA->id, 1); //cart in session
    Utils::clearLog();
    $client->payByCash($client->placeOrder());

    $this->assertRows(4, 'ticket');
    
    $client = new \WebUser($this->db);
    $client->login($user->username);
    $client->addToCart($catB->id, 2); //cart in session
    Utils::clearLog();
    $client->payByCash($client->placeOrder());
    
    
    //You should also login the boxoffice and check the correct values are displayed
    
  }
  
  

 
}