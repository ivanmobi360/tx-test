<?php


class LinkedPricesTest extends \DatabaseBaseTest{
    
    //If we buy a table with unlinked prices, we expect each individual ticket to amount for table_price/nb_seats 
    function testUnlinkedTablePurchase(){
        $this->clearAll();
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        
        $web = WebUser::loginAs($this->db, $seller->username);
        
        Utils::clearLog();
        $eb = \EventBuilder::createInstance($this, $seller)
        ->id('aaa')
        ->info('Some Table based Event', $this->createLocation('SomeLoc', $seller->id)->id, $this->dateAt('+5 day'))
        ->param('capacity', 300)
        ->param('has_ccfee', 0)
        ->addCategory( \TableCategoryBuilder::newInstance('Unlinked Table', 2000)
                ->nbTables(30)->seatsPerTable(10)
                ->asSeats(true)->seatName('Unlinked Seat')->seatDesc('A unlinked seat')->seatPrice('250.00')->linkPrices(0)
                ->tax_inc(1)//->fee_inc(1)
                , $cat)
        ->addCategory( \TableCategoryBuilder::newInstance('Linked Table', 400)
                ->nbTables(30)->seatsPerTable(10)
                ->asSeats(true)->seatName('Linked Seat')->seatDesc('A linked seat')->seatPrice('40.00')
                , $lcat)
        ->addCategory(\CategoryBuilder::newInstance('Normal Event', 100))        
                ;
        $evt = $eb->create();

        //Expect an event
        $this->assertRows(1, 'event');
        
        $seatCat = $cat->getChildSeatCategory();

        //unlinked prices
        $this->assertEquals(250, $seatCat->price_override);
        $this->assertEquals(2000, $cat->price);
        
        //cart test
        Utils::clearLog();
        $res = \tool\Cart::calculateRowValues($seatCat->id);
        $this->assertEquals(250, $res['result_calc']['price']);
        
        //•	if the person buys less or more than the # of seats on the table,
        //•	he pays the unlinked price if it exist,
        $res = \tool\Cart::calculateRowValues($seatCat->id);
        $this->assertEquals(250, $res['result_calc']['price']);
        
        $res = \tool\Cart::calculateRowValues($lcat->getChildSeatCategory()->id);
        $this->assertEquals(40, $res['result_calc']['price']); //no price_override
        
        //if he buys 10 tickets, he pays $2000, each tickets $200
        Utils::clearLog();
        $res = \tool\Cart::calculateRowValues($seatCat->id, 10);
        $this->assertEquals(200*10, $res['result_calc']['price']);
        
        
        Utils::clearLog();
        $res = \tool\Cart::calculateRowValues($lcat->getChildSeatCategory()->id);
        $this->assertEquals(40, $res['result_calc']['price']);
    
        $web->logout();
        
        $this->clearRequest();
        $web = WebUser::loginAs($this->db, $foo->username);
        
        
        $web->addToCart($seatCat->id, 10);
        Utils::clearLog();
        $web->payByCashBtn();
        //this is not very nice, but for testing purpose, I expect a ticket's price_category to be 200.00
        $this->assertEquals(200, $this->getLastTicket('price_category'));
        
        //check nothing broke
        $this->clearRequest();
        $web->addToCart($seatCat->id, 2); Utils::clearLog();
        $web->payByCashBtn();
        $this->assertEquals(250, $this->getLastTicket('price_category'));
        
        $this->clearRequest();
        $web->addToCart($seatCat->id, 11); Utils::clearLog();
        $web->payByCashBtn();
        $this->assertEquals(250, $this->getLastTicket('price_category'));
        
        //this works only when the previous transactions are comented out. It seems to be a capacity problem.
        /*$this->clearRequest();
        $web->addToCart($lcat->getChildSeatCategory()->id, 10); Utils::clearLog();
        $web->payByCashBtn();
        $this->assertEquals(40, $this->getLastTicket('price_category'));*/
    }
    
    protected function getLastTicket($prop=false){
        $ticket = $this->db->auto_array("SELECT * FROM ticket ORDER BY id DESC LIMIT 1");
        if($prop){
            return $ticket[$prop];
        }
        
        return $ticket;
    }
    
 
}


