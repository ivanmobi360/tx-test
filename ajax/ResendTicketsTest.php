<?php

namespace ajax;

class ResendTicketsTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    $this->clearAll();
    
    //Setup for manual testing
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('My new event', $seller->id, 1);
    $this->setEventPaymentMethodId($evt, self::OUR_CREDIT_CARD);
    $cat = $this->createCategory('Sala', $evt->id, 100.00);
    $cat2 = $this->createCategory('Patio', $evt->id, 100.00);
    
    $foo = $this->createUser('foo');
    $this->buyTickets($foo->id, $cat->id);
    
    
    $bar = $this->createUser('bar');
    $txn_id = $this->buyTickets('bar', $cat2->id, 3);
    
    $this->clearRequest();
    $_POST = array('param' => 'resend', 'txn_id' => $txn_id);
    
    $ajax = new Resendtickets();
    $ajax->Process();

  }
  
  
}