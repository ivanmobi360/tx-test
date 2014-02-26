<?php
namespace tool;
//Run from http://www.tixpro.local/the_firm_assignXMLconstructor.php
class TheFirmAssignXmlGeneratorTest extends \DatabaseBaseTest{
  
  public function testCreate(){
        $this->clearAll();
        $this->db->Query("TRUNCATE table ticket_pool");
            
        $main = new TheFirmAssignXmlGenerator();
        $main->build();
        
        \Utils::log(__METHOD__ . " xml: \n" . $main->getAssignXml() );
        
        //Expect 16 tables
        $this->db->Query("SELECT `table` FROM ticket_pool GROUP BY `table`");
        $this->assertEquals(16, $this->db->get_one("SELECT FOUND_ROWS()"));
        
        //Expect 82 tickets
        $this->assertRows(82, 'ticket_pool');
  }
  
     
}