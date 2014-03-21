<?php
/**
 * Ticket Multiplier logic 
 * @author Ivan Rodriguez
 *
 */
class ShortDescriptionTest extends DatabaseBaseTest{
  
  function testShort(){
      
    $this->clearAll();
    
    $user = $this->createUser('foo');
    $seller = $this->createUser('seller');
    $bo_id = $this->createBoxoffice('111-xbox', $seller->id);
    
    Utils::clearLog();
    $evt = $this->createEvent('Short Description Test', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
    $this->setEventId($evt, 'aaargh');
    $this->setEventParams($evt->id, ['description'=> $this->getLongDescription(), 'short_description'=> $this->charGenerator(\model\Events::SHORT_DESCRIPTION_MAX_LENGHT) ]);
    $catA = $this->createCategory('KID', $evt->id, 20.00);
    $catB = $this->createCategory('NORMAL', $evt->id, 50.00);
    
    $evt = new \model\Events($evt->id);
    $this->assertEquals(\model\Events::SHORT_DESCRIPTION_MAX_LENGHT, strlen($evt->short_description));
    
        
    $client = new \WebUser($this->db);
    $client->login($user->username);
    $client->addToCart($catA->id, 1); //cart in session
    Utils::clearLog();
    $client->payByCash($client->placeOrder());
    
  }
  
  protected function charGenerator($n){
      $res = '';
      for($i=1; $i<=$n; $i++){
          $res.= 'x';
      }
      $res[$n-1] = 'y';
      return $res;
  }
  
  
  
 protected function getLongDescription(){
     $txt= <<<eot
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam vitae lorem vitae purus aliquam consectetur ac quis lacus. Phasellus libero eros, consectetur nec tincidunt nec, varius sagittis lectus. Etiam sed ligula nisi. Donec volutpat est aliquet arcu blandit dictum. Pellentesque a orci elit. Ut adipiscing leo purus, et sollicitudin eros pulvinar sit amet. In auctor ullamcorper neque vel lacinia. Pellentesque egestas ornare est et malesuada. Aenean varius ullamcorper metus.

Cras ut lectus quis diam vestibulum semper. Nulla tristique metus justo, sit amet scelerisque tellus cursus vitae. Nulla porta scelerisque urna, ut lobortis velit ultricies ac. Cras accumsan imperdiet sem, a dignissim nisi commodo vitae. Cras posuere quam risus, quis imperdiet lectus scelerisque ac. Mauris at egestas massa, id commodo eros. Donec vitae fermentum diam, sit amet commodo justo. Fusce iaculis odio arcu, vel rhoncus magna bibendum ac. Fusce aliquam felis risus, eu ultricies massa bibendum quis. Integer nibh nisi, consectetur et porttitor in, pharetra nec lacus. In pellentesque, elit ut ultricies imperdiet, arcu urna tristique lorem, sit amet elementum lorem odio a neque. Quisque a quam tristique, ultrices ante ac, gravida nisi. Donec facilisis massa erat, et ullamcorper lorem vehicula non. Morbi lacinia purus ac pharetra laoreet.

Mauris non leo eu lectus consectetur blandit eu vel libero. Morbi sodales lacus quis convallis rhoncus. Nam elementum neque ut risus tristique fringilla. Morbi facilisis mollis sagittis. Praesent at convallis nibh, eu feugiat risus. Donec fermentum sit amet nunc eu rutrum. Morbi imperdiet arcu scelerisque dolor laoreet tristique. Pellentesque aliquam tincidunt orci sit amet sodales. Aenean a massa accumsan, porta mauris eu, condimentum ligula. Integer vel volutpat neque. Pellentesque semper diam quis erat aliquet, sed congue odio molestie.

In vulputate orci at neque tristique, in elementum nibh hendrerit. Pellentesque porta nulla id sodales vehicula. Integer nec condimentum orci, id dictum mauris. Donec sodales lorem ac purus interdum venenatis. Sed et tellus sit amet lectus pellentesque rutrum. In hac habitasse platea dictumst. Fusce tincidunt, mauris eget sodales laoreet, nisl sem tempor ante, id placerat quam purus vel nibh. Mauris sed consequat sem, ac sagittis lectus. Pellentesque commodo neque eros, non lacinia velit tincidunt in. Duis viverra arcu diam, eget hendrerit nisi porta ac. Morbi aliquet tellus ut tellus vehicula dictum. Praesent semper lacus eget lorem vehicula, vel semper sem luctus. Aliquam vehicula bibendum risus, sit amet egestas tortor dapibus non.

Ut egestas nunc dapibus sapien pellentesque, at facilisis neque molestie. Fusce tristique aliquam orci id hendrerit. Aliquam erat volutpat. Fusce fringilla porta enim, eu dictum lectus vulputate a. Vivamus semper risus at ultrices laoreet. Praesent lorem turpis, vestibulum ac semper et, pulvinar sed metus. Proin nec dolor turpis. Quisque feugiat at arcu mollis ullamcorper.

Mauris non leo eu lectus consectetur blandit eu vel libero. Morbi sodales lacus quis convallis rhoncus. Nam elementum neque ut risus tristique fringilla. Morbi facilisis mollis sagittis. Praesent at convallis nibh, eu feugiat risus. Donec fermentum sit amet nunc eu rutrum. Morbi imperdiet arcu scelerisque dolor laoreet tristique. Pellentesque aliquam tincidunt orci sit amet sodales. Aenean a massa accumsan, porta mauris eu, condimentum ligula. Integer vel volutpat neque. Pellentesque semper diam quis erat aliquet, sed congue odio molestie.

In vulputate orci at neque tristique, in elementum nibh hendrerit. Pellentesque porta nulla id sodales vehicula. Integer nec condimentum orci, id dictum mauris. Donec sodales lorem ac purus interdum venenatis. Sed et tellus sit amet lectus pellentesque rutrum. In hac habitasse platea dictumst. Fusce tincidunt, mauris eget sodales laoreet, nisl sem tempor ante, id placerat quam purus vel nibh. Mauris sed consequat sem, ac sagittis lectus. Pellentesque commodo neque eros, non lacinia velit tincidunt in. Duis viverra arcu diam, eget hendrerit nisi porta ac. Morbi aliquet tellus ut tellus vehicula dictum. Praesent semper lacus eget lorem vehicula, vel semper sem luctus. Aliquam vehicula bibendum risus, sit amet egestas tortor dapibus non.

Ut egestas nunc dapibus sapien pellentesque, at facilisis neque molestie. Fusce tristique aliquam orci id hendrerit. Aliquam erat volutpat. Fusce fringilla porta enim, eu dictum lectus vulputate a. Vivamus semper risus at ultrices laoreet. Praesent lorem turpis, vestibulum ac semper et, pulvinar sed metus. Proin nec dolor turpis. Quisque feugiat at arcu mollis ullamcorper.             
eot;
     return nl2br($txt);
 } 

 
}