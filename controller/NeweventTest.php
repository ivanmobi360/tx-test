<?php


namespace controller;
use \WebUser, \Utils;
class NeweventTest extends \DatabaseBaseTest{
  
    function testEventBuilder(){
        $this->clearAll();
        $seller = $this->createUser('seller');
        $this->createUser('foo');
        $loc = $this->createLocation('Quito', $seller->id);
    
    
        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
        ->id('aaa')
        ->info('Tuesday', $loc->id, $this->dateAt('+5 day'))
        ->addCategory(\CategoryBuilder::newInstance('Test', 45), $catA)
        ;
        $evt = $eb->create();
    
        //Expect an event
        $this->assertRows(1, 'event');
    
        $cat = $this->db->auto_array("SELECT * FROM category WHERE event_id=? LIMIT 1", $evt->id);
        $this->assertEquals($catA->id, $cat['id']);
        //$evt2 = new \model\Events($evt->id);
        $this->assertEquals('Tuesday', $evt->name);
        $this->assertEquals(1, $evt->has_ccfee);
        $this->assertEquals('aaa', $evt->id);
    }
 
}


