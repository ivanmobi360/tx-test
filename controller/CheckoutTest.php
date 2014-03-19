<?php
/**
 * TC backport, but in reality we'll do some setups here to be able to port back the has_ccfee flag
 * @author Ivan Rodriguez
 *
 */
namespace controller;
use Utils;
class CheckoutTest extends \DatabaseBaseTest{
  
  
    function testPurchase(){
    
        $this->clearAll();
    
        //create buyer
        $user = $this->createUser('foo');
        $seller = $this->createUser('seller');
    
        // **********************************************
        // Eventually this test will break for the dates
        // **********************************************
        $evt = $this->createEvent('Elecciones 2013', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
        $this->setEventId($evt, 'aaa');
        $this->setPaymentMethod($evt, self::MONERIS);
        $this->setEventParams($evt, array('has_ccfee'=>0));
        $catA = $this->createCategory('SILVER', $evt->id, 100);
        
        //return;
    
        $client = new \WebUser($this->db);
        $client->login($user->username);
        $client->addToCart($catA->id, 1); //cart in session
        
        Utils::clearLog();
        
        $client->payWithMoneris();
        
        //Expect zero ccfees
        $ticket = $this->db->auto_array("SELECT * FROM ticket LIMIT 1");
        $this->assertEquals(0, $ticket['price_ccfee']);
        
    } 
  
  

 
}

