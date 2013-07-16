<?php
/**
 * Not my code but some test runner
 * @author MASTER
 *
 */
namespace Myvirtualmerchant;
use Utils;

class RequestTest extends \DatabaseBaseTest{
  
 
  public function testCreate(){
    
    $req = new Request( $this->okData() );
    $req->setDemo(true);
    $req->process();

  }
  
  function okData(){
    return array(
        'ssl_invoice_number' => '111'
      , 'ssl_customer_code' => JOHN_DOE_ID
      , 'ssl_amount' => 99.99
      , 'ssl_cardholder_ip' => \Utils::ip_address()
      , 'ssl_card_number' => '5301250070000050'
      
      // not required apparently
      , 'ssl_cvv2cvc2' => 123
      , 'ssl_exp_date' => '1208' //apparently it has to be month and 2-digit year together
      , 'ssl_avs_address' => 'some street' //just use the one from John Doe
      , 'ssl_avs_zip' => '' //same with john doe 
    );
  }

 
}