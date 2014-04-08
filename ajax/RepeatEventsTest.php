<?php

namespace ajax;
use Utils, DateTime;
class RepeatEventsTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    $this->clearAll();
    
    //Setup for manual testing
    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');
    
    $evt = $this->createEvent('Some event', $seller->id, $this->createLocation()->id);
    $cat = $this->createCategory('Test', $evt->id, 100.00);
    
    $evt = $this->createEvent('My Repeatable event', $seller->id, $this->createLocation()->id);
    $cat = $this->createCategory('Normal', $evt->id, 100.00);
    
    
    $this->clearRequest();
    $_POST = $this->getRequest( $evt->id);
    
    $ajax = new RepeatEvents();
    $ajax->Process();
    $repeat_id = $ajax->inserted_id;
    
    $this->assertEquals($repeat_id, $this->db->get_one("SELECT repeat_id FROM event WHERE id=?", $evt->id));
    $this->assertRows(1, "repeat_cycle", "event_id=?", $evt->id);
    
    //update
    $this->clearRequest();
    $_POST = $this->getRequest( $evt->id);
    $_POST['repeat_id'] = $repeat_id;
    $_POST['name'] = 'test';
    
    $ajax = new RepeatEvents();
    $ajax->Process();
    
    $this->assertNull($ajax->inserted_id);
    $this->assertRows(1, "repeat_cycle", "id=? AND name=?", array($repeat_id, 'test'));

  }
  
  protected function getRequest($event_id){
      return array (
              'method' => 'save-pattern',
              'name' => 'Some Pattern',
              'event_id' => $event_id,
              'time' => '10:00',
              'frequency' => 'weekly',
              'interval' => '1',
              'date_start' => '2014-04-01',
              'range' => 'until',
              'date_end' => '2014-04-30',
              'byday' =>
              array (
                      0 => 'tu',
                      //1 => 'we',
              ),
      ); //repeat every tuesday, starting on 01-04, ending on 30-04. Expect it to generate  5 instances
  }
  
  //for now let's test here our repeat calculator
  function testCalc(){
      $this->clearAll();
      
      //Setup for manual testing
      $seller = $this->createUser('seller');
      $foo = $this->createUser('foo');
      $evt = $this->createEvent('My Repeatable event', $seller->id, $this->createLocation()->id, '2014-03-31');
      $cat = $this->createCategory('Normal', $evt->id, 100.00);
      
      $this->clearRequest();
      $_POST = $this->getRequest( $evt->id);
      $ajax = new RepeatEvents();
      $ajax->Process();
      
      $expected = [
          new DateTime('2014-04-01 10:00:00'),
          new DateTime('2014-04-08 10:00:00'),
          new DateTime('2014-04-15 10:00:00'),
          new DateTime('2014-04-22 10:00:00'),
          new DateTime('2014-04-29 10:00:00'),
      ];
      
      $calc = new \tool\RepeatEventCalculator();
      $res = $calc->get($evt->id, '2014-04-01');
      $this->assertEquals(5, count($res));
      foreach($expected as $key => $date){
          $this->assertEquals($date, $res[$key]);
      }
      
      //end date matches last occurrence
      $res = $calc->get($evt->id, '2014-04-01', '2014-04-29');
      $this->assertEquals(5, count($res));
      foreach($expected as $key => $date){
          $this->assertEquals($date, $res[$key]);
      }
      
      //end date matches last occurrence, but a time earlier than the event is specified
      $res = $calc->get($evt->id, '2014-04-01', '2014-04-29 06:00:00');
      $this->assertEquals(4, count($res)); //one less
      
      //end date matches repeat_date_end
      $res = $calc->get($evt->id, '2014-04-01', '2014-04-30');
      $this->assertEquals(5, count($res));
      foreach($expected as $key => $date){
          $this->assertEquals($date, $res[$key]);
      }
      
      //a far in the future end
      $res = $calc->get($evt->id, '2014-04-01', '2015-10-01');
      $this->assertEquals(5, count($res));
      foreach($expected as $key => $date){
          $this->assertEquals($date, $res[$key]);
      }
      
      //a start_date in the past, 3 months default end should span the pattern range and find the events
      $res = $calc->get($evt->id, '2014-03-01');
      $this->assertEquals(5, count($res));
      foreach($expected as $key => $date){
          $this->assertEquals($date, $res[$key]);
      }
      
      Utils::clearLog();
      //too far in the past should find nothing
      $res = $calc->get($evt->id, '2012-04-01' , '2012-04-30');
      $this->assertEquals(0, count($res));
      
      $calc->now = '2012-05-30';
      $res = $calc->get($evt->id, '2012-04-01');
      $this->assertEquals(0, count($res));
      
      $calc->now = false;
      
      //should find only the first one
      $res = $calc->get($evt->id, '2014-04-01' , '2014-04-01');
      $this->assertEquals([new DateTime('2014-04-01 10:00:00')], $res);
      
      
      //should find only the first one
      $res = $calc->get($evt->id, '2014-04-01' , '2014-04-07');
      $this->assertEquals([new DateTime('2014-04-01 10:00:00')], $res);
      

      $res = $calc->get($evt->id, '2014-04-01' , '2014-04-08');
      $this->assertEquals(2, count($res));
      
      
      //beyond rstart and rend should fail
      $res = $calc->get($evt->id, '2015-04-01', '2015-04-30');
      $this->assertEquals(0, count($res));
      
      $calc->now = '2015-03-15';
      $res = $calc->get($evt->id, '2015-04-01');
      $this->assertEquals(0, count($res));
      
      
      $calc->now = false;
      
      //get just 2 results
      $calc = new \tool\RepeatEventCalculator();
      $calc->setCount(2);
      $res = $calc->get($evt->id, '2014-04-01', '2017-04-01');
      $this->assertEquals(2, count($res));
      
      //one?
      $calc->setCount(1);
      $res = $calc->get($evt->id, '2014-04-01', '2017-04-01');
      $this->assertEquals([new DateTime('2014-04-01 10:00:00')], $res);
      
  }
  
  //no date_end defined case
  function testNoEnd(){
      $this->clearAll();
      
      //Setup for manual testing
      $seller = $this->createUser('seller');
      $foo = $this->createUser('foo');
      $evt = $this->createEvent('My Repeatable event', $seller->id, $this->createLocation()->id, '2014-03-31');
      $cat = $this->createCategory('Normal', $evt->id, 100.00);
      
      $this->clearRequest();
      $_POST = $this->getRequest( $evt->id);
      $_POST['range'] = 'no_end'; //limitless event
      $ajax = new RepeatEvents();
      $ajax->Process();

      $expectedApr = [
          new DateTime('2014-04-01 10:00:00'),
          new DateTime('2014-04-08 10:00:00'),
          new DateTime('2014-04-15 10:00:00'),
          new DateTime('2014-04-22 10:00:00'),
          new DateTime('2014-04-29 10:00:00'),
      ];
      $expectedMay = [
          new DateTime('2014-05-06 10:00:00'),
          new DateTime('2014-05-13 10:00:00'),
          new DateTime('2014-05-20 10:00:00'),
          new DateTime('2014-05-27 10:00:00'),
      ];
      
      $calc = new \tool\RepeatEventCalculator();
      
      $res = $calc->get($evt->id, '2014-04-01', '2014-04-29');
      $this->assertEquals(5, count($res));
      foreach($expectedApr as $key => $date){
          $this->assertEquals($date, $res[$key]);
      }
      
      $res = $calc->get($evt->id, '2014-04-01', '2014-05-31');
      $this->assertEquals(9, count($res));
      foreach($expectedApr + $expectedMay as $key => $date){
          $this->assertEquals($date, $res[$key]);
      }
      
      $res = $calc->get($evt->id, '2014-05-01', '2014-05-31');
      $this->assertEquals(4, count($res));
      foreach($expectedMay as $key => $date){
          $this->assertEquals($date, $res[$key]);
      }
      
      //too far in the past should find nothing
      $res = $calc->get($evt->id, '2012-04-01' , '2012-04-30');
      $this->assertEquals(0, count($res));
      
      $calc->now = '2012-05-30';
      $res = $calc->get($evt->id, '2012-04-01');
      $this->assertEquals(0, count($res));
      
      $calc->now = false;
      
      //should find one
      $res = $calc->get($evt->id, '2014-05-01', '2014-05-06');
      $this->assertEquals([new DateTime('2014-05-06 10:00:00')], $res);
      
      
      //should find one
      $res = $calc->get($evt->id, '2014-05-06', '2014-05-06');
      $this->assertEquals([new DateTime('2014-05-06 10:00:00')], $res);
      
      
      //get just 2 results
      $calc = new \tool\RepeatEventCalculator();
      $calc->setCount(2);
      $res = $calc->get($evt->id, '2014-04-01', '2017-04-01');
      $this->assertEquals(2, count($res));
      
      //one?
      $calc->setCount(1);
      $res = $calc->get($evt->id, '2014-04-01', '2017-04-01');
      $this->assertEquals([new DateTime('2014-04-01 10:00:00')], $res);
      
  }
  
  //Pattern starts in the midst of the month
  function testMid(){
      $this->clearAll();
      
      //Setup for manual testing
      $seller = $this->createUser('seller');
      $foo = $this->createUser('foo');
      $evt = $this->createEvent('My Repeatable event', $seller->id, '2014-03-31');
      $cat = $this->createCategory('Normal', $evt->id, 100.00);
      
      $this->clearRequest();
      $_POST = $this->getRequest( $evt->id);
      $_POST = array_merge($_POST, ['byday'=>['tu', 'th'], 'date_start'=>'2014-04-14'/*, 'range'=>'no_end'*/ ]);
      $ajax = new RepeatEvents();
      $ajax->Process();
      
      //return;
      
      $expected = [
      new DateTime('2014-04-15 10:00:00'),
      new DateTime('2014-04-17 10:00:00'),
      new DateTime('2014-04-22 10:00:00'),
      new DateTime('2014-04-24 10:00:00'),
      new DateTime('2014-04-29 10:00:00'),
      ];
      
      $calc = new \tool\RepeatEventCalculator();
      $res = $calc->get($evt->id, '2014-04-01');
      $this->assertEquals($expected, $res);
      foreach($expected as $key => $date){
          $this->assertEquals($date, $res[$key]);
      }

      $res = $calc->get($evt->id, '2014-04-01', '2014-04-30');
      $this->assertEquals($expected, $res);
      foreach($expected as $key => $date){
          $this->assertEquals($date, $res[$key]);
      }
      
  }
  
  /**
   * Okay, so a normal 
   * "this event happen every 2 weeks on 
   * thursday and friday" will get me all the good dates if I ask for 
   * "give me all the event dates between 2014-03-31 and 2014-09-01 ?
   * 2014-09-01 the end of MY request, forever is good for the event's repeat_cycle
   */
  function testGreg(){
      $this->clearAll();
      
      //Setup for manual testing
      $seller = $this->createUser('seller');
      $foo = $this->createUser('foo');
      $evt = $this->createEvent('My Repeatable event', $seller->id, $this->createLocation()->id, '2014-03-01');
      $cat = $this->createCategory('Normal', $evt->id, 100.00);
      
      $this->clearRequest();
      $_POST = $this->getRequest( $evt->id);
      $_POST = array_merge($_POST, [
              'interval' => 2,
              'byday'=>['th', 'fr'], 
              'date_start'=>'2014-04-03'
              , 'range'=>'no_end'
              ]);
      $ajax = new RepeatEvents();
      $ajax->Process();
      
      $calc = new \tool\RepeatEventCalculator();
      $res = $calc->get($evt->id, '2014-03-31', '2014-09-01' );
      
      foreach($res as $date){
          Utils::log($date->format('Y-m-d H:i:s'));
      }
      
      $this->assertEquals(new DateTime('2014-04-03 10:00:00'), $res[0]);
      
      $res = $calc->get($evt->id, '2014-04-01', '2014-09-01' );
      $this->assertEquals(new DateTime('2014-04-03 10:00:00'), $res[0]);
      
      $res = $calc->get($evt->id, '2014-03-01', '2014-09-01' );
      $this->assertEquals(new DateTime('2014-04-03 10:00:00'), $res[0]);
      
  }
  
  function testAutoCorrect(){
      $this->clearAll();
      
      //Setup for manual testing
      $seller = $this->createUser('seller');
      $foo = $this->createUser('foo');
      $evt = $this->createEvent('My Auto Correct Fixture', $seller->id, $this->createLocation()->id, '2014-03-01');
      $this->setEventId($evt, 'aaa');
      $cat = $this->createCategory('Normal', $evt->id, 100.00);
      
    $req = array (
  'page' => 'RepeatEvents',
  'method' => 'preview-pattern',
  'repeat_id' => '1',
  'name' => 'Some Pattern',
  'event_id' => 'aaa',
  'time' => '10:00',
  'frequency' => 'weekly',
  'interval' => '2',
  'date_start' => '2014-03-31',
  'range' => 'no_end',
  'date_end' => '',
  'byday' => 
  array (
    0 => 'th',
    1 => 'fr',
  ),
);

    $this->clearRequest(); Utils::clearLog();
    $_POST = $req;
    $ajax = new RepeatEvents();
    $ajax->Process();
    
    $res = $ajax->res;
    
    $this->assertEquals('2014-04-03', $res['corrected_date_start']);
    $this->assertEquals('2014-04-03', date('Y-m-d', strtotime($res['first_ocurrence'])) );
    
    // ***** Another case ***********************
    
    /*
     "I do this pattern: every 2 weeks, on thursday
the first ever occurence will be nov 6th, NOT nov 1st
so we auto-correct the promoter and change date_start to nov 6th
    "
    */
    $req = array (
  'page' => 'RepeatEvents',
  'method' => 'preview-pattern',
  'repeat_id' => '',
  'name' => 'Derp',
  'event_id' => 'aaa',
  'time' => '10:00',
  'frequency' => 'weekly',
  'interval' => '2',
  'date_start' => '2014-11-01',
  'range' => 'no_end',
  'date_end' => '',
  'byday' => 
  array (
    0 => 'th',
  ),
);
    $this->clearRequest(); Utils::clearLog();
    $_POST = $req;
    $ajax = new RepeatEvents();
    $ajax->Process();
    
    $res = $ajax->res;
    
    $this->assertEquals('2014-11-06', $res['corrected_date_start']);
    $this->assertEquals('2014-11-06', date('Y-m-d', strtotime($res['first_ocurrence'])) );
    
  }
  
  /**
   * you need to discard any dates that happens before the event is supposed to start. 
    For example, I start the repeat on the 1st of january, 
    but my event start date is on the 1st of july, 
    I should see no dates until the first of july.
   */
  function testDiscard(){
      $this->clearAll();
      
      //Setup for manual testing
      $seller = $this->createUser('seller');
      $foo = $this->createUser('foo');
      $evt = $this->createEvent('Discard Fixture', $seller->id, $this->createLocation()->id, '2014-07-01');
      $this->setEventId($evt, 'aaa');
      $cat = $this->createCategory('Normal', $evt->id, 100.00);
      
      //create the pattern
      $this->clearRequest(); Utils::clearLog();
      
      $_POST = array (
          'page' => 'RepeatEvents',
          'method' => 'save-pattern', //'preview-pattern',
          'repeat_id' => '',
          'name' => 'Back to January',
          'event_id' => 'aaa',
          'time' => '08:00',
          'frequency' => 'weekly',
          'interval' => '1',
          'date_start' => '2014-01-01',
          'range' => 'no_end',
          'date_end' => '',
          'byday' => 
          array (
            0 => 'tu',
            //1 => 'fr',
          ),
        );
      $ajax = new RepeatEvents();
      $ajax->Process();
      
      
      $calc = new \tool\RepeatEventCalculator();
      $res = $calc->get($evt->id, '2014-04-01', '2014-05-01');
      $this->assertEquals([], $res);
      
      $res = $calc->get($evt->id, '2014-06-01', '2014-07-31');
      $this->assertEquals(5, count($res));
      $this->assertEquals(new DateTime('2014-07-01 08:00:00'), $res[0]);
      
      $res = $calc->get($evt->id, '2014-07-01', '2014-07-31');
      $this->assertEquals(5, count($res));
      $this->assertEquals(new DateTime('2014-07-01 08:00:00'), $res[0]);
      
      $res = $calc->get($evt->id, '2012-07-01', '2014-07-31');
      $this->assertEquals(5, count($res));
      $this->assertEquals(new DateTime('2014-07-01 08:00:00'), $res[0]);
      
  }
  
  
  //fixture to check listing in front page
  function testListing(){
      //we expect it to happen 
  }
  
  
  
}