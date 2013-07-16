<?php
namespace cron;

use model\ReminderType;

use WebUser;

class MerchantInvoiceListTest extends \DatabaseBaseTest{
  
  protected function createInstance(){
    return new MerchantInvoiceCron();
  }
  
  function fixture(){
    //Create a good chunck of events/merchant invoices
    $this->clearAll();
    
    // ------------------
    //some finished event
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Marchas en Quito', $seller->id, 1, '2012-03-23'/*, date('Y-m-d', strtotime('+1 day'))*/  ); // use future to make it refundable
    $this->setEventId($evt, 'aaa');
    $this->setPaymentMethod($evt,self::OUR_CREDIT_CARD); //Apparently this still has no effect on writing price_ccfee values
    $cat1 = $this->createCategory('Conaie', $evt->id, 100.00);
    $cat2 = $this->createCategory('Ecuarurani', $evt->id, 25.00);
    
    $this->createBanner($evt, 1, 1);
    
    //have some buyers
    $buyer = $this->createUser('foo');
    $txn_id = $this->buyTickets($buyer->id, $cat1->id);
    
    $buyer = $this->createUser('bar');
    $this->buyTickets($buyer->id, $cat1->id, 3);
    
    // ----------------------------------------------------------------------------------------
    $this->createUser('seller2');
    $evt = $this->createEvent('Mercosur', 'seller2', $this->createLocation('Quito')->id,  '2012-04-15');
    $this->setEventId($evt, 'bbb');
    $this->setPaymentMethod($evt,self::OUR_CREDIT_CARD); //Apparently this still has no effect on writing price_ccfee values
    $cat1 = $this->createCategory('Argies', $evt->id, 100.00);
    $this->createBanner($evt, 2, 3);
    
    $buyer = $this->createUser('paris');
    $this->buyTickets('paris', $cat1->id);
    
    // ----------------------------------------------------------------------------------------
     $this->createUser('seller3');
    $evt = $this->createEvent('Cumbre de las Américas', 'seller3', $this->createLocation('Cartagena')->id,  '2012-04-19');
    $this->setEventId($evt, 'ccc');
    $cat1 = $this->createCategory('Negocios', $evt->id, 55.50);
    $cat2 = $this->createCategory('Gringos', $evt->id, 55.50);
    $this->createBanner($evt, 2, 3);
    $this->createBanner($evt, 3, 2);
    
    $buyer = $this->createUser('quo');
    $this->buyTickets('quo', $cat1->id, 3);
    $buyer = $this->createUser('vadis');
    $this->buyTickets('vadis', $cat2->id, 2);
    
    
    
   
    //for now hardcode cc fees on tickets
    $this->db->update('ticket', array('price_ccfee'=> 0.25), " 1 "); // it still doesn't seem to be working with my data :/  - Ivan
   
    
    // **********************************************************
  }
  
  public function testCreate(){
    $this->fixture();

    $cron = $this->createInstance();
    $cron->execute();
    
    //no new invoice created
    $total = 3;
    $this->assertRows($total, 'merchant_invoice' );
    $this->assertEquals($total, $cron->total_sent);
    
  }
  
  
  
  
  
  
  
  
  
  
  
  
  
 
}