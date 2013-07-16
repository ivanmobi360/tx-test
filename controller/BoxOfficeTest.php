<?php
/**
 * Originally BoxOffice was developed for Tixpro Caribbean.
 * Later on it was ported back to Tixpro. I didn't do the port.
 * So these tests are copied from TC, and adapted to work here if possible
 * @author MASTER
 */
namespace controller;
use controller\Boxofficezreport;
use model\DeliveryMethod;
use model\Eventsmanager;
use tool\Date;

use \DatabaseBaseTest;

class BoxOfficeTest extends DatabaseBaseTest{
  
  const HARDCODED_EVENT_ID = 'aaa'; //use it to override in FunwalBoxOfficeTest
  
  function testLogin(){
    $this->clearAll();
    

    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    
    $bo_id = $this->createBoxoffice('xbox', $seller->id);
    
    $box = new \BoxOfficeModule($this);
    $this->assertFalse($box->login('111-xbox')); //if no event, it should fail
    //return ;
    
    // **********************************************
    // Eventually this test will break for the dates
    // **********************************************
    $evt = $this->createEvent('Normal Event', 'seller', $this->createLocation()->id/*, '2012-08-07', '09:00', '2012-08-24'*/ );
    $this->setEventId($evt, 'nnn');
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    $this->createCategory('Super Category', $evt->id, 40);
    $this->createCategory('VIP Category', $evt->id, 30);
    $this->createCategory('Whatever Section', $evt->id, 20);
    
    
    //login test
    
    $this->assertFalse($box->login('blah'));
    $this->assertFalse($box->login('111-31'));
    $this->assertFalse($box->login('111')); //no bo_id
    
    $this->assertFalse($box->login('111-xbox', 'xxx')); //wrong password
    $this->assertTrue($box->login('111-xbox', '123456' ), "Could not login");
    
    //return; //temporarly
    
    //Purchase time
    $box->addItem($evt->id, $catA->id, 1);
    $this->assertEquals(1, $box->getCart()->size());
    $box->payByCash();
    
    //assert expected values
    $trans = $this->db->auto_array("SELECT * FROM ticket_transaction ORDER BY id DESC LIMIT 1");
    //$this->assertEquals(100, $trans['price_paid']);
    $this->assertEquals(DeliveryMethod::BOXOFFICE_CASH, $trans['delivery_method']);
    $this->assertEquals($bo_id, $trans['bo_id']);
    
    $this->assertEquals(1, $trans['completed']);
    $this->assertEquals(0, $trans['cancelled']);
    
    //ticket should be burn and paid -- apparently not anymore, so check just for paid
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(id) FROM ticket WHERE paid=1  "));
    //and ccfee should be 0.00
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(id) FROM ticket WHERE price_ccfee<=0.002 "));
   
    
  }
  
  function testResizeFixture(){
      //simple fixture to be able to test multiple screen configurations
      $this->clearAll();
      
      
      $seller = $this->createUser('seller');
      $this->setUserHomePhone($seller, '111');
      
      $bo_id = $this->createBoxoffice('xbox', $seller->id);

      $evt = $this->createEvent('Normal Event', 'seller', $this->createLocation()->id);
      $this->setEventId($evt, 'nnn');
      $catA = $this->createCategory('ADULT', $evt->id, 100);
      $catB = $this->createCategory('KID'  , $evt->id, 50);
      $this->createCategory('Super Category', $evt->id, 40);
      $this->createCategory('VIP Category', $evt->id, 30);
      $this->createCategory('Whatever Section', $evt->id, 20);
      
      $evt = $this->createEvent('Man of Steel', 'seller', $this->createLocation()->id);
      $this->setEventId($evt, 'mos');
      $this->createCategory('General', $evt->id, 100);
      $this->createCategory('3D'  , $evt->id, 50);
      $this->createCategory('3D XD', $evt->id, 40);
      $this->createCategory('Vermouth', $evt->id, 30);
      $this->createCategory('Ladies Night', $evt->id, 20);
  }
  

  function testZReport(){
    $this->clearAll();
    
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    
    $bo_id = $this->createBoxoffice('xbox', $seller->id);
    
    $evt = $this->createEvent('Java Event', 'seller', $this->createLocation()->id, $this->dateAt('+5 day')  );
    $this->setEventId($evt, 'aaa');
    $catA = $this->createCategory('Adult', $evt->id, 100);
    $catB = $this->createCategory('Kid', $evt->id, 50);
    
    $evt = $this->createEvent('Net Event', 'seller', $this->createLocation()->id, $this->dateAt('+10 day'));
    $this->setEventId($evt, 'bbb');
    $catM = $this->createCategory('Senior Admins', $evt->id, 100);
    $catN = $this->createCategory('Admins', $evt->id, 50);
    
    $evt = $this->createEvent('This event does nothing', 'seller', $this->createLocation()->id, $this->dateAt('+1 day'));
    
    $foo = $this->createUser('foo');
     
    
    $box = new \BoxOfficeModule($this, '111-xbox');
    
    $box->addItem('aaa', $catA->id, 1);
    $box->payByCash();
    
    $box->addItem('bbb', $catN->id, 1);
    $box->payByCash();
    
  }
  
  function testDecimal(){
    $this->clearAll();
    
    $this->db->beginTransaction();    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    
    $bo_id = $this->createBoxoffice('xbox', $seller->id);
    
    $evt = $this->createEvent('Java Event', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'aaa');
    $catA = $this->createCategory('Adult', $evt->id, 100);
    $catB = $this->createCategory('Kid', $evt->id, 50);
    

    
    $foo = $this->createUser('foo');
     
    
    $box = new \BoxOfficeModule($this, '111-xbox');
    $n = 7;
    $box->addItem('aaa', $catB->id, $n);
    $box->payByCash();
    $this->db->commit();
    
    
    $page = new MockBoxofficezreport();
    
    $item = $page->getDataItem('aaa', $box->getId());
    //Utils::dump($item, ' event details');
    //$this->assertEquals($n*50.00, $item->eventGrandTotal, '', 0.001); //Won't match when taxes are involved
    
  }
  
  //Manual cancel????
  function xtestCanceled(){
    $this->clearAll();
    
    $this->db->beginTransaction();    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    
    $bo_id = $this->createBoxoffice('xbox', $seller->id);
    
    $event_id = static::HARDCODED_EVENT_ID;
    
    $evt = $this->createEvent('Ecuador Vs Paraguay', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, $event_id);
    $catA = $this->createCategory('Adult', $evt->id, 100);
    $catB = $this->createCategory('Kid', $evt->id, 50);

    
    $foo = $this->createUser('foo');
     
    
    $box = new \BoxOfficeModule($this, '111-xbox');
    $box->addItem($evt->id, $catA->id, 1);
    $box->addItem($evt->id, $catB->id, 2);
    $txn_id = $box->payByCash();
    $this->manualCancel($txn_id);
    
    //another one
    $box = new \BoxOfficeModule($this, '111-xbox');
    $box->addItem($evt->id, $catA->id, 1);
    $txn_id = $box->payByCash();
    
    
    $this->db->commit();
    
  }
  
  

}

class MockBoxofficezreport extends Boxofficezreport{
  
}