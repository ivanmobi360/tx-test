<?php
namespace Ext;
use tool\Request, Utils;

class MainTest extends \DatabaseBaseTest{
  
  protected $seller;
  
  public function fixture(){
    $this->clearAll();
    $this->db->Query("TRUNCATE TABLE mstr_tokens");
    
    $this->seller = $this->createUser('seller');
    
  }

  public function testInstance(){
    $this->fixture();
    $main = new Main();
    $main->process();
  }
  
  function testLogin(){
    $this->fixture();
    
    $app = new MockApp($this->db);
    $this->assertFalse($app->isLoggedIn());
    
    $app->login('seller');
    $this->assertTrue($app->isLoggedIn());
    $app->doEcho('BAZ');
    $this->assertEquals(array('msg'=>'BAZ'), $app->getResponse());
    
    $app->logout();
    $this->assertFalse($app->isLoggedIn());
    
    $app->doEcho('Foo');
    $this->assertNotEquals(array('msg'=>'Foo'), $app->getResponse());
  }
  

  public function testEcho(){
    $this->fixture();
    $app = new MockApp($this->db);
    $app->login('seller');
    $app->doEcho('Hello');
    $this->assertEquals(array('msg'=>'Hello'), $app->getResponse());
    
    $app->doCommand('echo', array('msg' => 'Bar') );
    $this->assertEquals(array('msg'=>'Bar'), $app->getResponse());
  }
  
  public function testEventList(){
    $this->fixture();
    
    //should  handle empty
    $app = new MockApp($this->db);
    $app->login('seller');
    $app->doCommand('getlistevents');
    
    $this->assertNotNull($app->getResponse());
    
    $evt = $this->createEvent('ALFA', $this->seller->id, 1, date('Y-m-d H:i:s'));
    $evt = $this->createEvent('BETA', $this->seller->id, 1, date('Y-m-d H:i:s'));
    $rs = $app->doCommand('getlistevents');
    $this->assertEquals(2, count($rs['events']));
    
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('GAMMA', $seller->id, 1, date('Y-m-d H:i:s'));
    
    $rs = $app->doCommand('getlistevents');
    $this->assertEquals(3, count($rs['events'])); //so expect all the events regardless who logs in
    
    $app2 = new MockApp($this->db);
    $app2->login('seller2'); //userid is ignored as of now
    $rs = $app2->doCommand('getlistevents');
    $this->assertEquals(3, count($rs['events'])); //so expect all the events regardless who logs in
    
  }
  
  public function testCheckTicket(){
    $this->fixture();
    
    
    $evt = $this->createEvent('Tonight Dinner', $this->seller->id, 1);
    $cat = $this->createCategory('Room A', $evt->id, 100.00);
    
    //First some nonexisting code
    $code = str_repeat('a', 16);
    $app = new MockApp($this->db);
    $app->login('seller');
    $res = $app->checkTicket($code, $evt->id);
    $this->assertEquals(1, $res['non_existing']);
    
    //buy a ticket
    $foo = $this->createUser('foo');
    $client = new \WebUser($this->db);
    $client->login($foo->username);
    $tickets = $this->buyTickets($foo->id, $cat->id);
    $this->assertEquals(1, count($tickets));
    
    $ticket = $tickets[0];
    $code = $ticket['code'];
    
    $this->assertEquals(0, $this->db->get_one("SELECT used FROM ticket WHERE code=?", $code));
    
    //let's unpay it for test
    $this->db->update('ticket', array('paid'=>0), 'code=?', $code);
    $res = $app->checkTicket($code, $evt->id);
    $this->assertEquals(1, $res['unpaid']);
    $this->db->update('ticket', array('paid'=>1), 'code=?', $code);
    Utils::clearLog();
    $app->output = 'json';
    $res = $app->checkTicket($code, '0' /* $evt->id*/);
    $this->assertEquals(1, $res['process_ok']);
    $this->assertEquals(1, $this->db->get_one("SELECT used FROM ticket WHERE code=?", $code));
    return;
    //check it again?
    $res = $app->checkTicket($code, $evt->id);
    $this->assertEquals(1, $res['already_used']);
    //$this->assertEquals(array('msg'=>'Hello'), $main->getResultData());
  }
  
  function testTicketInfo(){
    $this->fixture();
    
    $evt = $this->createEvent('Concert', $this->seller->id, 1);
    $cat = $this->createCategory('Silla', $evt->id, 100.00);
    
    
    $app = new MockApp($this->db);
    $app->login('seller');
    
    $code = str_repeat('a', 16);
    $res = $app->doCommand('ticketinfo', array('code' => $code, 'eventid' => $evt->id));
    
    
  }
  
  
}

class MockApp{
  protected $db;
  protected $token=false, $response=false;
  
  public $output=false;
  
  function __construct($db){
    $this->db = $db;
  }
  
  function clearRequest(){
    $_POST = array();
    \tool\Request::clear();
  }
  
  public function getAuth($userid){
    return Actions\GetToken::encryptlow(implode('|', array( 'x', $userid, 'x', 'blah', 10000  )));
  }
  
  function getToken(){
    return $this->token;
  }
  
  function login($userid){
    //First lets get a token
    $data = $this->doCommand('gettoken', array('auth'=>$this->getAuth($userid)));
    $this->token = $data['token'];
  }
  
  function logout(){
    $data = $this->doCommand('logout');
    Utils::log(__METHOD__ . print_r($data, true));
    if ($data==array()){ //wut?
      $this->token = false;
    }
  }
  
  function isLoggedIn(){
    return !empty($this->token);
  }
  
  function doEcho($msg){
    return $this->doCommand('echo', array('msg'=>$msg));
  }
  
  function checkTicket($code, $event_id){
    return $this->doCommand('checkticket', array('code'=>$code, 'eventid'=>$event_id));
  }
  
  function doCommand($action, $params=array()){
    $this->response = false;
    
    $data = array('action'=>$action);
    if($this->token){
      $data['token'] = $this->token;
    }
    
    if($this->output){
      $data['output'] = $this->output;
    }
    
    $_GET = array_merge($data, $params);
    $main = new Main();
    $main->process();
    
    
    $this->clearRequest();
    $this->response = $main->getResultData();
    return $this->response;
    
    
  }
  
  function getResponse(){
    return $this->response;
  }
  
}