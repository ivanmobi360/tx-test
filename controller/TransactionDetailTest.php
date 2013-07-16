<?php
/**
 * Hello,
we are showing the wrong category price when we look at a transaction detail.
When you go to (this is an example) Reports -> Transaction List (both in the merchant website and the admin360 site), 
and click on a transaction, the price shown under the column "Category Price" at the bottom is taken right from the 
category table, but that can be changed over time if the merchant decide to change the price of a category after 
some tickets have been sold. We should take the `price_category` column from the ticket table, which has the original 
price the ticket was sold at. 
 */


class TransactionDetailTest extends DatabaseBaseTest{
  
  //fixture to create activity for report
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();

    //Events
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('First', $seller->id, 1, '2012-01-01', '9:00', '2012-01-05', '18:00' );
    $catA = $this->createCategory('CatA', $evt->id, 3.00, 500, 0, array('tax_inc'=>1) );
    $catB = $this->createCategory('CatB', $evt->id, 5.00, 100, 0, array('tax_inc'=>1) );
    $catC = $this->createCategory('CatC', $evt->id, 20.00, 100, 0, array('tax_inc'=>1) );
    
    
    $foo = $this->createUser('foo');
    
    //$this->createPromocode('SOME-CO3D', $catA);
    
    $this->buyTickets($foo->id, $catC->id, 3);

    
    //a more complex purchase
    $bar = $this->createUser('bar');
    $client = new WebUser($this->db);
    $client->addToCart($catA->id, 1);
    $client->addToCart($catB->id, 1);
    $client->addToCart($catC->id, 5);
    $txn_id = $client->placeOrder();
    $this->completeTransaction($txn_id);
    
    
  }
 
}


