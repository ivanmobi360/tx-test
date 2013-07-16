<?php

use reports\ReportLib;
use model\Eventsmanager;
use model\Events;
use tool\Log;
class CurrentEventOverviewTest extends \DatabaseBaseTest{
  
  protected function fixture(){
    $this->clearAll();
  }
  
  public function testCreate(){
    
    
    //let's create some events
    $this->fixture();
    
    $seller = $this->createUser('seller');
    
    //create some location
    $loc = $this->createLocation();
    $loc->user_id = $seller->id;
    $loc->update();
    
    
    $evt = $this->createEvent('Event A', $seller->id, 1, '2012-01-01', '9:00', '2012-01-10', '18:00' );
    $cat = $this->createCategory('Cat A1', $evt->id, 9.99);
    $cat = $this->createCategory('Cat A2', $evt->id, 6.00);
    $cat = $this->createCategory('Cat A3', $evt->id, 2.50);
    
    $evt->id = null; //reset
    $evt->name = 'Event B';
    $evt->date_from = '2012-01-05';
    $evt->date_to = '2012-01-15';
    $evt->insert();
    $cat = $this->createCategory('Cat B', $evt->id, 15.00);
    
    $evt->id = null;
    $evt->name = 'Event C';
    $evt->date_from = '2012-01-20';
    $evt->date_to = '2012-01-30';
    $evt->insert();
    $cat = $this->createCategory('Cat C', $evt->id, 4.50);
    
    // Now retrieve report sql
    $sql = Eventsmanager::getCurrentListSql('2012-01-11', 'event');
    $rows = $this->db->getIterator($sql);
    $this->assertEquals(2, count($rows));

    
    $sql = Eventsmanager::getCurrentListSql('2012-01-20', 'event');
    $rows = $this->db->getIterator($sql);
    $this->assertEquals(1, count($rows));

    $sql = Eventsmanager::getCurrentListSql('2012-02-01', 'event');
    $rows = $this->db->getIterator($sql);
    $this->assertEquals(0, count($rows));
    
    $sql = Eventsmanager::getCurrentListSql('2012-01-01', 'event');
    $rows = $this->db->getIterator($sql);
    $this->assertEquals(3, count($rows));
    
    
    $seller = $this->createUser('gates');
    
    $evt->id = null;
    $evt->name = 'Event D';
    $evt->date_from = '2011-12-31';
    $evt->date_to = '2022-01-30';
    $evt->user_id = $seller->id;
    $evt->insert();
    $cat = $this->createCategory('Cat D', $evt->id, 3.25);
    $sql = Eventsmanager::getEventOverviewSql('2012-01-01', 'event');
    $rows = $this->db->getIterator($sql);
    $this->assertEquals(4, count($rows));
    
  }
  
  public function testTickets(){
    
    $this->fixture();
    
    //$this->db->beginTransaction();
    $seller = $this->createUser('seller');
    $loc = $this->createLocation();
    $loc->city = 'Las Vegas'; $loc->update();
    $event = $this->createEvent('CES', $seller->id, $loc->id, '2012-01-01', '9:00', '2022-01-01', '9:00');
    $this->setEventId($event, 'aaa');
    $cat = $this->createCategory('Blah', $event->id, 100.00, 200);
    
    

    $cammy = $this->createUser('cammy');
    $client = new WebUser($this->db);
    $client->login($cammy->username);
    
    Utils::clearLog();
    $client->addToCart($cat->id, 25);
    $txnid = $client->placeOrder(); 
    $this->completeTransaction($txnid);
    
    $this->buyTickets('cammy', $cat->id, 25 );
    
    //$this->db->commit();return;
    
    
    //while we're at it, create some event with multiple categories
    $loc = $this->createLocation();
    $loc->city = 'Quito'; $loc->update();
    $event = $this->createEvent('Concert', $seller->id, $loc->id, '2011-12-31', '9:00', '2022-01-01', '9:00');
    $id = 'bbb';
    $this->db->update('event', array('id'=>$id), "id=?", $event->id); $event->id = $id;
    $this->createCategory('General', $event->id, 15.99);
    $tri = $this->createCategory('Tribuna', $event->id, 35.50);
    $silla = $this->createCategory('Silla', $event->id, 150.00);
    
    //sell 3 Silla tickets
    $pilly = $this->createUser('pilly');
    $client = new WebUser($this->db);
    $client->login($pilly->username);
    $client->addToCart($silla->id, 3);
    //$client->placeOrder();
    $this->completeTransaction($client->placeOrder());
    
    //$this->db->commit();return;
      
    //And 2 Tribuna
    $milly = $this->createUser('milly');
    $client = new WebUser($this->db);
    $client->login($milly->username);
    $client->addToCart($tri->id, 2);
    //$client->placeOrder();
    $this->completeTransaction($client->placeOrder());
   
    
    //$this->db->commit();
    
    //Event categories should be retriveables
    $lib = new ReportLib();
    $this->assertEquals(3, count($this->db->getIterator($lib->getEventDetailsSql(), $event->id ))  );
    
    
    //Some assumptions in case the sql breaks
    $data = \Database::getAll(model\Eventsmanager::getEventOverviewSql( date('Y-m-d H:i:s',strtotime("+2 day")) , 'event'));
    Utils::log(print_r($data, true));
    $this->assertEquals(0, (int) $data[0]['sold_today']);
    $this->assertEquals(50, (int) $data[0]['sold_to_date']);
    $this->assertTrue((float) $data[0]['collected_to_date']  >= 5000 );
    
    $this->assertEquals(0, (int) $data[1]['sold_today']);
    $this->assertEquals(5, (int) $data[1]['sold_to_date']);
    $this->assertTrue((float) $data[1]['collected_to_date']>=  521  );
    
    
    
    
    
    //add activity from another merchant just for kicks
    $seller = $this->createUser('seller2');
    $loc = $this->createLocation();
    $loc->city = 'Quito'; $loc->update();
    $event = $this->createEvent('Gamez', $seller->id, $loc->id, '2012-01-01', '9:00', '2022-01-01', '9:00');
    $id = 'zzz';
    $this->db->update('event', array('id'=>$id, 'currency_id'=>2), "id=?", $event->id); $event->id = $id;
    $cat = $this->createCategory('Blah', $event->id, 49.00);
    
    
    $charly = $this->createUser('charly');
    $client = new WebUser($this->db);
    $client->login($charly->username);
    $client->addToCart($cat->id, 7);
    //$client->placeOrder();
    $this->completeTransaction($client->placeOrder());
    
    
  }
  
  
  
  public function testOnOffSales(){
    $this->fixture();
    
    $seller = $this->createUser('seller');
    $loc = $this->createLocation();
    $event = $this->createEvent('CES', $seller->id, $loc->id, '2012-01-01', '9:00', '2022-01-01', '9:00');
    $cat = $this->createCategory('VIP', $event->id, 100.00);
    
    $nia = $this->createUser('nia');
    $client = new WebUser($this->db);
    $client->login($nia->username);
    
    $client->addToCart($cat->id, 6);
    $this->createTickets($client->placeOrder(false, '2012-01-15 15:00'));
    
    $client->addToCart($cat->id, 2);
    $this->createTickets($client->placeOrder(false, '2012-01-15 9:30'));
    
    $client->addToCart($cat->id, 7);
    $this->createTickets($client->placeOrder(false, '2012-01-15 11:50'));
    
    $client->addToCart($cat->id, 3);
    $this->createTickets($client->placeOrder(false, '2012-01-15 11:30'));
  }
  
  /**
   * "make me an other box in the overview for past events" 
   */
  public function testPast(){
    $this->fixture();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Expired Event', $seller->id, $this->createLocation('Quito')->id, '2012-01-01', '9:00');
    $catE = $this->createCategory('VIP', $evt->id, 100.00);
    
    $evt = $this->createEvent('Future Event', $seller->id, $this->createLocation('Cuenca')->id, date('Y-m-d', strtotime('+7 days')  ));
    $cat = $this->createCategory('Room A', $evt->id, 100.00);
    
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Myth Event', $seller->id, $this->createLocation('Greek')->id,  '2012-01-15'  );
    $cat = $this->createCategory('Olimpo', $evt->id, 100.00);
    
    //It should show the tickets bought in the past (?)
    $foo = $this->createUser('foo');
    $this->buyTickets('foo', $catE->id, 5);
    
    
    
    
  }
  
  
  
 
  
 

  
}