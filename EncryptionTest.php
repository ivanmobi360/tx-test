<?php
class EncryptionTest extends DatabaseBaseTest{
  
  function setUp(){
    parent::setUp();
    
    $this->db->Query("TRUNCATE TABLE cc_info");
    
  }
  
  function testInsert(){
    
    $key = 'secret_key';
    
    $ccnum = '1234123412341234';
    
    $sql = "INSERT INTO `cc_info` 
     (`cc_num`, `service_code`, `name_on_card`)
     VALUES ( 
     AES_ENCRYPT('$ccnum', '$key' ) , 
     AES_ENCRYPT('1234', '$key'), 
     AES_ENCRYPT('John Doe', '$key')); 
    ";
    $this->db->Query($sql); //Encrypted insert, relies on binary columns
    
    $sql = "SELECT id, 
    AES_DECRYPT(`cc_num`,       '$key') AS `cc_num`, 
    AES_DECRYPT(`service_code`, '$key') AS `service_code`, 
    AES_DECRYPT(`name_on_card`, '$key') AS `name_on_card` 
	FROM `cc_info`";
    
    $row = $this->db->auto_array($sql);
    
    Utils::log(__METHOD__ . "recovered row: " . print_r($row, true) );
    
    $this->assertEquals($ccnum, $row['cc_num']);
    
        
  }

  
}

