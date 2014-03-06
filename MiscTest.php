<?php
use Moneris\MonerisTestTools;

class MiscTest extends DatabaseBaseTest{
  
  
  function testInsert(){
    
       $xml = MonerisTestTools::createCancelXml('foo', 'TX-ABC-123');
       
       $arr = $arr = tool\Xml::xmlToArray($xml); 
       Utils::log(print_r($arr, true));
       
       $this->assertEquals(914, $arr['response_code']);
       $this->assertEquals(-1, $arr['result']);


       //$arr = tool\Xml::toArray($xml);
       //Utils::log(print_r($arr, true));
  }


  
}

