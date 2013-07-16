<?php
use filters\ErrorTrackFilter;
class ErrorTrackFilterTest extends DatabaseBaseTest{
  
  public function testCreate(){
    
    //$this->clearAll();
    $obj = new ErrorTrackFilter('foo');
    
    $data = array('viewed'=>1);
    $obj->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals(1, $obj->getValue('viewed'));
    
    $data = array('viewed'=>0);
    $obj->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals(0, $obj->getValue('viewed'));
    
    $data = array();
    $obj->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals('foo', $obj->getValue('viewed', 'foo'));
  }
  
  
  public function tearDown(){
    $_SESSION = array();
  }
  
  
  
  
  
  
  
 
  
 

  
}