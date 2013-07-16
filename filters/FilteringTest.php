<?php

use filters\DateRangeFilterRule;
use filters\Filtering;
use filters\SimpleFilterRule;
use filters\EmptyFilterRule;

class FilteringTest extends DatabaseBaseTest{
  
  public function testCreate(){
    
    //$this->clearAll();
    $obj = new Filtering('some_page');
    $obj->addRule( 'viewed', new SimpleFilterRule( 'viewed',  ' AND error_track.viewed=? '   ) ); 
    
    $data = array('viewed'=>1);
    $obj->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals(1, $obj->getValue('viewed'));
    
    $data = array('viewed'=>0);
    $obj->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals(0, $obj->getValue('viewed'));
    
    Utils::clearLog();
    $data = array(/*'viewed'=>''*/);
    $obj->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals('foo', $obj->getValue('viewed', 'foo'));
  }
  
  public function testLike(){
    
    //$this->clearAll();
    $obj = new Filtering('some_page');
    $rule = new SimpleFilterRule( 'merchant_name',  ' AND contact.name LIKE "%?%" ' );
    $rule->quotes = false;
    $obj->addRule( 'merchant_name', $rule ); 
    
    $data = array('merchant_name'=>'seller');
    $obj->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals('seller', $obj->getValue('merchant_name'));
    $this->assertEquals(' AND contact.name LIKE "%seller%" ', $rule->buildSql($data));

  }
  
  function testFloatAndLike(){
    $this->filtering = new Filtering('blah');
    $rule = new SimpleFilterRule('merchant_name', " AND contact.name LIKE '%?%' ");
    $rule->quotes = false; $rule->resetValue = '';
    $this->filtering->addRule('merchant_name', $rule);
    
    $rule = new SimpleFilterRule('invoice_number', " AND merchant_invoice.serial_no LIKE '%?%' ");
    $rule->quotes = false; $rule->resetValue = '';
    $this->filtering->addRule('invoice_number', $rule);
    
    $rule = new SimpleFilterRule('net_amount', " AND ABS(merchant_invoice.net_total - ? ) <= 0.001 ");
    $rule->quotes = false; $rule->resetValue = '';
    $this->filtering->addRule('net_amount', $rule);
    
    $data = array('net_amount' => 5
    , 'merchant_name' => ''
    , 'invoice_number' => ''
    );
    $obj = $this->filtering;
    $obj->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals(5, $obj->getValue('net_amount'));
    
  }
  
  public function testRangeFiltering(){
    $obj = new Filtering('bar');
    $obj->addRule( 'viewed', new SimpleFilterRule( 'viewed',  ' AND error_track.viewed=? '   ) );
    $obj->addRule( 'range_start', new DateRangeFilterRule( 'event.date_from' ) );
    $obj->addRule( 'range_end', new EmptyFilterRule( 'range_end' ) );
    
    $start = '2012-01-05';
    $end = '2012-01-15';
    
    $data = array('range_start'=>$start);
    $obj->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals($start, $obj->getValue('range_start'));
    
    $data = array('range_start'=>$start, 'range_end'=>$end);
    $obj->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals($start, $obj->getValue('range_start'));
    $this->assertEquals($end, $obj->getValue('range_end'));
    
  }
  
  function testDateRange(){
    $now = '2012-05-01';
    $filter = new Filtering('some_page');
    $date = new DateRangeFilterRule('event.date_from', 'start', 'end');
    $date->now = $now;
    $filter->addRule(array('start', 'end'), $date );
    //$filter->addRule('end', new EmptyFilterRule('end'));
    
    $start = '2012-02-01';
    $end = '2012-10-30';  
    
    $data = array('start'=>$start);
    $filter->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals($start, $filter->getValue('start'));
    $this->assertEquals($now, $filter->getValue('end')); //not visible but it works
    
    $data = array('end'=>$end);
    $filter->process($data);
    Utils::log(print_r($_SESSION, true));
    $this->assertEquals($now, $filter->getValue('start'));
    
  }
  
  /**
   * "When we are on the "Totals: sorted by payment method" page, 
   * we have a date range filter. 
	 I want that filter to also be present (pre-filled) on the "Payment Method Transactions" that we go to when clicking the payment method."
   */
  function testPrefill(){
    
    //Setup
    
    $filter = new Filtering('page1');
    $filter->addRule(array('start', 'end'), new DateRangeFilterRule('event.date_from', 'start', 'end') );
    $start = '2012-02-01';
    $end = '2012-10-30';  
    
    $data = array('start'=>$start, 'end' => $end);
    $filter->process($data);
    //Utils::log(print_r($_SESSION, true));
    $this->assertEquals($start, $filter->getValue('start'));
    $this->assertEquals($end, $filter->getValue('end'));
    
    
    $filter = new Filtering('page2');
    $filter->addRule(array('start', 'end'), new DateRangeFilterRule('transactions.date_from', 'start', 'end') ); //it doesn't even need to be the same column
    $this->assertEquals(false, $filter->getValue('start'));
    $this->assertEquals(false, $filter->getValue('end'));
    
    $this->assertFalse($filter->isProcessed());
    
    $filter->importFromNamespace('page1');
    $this->assertEquals($start, $filter->getValue('start'));
    $this->assertEquals($end, $filter->getValue('end'));
    Utils::log(print_r($_SESSION, true));
  }
  
  
  public function tearDown(){
    $_SESSION = array();
  }
  
  
  
  
  
  
  
 
  
 

  
}