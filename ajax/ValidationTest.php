<?php
/**
 * @author Ivan Rodriguez
 * This is the one in the administration page!
 * For the Validation module, use ValidationTest!
 */
namespace ajax;


use tool\Request;

use Utils;

class ValidationTest extends TicketvalidationTest{
  
    
  function createInstance(){
    return new Validation();
  }
  
  
}