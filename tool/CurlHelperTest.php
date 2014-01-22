<?php
namespace tool;
use Utils;
class CurlHelperTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    
  	$client = new CurlHelper();
  	
  	$res = $client->get('http://www.google.com');
    
    Utils::log($res);
    
  }
   
}