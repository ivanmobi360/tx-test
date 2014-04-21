<?php

use reports\ReportLib;
class FinancialReportTest extends DatabaseBaseTest{
  
  //fixture to create activity for report
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();

    

    //Events
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('First', $seller->id, $this->createLocation()->id, '2012-01-01', '9:00', '2012-01-05', '18:00' );
    $this->setEventId( $evt, 'aaa');
    $this->setPaymentMethod($evt, self::OUR_CREDIT_CARD);
    $catA = $this->createCategory('CatA', $evt->id, 10.00, 500, 0, array('tax_inc'=>1, 'cc_fee_id'=>11));
    $catB = $this->createCategory('CatB', $evt->id, 4.00);
    
    Utils::clearLog();
    $foo = $this->createUser('foo');
    $this->buyTickets($foo->id, $catA->id, 5);
    
    
    $foo = $this->createUser('bar');
    $this->buyTickets($foo->id, $catA->id, 1);
    
    $foo = $this->createUser('baz');
    $this->buyTickets($foo->id, $catB->id, 1);

  }
  
  function xtestHST(){
    $this->clearAll();
  }
  
  //Based on some error reported by Greg - Discount based tickets ---- The fix was adding show the ticket's cc_fees
  function testCirqueFixture(){
    $this->clearAll();
    
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Cirque of Happy Fun', $seller->id, $this->createLocation()->id/*, '2012-01-01', '9:00', '2012-01-05', '18:00' */);
    $this->setEventId( $evt, 'aaa');
    $this->setPaymentMethod($evt, self::OUR_CREDIT_CARD);
    $cat = $this->createCategory('General', $evt->id, 15.00, 500, 0, array('tax_inc'=>0, 'cc_fee_id'=>NULL));
    
    //special fee
    $this->db->delete('fee', 'id>16');
    $this->db->Query("INSERT INTO `fee` (`id`, `type`, `name`, `fixed`, `percentage`, `is_default`, `fee_max`) VALUES
(20, 'tf', '', 1.45, 2.5, 0, '9.95');
    ");
    $this->db->update('event', array('fee_id'=>20, 'has_tax'=>0), "id='aaa'");
    
    $foo = $this->createUser('foo');
    $this->buyTicketsWithCC($foo->id, $cat->id, 2);
    
  }
  
  function testMultiplier(){
  
      $this->clearAll();
  
      $user = $this->createUser('foo');
      $seller = $this->createUser('seller');
      $bo_id = $this->createBoxoffice('111-xbox', $seller->id);
  
      // **********************************************
      // Eventually this test will break for the dates
      // **********************************************
      $evt = $this->createEvent('Multiplier Test', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'aaargh');
      //$this->setEventParams($evt, ['has_tax'=>0]);
      $this->setEventPaymentMethodId($evt, self::MONERIS); //for the correct ccfee to be triggered, it's not enough to 'pay with moneris'. Event must be associated with Moneris payment method
      $catA = $this->createCategory('CREATES FOUR', $evt->id, 20.00, 100, 0, ['ticket_multiplier'=>4, 'fee_inc'=>1]);
      $catB = $this->createCategory('NORMAL', $evt->id, 50.00, 100, 0, ['fee_inc'=>1]);
  
  
      //return; //manual test fixture
  
      $client = new \WebUser($this->db);
      $client->login($user->username);
      $client->addToCart($catA->id, 1); //cart in session
      Utils::clearLog();
      $client->payWithMoneris();
  
      $this->assertRows(4, 'ticket');
      //also assert completed=1 (this is more a cash payment method test)
      $this->assertRows(1, 'ticket_transaction', " completed=1 AND cancelled=0" );
  
      $client = new \WebUser($this->db);
      $client->login($user->username);
      $client->addToCart($catB->id, 2); //cart in session
      Utils::clearLog();
      $client->payWithMoneris();
  
  
      //now view the report
  
  }
 
}


