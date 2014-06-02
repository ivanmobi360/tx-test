<?php

namespace ajax;

use Utils, DateTime;

class RepeatEventsTest extends \DatabaseBaseTest
{

    protected function getRequest($event_id)
    {
        return array ('method' => 'save-pattern','name' => 'Some Pattern','event_id' => $event_id,'time_start' => '10:00','frequency' => 'weekly','interval' => '1','date_start' => '2014-04-01','range' => 'until','date_end' => '2014-04-30',
                'byday' => [ 0 => 'tu' ]
                // 1 => 'we',
                 )

        ; // repeat every tuesday, starting on 01-04, ending on 30-04. Expect it to generate 5 instances
    }

    public function testCreate()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        
        $evt = \EventBuilder::createInstance($this, $seller)->info('Some event', $this->addLocation($seller->id)->id, $this->dateAt('+5 day'))->id('aaa')->addCategory(\CategoryBuilder::newInstance('Test', 100.00))->create();
        
        $evt = \EventBuilder::createInstance($this, $seller)->info('My Repeatable event', $this->addLocation($seller->id)->id, $this->dateAt('+5 day'))->id('bbb')->addCategory(\CategoryBuilder::newInstance('Normal', 100.00))->create();
        
        $this->clearRequest();
        $_POST = $this->getRequest($evt->id);
        
        $ajax = new RepeatEvents();
        $ajax->Process();
        $repeat_id = $ajax->inserted_id;
        
        $this->assertEquals($repeat_id, $this->db->get_one("SELECT repeat_id FROM event WHERE id=?", $evt->id));
        $this->assertRows(1, "repeat_cycle", "event_id=?", $evt->id);
        
        // update
        $this->clearRequest();
        $_POST = $this->getRequest($evt->id);
        $_POST['repeat_id'] = $repeat_id;
        $_POST['name'] = 'test';
        
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $this->assertNull($ajax->inserted_id);
        $this->assertRows(1, "repeat_cycle", "id=? AND name=?", array ($repeat_id,'test' ));
    }

    /**
     * "we need to be able to have more than one repeat pattern per event
     * "Every monday and tuesday of every week, at noon"
     * "Every thursday of every week, at 19h"
     */
    function testMultiplePatterns()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        
        $evt = \EventBuilder::createInstance($this, $seller)->info('Some event', $this->addLocation($seller->id)->id, '2014-05-15')->id('aaa')->addCategory(\CategoryBuilder::newInstance('Test', 100.00))->create();
        
        $evt = \EventBuilder::createInstance($this, $seller)->info('Multiple patterns per event', $this->addLocation($seller->id)->id, '2014-05-15')->id('bbb')->addCategory(\CategoryBuilder::newInstance('Normal', 100.00))->create();
        
        // pattern 1
        $this->clearRequest();
        $req = $this->getRequest($evt->id);
        $req['name'] = 'Every monday and tuesday of every week, at noon';
        $req['time_start'] = '18:00';
        $req['date_start'] = '2014-05-19';
        $req['range'] = 'no_end';
        $req['byday'] = [ 'mo','tu' ];
        $req['duration'] = '';
        $_POST = $req;
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $this->assertNull($this->db->get_one("SELECT duration FROM repeat_cycle WHERE id=?", $ajax->inserted_id));
        
        // pattern 2
        $this->clearRequest();
        $req = $this->getRequest($evt->id);
        $req['name'] = 'Every thursday of every week, at 19h';
        $req['time_start'] = '19:00';
        $req['date_start'] = '2014-05-22';
        $req['range'] = 'no_end';
        $req['byday'] = [ 'th' ];
        
        $_POST = $req;
        
        $ajax = new RepeatEvents();
        $ajax->Process();
        $repeat_id = $ajax->inserted_id;
        
        $this->assertRows(2, "repeat_cycle", "event_id=?", $evt->id);
        $this->assertEquals(1, $this->db->get_one("SELECT repeat_id FROM event WHERE id=?", $evt->id));
        
        // repeat with duration
        $this->clearRequest();
        $req['repeat_id'] = $repeat_id;
        $req['duration'] = '5';
        $_POST = $req;
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $this->assertEquals('05:00:00', $this->db->get_one("SELECT duration FROM repeat_cycle WHERE id=?", $repeat_id));
        
        // accept 1.5
        $this->clearRequest();
        $req['repeat_id'] = $repeat_id;
        $req['duration'] = '1.5';
        $_POST = $req;
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $this->assertEquals('01:30:00', $this->db->get_one("SELECT duration FROM repeat_cycle WHERE id=?", $repeat_id));
    }
    
    // for now let's test here our repeat calculator
    function testCalc()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        $evt = $this->createEvent('My Repeatable event', $seller->id, $this->createLocation()->id, '2014-03-31');
        $cat = $this->createCategory('Normal', $evt->id, 100.00);
        
        $this->clearRequest();
        $_POST = $this->getRequest($evt->id);
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $expected = [ new DateTime('2014-04-01 10:00:00'),new DateTime('2014-04-08 10:00:00'),new DateTime('2014-04-15 10:00:00'),new DateTime('2014-04-22 10:00:00'),new DateTime('2014-04-29 10:00:00') ];
        
        Utils::clearLog();
        $calc = new \tool\RepeatEventCalculator();
        $res = $calc->get($evt->id, '2014-04-01');
        $this->assertEquals(5, count($res));
        foreach ( $expected as $key => $date ) {
            $this->assertEquals($date, $res[$key]);
        }
        
        // end date matches last occurrence
        $res = $calc->get($evt->id, '2014-04-01', '2014-04-29');
        $this->assertEquals(5, count($res));
        foreach ( $expected as $key => $date ) {
            $this->assertEquals($date, $res[$key]);
        }
        
        // end date matches last occurrence, but a time earlier than the event is specified
        $res = $calc->get($evt->id, '2014-04-01', '2014-04-29 06:00:00');
        $this->assertEquals(4, count($res)); // one less
                                             
        // end date matches repeat_date_end
        $res = $calc->get($evt->id, '2014-04-01', '2014-04-30');
        $this->assertEquals(5, count($res));
        foreach ( $expected as $key => $date ) {
            $this->assertEquals($date, $res[$key]);
        }
        
        // a far in the future end
        $res = $calc->get($evt->id, '2014-04-01', '2015-10-01');
        $this->assertEquals(5, count($res));
        foreach ( $expected as $key => $date ) {
            $this->assertEquals($date, $res[$key]);
        }
        
        // a start_date in the past, 3 months default end should span the pattern range and find the events
        $res = $calc->get($evt->id, '2014-03-01');
        $this->assertEquals(5, count($res));
        foreach ( $expected as $key => $date ) {
            $this->assertEquals($date, $res[$key]);
        }
        
        Utils::clearLog();
        // too far in the past should find nothing
        $res = $calc->get($evt->id, '2012-04-01', '2012-04-30');
        $this->assertEquals(0, count($res));
        
        $calc->now = '2012-05-30';
        $res = $calc->get($evt->id, '2012-04-01');
        $this->assertEquals(0, count($res));
        
        $calc->now = false;
        
        // should find only the first one
        $res = $calc->get($evt->id, '2014-04-01', '2014-04-01');
        $this->assertEquals([ new DateTime('2014-04-01 10:00:00') ], $res);
        
        // should find only the first one
        $res = $calc->get($evt->id, '2014-04-01', '2014-04-07');
        $this->assertEquals([ new DateTime('2014-04-01 10:00:00') ], $res);
        
        $res = $calc->get($evt->id, '2014-04-01', '2014-04-08');
        $this->assertEquals(2, count($res));
        
        // beyond rstart and rend should fail
        $res = $calc->get($evt->id, '2015-04-01', '2015-04-30');
        $this->assertEquals(0, count($res));
        
        $calc->now = '2015-03-15';
        $res = $calc->get($evt->id, '2015-04-01');
        $this->assertEquals(0, count($res));
        
        $calc->now = false;
        
        // get just 2 results
        $calc = new \tool\RepeatEventCalculator();
        $calc->setCount(2);
        $res = $calc->get($evt->id, '2014-04-01', '2017-04-01');
        $this->assertEquals(2, count($res));
        
        // one?
        $calc->setCount(1);
        $res = $calc->get($evt->id, '2014-04-01', '2017-04-01');
        $this->assertEquals([ new DateTime('2014-04-01 10:00:00') ], $res);
    }
    
    // no date_end defined case
    function testNoEnd()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        $evt = $this->createEvent('My Repeatable event', $seller->id, $this->createLocation()->id, '2014-03-31');
        $cat = $this->createCategory('Normal', $evt->id, 100.00);
        
        $this->clearRequest();
        $_POST = $this->getRequest($evt->id);
        $_POST['range'] = 'no_end'; // limitless event
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $expectedApr = [ new DateTime('2014-04-01 10:00:00'),new DateTime('2014-04-08 10:00:00'),new DateTime('2014-04-15 10:00:00'),new DateTime('2014-04-22 10:00:00'),new DateTime('2014-04-29 10:00:00') ];
        $expectedMay = [ new DateTime('2014-05-06 10:00:00'),new DateTime('2014-05-13 10:00:00'),new DateTime('2014-05-20 10:00:00'),new DateTime('2014-05-27 10:00:00') ];
        
        $calc = new \tool\RepeatEventCalculator();
        
        $res = $calc->get($evt->id, '2014-04-01', '2014-04-29');
        $this->assertEquals(5, count($res));
        foreach ( $expectedApr as $key => $date ) {
            $this->assertEquals($date, $res[$key]);
        }
        
        $res = $calc->get($evt->id, '2014-04-01', '2014-05-31');
        $this->assertEquals(9, count($res));
        foreach ( $expectedApr + $expectedMay as $key => $date ) {
            $this->assertEquals($date, $res[$key]);
        }
        
        $res = $calc->get($evt->id, '2014-05-01', '2014-05-31');
        $this->assertEquals(4, count($res));
        foreach ( $expectedMay as $key => $date ) {
            $this->assertEquals($date, $res[$key]);
        }
        
        // too far in the past should find nothing
        $res = $calc->get($evt->id, '2012-04-01', '2012-04-30');
        $this->assertEquals(0, count($res));
        
        $calc->now = '2012-05-30';
        $res = $calc->get($evt->id, '2012-04-01');
        $this->assertEquals(0, count($res));
        
        $calc->now = false;
        
        // should find one
        $res = $calc->get($evt->id, '2014-05-01', '2014-05-06');
        $this->assertEquals([ new DateTime('2014-05-06 10:00:00') ], $res);
        
        // should find one
        $res = $calc->get($evt->id, '2014-05-06', '2014-05-06');
        $this->assertEquals([ new DateTime('2014-05-06 10:00:00') ], $res);
        
        // get just 2 results
        $calc = new \tool\RepeatEventCalculator();
        $calc->setCount(2);
        $res = $calc->get($evt->id, '2014-04-01', '2017-04-01');
        $this->assertEquals(2, count($res));
        
        // one?
        $calc->setCount(1);
        $res = $calc->get($evt->id, '2014-04-01', '2017-04-01');
        $this->assertEquals([ new DateTime('2014-04-01 10:00:00') ], $res);
    }
    
    // Pattern starts in the midst of the month
    function testMid()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        $evt = \EventBuilder::createInstance($this, $seller)->id('aaa')->info("My Repeatable event", $this->createLocation('aLoc', $seller->id)->id, '2014-03-31'/*, '23:00:00', '', '03:00:00'*/)->addCategory(\CategoryBuilder::newInstance('Normal', 100.00))->create();
        
        $this->clearRequest();
        $_POST = $this->getRequest($evt->id);
        $_POST = array_merge($_POST, [ 'byday' => [ 'tu','th' ],'date_start' => '2014-04-14'/*, 'range'=>'no_end'*/ ]);
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        // return;
        
        $expected = [ new DateTime('2014-04-15 10:00:00'),new DateTime('2014-04-17 10:00:00'),new DateTime('2014-04-22 10:00:00'),new DateTime('2014-04-24 10:00:00'),new DateTime('2014-04-29 10:00:00') ];
        
        Utils::clearLog();
        
        $calc = new \tool\RepeatEventCalculator();
        $res = $calc->get($evt->id, '2014-04-01');
        $this->assertEquals($expected, $res);
        foreach ( $expected as $key => $date ) {
            $this->assertEquals($date, $res[$key]);
        }
        
        $res = $calc->get($evt->id, '2014-04-01', '2014-04-30');
        $this->assertEquals($expected, $res);
        foreach ( $expected as $key => $date ) {
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
    function testGreg()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        /*
         * $evt = $this->createEvent('My Repeatable event', $seller->id, $this->createLocation()->id, '2014-03-01'); $cat = $this->createCategory('Normal', $evt->id, 100.00);
         */
        $evt = \EventBuilder::createInstance($this, $seller)->info("My Repeateable Event", $this->createLocation('MyLoc', $seller->id)->id, '2014-03-01')->addCategory(\CategoryBuilder::newInstance('Normal', 100.00))->create();
        
        $this->clearRequest();
        $_POST = $this->getRequest($evt->id);
        $_POST = array_merge($_POST, [ 'interval' => 2,'byday' => [ 'th','fr' ],'date_start' => '2014-04-03','range' => 'no_end' ]);
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $calc = new \tool\RepeatEventCalculator();
        $res = $calc->get($evt->id, '2014-03-31', '2014-09-01');
        
        foreach ( $res as $date ) {
            Utils::log($date->format('Y-m-d H:i:s'));
        }
        
        $this->assertEquals(new DateTime('2014-04-03 10:00:00'), $res[0]);
        
        $res = $calc->get($evt->id, '2014-04-01', '2014-09-01');
        $this->assertEquals(new DateTime('2014-04-03 10:00:00'), $res[0]);
        
        $res = $calc->get($evt->id, '2014-03-01', '2014-09-01');
        $this->assertEquals(new DateTime('2014-04-03 10:00:00'), $res[0]);
    }

    function testAutoCorrect()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        $evt = $this->createEvent('My Auto Correct Fixture', $seller->id, $this->createLocation()->id, '2014-03-01');
        $this->setEventId($evt, 'aaa');
        $cat = $this->createCategory('Normal', $evt->id, 100.00);
        
        $req = array ('page' => 'RepeatEvents','method' => 'preview-pattern','repeat_id' => '1','name' => 'Some Pattern','event_id' => 'aaa','time_start' => '10:00','frequency' => 'weekly','interval' => '2','date_start' => '2014-03-31','range' => 'no_end','date_end' => '',
                'byday' => array (0 => 'th',1 => 'fr' ) );
        
        $this->clearRequest();
        Utils::clearLog();
        $_POST = $req;
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $res = $ajax->res;
        
        $this->assertEquals('2014-04-03', $res['corrected_date_start']);
        $this->assertEquals('2014-04-03', date('Y-m-d', strtotime($res['first_ocurrence'])));
        
        // ***** Another case ***********************
        
        /*
         * "I do this pattern: every 2 weeks, on thursday the first ever occurence will be nov 6th, NOT nov 1st so we auto-correct the promoter and change date_start to nov 6th "
         */
        $req = array ('page' => 'RepeatEvents','method' => 'preview-pattern','repeat_id' => '','name' => 'Derp','event_id' => 'aaa','time_start' => '10:00','frequency' => 'weekly','interval' => '2','date_start' => '2014-11-01','range' => 'no_end','date_end' => '',
                'byday' => array (0 => 'th' ) );
        $this->clearRequest();
        Utils::clearLog();
        $_POST = $req;
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $res = $ajax->res;
        
        $this->assertEquals('2014-11-06', $res['corrected_date_start']);
        $this->assertEquals('2014-11-06', date('Y-m-d', strtotime($res['first_ocurrence'])));
    }

    /**
     * you need to discard any dates that happens before the event is supposed to start.
     *
     * For example, I start the repeat on the 1st of january,
     * but my event start date is on the 1st of july,
     * I should see no dates until the first of july.
     */
    function testDiscard()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        $evt = $this->createEvent('Discard Fixture', $seller->id, $this->createLocation()->id, '2014-07-01');
        $this->setEventId($evt, 'aaa');
        $cat = $this->createCategory('Normal', $evt->id, 100.00);
        
        // create the pattern
        $this->clearRequest();
        Utils::clearLog();
        
        $_POST = array ('page' => 'RepeatEvents','method' => 'save-pattern',        // 'preview-pattern',
        'repeat_id' => '','name' => 'Back to January','event_id' => 'aaa','time_start' => '08:00','frequency' => 'weekly','interval' => '1','date_start' => '2014-01-01','range' => 'no_end','date_end' => '','byday' => array (0 => 'tu' )
        // 1 => 'fr',
         );
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $calc = new \tool\RepeatEventCalculator();
        $res = $calc->get($evt->id, '2014-04-01', '2014-05-01');
        $this->assertEquals([ ], $res);
        
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

    function testEndDates()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        $evt = \EventBuilder::createInstance($this, $seller)->id('aaa')->info("Wee need End Dates Event", $this->createLocation('MyLoc', $seller->id)->id, '2014-02-03', '23:00:00'/*, '', '03:00:00'*/)->addCategory(\CategoryBuilder::newInstance('Normal', 100.00))->create();
        
        $this->clearRequest();
        $_POST = $this->getRequest($evt->id);
        $_POST = array_merge($_POST, [ 'interval' => 1,'byday' => [ 'mo' ],'date_start' => '2014-02-03','time_start' => '23:00','duration' => 4,        // 4 hours, ends at 03:00:00 of the next day
        'range' => 'no_end' ]);
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $calc = new \tool\RepeatEventCalculator();
        $calc->setCount(1);
        $res = $calc->get($evt->id, '2014-05-01');
        $this->assertEquals(new DateTime('2014-05-05 23:00:00'), $res[0]);
        
        $calc->setReturnSpectrum('end');
        $res = $calc->get($evt->id, '2014-05-01');
        $this->assertEquals(new DateTime('2014-05-06 03:00:00'), $res[0]);
        
        Utils::clearLog();
        $calc->setReturnSpectrum('both');
        $res = $calc->get($evt->id, '2014-05-01 00:00:00');
        $val = $res[0];
        $this->assertEquals(new DateTime('2014-05-05 23:00:00'), $val->start);
        $this->assertEquals(new DateTime('2014-05-06 03:00:00'), $val->end);
    }

    /**
     * "the dates available between the two dates provided to the method needs to merge all the dates from all the rules
     * event rule #1: Every Monday, Wednesday, Thursday at 19h, last 2h
     * event rule #2: Every Wednesday, Friday at 21h, last 3h
     * If I request the event dates between Tuesday and Friday, I should get:
     * Wednesday 19h
     * Wednesday 21h
     * Thursday 19h
     *
     * from 2014-05-20 23:59:59 to 2014-05-22 23:59:59
     * or 2014-05-21 00:00:00 to 2014-05-23 00:00:00 to be consistent
     * "
     */
    function testMixedResults()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        
        $evt = \EventBuilder::createInstance($this, $seller)->info('Mixed Results Event', $this->addLocation($seller->id)->id, '2014-05-19', '00:00:00')->id('aaa')->addCategory(\CategoryBuilder::newInstance('Test', 100.00))->create();
        
        // pattern 1
        $this->clearRequest();
        $req = $this->getRequest($evt->id);
        $req['name'] = 'Every Monday, Wednesday, Thursday at 19h, last 2h';
        $req['time_start'] = '19:00';
        $req['date_start'] = '2014-05-19';
        $req['range'] = 'no_end';
        $req['byday'] = [ 'mo','we','th' ];
        $req['duration'] = '2';
        $_POST = $req;
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        // pattern 2
        $this->clearRequest();
        $req = $this->getRequest($evt->id);
        $req['name'] = 'Every Wednesday, Friday at 21h, last 3h';
        $req['time_start'] = '21:00';
        $req['date_start'] = '2014-05-21';
        $req['range'] = 'no_end';
        $req['byday'] = [ 'we','fr' ];
        $req['duration'] = '3';
        $_POST = $req;
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        $expected = [ new DateTime('2014-05-21 19:00:00'),new DateTime('2014-05-21 21:00:00'),new DateTime('2014-05-22 19:00:00') ];
        
        Utils::clearLog();
        
        $calc = new \tool\RepeatEventCalculator();
        $calc->setCount(3);
        $res = $calc->get($evt->id, '2014-05-21 00:00:00', '2014-05-23 00:00:00');
        $this->assertEquals($expected, $res);
        foreach ( $expected as $key => $date ) {
            $this->assertEquals($date, $res[$key]);
        }
        
        // same result with alternate range
        Utils::clearLog();
        $calc = new \tool\RepeatEventCalculator();
        $calc->setCount(3);
        $res = $calc->get($evt->id, '2014-05-20 23:59:59', '2014-05-22 23:59:59');
        $this->assertEquals($expected, $res);
        foreach ( $expected as $key => $date ) {
            $this->assertEquals($date, $res[$key]);
        }
    }
    
    // this test was spcifically designed to run at the week of 2014-05-28 (NOW() in some sql) so prepare to do changes accordingly
    function testMathias()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        
        $evt = \EventBuilder::createInstance($this, $seller)->info('Mixed Results Event', $this->addLocation($seller->id)->id, '2014-04-29', '05:00:00', '2014-04-30', '23:00:00')->id('aaa')->addCategory(\CategoryBuilder::newInstance('Adult', 100.00))->addCategory(\CategoryBuilder::newInstance('Kid', 60.00))->create();
        
        // pattern 1
        $this->clearRequest();
        $req = $this->getRequest($evt->id);
        $req['name'] = 'Every Saturday';
        $req['time_start'] = '16:00';
        $req['date_start'] = '2014-05-03';
        $req['range'] = 'no_end';
        $req['byday'] = [ 'sa' ];
        $req['duration'] = '2';
        $_POST = $req;
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        Utils::clearLog();
        
        $calc = new \tool\RepeatEventCalculator();
        $calc->setCount(2); // apparently there's a bug in the count logic and it won't work with 1 (confirmed to fail with 1)
        $res = $calc->get($evt->id, '2014-05-24 16:00:01');
        $this->assertEquals(new DateTime('2014-05-31 16:00:00'), $res[0]);
        
        $calc = new \tool\RepeatEventCalculator();
        $calc->setCount(1); // apparently there's a bug in the count logic and it won't work with 1
        $res = $calc->get($evt->id, '2014-05-28 10:30:00');
        $this->assertEquals(new DateTime('2014-05-31 16:00:00'), $res[0]);
        
        // Utils::log(print_r($res, true));
        
        // so, um, let's like, run the cron
        Utils::clearLog();
        $cron = new \cron\NextEvent();
        $cron->execute();
        // this test may break after the week the test was originally authored
        // $this->assertEquals('2014-05-31 16:00:00', $this->db->get_one("SELECT next_event_from FROM event LIMIT 1"));
    }
    
    // We might need to move these test cases to a new test class
    function testYearly()
    {
        $this->clearAll();
        
        // Setup for manual testing
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        
        $evt = \EventBuilder::createInstance($this, $seller)->info('Yearly Event', $this->addLocation($seller->id)->id, '2014-04-29', '08:00:00')->id('aaa')->addCategory(\CategoryBuilder::newInstance('Adult', 100.00))->addCategory(\CategoryBuilder::newInstance('Kid', 60.00))->create();
        
        // every October 20th
        // pattern 1
        $this->clearRequest();
        Utils::clearLog();
        $req = $this->yearlyRequest($evt->id);
        $_POST = $req;
        $ajax = new RepeatEvents();
        $ajax->Process();
        
        // every October 20th
        $results[] = new DateTime('2014-10-20 09:00:00');
        $results[] = new DateTime('2015-10-20 09:00:00');
        $results[] = new DateTime('2016-10-20 09:00:00');
        
        $calc = new \tool\RepeatEventCalculator();
        $calc->now = '2014-10-15 00:00:00';
        $calc->setCount(3); // apparently there's a bug in the count logic and it won't work with 1 (confirmed to fail with 1)
        $res = $calc->get($evt->id, '2014-10-15 00:00:00', '2017-01-01 00:00:00');
        $this->assertEquals($results, $res);
    }

    function yearlyRequest($event_id)
    {
        return array ('method' => 'save-pattern','name' => 'Yearly Pattern','event_id' => $event_id,'time_start' => '09:00','frequency' => 'yearly','interval' => '1','date_start' => '2014-10-20','range' => 'no_end','duration' => 24,        // hours
        'bymonth' => 10,        // october
        'bymonthday' => 20 )        // 20th
        

        ;
    }

    function testWhen()
    {
        // every October 20th
        $results[] = new DateTime('1997-10-20 09:00:00');
        $results[] = new DateTime('1998-10-20 09:00:00');
        $results[] = new DateTime('1999-10-20 09:00:00');
        
        $r = new \When\When();
        
        $r->startDate(new DateTime("19971020T090000"))->freq("yearly")->count(3)->bymonth("10")->bymonthday("20")->generateOccurrences();
        
        $occurrences = $r->occurrences;
        
        foreach ( $results as $key => $result ) {
            $this->assertEquals($result, $occurrences[$key]);
        }
    }

    function testDatesInAMonth()
    {
        
    }
}