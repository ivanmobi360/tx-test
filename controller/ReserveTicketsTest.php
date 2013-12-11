<?php
/**
 * I didn't write this module so this is essentially a test runner
 * to reproduce the conditions this module needs to be tested.
 * It expects data to already exists on ticket_pool. For this it seems that it is enough to run website/tableseatingcr.php
 * Update 2013-12-05: Apparently it is now webiste/assignXMLconstructor.php
 * @author Ivan Rodriguez
 *
 */

namespace controller;
use tool\TableRemover;

use tool\TableAssigner;

use model\Categoriesmanager;

use \WebUser, \Utils;
class ReserveTicketsTest extends \DatabaseBaseTest{
  
  
  function testAssign(){
    $this->clearAll();
    
    //$this->db->beginTransaction();
    
    //A merchant creates some event with 3 categories. (Basic->Open, Premium->Table As seats, VIP -> Table )
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent("Stranger in the Night", $seller->id, $this->createLocation()->id);
    $this->setEventId($evt, self::STRANGER_IN_THE_NIGHT_ID);
    $cat = $this->createCategory('Stranger 243', $evt->id, 100);
    $this->setCategoryId($cat, 243);
    

  }
  
  
  
}


