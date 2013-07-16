<?php
namespace Optimal;
use Utils;

class ResponseTest extends \DatabaseBaseTest{
  
  function getError(){
    return file_get_contents(__DIR__ . "/error.xml");
  }
  
  function getSuccess(){
    return file_get_contents(__DIR__ . "/success.xml");
  }
  
  public function testCreate(){
    
    $response = Response::fromXml($this->getError());
    $this->assertEquals('ERROR', $response->decision);
    $this->assertEquals(4, count($response->detail));
    
    
  }

 
}