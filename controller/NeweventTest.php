<?php


namespace controller;
use \WebUser, \Utils;
class NeweventTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();
    //return;
    
    //2 open categories - not logged in
    $qs = "MAX_FILE_SIZE=3000000&is_logged_in=0&e_name=some&e_date_from=2012-03-01&e_time_from=&e_date_to=&e_time_to=&e_description=%3Cp%3Easdfsdfas%3C%2Fp%3E&c_name=Bill+Gates&c_email=billgates%40gmail.com&c_phone=1234567890&l_latitude=53.9332706&l_longitude=-116.5765035&l_name=My+Location&l_street=Some+street&l_street2=&l_country_id=124&l_state_id=4&l_state=&l_city=Some+city&l_zipcode=ABC&dialog_video_title=&dialog_video_content=&id_ticket_template=1&e_currency_id=1&paypal_account=d00d%40blah.com&use_paypal=1&tax_ref_hst=&tax_ref_gst=456545&tax_ref_pst=&tax_other_name=&tax_other_percentage=&tax_ref_other=&ticket_type=open&cat_all%5B%5D=1&cat_1_name=Pear&cat_1_description=the+pear+room&cat_1_capa=30&cat_1_over=10&cat_1_price=7.00&cat_1_taxIsInc=1&cat_all%5B%5D=0&cat_0_name=Orange&cat_0_description=the+orange+kun&cat_0_capa=50&cat_0_over=5&cat_0_price=10.00&cat_0_taxIsInc=1&u_username=seller%40blah.com&u_new_password=123456&u_confirm_password=123456&u_language_id=en&u_c_same=true&u_c_name=&u_c_email=&u_c_phone=&u_l_latitude=&u_l_longitude=&u_l_same=true&u_l_name=&u_l_street=&u_l_street2=&u_l_country_id=124&u_l_state_id=4&u_l_state=&u_l_city=&u_l_zipcode=&b_u_l_latitude=&b_u_l_longitude=&b_u_l_same=true&b_u_l_name=&b_u_l_street=&b_u_l_street2=&b_u_l_country_id=124&b_u_l_state_id=4&b_u_l_state=&b_u_l_city=&b_u_l_zipcode=&create=do";
    
    parse_str($qs, $_POST);
    
    //Utils::log(print_r($_POST, true));
    
    $cont = new Newevent(); //all the logic in the constructor haha

    
  }
  
  public function testTable(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $loc = $this->createLocation('Quito');
    $evt = $this->createEvent('Cenepa', $seller->id, $loc->id, $this->dateAt('+1 day') );
    $this->setEventId($evt, 'aaa');
    $cat = $this->createCategory('Ele', $evt->id, 100.00);
    
    
    $client = new WebUser($this->db);
    $client->login($seller->username);
    $_POST = $this->getCreateTableEventData();
    
    Utils::clearLog();
    
    $cont = new Newevent(); //all the logic in the constructor haha
    
    $id = $this->getLastEventId();
    $this->changeEventId($id, 'bbb');
    
    $this->clearRequest();
    
    //create another?
    $data = $this->getCreateTableEventData();
    $data = array_merge($data, array( 
                                'e_name' => 'Oscars'  
                              , 'cat_1_name' => 'VIP'
                              , 'cat_1_capa' => 3
                              , 'cat_1_tcapa' => 7
    ));
    $_POST = $data;
    $cont = new Newevent(); //all the logic in the constructor haha
    
    $id = $this->getLastEventId();
    $this->changeEventId($id, 'ccc'); 
    
    
    //how about some seats
    $this->clearRequest();
    $data = $this->getCreateAsSeatsEventData();
    $_POST = $data;
    $cont = new Newevent(); //all the logic in the constructor haha
    
    $id = $this->getLastEventId();
    $this->changeEventId($id, 'seats');
    
    
    $foo = $this->createUser('foo');
    
  }
  
  function getCreateTableEventData(){
    $data = array(
      	'is_logged_in' => 1
      //, 'copy_event' => 'aaa'
        , 'e_name' => 'Dinner'
        , 'e_date_from' => '2014-05-21'
        , 'e_description' => '<p>asdf</p>'
        
        , 'c_id' => 1 //existing contact id ?
        , 'l_id' => 2 //existing location owned by logged in user
        , 'l_name' => 'Quito'
        , 'l_street' => 'Calle 1'
        , 'l_country_id' => '124'
        
        , 'id_ticket_template' => 1
        , 'e_currency_id' => 1
        , 'payment_method' => 3
        /*
        ,'tax_ref_hst' =>'' 
        ,'tax_ref_pst' => ''
        ,'tax_other_name' => ''
        ,'tax_other_percentage' =>'' 
        ,'tax_ref_other' => '' 
        */
        , 'ticket_type' => 'table'
        
        , 'cat_all' => array('1')
        
        , 'cat_1_type' => 'table'
        , 'cat_1_name' => 'Panas'
        , 'cat_1_description' => 'para los panas'
        , 'cat_1_capa' => 4 //mesas
        , 'cat_1_over' => 0
        , 'cat_1_tcapa' => 3 //asientos por mesa
        , 'cat_1_price' => 15.25
        , 'cat_1_ticket_price' => 0.00
        
        
        , 'create' => 'do'
        
    );
    return $data;
  }
  
  function getCreateAsSeatsEventData(){
    $data = array(
      'is_logged_in' => 1
    , 'copy_event' => 'aaa'
    , 'e_name' => 'Almuerzo'
    , 'e_date_from' => '2014-05-21'
    , 'e_time_from' => ''
    , 'e_date_to' => ''
    , 'e_time_to' => ''
    , 'e_description' => '<p>asdf</p>'
    , 'reminder_email' =>'' 
    , 'reminder_sms' => ''
    , 'c_id' => 1
    , 'l_latitude' => '52.9399159'
    , 'l_longitude' => '-73.5491361'
    , 'l_id' => 2
    , 'dialog_video_title' =>'' 
    , 'dialog_video_content' =>'' 
    , 'id_ticket_template' => 1
    , 'e_currency_id' => 1
    , 'payment_method' => 3
    , 'paypal_account' => ''
    , 'tax_ref_hst' => ''
    , 'tax_ref_pst' => ''
    , 'tax_other_name' =>'' 
    , 'tax_other_percentage' =>'' 
    , 'tax_ref_other' => ''
    , 'ticket_type' => 'open'
    , 'cat_all' => array( '0' => 0 )

    , 'cat_0_type' => 'table'
    , 'cat_0_name' => 'Paralelo A'
    , 'cat_0_description' => 'para los panas'
    , 'cat_0_capa' => 4
    , 'cat_0_over' => 0
    , 'cat_0_tcapa' => 3
    , 'cat_0_price' => 45.75
    , 'cat_0_single_ticket' => 'true'
    , 'cat_0_ticket_price' => 15.25
    , 'cat_0_seat_name' => 'Single SEat'
    , 'cat_0_seat_desc' => 'And single seat description?'
    , 'create' => 'do'
    
    );  
    
    return $data;
    
  }
 
}


