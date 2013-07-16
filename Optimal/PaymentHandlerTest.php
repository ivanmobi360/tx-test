<?php
namespace Optimal;
use Utils;

class PaymentHanlderTest extends \DatabaseBaseTest{
  
  function getError(){
    return $this->getResponse("error.xml");
  }
  
  function getSuccess(){
    return $this->getResponse("success.xml");
  }
  
  function getResponse($filename){
    return file_get_contents(__DIR__ . "/responses/" . $filename);
  }
  
  function createInstance($user_id){
    return new MockPaymentHandler($user_id);
  }
  
  function fixture(){
    $this->clearAll();
    
    
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Quebec CES' , $seller->id, $this->createLocation()->id, date('Y-m-d', strtotime("+1 day")) );
    $this->setEventId($evt, 'aaa');
    $this->setEventPaymentMethodId($evt, self::OUR_CREDIT_CARD);
    $this->cat = $this->createCategory('Verde', $evt->id, 10.00, 100, 0, array('tax_inc'=>1) );
    
    
    
    //Transaction setup
    $this->foo = $this->createUser('foo');
    
    //let's buy
    $this->buyer = new \WebUser($this->db);
    $this->buyer->login($this->foo->username);

    //let's pay
    Utils::clearLog();
  }
  
  public function testError(){
    
    $this->fixture();
    
    $payer = $this->createInstance('foo');
    $payer->process();
    
    $this->assertFalse($payer->success()); //fail if no data
    
    $payer = $this->createInstance('foo');
    $payer->setData($this->getCCPurchaseData());
    $payer->process();
    $this->assertFalse($payer->success()); //fail if no cart
    
    $this->clearRequest();
    $buyer = new \WebUser($this->db);
    $buyer->login($this->foo->username);
    $buyer->addToCart($this->cat->id, 3); //cart in session
    
    $payer = $this->createInstance('foo');
    $payer->setData($this->getCCPurchaseData());
    $payer->setCart($buyer->getCart());
    $payer->response = '';
    $payer->process();
    $this->assertFalse($payer->success()); //fail if no service response
    $this->assertRows(1, 'optimal_transactions'); //the errored response
    
    $payer = $this->createInstance('foo');
    $payer->setData($this->getCCPurchaseData());
    $payer->setCart($buyer->getCart());
    $payer->response = 'asdf';
    $payer->process();
    $this->assertFalse($payer->success()); //fail if gibberish
    $this->assertRows(2, 'optimal_transactions'); //the errored response
    
    $payer = $this->createInstance('foo');
    $payer->setData($this->getCCPurchaseData());
    $payer->setCart($buyer->getCart());
    $payer->response = $this->getError();
    $payer->process();
    $this->assertFalse($payer->success()); //fail if gibberish
    $this->assertRows(3, 'optimal_transactions'); //the errored response
    
    
    $this->assertRows(5, 'error_track');
    $this->assertRows(3, 'ticket_transaction'); //empty carts generate no transactions
 
  }
  
  function testSuccess(){
    
    $this->fixture();
    
    $this->buyer->addToCart($this->cat->id, 3); //cart in session
    
    $payer = $this->createInstance('foo');
    $payer->setData($this->getCCPurchaseData());
    $payer->setCart($this->buyer->getCart());
    $payer->response = $this->getSuccess();// file_get_contents(__DIR__ . '/success.xml');
    $payer->process();
    
    $this->assertTrue($payer->success()); 
    
    //3 tickets
    $this->assertRows(3, 'ticket');
    $this->assertRows(1, 'processor_transactions');
    $this->assertRows(1, 'optimal_transactions');
    
    
  }
  
  function testAvsError(){
    $this->fixture();
    
    $this->buyer->addToCart($this->cat->id, 3); //cart in session
    
    $payer = $this->createInstance('foo');
    $payer->setData($this->getCCPurchaseData());
    $payer->setCart($this->buyer->getCart());
    $payer->response = $this->getResponse('avs-error.xml');
    $payer->process();
    
    $this->assertFalse($payer->success()); 
    
    //3 tickets
    $this->assertRows(0, 'ticket');
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(id) FROM  optimal_transactions WHERE avs_response=?", 'N' ));
    
  }
  
  function testCvdError(){
    $this->fixture();
    
    $this->buyer->addToCart($this->cat->id, 3); //cart in session
    
    $payer = $this->createInstance('foo');
    $payer->setData($this->getCCPurchaseData());
    $payer->setCart($this->buyer->getCart());
    $payer->response = $this->getResponse('cvd-error.xml');
    $payer->process();
    
    $this->assertFalse($payer->success()); 
    
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(id) FROM  optimal_transactions WHERE cvd_response=?", 'N' ));
    
  }
  
  function testBankDecline(){
    $this->fixture();
    
    $this->buyer->addToCart($this->cat->id, 3); //cart in session
    
    $payer = $this->createInstance('foo');
    $payer->setData($this->getCCPurchaseData());
    $payer->setCart($this->buyer->getCart());
    $payer->response = $this->getResponse('bank-decline.xml');
    $payer->process();
    
    $this->assertFalse($payer->success()); 
    
    //$this->assertEquals(1, $this->db->get_one("SELECT COUNT(id) FROM  optimal_transactions WHERE cvd_response=?", 'N' ));
    $this->assertRows(1, 'optimal_transactions');
    
  }
  
  //optional test to connect and inspect results
  function xtestLive(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Quebec CES' , $seller->id, $this->createLocation()->id, date('Y-m-d', strtotime("+1 day")) );
    $this->setEventId($evt, 'aaa');
    $cat = $this->createCategory('Verde', $evt->id, 10.00);
    
    
    
    //Transaction setup
    $foo = $this->createUser('foo');
    
    //let's buy
    $buyer = new \WebUser($this->db);
    $buyer->login($foo->username);

    //let's pay
    Utils::clearLog();
    
    $buyer->addToCart($cat->id, 3); //cart in session
    
    $data = $this->getCCPurchaseData();
    //$data['street'] = 'N ' . $data['street']; //fail avs
    $data['cc_cvd'] = '666'; //fail cvd 
    
    $payer = $this->createInstance('foo');
    $payer->setData($data);
    $payer->setCart($buyer->getCart());
    //$payer->amount_override = '0.25'; //hardcoded fail
    $payer->process();
    
    $this->assertFalse($payer->success()); 
  }
  
  function xtestLiveWholeNumber(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Quebec CES' , $seller->id, $this->createLocation()->id, date('Y-m-d', strtotime("+1 day")) );
    $this->setEventId($evt, 'aaa');
    $cat = $this->createCategory('Verde', $evt->id, 10.00);
    
    
    
    //Transaction setup
    $foo = $this->createUser('foo');
    
    //let's buy
    $buyer = new \WebUser($this->db);
    $buyer->login($foo->username);

    //let's pay
    Utils::clearLog();
    
    $buyer->addToCart($cat->id, 3); //cart in session
    
    $data = $this->getCCPurchaseData();
    
    $payer = $this->createInstance('foo');
    $payer->setData($data);
    $payer->setCart($buyer->getCart());
    $payer->amount_override = '12'; //whole number
    $payer->process();
    
    $this->assertTrue($payer->success()); 
  }

 
}