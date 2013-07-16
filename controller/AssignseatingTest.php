<?php
namespace controller;
use Utils;
class AssignseatingTest extends \DatabaseBaseTest{
  
  
  public function testCreate(){
    $this->clearAll();
    
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Quebec CES' , $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $this->setEventId($evt, 'aaa');
    $cat = $this->createCategory('Verde', $evt->id, 10.00);
    
    
  }
  
  function getOkData(){
    return array (
          'reg_new_username' => '',
          'reg_confirm_username' => '',
          'reg_new_password' => '',
          'reg_confirm_password' => '',
          'reg_language_id' => '',
          'reg_name' => '',
          'reg_home_phone' => '',
          'reg_phone' => '',
          'reg_l_street' => '',
          'reg_l_country_id' => '',
          'reg_l_state' => '',
          'reg_l_city' => '',
          'reg_l_zipcode' => '',
          'reg_l_street2' => '',
          'user_id' => '1eb3e6d4',
          'total' => '29.54',
          244 => '1',
          'cat_list' => 
          array (
            0 => '244',
            1 => '242',
            2 => '243',
            3 => '78',
          ),
          242 => '0',
          243 => '0',
          78 => '0',
          'cc_holder_name' => 'Krusty Clown',
          'cc_number' => '5301250070000050',
          'cc_ccv' => '123',
          'cc_month' => '01',
          'cc_year' => '2021',
          'bil_name' => '3465 Hutchison',
          'bil_city' => 'Montreal',
          'bil_state' => 'Quebec',
          'bil_country' => 'Canada',
          'bil_zipcode' => 'H2X2G3',
          'mailing_list' => 'yes',
        );
  }
  
  

 
}

