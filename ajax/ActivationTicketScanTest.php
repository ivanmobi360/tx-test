<?php
/**
 * @author Ivan Rodriguez
 * Backport of TC's activation module test. Backport was made by Osvaldo.
 * Activation module
 */
namespace ajax;


use tool\Request;

use Utils;

class ActivationTicketScanTest extends \DatabaseBaseTest {
  
  protected $code = 'AAAABBBBCCCCDDDD';
    
  function createInstance(){
    return new Activation();
  }
  
  // ************************************ PRINTED TICKETS **************************************
  
  // Activate mode sets ticket to paid = 1;
  function testActivateMode(){
      $this->clearAll(); 
      
      $seller = $this->createUser('seller');
      
      $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id);
      $this->setEventId($evt, 'aaa');
      $this->setEventParams($evt, array('has_tax'=>0));
      $catA = $this->createCategory('VIP Adult', $evt->id, 100);
      
      $this->createPrintedTickets(5, $evt->id, $catA->id, 'Adult');
      
      //use a known code
      $code = $this->code;
      $this->db->update('ticket', array('code' => $code ), " 1 LIMIT 1");
      
      $ticket = $this->getTicket($code);
      $this->assertEquals(0, $ticket['paid']); //Apparently, unlike TC, on TX printed tickets are created as paid=1. 2014 24 01 Edit: Nope, they're again set to paid=0 
      
      //a quick test of this function, because we added joins to the sql and don't have time to setup a full tour test
      //$ticket2 = \model\Validation::getTicketinEvent($code, $evt->id); //not present in TX
      //$this->assertEquals($ticket['category_id'], $ticket2['category_id']);
      
      //return; //for manual testing
      
      //Validate it
      Utils::clearLog();
      Request::clear();
      $_POST = array('data' => $code, 'event' => $evt->id, 'mode'=>'activate', 'check_balance'=>0);
      $ajax = $this->createInstance();
      $ajax->Process();
      
      $ticket = $this->getTicket($code);
      $this->assertEquals(1, $ticket['paid']);
      
      //we should be able to deactivate it
      Request::clear();
      $_POST = array('data' => $code, 'event' => $evt->id, 'mode'=>'deactivate', 'check_balance'=>0);
      $ajax = $this->createInstance();
      $ajax->Process();
      
      $ticket = $this->getTicket($code);
      $this->assertEquals(0, $ticket['paid']);
  }
  
  
  
}