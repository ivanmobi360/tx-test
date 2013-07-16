<?php
namespace tool;
use Utils;
class CartTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $loc = $this->createLocation();
    
    $evt = $this->createEvent('Circo', $seller->id, $loc->id, '2012-05-05');
    $cat = $this->createCategory('Fila', $evt->id, 15.45);
    
    $foo = $this->createUser('foo');
    
    $n = Cart::calculateRowValues($cat->id, 2, $foo->id );
    
    Utils::log(print_r($n, true));
    
  }
   
}