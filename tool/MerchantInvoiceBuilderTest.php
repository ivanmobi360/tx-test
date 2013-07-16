<?php
namespace tool;

//use tool\MerchantInvoiceBuilder;

use model\MerchantInvoices;

use model\ReminderType;

use WebUser;

class MerchantInvoiceBuilderTest extends \DatabaseBaseTest{
  
   
  //fixture only
  function test_paypal_and_cc(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Ivans Caribbean Taste', $seller->id, $this->createLocation()->id); //paypal event
    $this->setEventId($evt, 'aaa');
    $cat1 = $this->createCategory('Floor', $evt->id, 100.00, 510, 255, array('tax_inc'=>1/*, 'cc_fee_id'=>11*/) );
    
    $this->createBanner($evt, 1, 1);
    
    $buyer = $this->createUser('foo');
    $txn_id = $this->buyTickets($buyer->id, $cat1->id, 2);
    
    $this->setPaymentMethod($evt, self::OUR_CREDIT_CARD); //required to force the charge of cc fees - ASSERTED LATER!
    $buyer = $this->createUser('bar');
    $txn_id = $this->buyTicketsWithCC($buyer->id, $cat1->id);
    
    $this->assertTrue(0 != $this->db->get_one("SELECT COUNT(id) FROM ticket WHERE price_ccfee!=0 "));
    
    //make the first ticket printed=1, that should move it to the third box
    $this->db->update('ticket', array('printed'=>1), "id=1");
    
    //build the invoice
    $builder = new MerchantInvoiceBuilder();
    $builder->createForEvent($evt->id);
    
    
    // *********************************************************************************
    //Another seller. Verify only owned invoices are listed on website
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Embassy escape', $seller->id, $this->createLocation('Third Ambassy')->id);
    $this->setEventId($evt, 'bbb');
    $cat = $this->createCategory('Refugee Room', $evt->id, 50.00);
    $this->buyTickets('foo', $cat->id);
    
    $builder = new MerchantInvoiceBuilder();
    $builder->createForEvent($evt->id);
    
  }
  
  function testEmpty(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Ivans Caribbean Taste', $seller->id, $this->createLocation()->id); //paypal event
    $this->setEventId($evt, 'aaa');
    $cat1 = $this->createCategory('Floor', $evt->id, 100.00, 510, 255, array('tax_inc'=>1) );
    
    $builder = new MerchantInvoiceBuilder();
    $builder->createForEvent($evt->id);
  }
  
  
  
  
  
  
  
  
  
 
}