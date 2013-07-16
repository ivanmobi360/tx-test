<?php


use model\Remindermanager;
use tool\Request;
use controller\Reminders;
use controller\Newevent;
use reports\ReportLib;
class RemindersTest extends DatabaseBaseTest{
  
  function testParams(){
    $_POST = array('foo'=> array( 'bar' => array('baz' => 5) ) );
    $this->assertEquals(5, Utils::getArrayParam( 'foo[bar][baz]', $_POST  ));
    $this->assertEquals(array('baz' => 5), Utils::getArrayParam( 'foo[bar]', $_POST  ));
    $this->assertEquals(5, Utils::getArrayParam( 'baz', array('baz' => 5)  ));
    $this->assertEquals(false, Utils::getArrayParam( 'foo', array('baz' => 5)  ));
    $this->assertEquals(false, Utils::getArrayParam( 'foo', array()  ));
    $this->assertEquals(false, Utils::getArrayParam( 'foo', 'bar'  ));
  }
  
  function fixture(){
    $seller = $this->createUser('seller');
    $client = new WebUser($this->db);
    $client->login($seller->username);
    
    $evt = $this->createEvent('Pizza', $seller->id, 1, '2012-03-15');
    $this->setEventId($evt, 'aaa');
    return $evt;
  }
  
  function okData(){
    return array(
    'event_id' => 'aaa',
    'sent' => 1,
    'ema' => array(
            'active' => '1'  
          ,  'date' => '2012-03-09'
          , 'time' => '09:38'
          , 'content' => '<b>Hello</b> world'
          ),
     
     'sms' => array(
            'active' => '1'
          , 'date' => '2012-03-09'
          , 'time' => '20:00'
          , 'content' => '<i>Hello</i>'
     )
          
    );
  }
  
  public function testEmail(){
    $this->clearAll();
    $evt = $this->fixture();
    
    return;
    
    $data = $this->okData();
    unset($data['sms']['active']);
    
    $_POST = $data;
    
    $cont = new Reminders();
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(*) FROM reminder"));
   

    
  }
  
  function testBoth(){
    
    $this->clearAll();
    $evt = $this->fixture();
    
    $data = $this->okData();
    
    
    $_POST = $data;
    $cont = new Reminders();
    $this->assertEquals(2, $this->db->get_one("SELECT COUNT(*) FROM reminder"));
    
    
    
    $reminder = Remindermanager::findSms($evt->id);
    $this->assertEquals('Hello', $reminder->content);
    $this->assertEquals('2012-03-09 20:00:00', $reminder->send_at);
    
  }
  
  function testFailEmailAfterEventStart(){
    $this->clearAll();
    $this->fixture();
    
    $data = $this->okData();
    $data['ema']['date'] = '2012-03-16';
    
    $_POST = $data;
    $cont = new Reminders();
    $this->assertEquals(0, $this->db->get_one("SELECT COUNT(*) FROM reminder"));
  }
  
  function testFailSmsAfterEventStart(){
    $this->clearAll();
    $this->fixture();
    
    $data = $this->okData();
    $data['sms']['date'] = '2012-03-16';
    
    $_POST = $data;
    $cont = new Reminders();
    $this->assertEquals(0, $this->db->get_one("SELECT COUNT(*) FROM reminder"));
  }
  
  function testFailSmsLength140(){
    $this->clearAll();
    $this->fixture();
    
    $data = $this->okData();
    $data['sms']['content'] = str_repeat('x', 141);
    
    $_POST = $data;
    $cont = new Reminders();
    $this->assertEquals(0, $this->db->get_one("SELECT COUNT(*) FROM reminder"));
  }
  
  function testOnlyEmail(){
    $this->clearAll();
    $this->fixture();
    //return;
    
    $data = $this->okData();
    unset($data['sms']['active']); //Idlir's change apparently. The sms row is created, but it is not active
    
    $_POST = $data;
    $cont = new Reminders();
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(*) FROM reminder WHERE active=1"));
  }
  
  function badEarlyTime(){
    return array(
      array('')
      , array( '   ')
      //, array( 'asdf' ) //this won't even make it the first checks
      , array( '08:00' )
      , array( '07:00' )
    );
  }
  
  /**
   * 
   * @dataProvider badEarlyTime
   */
  function testAdjustSmsTime($time){
    $this->clearAll();
    $evt = $this->fixture();
    
    
    $data = $this->okData();
    $data['sms']['time'] = $time;
    
    $_POST = $data;
    $cont = new Reminders();
    $this->assertEquals(2, $this->db->get_one("SELECT COUNT(*) FROM reminder"));
    
    $send_at = Remindermanager::findSms($evt->id)->send_at;
    $this->assertEquals('2012-03-09 09:00:00', date('Y-m-d H:i:s', strtotime($send_at)));
    
  }
  
  function testAcceptEmptyContent(){
    $this->clearAll();
    $evt = $this->fixture();
    
    $data = $this->okData();
    $data['ema']['content'] = '';
    $data['sms']['content'] = '';
    
    $_POST = $data;
    
    $cont = new Reminders();
    
    $reminder = Remindermanager::findSms($evt->id);
    $this->assertEquals('', $reminder->content);
    //$this->assertEquals('2012-03-09 20:00:00', $reminder->send_at);
  }
  
  function testDoubledPost(){
    $this->clearAll();
    $evt = $this->fixture();
    
    $data = $this->okData();
    
    $_POST = $data;
    
    $cont = new Reminders();
    $cont = new Reminders();
    $this->assertEquals(2, $this->db->get_one("SELECT COUNT(*) FROM reminder"));
  }
  
  
  
  
  function tearDown(){
    $_POST = array();
    parent::tearDown();
  }

 
}


