<?php
/**
 * @author Ivan Rodriguez
 * This is the one in the administration page!
 * For the Validation module, use ValidationTest!
 */
namespace ajax;


use tool\Request;

use Utils;

class TicketvalidationTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    $this->clearAll();
    
    //Setup for manual testing
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('My new event', $seller->id, 1);
    $cat = $this->createCategory('Sala', $evt->id, 100.00);
    
    $foo = $this->createUser('foo');
    //$client = new \WebUser($this->db);
    //$client->login($foo->username);
    $this->buyTickets($foo->id, $cat->id);
    
    //use a known code
    $code = 'AAAABBBBCCCCDDDD';
    $this->db->update('ticket', array('code' => $code ), " 1 ");
    
    //unscanned
    $this->assertScanned($code, 0);
    
    
    //return; //for manual setup
    Utils::clearLog();
    
    $_POST = array('data' => $code, 'event' => $evt->id);
    $ajax = $this->createInstance();
    $ajax->Process();
    
    //scanned
    $this->assertScanned($code, 1);
    //return;
    
    //************************************
    Utils::clearLog();
    Request::clear();
    $_POST = array('data' => $code, 'event' => $evt->id);
    $ajax = $this->createInstance();
    $ajax->Process();
    $ticket = $this->assertScanned($code, 1);
    
    //return;
    
    
    //***************************************
    
    Utils::clearLog();
    Request::clear();
    $_POST = array('data' => $code, 'event' => $evt->id, 'mode'=>'unscan');
    $ajax = $this->createInstance();
    $ajax->Process();
    $ticket = $this->assertScanned($code, 0);
    $this->assertEquals(1, $ticket['use_attempts'] );

  }
  
  function createInstance(){
    return new Ticketvalidation();
  }
  
  function getTicket($code){
    return $this->db->auto_array("SELECT * FROM ticket WHERE code=?", $code);
  }
  
  function assertScanned($code, $scanned){
    $ticket = $this->getTicket($code);
    $this->assertEquals($scanned, $ticket['used'] );
    return $ticket;
  }
  
}