<?php
use tool\Ordering;
class OrderingTest extends DatabaseBaseTest{
  
  public function testCreate(){
    $obj = new Ordering();
    $obj->addRule('date', 't.date_processed', 'DESC'  );
    $obj->addRule('count', 't.count');
    $obj->addRule('name', 't.name');
    
    $obj->setCurrent('date');
    
    $byDate =  array('orderby'=>'date', 'sort'=>'desc');
    $this->assertEquals($byDate, $obj->asArray()  );
    
    //Clear
    $obj->setCurrent(false);
    $this->assertEquals(array(), $obj->asArray()  );
    
    $qs = array('foo'=>'bar', 'orderby'=>'count');
    $obj->findByParams($qs);
    $expected =  array('orderby'=>'count', 'sort'=>'asc');
    $this->assertEquals($expected, $obj->asArray()  );
    
    $expected =  array('orderby'=>'count', 'sort'=>'desc');
    $obj->findByParams( array('baz' => 'bal', 'orderby'=>'count', 'sort'=>'desc'   ) );
    $this->assertEquals($expected, $obj->asArray()  );
    
    $obj->findByParams( array('foo' => 'bar' ) );
    $this->assertEquals(array(), $obj->asArray()  );
    
    $obj->findByParams( array('orderby' => 'secret', 'sort'=>'asc' ) );
    $this->assertEquals(array(), $obj->asArray()  );
    
    $obj->findByParams( array('orderby' => 'name') );
    $this->assertEquals("ORDER BY t.name ASC", $obj->getSql()  );
    $this->assertEquals( 'orderby=name&sort=desc', $obj->getAltQs('name') );
    
    $obj->findByParams( array('orderby' => 'name', 'sort'=>'desc') );
    $this->assertEquals("ORDER BY t.name DESC", $obj->getSql()  );
    $this->assertEquals( 'orderby=name&sort=asc', $obj->getAltQs('name') );
    
    $this->assertEquals( 'orderby=count&sort=asc', $obj->getAltQs('count') );
    $this->assertEquals( '', $obj->getAltQs('foo') );
  }
  
  function testDefault(){
    $this->ordering = new Ordering();
    $this->ordering->addRule('ticket_count', 'ticket_count');
    $this->ordering->addRule('date', 'ticket_transaction.date_processed', 'DESC');
    $this->ordering->setCurrent('date');
    
    $sql = 'ORDER BY ticket_transaction.date_processed DESC';
    //echo $this->ordering->getSql();
    
    $this->assertEquals($sql, $this->ordering->getSql());
    
    $this->ordering->findByParams( array(), 'date' );
    $this->assertEquals($sql, $this->ordering->getSql()  );
    
  }
   
}