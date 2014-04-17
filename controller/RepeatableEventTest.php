<?php

/**
 * The idea of repeat properties as part of the event creation was deprecated.
 * @author Ivan Rodriguez
 * Look at ajax\RepeatEventsTest for repeatable logic
 * @deprecated
 */
namespace controller;
use \WebUser, \Utils;
class RepeatableEventTest extends \DatabaseBaseTest{
  
  
  public function xtestTable(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $loc = $this->createLocation();
    $evt = $this->createEvent('Simple Event', $seller->id, $loc->id, $this->dateAt('+1 day') );
    $this->setEventId($evt, 'aaa');
    $cat = $this->createCategory('Normal', $evt->id, 100.00);
    
    
    $client = new WebUser($this->db);
    $client->login($seller->username);
    
    $this->clearRequest();
    $_POST = $this->repeatableEventData();
    Utils::clearLog();
    
    $cont = new Newevent(); 
    /*
    $id = $this->getLastEventId();
    $this->changeEventId($id, 'bbb');*/
    
    $this->assertRows(1, 'repeat_cycle');
    
    $this->clearRequest();
    
    
    $foo = $this->createUser('foo');
    
  }
  
  protected function repeatableEventData(){
    $data = array (
  'MAX_FILE_SIZE' => '3000000',
  'is_logged_in' => '1',
  'copy_event' => '081a9adc',
  'e_name' => 'Test Repeatable',
  'e_capacity' => '',
  'repeat_mode' => 'repeat',
  'e_date_from' => '2014-03-29',
  'e_time_from' => '',
  'e_date_to' => '',
  'e_time_to' => '',
  'repeat' => 
  array (
    'frequency' => 'weekly',
    'interval' => '2',
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
  'l_id' => '3',
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
    return $data;
  }
  
  
 
}


