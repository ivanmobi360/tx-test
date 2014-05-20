<?php
class DatabaseTest extends DatabaseBaseTest{
  
  public function clearTestTable(){
    $this->db->Query("TRUNCATE table test");
  }
  
  public function testCreate(){
    $this->assertEquals( $this->database_name, $this->db->getDbName());
    $error = $this->db->error();
  }
  
  protected function getPost($id){
    return $this->db->auto_array("SELECT * FROM test WHERE id=?", $id);
  }
  
  public function testQuery(){
    $this->clearTestTable();
    //Insert
    $this->db->insert('test', array('title'=>'foo', 'content'=>'Some Content', 'hits'=>5), true);
    $id = $this->db->insert_id();
    
    $this->db->insert('test', array('title'=>'bar', 'content'=>'Some bar Content', 'hits'=>99), true); //another row to keep around for final test
    $id2 = $this->db->insert_id();
    $this->db->insert('test', array('title'=>'baz', 'content'=>'Some baz Content', 'hits'=>55), true); //another row to keep around for final test
    $id3 = $this->db->insert_id();
    
    $this->assertEquals(1, $id);
    
    //Retrieve one
    $this->assertEquals(5, $this->db->get_one("SELECT hits FROM test WHERE id='$id'") );
    $this->assertEquals(5, $this->db->get_one("SELECT hits FROM test WHERE id=?", array($id)) );
    $this->assertEquals(5, $this->db->get_one("SELECT hits FROM test WHERE id=?", $id) ); //shortcut
    
    //Retrieve
    $post = $this->db->auto_array("SELECT * FROM test WHERE id='$id'");
    $this->assertEquals($id, $post['id']);
    $this->assertEquals('foo', $post['title']);
    $this->assertEquals('Some Content', $post['content']);
    $this->assertEquals(5, $post['hits']);
    
    //again, but with ?
    $post = $this->db->auto_array("SELECT * FROM test WHERE id=?", array($id));
    $this->assertEquals($id, $post['id']);
    $this->assertEquals('foo', $post['title']);
    $this->assertEquals('Some Content', $post['content']);
    $this->assertEquals(5, $post['hits']);
    
    //? and scalar
    $post = $this->db->auto_array("SELECT * FROM test WHERE id=?", $id);
    $this->assertEquals($id, $post['id']);
    $this->assertEquals('foo', $post['title']);
    $this->assertEquals('Some Content', $post['content']);
    $this->assertEquals(5, $post['hits']);
    
    //update
    $this->db->update( 'test', array('hits'=>10), " id='$id' " );
    $post = $this->getPost($id);
    $this->assertEquals(10, $post['hits']);
    
    //update with ?
    $this->db->update( 'test', array('hits'=>11), " id=? ", array($id) );
    $post = $this->getPost($id);
    $this->assertEquals(11, $post['hits']);
    
    //update with ? and scalar
    $this->db->update( 'test', array('hits'=>12), " id=? ", $id );
    $post = $this->getPost($id);
    $this->assertEquals(12, $post['hits']);
    
    //Delete
    $this->db->delete('test', " id='$id' ");
    $this->assertFalse( $this->getPost($id));
    
    //Delete and ?
    $this->db->delete('test', " id=? ", array($id2));
    $this->assertFalse( $this->getPost($id2));
    
    //Delete and ? and scalar
    $this->db->delete('test', " id=? ", $id3);
    $this->assertFalse( $this->getPost($id3));

  }
  
  public function testResultset(){
    $this->clearTestTable();
    $this->insertTestRows(10);
     
    $result = $this->db->Query("SELECT * FROM test");
    
    $this->assertEquals(10, $this->db->num_rows($result));
    
    
    $post = $this->db->fetch_row($result);
    $this->assertEquals(1 , $post['id']);
    $this->assertEquals(1 , $post[0]);
    
    $post = $this->db->fetch_row($result);
    $this->assertEquals(2 , $post['id']);
    $this->assertEquals(2 , $post[0]);
    
    $post = $this->db->fetch_row($result);
    $this->assertEquals(3 , $post['id']);
    $this->assertEquals(3 , $post[0]);
    
    $post = $this->db->fetch_row($result);
    $post = $this->db->fetch_row($result);
    $post = $this->db->fetch_row($result);
    
    $post = $this->db->fetch_row($result);
    $this->assertEquals(7 , $post['id']);
    $this->assertEquals(7 , $post[0]);
    
    //reset - bring array view
    //$result = $this->db->Query("SELECT * FROM test");
    //$posts = $this->db->fetch_row($result, true);
    $posts = $this->db->getAll( "SELECT * FROM test" );
    for ($i =1; $i<=10; $i++ ){
      $this->assertEquals($i, $posts[$i-1]['id']);  
    }
    
    
  }
  
  public function testFormatQuery(){
    $sql = "SELECT * FROM test WHERE id=?";
    $sql2 = $this->db->formatQuery($sql, array(5));
    
    $this->assertEquals( "SELECT * FROM test WHERE id='5'", $sql2  );
    
    $sql = "SELECT * FROM test WHERE userid=? AND deleted=?";
    $res = "SELECT * FROM test WHERE userid='foo' AND deleted='0'";
    $this->assertEquals( $res, $this->db->formatQuery($sql, array('foo', 0 )) );
    $this->assertEquals( $res, $this->db->formatQuery($sql, array('foo', 0, 'bar', 'baz' )) );
    
    
    $sql = "SELECT * 
            FROM test 
            WHERE id=? 
            OR id=?";
    $parsed = $this->db->formatQuery($sql, array('foo', 0));
    $this->assertFalse(strpos('\n', $parsed) );
    $this->db->Query($parsed);
    
    
    $sql = "foo=?";
    //strings with octal like numbers
    $this->assertEquals( "foo='025'", $this->db->formatQuery($sql, array('025')));
    $this->assertEquals( "foo='025'", $this->db->formatQuery($sql, '025' ));
    //strings with hex like numbers
    $this->assertEquals( "foo='0x25'", $this->db->formatQuery($sql, array('0x25')));
    $this->assertEquals( "foo='x25'", $this->db->formatQuery($sql, 'x25' ));

  }
  
  public function testResultset2(){
    $this->clearTestTable();
    $this->insertTestRows(10);
     
    $result = $this->db->Query("SELECT * FROM test WHERE id=? OR id=? ", array(3, 5) );
    $this->assertEquals(2 , $this->db->num_rows($result));
  }
  
  public function testWalkIterator(){
    $this->clearTestTable();
    $this->insertTestRows(2);
    
    $iterator = $this->db->getIterator("SELECT * FROM test");
    
    $this->assertFalse($iterator->valid());
    
    
    //Must call rewind first
    $iterator->rewind();
    
    //1st item
    $this->assertTrue($iterator->valid());
    $row = $iterator->current();
    $this->assertEquals( 1, $row['id'] );
    $this->assertEquals( 'foo1', $row['title'] );
    $iterator->next();
    
    //2nd item
    $this->assertTrue($iterator->valid());
    $row = $iterator->current();
    $this->assertEquals( 2, $row['id'] );
    $this->assertEquals( 'foo2', $row['title'] );
    $iterator->next();
    
    $this->assertFalse($iterator->valid());
    
  }
  
  public function testForEachIterator(){
    $this->clearTestTable();
    $this->insertTestRows(20);
    
    $iterator = $this->db->getIterator("SELECT * FROM test");
    $n = 0;
    foreach($iterator as $row){
      $n++;
    }
    $this->assertEquals(20, $n);
    
    
    $iterator = $this->db->getIterator("SELECT * FROM test WHERE id<=?", array(10));
    $n = 0;
    foreach($iterator as $row){
      $n++;
    }
    $this->assertEquals(10, $n);
    
  }
  
  public function testEmptyIterator(){
    $this->clearTestTable();
    $this->insertTestRows(10);
    $iterator = $this->db->getIterator("SELECT * FROM test WHERE id=30");
    $n =0;
    foreach($iterator as $row){
      $n++;
    }
    $this->assertEquals(0, $n);
  }
  
  
  protected function insertTestRows($total){
    $this->db->beginTransaction();
    for($i = 1; $i<=$total; $i++){
      $this->db->insert('test', array('title'=>"foo$i", 'content'=>"Some Content $i", 'hits'=>10+$i), true);
     }
    $this->db->commit();
  }
  
  /**
   * @expectedException Exception
   */
  public function testException(){
    $this->clearTestTable();
    $this->db->insert('test', array('foo'=>'bar'));
  }
  
  // **************** Test Quentin's functions (if possible, deprecate!!!)
  function testGetData(){
  	$this->clearTestTable();
  	$data = ['title'=>'foo', 'content'=>'bar', 'hits'=>255];
  	$this->db->insert('test', $data);
  	$res = $this->db->getData("SELECT title, hits, content FROM test WHERE id=1", \Database::ROW);
  	$this->assertEquals($data, $res);
  	
  	$this->clearTestTable();
  	$this->insertTestRows(5);
  	$res = $this->db->getData("SELECT title, hits, content FROM test WHERE id=1", \Database::ROW);
  	$this->assertEquals('foo1', $res['title']);
  	
  	$this->assertEquals(5, $this->db->getData("SELECT COUNT(id) FROM test", \Database::CELL) ); //scalar
  	$this->assertEquals(12, $this->db->getData("SELECT hits FROM test WHERE id=2", \Database::CELL) );
  	$this->assertEquals(12, $this->db->getData("SELECT hits FROM test WHERE id=?", \Database::CELL, \Database::ASSOC, 2) );
  	
  }
  
  function testSetData(){
  	$this->clearTestTable();
  	$data = ['title'=>'foo', 'content'=>'bar', 'hits'=>255];
  	$this->db->insert('test', $data);
  	
  	$res = $this->db->setData("UPDATE test SET content='baz'");
  	Utils::dump($res, __METHOD__);
  	$this->assertEquals('baz', $this->db->get_one("SELECT content FROM test LIMIT 1"));
  	
  	//force error?
  	try {
  		$res = $this->db->setData("UPDATE test SET blah='baz'");
  		$this->fail("Didn't fail");
  	} catch (Exception $e) {
  	}
  	
  	
  }
  
  function testGetLastId(){
	$this->clearTestTable();
	foreach(range(1, 10) as $id){
		$this->db->insert('test', ['title'=>$id, 'content'=>$id]);
		$this->assertEquals($id, $this->db->insert_id());
	}  	
  }
  
  function testSelect(){
  	//aparently this function can accept either null (select *), str (SELECT str), or array (SELECT [col1, ... coln]) syntax
  	$this->clearTestTable();
  	$this->insertTestRows(5);
  	$this->assertEquals(5, count($this->db->select('test')));
  	
  	$row = $this->db->select('test', ['title', 'content'], ['id'=>5], \Database::ROW );
  	Utils::dump($row);
  	$this->assertEquals(2, count($row));
  	
  	$row = $this->db->select('test', 'title', ['id'=>4], \Database::CELL );
  	$this->assertEquals('foo4', $row);
  	
  	$this->assertEquals(range(1, 5), $this->db->select('test', ['id'], '1', \Database::COL));
  	$this->assertEquals(range(1, 5), $this->db->get_col("SELECT id FROM test"));
  	
  	$this->db->update('test', ['title'=>'foo'], ' 1 ');
  	$this->assertEquals(5, $this->db->affected_rows());
  }
  
  
}