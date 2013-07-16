<?php
namespace tool;
class PagerTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    
    $this->clearTestTable();
    $this->insertTestRows(50);
    
    $pager = new Pager("SELECT * FROM test", 5);
    $pager->setPage(1);
    $pager->init();
    $this->assertTrue($pager instanceof Pager);
    
    $this->assertTrue($pager->haveToPaginate());
    
    //First page
    $this->assertEquals(1, $pager->getPage());
    $this->assertEquals(10, $pager->getLastPage());
    $this->assertEquals(1, $pager->getPreviousPage());
    $this->assertEquals(2, $pager->getNextPage());
    $this->assertEquals(1, $pager->getFirstIndice());
    $this->assertEquals(5, $pager->getLastIndice());
    
    //Let's move onward - Page 2
    $pager->setPage(2);
    $this->assertEquals(2, $pager->getPage());
    $this->assertEquals(10, $pager->getLastPage());
    $this->assertEquals(1, $pager->getPreviousPage());
    $this->assertEquals(3, $pager->getNextPage());
    $this->assertEquals(6, $pager->getFirstIndice());
    $this->assertEquals(10, $pager->getLastIndice());
    
    
    
    //Let's move onward - Page 3
    $pager->setPage(3);
    $this->assertEquals(3, $pager->getPage());
    $this->assertEquals(10, $pager->getLastPage());
    $this->assertEquals(2, $pager->getPreviousPage());
    $this->assertEquals(4, $pager->getNextPage());
    $this->assertEquals(11, $pager->getFirstIndice());
    $this->assertEquals(15, $pager->getLastIndice());
    
    // ...
    
    //Let's move onward
    $pager->setPage(9);
    $this->assertEquals(9, $pager->getPage());
    $this->assertEquals(10, $pager->getLastPage());
    $this->assertEquals(8, $pager->getPreviousPage());
    $this->assertEquals(10, $pager->getNextPage());
    
    //Let's move onward
    $pager->setPage(10);
    $this->assertEquals(10, $pager->getPage());
    $this->assertEquals(10, $pager->getLastPage());
    $this->assertEquals(9, $pager->getPreviousPage());
    $this->assertEquals(10, $pager->getNextPage());
    
  }
  
  public function testEmpty(){
    
    $this->clearTestTable();
    
    $pager = new Pager("SELECT * FROM test", 5);
    $pager->setPage(1);
    $pager->init();
    
    $this->assertFalse($pager->haveToPaginate());
  }
  
  public function testGetLinks(){
    $this->clearTestTable();
    $this->insertTestRows(100);
    
    $pager = new Pager("SELECT * FROM test", 5);
    $pager->setPage(10);
    $pager->init();
    
    $this->assertTrue($pager->haveToPaginate());
    
    Log::write(print_r($pager->getLinks(), true));
  }
  
  public function testIterator(){
    $this->clearTestTable();
    $this->insertTestRows(10);
    
    $pager = new Pager("SELECT * FROM test", 5);
    $pager->setPage(1);
    $pager->init();
    
    $this->assertEquals(10, count($pager));
    
    $pager->rewind();
    $this->assertTrue($pager->valid());
    $row = $pager->current();
    $this->assertEquals('foo1', $row['title'] );
    
    $pager->setPage(2); //state change. Forces recreation of internal iterator
    $pager->rewind();
    $this->assertTrue($pager->valid());
    $row = $pager->current();
    $this->assertEquals('foo6', $row['title'] );
    $this->assertEquals(6, $pager->getFirstIndice());
    
    $cnt=0;
    foreach($pager as $row){
      $cnt++;
    }
    $this->assertEquals(5, $cnt);

  }
  

  
}