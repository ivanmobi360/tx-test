<?php
use tool\Ordering;
use tool\Route;
class RouteTest extends DatabaseBaseTest{
  
  public function testCreate(){
    $route = new Route();
    $route->set('test');
    
    $this->assertEquals('test-1-2', $route->generate(array( 'foo' => 1, 'bar' => 2  )));
    $this->assertEquals('test-1-2', $route->generate(array( 'bar' => 2, 'foo' => 1  )));
    $this->assertEquals('test-1-2', $route->generate(array( 'bar' => 2, 'foo' => 1  )));
    
    $this->assertEquals('test-1-2?x=y', $route->generate(array( 'foo' => 1, 'bar' => 2, 'x' => 'y'  )));
    $this->assertEquals('test-1-2?x=y', $route->generate(array( 'foo' => 1, 'x' => 'y', 'bar' => 2  )));
    $this->assertEquals('test-1-2?x=y', $route->generate(array( 'x' => 'y', 'foo' => 1, 'bar' => 2  )));
    
    $route = new Route('test');
    $this->assertEquals('test-1-2', $route->generate(array( 'foo' => 1, 'bar' => 2  )));
    
    //ordering mix;
    $order = new Ordering();
    $order->addRule('date', 'foo.date', 'ASC');
    //$order->setCurrent('date');
    $order->findByParams(array('orderby'=>'date', 'sort'=>'desc'));
    $expected = array( 'a'=>'b', 'orderby' =>'date', 'sort'=>'desc' );
    $this->assertEquals($expected, $order->asArray('date', array('a'=>'b')) );
    $expected['sort'] = 'asc';
    $this->assertEquals($expected, $order->asInvArray('date', array('a'=>'b')) );
    
    //now a mixed route
    $this->assertEquals('test-1-2?orderby=date&sort=asc', $route->generate( $order->asInvArray('date', array('foo'=>1, 'bar'=>2)  )));
    
    
    //new order
    $order = new Ordering();
    $order->addRule('name', 'table.name');
    $order->addRule('surname', 'table.surname');
    
    $qs = array('orderby'=>'name', 'sort'=>'desc', 'foo'=>1, 'bar'=>2);
    
    $order->findByParams($qs);
    //$order->setCurrent('name');
    $this->assertEquals('test-1-2?orderby=name&sort=asc', $route->generate( $order->asInvArray('name', $qs  )));
    $this->assertEquals('test-1-2?orderby=surname&sort=asc', $route->generate( $order->asInvArray('surname', $qs )));
    
  }
   
}