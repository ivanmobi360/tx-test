<?php

namespace controller;

use \WebUser,
    \Utils;

class NeweventTest extends \DatabaseBaseTest {

    function testBuilder() {
        $this->clearAll();
        $seller = $this->createUser('seller');
        $this->createUser('foo');
        $loc = $this->createLocation('Quito', $seller->id);

        $name = 'Some Boring Event';

        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
                ->id('aaa')
                ->info($name, $loc->id, $this->dateAt('+5 day'))
                ->addCategory(\CategoryBuilder::newInstance('Test', 45), $catA)
        ;
        $evt = $eb->create();

        //Expect an event
        $this->assertRows(1, 'event');

        $cat = $this->db->auto_array("SELECT * FROM category WHERE event_id=? LIMIT 1", $evt->id);
        $this->assertEquals($catA->id, $cat['id']);
        //$evt2 = new \model\Events($evt->id);
        $this->assertEquals($name, $evt->name);
        $this->assertEquals(1, $evt->has_ccfee);
        $this->assertEquals('aaa', $evt->id);
        //default to 50
        $this->assertEquals(50, $cat['order']);
    }
    
    /**
     * we need to show a new input text field for each category called "Display Order" (or maybe just "Order" to make it short)
     * that will be prefilled with the number 50 at creation and will show the actual `order` field at edition
     */
    function testOrder() {
        $this->clearAll();
        $seller = $this->createUser('seller');
        $this->createUser('foo');
        $loc = $this->createLocation('Quito', $seller->id);
    
    
        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
        ->id('aaa')
        ->info('Ordering Test', $loc->id, $this->dateAt('+5 day'))
        ->addCategory(\CategoryBuilder::newInstance('Test 10', 45)->param('order', 10), $catA)
        ->addCategory(\CategoryBuilder::newInstance('Test 30', 45)->param('order', 30), $catB)
        ->addCategory(\CategoryBuilder::newInstance('Test 20', 45)->param('order', 20), $catB)
        ;
        $evt = $eb->create();
    
        //Expect an event
        $this->assertRows(1, 'event');
    
        $expected = [
        ['name' => 'Test 10', 'order' => 10]
        , ['name' => 'Test 20', 'order' => 20]
        , ['name' => 'Test 30', 'order' => 30]
        ];
    
        $res = $this->db->getIterator("SELECT name, `order` FROM category WHERE event_id=? ORDER BY `order`", $evt->id);
        foreach ($res as $i => $cat) {
            $this->assertEquals($expected[$i]['name'], $cat['name']);
            $this->assertEquals($expected[$i]['order'], $cat['order']);
        }
    
    }

    function testLinkedTable() {
        $this->clearAll();
        $seller = $this->createUser('seller');
        $this->createUser('foo');
        $loc = $this->createLocation('Cuenca', $seller->id);


        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
                ->id('aaa')
                ->info('Dinner Time', $loc->id, $this->dateAt('+5 day'))
                ->addCategory(\TableCategoryBuilder::newInstance('Some Table', 1000)
                ->nbTables(3)->seatsPerTable(10)
                ->asSeats(true)->seatName('A seat')->seatDesc('This is A Seat')->seatPrice('40.00') //this price wins (as of now)
                , $cat)
        ;
        $evt = $eb->create();

        //Expect an event
        $this->assertRows(1, 'event');

        $this->assertEquals(400, $cat->price);
        $this->assertNull($cat->price_override);
        $this->assertEquals(40, $cat->getChildSeatCategory()->price);
        $this->assertNull($cat->getChildSeatCategory()->price_override); //no override
        //must be linked
        $this->assertEquals(null, $cat->price_override);
        //$this->assertEquals(1, $cat->getChildSeatCategory()->link_prices);
        //echo $catA->id; //It returned the id of the main table
        //return;
        // **************************************************************************
        //Now edit it, I want it to be unlinked
        $this->clearRequest();
        $_GET = array(
            'action' => 'administration',
            'mod' => 'events',
            'do' => 'edit',
            'id' => 'aaa',
        );
        $_POST = $this->get_linked_2_unlinked_request();
        Utils::clearLog();
        @$cont = new \controller\Editevents();
        //reload cat
        $cat = new \model\Categories($cat->id);
        //$this->assertEquals(0, $cat->link_prices);
        //$this->assertEquals(0, $cat->getChildSeatCategory()->link_prices);

        $this->assertEquals(400, $cat->price);
        $this->assertNull($cat->price_override);
        $this->assertEquals(40, $cat->getChildSeatCategory()->price);
        $this->assertEquals(50, $cat->getChildSeatCategory()->price_override);
    }

    

    function testUnlinkedTable() {
        $this->clearAll();
        $seller = $this->createUser('seller');
        $this->createUser('foo');

        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
                ->id('aaa')
                ->info('Dinner Time', $this->createLocation('SomeLoc', $seller->id)->id, $this->dateAt('+5 day'))
                ->param('has_ccfee', 0)
                ->addCategory(\TableCategoryBuilder::newInstance('Unlinked Table', 2000)
                ->nbTables(3)->seatsPerTable(10)
                ->asSeats(true)->seatName('Unlinked Seat')->seatDesc('A unlinked seat')->seatPrice('250.00')->linkPrices(0)
                , $cat)
        ;
        $evt = $eb->create();

        //Expect an event
        $this->assertRows(1, 'event');

        //unlinked prices
        $this->assertEquals(2000.00, $cat->price);
        $this->assertNull($cat->price_override);
        $this->assertEquals(200.00, $cat->getChildSeatCategory()->price);
        $this->assertEquals(250.00, $cat->getChildSeatCategory()->price_override);

        //cart test
        Utils::clearLog();
        $res = \tool\Cart::calculateRowValues($cat->getChildSeatCategory()->id);
        $this->assertEquals(250, $res['result_calc']['price']);





        //must be unlinked
        //$this->assertEquals(0, $cat->link_prices);
        //$this->assertEquals(0, $cat->getChildSeatCategory()->link_prices);
        //return;
        // **************************************************************************
        //Now edit it, I want it to be linked
        $this->clearRequest();
        $_GET = array(
            'action' => 'administration',
            'mod' => 'events',
            'do' => 'edit',
            'id' => 'aaa',
        );
        $_POST = $this->get_unlinked_to_linked_request();
        Utils::clearLog();
        @$cont = new \controller\Editevents();
        //reload cat
        $cat = new \model\Categories($cat->id);
        //$this->assertEquals(1, $cat->link_prices);
        //$this->assertEquals(1, $cat->getChildSeatCategory()->link_prices);

        $this->assertEquals(2000, $cat->price);
        $this->assertNull($cat->price_override);
        $this->assertEquals(200, $cat->getChildSeatCategory()->price);
        $this->assertNull($cat->getChildSeatCategory()->price_override);
    }
    
    function testHideCategories() {
        $this->clearAll();
        $seller = $this->createUser('seller');
        $this->createUser('foo');
        $loc = $this->createLocation('Quito', $seller->id);
    
        
        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
        ->id('aaa')
        ->info('Hide Test', $loc->id, $this->dateAt('+5 day'))
        ->addCategory(\CategoryBuilder::newInstance('Adult', 100), $catA)
        ->addCategory(\CategoryBuilder::newInstance('Adult Hidden', 25)->show(0), $catB)
        ;
        $evt = $eb->create();
    
        $catA = $this->db->auto_array("SELECT * FROM category WHERE id=? LIMIT 1", $catA->id);
        $catB = $this->db->auto_array("SELECT * FROM category WHERE id=? LIMIT 1", $catB->id);
        
        $this->assertEquals(0, $catA['hidden']);
        $this->assertEquals(1, $catB['hidden']);
        
        //Even better, ask the controllers
        $this->assertEquals(1, count(\model\Categoriesmanager::getByEventId($evt->id)) );
        $this->assertEquals(2, count(\model\Categoriesmanager::getByEventIdForEditor($evt->id)) );
        
        // ******************
        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
        //->id('aaa')
        ->info('All Hidden', $loc->id, $this->dateAt('+5 day'))
        ->addCategory(\CategoryBuilder::newInstance('Adult', 100)->show(0), $catA)
        ->addCategory(\CategoryBuilder::newInstance('Adult Hidden', 25)->show(0), $catB)
        ;
        $evt = $eb->create();
        
        $catA = $this->db->auto_array("SELECT * FROM category WHERE id=? LIMIT 1", $catA->id);
        $catB = $this->db->auto_array("SELECT * FROM category WHERE id=? LIMIT 1", $catB->id);
        
        $this->assertEquals(1, $catA['hidden']);
        $this->assertEquals(1, $catB['hidden']);
        
        //Even better, ask the controllers
        $this->assertEmpty(\model\Categoriesmanager::getByEventId($evt->id)) ;
        $this->assertEquals(2, count(\model\Categoriesmanager::getByEventIdForEditor($evt->id)) );
        
        // *****************************
        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
        //->id('aaa')
        ->info('All Shown', $loc->id, $this->dateAt('+5 day'))
        ->addCategory(\CategoryBuilder::newInstance('Adult', 100)->show(1), $catA)
        ->addCategory(\CategoryBuilder::newInstance('Adult Hidden', 25)->show(1), $catB)
        ;
        $evt = $eb->create();
        
        $catA = $this->db->auto_array("SELECT * FROM category WHERE id=? LIMIT 1", $catA->id);
        $catB = $this->db->auto_array("SELECT * FROM category WHERE id=? LIMIT 1", $catB->id);
        
        $this->assertEquals(0, $catA['hidden']);
        $this->assertEquals(0, $catB['hidden']);
        
        //Even better, ask the controllers
        $this->assertEquals(2, count(\model\Categoriesmanager::getByEventId($evt->id)) );
        $this->assertEquals(2, count(\model\Categoriesmanager::getByEventIdForEditor($evt->id)) );
        
        
        // *****************************************
        //Now let's work the table case
        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
        ->id('table1')
        ->info('Event with tables', $loc->id, $this->dateAt('+5 day'))
        ->addCategory(\TableCategoryBuilder::newInstance('Full Table', 1000)->order(1)
                ->description('Full Table Desc')->nbTables(10)->seatsPerTable(5)
                ->show(1)
                , $catA)
        ->addCategory(\TableCategoryBuilder::newInstance('Single Seat Table', 800)
                ->description('Single Seat Table Desc')->nbTables(6)->seatsPerTable(8)
                ->asSeats(1)
                ->seatName('A single seat')->seatDesc('A single seat description')
                ->show(1)
                , $catB)
        ;
        $evt = $eb->create();
        
        $this->assertEquals(3, count(\model\Categoriesmanager::getByEventId($evt->id)) );
        $this->assertEquals(3, count(\model\Categoriesmanager::getByEventIdForEditor($evt->id)) );
        $this->assertEquals(3, count(\model\Categoriesmanager::listCategoriesByEventIDForEditor($evt->id, $seller->id)) ); //the hidden one then is parsed in view to show 2 boxes
        
        
        // *****************************************
        //Now let's work the table case
        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
        ->id('table2')
        ->info('Full is hidden, Sold as seat visible', $loc->id, $this->dateAt('+5 day'))
        ->addCategory(\TableCategoryBuilder::newInstance('Full Table', 1000)->order(1)
                ->description('Full Table Desc')->nbTables(10)->seatsPerTable(5)
                ->show(0)
                , $catA)
        ->addCategory(\TableCategoryBuilder::newInstance('Single Seat Table', 800)
                ->description('Single Seat Table Desc')->nbTables(6)->seatsPerTable(8)
                ->asSeats(1)
                ->seatName('A single seat')->seatDesc('A single seat description')
                ->show(1)
                , $catB)
        ;
        $evt = $eb->create();
        
        $this->assertEquals(2, count(\model\Categoriesmanager::getByEventId($evt->id)) );
        $this->assertEquals(3, count(\model\Categoriesmanager::getByEventIdForEditor($evt->id)) );
        $this->assertEquals(3, count(\model\Categoriesmanager::listCategoriesByEventIDForEditor($evt->id, $seller->id)) );
        
        // *****************************************
        //Now let's work the table case
        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
        ->id('table3')
        ->info('Full visible, Sold As seat is hidden', $loc->id, $this->dateAt('+5 day'))
        ->addCategory(\TableCategoryBuilder::newInstance('Full Table', 1000)->order(1)
                ->description('Full Table Desc')->nbTables(10)->seatsPerTable(5)
                ->show(1)
                , $catA)
        ->addCategory(\TableCategoryBuilder::newInstance('Sold as Seat Table', 800)
                ->description('Sold as Seat Table Desc')->nbTables(6)->seatsPerTable(8)
                ->asSeats(1)
                ->seatName('A single seat')->seatDesc('A single seat description')
                ->show(0)
                , $catB)
                ;
        $evt = $eb->create();
        
        $catA = $this->db->auto_array("SELECT * FROM category WHERE id=? LIMIT 1", $catA->id);
        $catB = $this->db->auto_array("SELECT * FROM category WHERE id=? LIMIT 1", $catB->id);
        
        $this->assertEquals(0, $catA['hidden']);
        $this->assertEquals(1, $catB['hidden']);
        //the child must be  hidden too
        //$this->assertEquals(1, $this->db->get_one('SELECT hidden FROM category WHERE id=?', $catB['category_id']));

        $this->assertEquals(1, count(\model\Categoriesmanager::getByEventId($evt->id)) ); //for website, only Full is visible
        $this->assertEquals(3, count(\model\Categoriesmanager::getByEventIdForEditor($evt->id)) ); 
        $this->assertEquals(3, count(\model\Categoriesmanager::listCategoriesByEventIDForEditor($evt->id, $seller->id)) );
        
    }
    
    function testBug01_hidden_to_show(){
        $this->clearAll();
        $seller = $this->createUser('seller');
        $this->createUser('foo');
        $loc = $this->createLocation('Quito', $seller->id);
    
        
    
        // *****************************************
        //Now let's work the table case
        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
        ->id('table1')
        ->info('Event with tables', $loc->id, $this->dateAt('+5 day'))
        ->addCategory(\TableCategoryBuilder::newInstance('Full Table', 1000)->order(1)
                ->description('Full Table Desc')->nbTables(10)->seatsPerTable(5)
                ->show(1)
                , $catA)
        ;
        $evt = $eb->create();
        
        //edit the event, make the category shown (hidden = 0)
        $this->clearRequest();
        $_GET = array(
                'action' => 'administration',
                'mod' => 'events',
                'do' => 'edit',
                'id' => $evt->id,
        );
        $_POST = $this->hidden_to_shown_data();
        Utils::clearLog();
        @$cont = new \controller\Editevents();
        
        $this->assertEquals(0, $this->db->get_one("SELECT hidden FROM category WHERE id=?", $catA->id));
        
        // hide! hidden=1
        $this->clearRequest();
        $_GET = array(
                'action' => 'administration',
                'mod' => 'events',
                'do' => 'edit',
                'id' => $evt->id,
        );
        $_POST = $this->hidden_to_shown_data();
        unset($_POST['cat_0_show']);
        Utils::clearLog();
        @$cont = new \controller\Editevents();
        
        $this->assertEquals(1, $this->db->get_one("SELECT hidden FROM category WHERE id=?", $catA->id));
        
        // mave visible (hidden=0)
        $this->clearRequest();
        $_GET = array(
                'action' => 'administration',
                'mod' => 'events',
                'do' => 'edit',
                'id' => $evt->id,
        );
        $_POST = $this->hidden_to_shown_data();
        $_POST['cat_0_show']=1;
        Utils::clearLog();
        @$cont = new \controller\Editevents();
        
        $this->assertEquals(0, $this->db->get_one("SELECT hidden FROM category WHERE id=?", $catA->id));
        //bug was fixed by changing a sql in Categoriesmanager::updateCategory that was enforcing hidden=0 condition
    }

    function get_linked_2_unlinked_request() {
        return array(
            'event_id' => 'aaa',
            'MAX_FILE_SIZE' => '3000000',
            'e_name' => 'Dinner Time',
            'e_capacity' => '25',
            'e_date_from' => '2014-05-03',
            'e_time_from' => '',
            'e_date_to' => '',
            'e_time_to' => '',
            'e_description' => '<p>test</p>',
            'e_short_description' => '',
            'ema' =>
            array(
                'content' => '',
            ),
            'sms' =>
            array(
                'content' => '',
            ),
            'c_id' => '2',
            'c_name' => 'Seller',
            'c_email' => 'Seller@gmail.com',
            'c_companyname' => '',
            'c_position' => '',
            'c_home_phone' => '579135104',
            'c_phone' => '579135104',
            'l_latitude' => '9.409469999999999',
            'l_longitude' => '-75.70060999999998',
            'l_id' => '4',
            'l_name' => 'Cuenca',
            'l_street' => 'Calle 1',
            'l_street2' => '',
            'l_country_id' => '124',
            'l_state_id' => '2',
            'l_state' => 'AOHA',
            'l_city' => 'Quebec',
            'l_zipcode' => 'BB',
            'dialog_video_title' => '',
            'dialog_video_content' => '',
            'id_ticket_template' => '6',
            'email_id' => '1',
            'email_description' => 'on',
            'e_currency_id' => '1',
            'payment_method' => '7',
            'paypal_account' => '',
            'has_ccfee_cb' => '1',
            'no_tax' => 'on',
            'tax_ref_hst' => '',
            'tax_ref_pst' => '',
            'tax_other_name' => '',
            'tax_other_percentage' => '0',
            'tax_ref_other' => '',
            'ticket_type' => 'open',
            'cat_all' =>
            array(
                0 => '0',
            ),
            'cat_0_type' => 'table',
            'cat_0_id' => '330',
            'cat_0_name' => 'Some Table',
            'cat_0_description' => 'A description',
            'cat_0_capa' => '3',
            'cat_0_over' => '0',
            'cat_0_display_mode' => '1',
            'cat_0_tcapa' => '10',
            'cat_0_price' => '400.00',
            'cat_0_single_ticket' => 'true',
            'cat_0_ticket_price' => '50',
            'cat_0_seat_name' => 'A seat',
            'cat_0_seat_desc' => 'This is A Seat',
            'save' => 'Save',
            'has_ccfee' => '1',
        );
    }

    function get_unlinked_to_linked_request() {
        return array(
            'event_id' => 'aaa',
            'MAX_FILE_SIZE' => '3000000',
            'e_name' => 'Dinner Time',
            'e_capacity' => '25',
            'e_date_from' => '2014-05-04',
            'e_time_from' => '',
            'e_date_to' => '',
            'e_time_to' => '',
            'e_description' => '<p>test</p>',
            'e_short_description' => '',
            'ema' =>
            array(
                'content' => '',
            ),
            'sms' =>
            array(
                'content' => '',
            ),
            'c_id' => '2',
            'c_name' => 'Seller',
            'c_email' => 'Seller@gmail.com',
            'c_companyname' => '',
            'c_position' => '',
            'c_home_phone' => '670610252',
            'c_phone' => '670610252',
            'l_latitude' => '9.409469999999999',
            'l_longitude' => '-75.70060999999998',
            'l_id' => '4',
            'l_name' => 'SomeLoc',
            'l_street' => 'Calle 1',
            'l_street2' => '',
            'l_country_id' => '124',
            'l_state_id' => '2',
            'l_state' => 'AOHA',
            'l_city' => 'Quebec',
            'l_zipcode' => 'BB',
            'dialog_video_title' => '',
            'dialog_video_content' => '',
            'id_ticket_template' => '6',
            'email_id' => '1',
            'email_description' => 'on',
            'e_currency_id' => '1',
            'payment_method' => '7',
            'paypal_account' => '',
            'no_tax' => 'on',
            'tax_ref_hst' => '',
            'tax_ref_pst' => '',
            'tax_other_name' => '',
            'tax_other_percentage' => '0',
            'tax_ref_other' => '',
            'ticket_type' => 'open',
            'cat_all' =>
            array(
                0 => '0',
            ),
            'cat_0_type' => 'table',
            'cat_0_id' => '330',
            'cat_0_name' => 'Unlinked Table',
            'cat_0_description' => 'A description',
            'cat_0_capa' => '3',
            'cat_0_over' => '0',
            'cat_0_display_mode' => '1',
            'cat_0_tcapa' => '10',
            'cat_0_price' => '2000.00',
            'cat_0_single_ticket' => 'true',
            'cat_0_ticket_price' => '200.00',
            'cat_0_link_prices' => '1',
            'cat_0_seat_name' => 'Unlinked Seat',
            'cat_0_seat_desc' => 'A unlinked seat',
            'save' => 'Save',
            'has_ccfee' => '0',
        );
    }
    
    //for table only category
    function hidden_to_shown_data(){
        return array (
  'event_id' => 'table1',
  'MAX_FILE_SIZE' => '3000000',
  'e_name' => 'Event with tables',
  'e_capacity' => '25',
  'e_date_from' => '2014-06-03',
  'e_time_from' => '',
  'e_date_to' => '',
  'e_time_to' => '',
  'e_description' => '<p>test</p>',
  'e_short_description' => '',
  'ema' => 
  array (
    'content' => '',
  ),
  'sms' => 
  array (
    'content' => '',
  ),
  'c_id' => '2',
  'c_name' => 'Seller',
  'c_email' => 'Seller@gmail.com',
  'c_companyname' => '',
  'c_position' => '',
  'c_home_phone' => '517212495',
  'c_phone' => '517212495',
  'l_latitude' => '9.409469999999999',
  'l_longitude' => '-75.70060999999998',
  'l_id' => '4',
  'l_name' => 'Quito',
  'l_street' => 'Calle 1',
  'l_street2' => '',
  'l_country_id' => '124',
  'l_state_id' => '2',
  'l_state' => 'AOHA',
  'l_city' => 'Quebec',
  'l_zipcode' => 'BB',
  'dialog_video_title' => '',
  'dialog_video_content' => '',
  'id_ticket_template' => '6',
  'email_id' => '1',
  'email_description' => 'on',
  'e_currency_id' => '1',
  'payment_method' => '7',
  'paypal_account' => '',
  'has_ccfee_cb' => '1',
  'no_tax' => 'on',
  'tax_ref_hst' => '',
  'tax_ref_pst' => '',
  'tax_other_name' => '',
  'tax_other_percentage' => '0',
  'tax_ref_other' => '',
  'ticket_type' => 'open',
  'cat_all' => 
  array (
    0 => '0',
  ),
  'cat_0_type' => 'table',
  'cat_0_id' => '331',
  'cat_0_name' => 'Full Table',
  'cat_0_description' => 'Full Table Desc',
  'cat_0_order' => '1',
  'cat_0_show' => '1',
  'cat_0_capa' => '10',
  'cat_0_over' => '0',
  'cat_0_display_mode' => '1',
  'cat_0_tcapa' => '5',
  'cat_0_price' => '1000.00',
  'cat_0_ticket_price' => '0.00',
  'cat_0_link_prices' => '1',
  'cat_0_seat_name' => '',
  'cat_0_seat_desc' => '',
  'save' => 'Save',
  'has_ccfee' => '1',
);
    }

}
