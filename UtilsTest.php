<?php

class UtilsTest extends BaseTest{
  
  
  public function testWalk(){
    $x = 'A';
    
    for($i = 'A'; $i!='ZZ'; $i++ ){
      //echo $i . "\n"; //simple incrementor confirmed to work
    }
    
    $col = 'C';
    //only pre increments worked
    $this->assertEquals('D', ++$col);
    $this->assertEquals('E', ++$col);
    
    //all these failed
    $col = 'C';
    //$this->assertEquals('D', $col+1);
    //$this->assertEquals('D', ($col+1));
    //$this->assertEquals('B', --$col);
    
  }
  
  function testBracketGet(){
      $data = $this->getFormData();
      
      $this->assertEquals('weekly', Utils::getArrayParam('repeat[frequency]', $data));
      $this->assertEquals(['mo', 'we', 'fr'], Utils::getArrayParam('repeat[r_at][byday]', $data));
      $this->assertFalse(in_array('tu', Utils::getArrayParam('repeat[r_at][byday]', $data)));
      $this->assertTrue(in_array('mo', Utils::getArrayParam('repeat[r_at][byday]', $data)));
      $this->assertFalse(Utils::getArrayParam('foo[bar][baz]', $data));
  }
  
  protected function getFormData(){
      return $data = array (
  'MAX_FILE_SIZE' => '3000000',
  'is_logged_in' => '1',
  'copy_event' => '081a9adc',
  'e_name' => 'Test Repeat 3',
  'e_capacity' => '',
  'repeat_mode' => 'repeat',
  'e_date_from' => '2014-03-29',
  'e_time_from' => '',
  'e_date_to' => '',
  'e_time_to' => '',
  'repeat' => 
  array (
    'frequency' => 'weekly',
    'interval' => '1',
    'r_at' => 
    array (
      'byday' => 
      array (
        0 => 'mo',
        1 => 'we',
        2 => 'fr',
      ),
    ),
    'range' => 'until',
    'until' => '2014-04-25',
  ),
  'e_description' => '<p>blah</p>',
  'e_short_description' => '',
  'reminder_email' => '',
  'sms' => 
  array (
    'content' => '',
  ),
  'c_id' => '2',
  'l_latitude' => '52.9399159',
  'l_longitude' => '-73.5491361',
  'l_id' => '4',
  'dialog_video_title' => '',
  'dialog_video_content' => '',
  'id_ticket_template' => '1',
  'e_currency_id' => '1',
  'payment_method' => '7',
  'has_ccfee_cb' => '1',
  'paypal_account' => '',
  'no_tax' => 'on',
  'tax_ref_hst' => '',
  'tax_ref_pst' => '',
  'tax_other_name' => '',
  'tax_other_percentage' => '0',
  'tax_ref_other' => '',
  'ticket_type' => 'open',
  'cat_all' => 
  array (
    0 => '0',
  ),
  'cat_0_type' => 'open',
  'cat_0_name' => 'Normal',
  'cat_0_description' => '',
  'cat_0_multiplier' => '1',
  'cat_0_capa' => '100',
  'cat_0_over' => '0',
  'cat_0_price' => '45.00',
  'cat_0_feeIsInc' => '1',
  'create' => 'do',
  'has_ccfee' => '1',

        
    );
  }
  

  
  
}


