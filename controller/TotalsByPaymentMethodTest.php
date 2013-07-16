<?php


use reports\ReportLib;
class TotalsByPaymentMethodTest extends DatabaseBaseTest{
  
  //fixture to create activity for report
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();

    

    //Events
    $seller = $this->createUser('seller');
    $loc = $this->createLocation();
    $evt = $this->createEvent('First', $seller->id, $loc->id);
    $catA = $this->createCategory('CatA', $evt->id, 10.00);
    $catB = $this->createCategory('CatB', $evt->id, 4.00);
    
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Second', $seller->id, $loc->id);
    $cat2 = $this->createCategory('Cat2', $evt->id, 15.00);
    
    $seller = $this->createUser('seller3');
    $evt = $this->createEvent('Third', $seller->id,  $loc->id);
    $this->setPaymentMethod($evt, self::OUR_CREDIT_CARD);
    $cat3 = $this->createCategory('Cat3', $evt->id, 20.00);
    
    
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    
    $client->addToCart($catA->id, 1);
    $client->addToCart($catB->id, 2);
    
    $this->completeTransaction($client->placeOrder(false));
    
    $this->buyTickets('foo', $cat2->id, 5);
    
    /*
    $client->addToCart($cat2->id, 5);
    $this->completeTransaction($client->placeOrder(false));*/
    /*
    $client->addToCart($catA->id, 9);
    $this->completeTransaction($client->placeOrder(false));*/
    $this->buyTickets('foo', $catA->id, 9);
                      
    
    //$bar = $this->createUser('bar');
    $bar = $this->createUser('bar');
    /*$client = new WebUser($this->db);
    $client->login($bar->username);
    $client->addToCart($cat3->id, 3);
    $this->completeTransaction($client->placeOrder('google'), self::OUR_CREDIT_CARD);
    */
    $this->buyTickets('bar', $cat3->id, 3);
    
    //another google transaction
    /*$client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($catA->id, 5);
    $this->completeTransaction($client->placeOrder(false), self::OUR_CREDIT_CARD);*/
    $this->buyTickets('foo', $catA->id, 5);
    
  }
  
  /**
   * Fixture for Strangers in the Night 9
   * Enter description here ...
   */
  public function testStrangers9(){
    $this->clearAll();
    
    $seller = $this->createUser( 'seller' /*'76be1612'*/);
    $evt = $this->createEvent('Strangers in the Night 9 - TEST', $seller->id, $this->createLocation()->id );
    $this->setEventId($evt, '0541c021');
    $this->setEventParams($evt, array('payment_method_id' => 3));
    
    //$catA = $this->createCategory('General Admission Ticket', $evt->id, 25.00, 2000, 0);
    $this->insertStrangersCategory();
    
    $this->db->Query("TRUNCATE TABLE ticket_pool");
    $this->db->beginTransaction();
    $this->db->executeBlock(file_get_contents(__DIR__ . "/../fixture/strangers_ticket_pool.sql"));
    $this->db->commit();
    
    $foo = $this->createUser('foo');
    
  }
  
  protected function insertStrangersCategory(){
    
    $sql = "INSERT INTO `category` (`id`, `name`, `description`, `event_id`, `category_id`, `price`, `capacity`, `capacity_max`, `capacity_min`, `overbooking`, `tax_inc`, `fee_inc`, `cc_fee_inc`, `fee_id`, `cc_fee_id`, `as_seats`, `hidden`, `locked_fee`, `assign`, `order`) VALUES
(342, '', '', '0541c021', NULL, '500.00', 10, 10, 0, 0, 0, 0, 0, 31, NULL, 0, 1, NULL, 1, 0),
(343, 'Single Basic Seating', 'Open Bar from 6:00 PM – 11:00 PM </br> Single seating</br> Access to all restaurants and entertainment</br> ** Get upgrade to “ Preferred Table Seating “ by completing a table of ten(10) seats with friends and/or  family !!', '0541c021', NULL, '200.00', 10, 10, 0, 0, 0, 0, 0, 31, NULL, 1, 0, NULL, 1, 2),
(344, 'VIP Premium Seating', 'Premium Open Bar from 5:00 PM – 1:00 AM </br>VIP Valet Parking </br>Front Of The Line Drive home Service </br>Private Preferred Table Seating for 10 </br>Dedicated servers </br>Access to VIP Lounge</br>Return Home Drive Service</br>Access to all restaurants and entertainment</br>Early entrance option available at 5 PM', '0541c021', 342, '5000.00', 100, 100, 0, 0, 0, 0, 0, 31, NULL, 0, 0, NULL, 1, 4),
(345, 'Standard Preferred Seating', 'Open Bar from 6:00 PM – 11:00 PM </br>Table seating for 10 </br>Access to all restaurants and entertainment', '0541c021', 343, '1800.00', 300, 300, 0, 0, 0, 0, 0, 31, NULL, 1, 0, NULL, 1, 3),
(346, 'General Admission Ticket', 'Includes: Entrance to site after 9pm  </br>Kool and the Gang and others entertainment </br> Cash Bar', '0541c021', 0, '25.00', 2000, 2000, 0, 0, 0, 0, 0, 31, NULL, 0, 0, NULL, 0, 1),
(347, 'TEST General Admission Ticket', 'TEST Includes: Entrance to site after 9pm  </br>April Wine and others entertainment </br> Cash Bar', '0541c021', 0, '1.02', 100, 100, 0, 0, 0, 0, 0, 31, NULL, 0, 1, NULL, 0, 0);
    ";
    
    $this->db->Query($sql);
    
  }
  
 
}


