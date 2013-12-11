<?php

use model\FeeVO;
use model\Module;
use tool\FeeFinder;
use model\Eventsmanager;
use tool\Date;
class FeeTest extends DatabaseBaseTest{
  
  function testReset(){
    $this->clearAll();
    
    //create sellers
    $seller = $this->createUser('seller');
    $this->createUser('seller2');
    $this->createUser('seller3');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
    $this->setEventId($evt, 'aaa');
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    $evt = $this->createEvent('Tacos Time', 'seller', $this->createLocation()->id, $this->dateAt("+3 day"), '15:00', $this->dateAt("+2 day") , '17:00' );
    $this->setEventId($evt, 'tacos');
    $catQ = $this->createCategory('Cuates', $evt->id, 100);
    
    $evt = $this->createEvent('Dynamite', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"), '15:00', $this->dateAt("+2 day") , '17:00' );
    $evt = $this->createEvent('Elecciones 2013', 'seller2', $this->createLocation()->id, $this->dateAt("+5 day"), '15:00', $this->dateAt("+2 day") , '17:00' );
    
    
    //create buyers
    $this->createUser('foo');
    $this->createUser('bar');
    $this->createUser('baz');
    
  }
  
  

  
  function testRetrieval(){
    
    $this->clearAll();
    
    $this->db->beginTransaction();
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $box = $this->createBoxoffice('xbox', $seller->id);
    
    
    $evt = $this->createEvent('Tacos Night', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'tacos');
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    
    /*
    $evt = $this->createEvent('Filler Event', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'filler');
    $caXA = $this->createCategory('HORIZON', $evt->id, 100);*/
    
    $this->db->commit();
    
    
    //Create
    $this->baseModuleFees();

  }
  
  function baseModuleFees(){
    $this->createModuleFee('Outlet Fees', 2.2, 2.3, 122, Module::OUTLET);
    $this->createModuleFee('Boxoffice Fees', 3, 3.3, 133, Module::BOX_OFFICE);
    $this->createModuleFee('Reservation Fees', 4, 4.44, 100.44, Module::RESERVATION);
  }
  
  
  function testFinder(){
    
    $this->clearAll();
    $this->db->beginTransaction();
    $seller = $this->createUser('seller');

    $evt = $this->createEvent('Tacos Night', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'tacos');
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    
    $evt = $this->createEvent('Lunch', $this->createUser('seller2')->id, $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'lunch');
    $catX = $this->createCategory('CHEAPO', $evt->id, 2);
    
    
    $this->db->commit();
    Utils::clearLog();
    
    // *** Should find the default global fees
    $finder = new FeeFinder();
    $feeVo = $finder->find(Module::WEBSITE, $catA->id );
    
    $fee_global = $this->currentGlobalFee();// new FeeVO(1.08, 2.5, 9.95);
    
    $this->assertEquals($fee_global, $feeVo);
    
    
    // *** Should find the default module fees
    $fee_web          = $this->createModuleFee("Website Default Fee", 1.25, 3.5, 11.25, Module::WEBSITE);
    $fees_reservation = $this->createModuleFee("Reservation Default Fee", 5, 10, 15, Module::RESERVATION);
    
    $feeVo = $finder->find(Module::WEBSITE, $catA->id );
    $this->assertEquals($fee_web, $feeVo);


    $fees_out_v2 = $this->createSpecificFee('venue', 1.1, 1.2, 1.3, Module::OUTLET);
    $feeVo = $finder->find(Module::WEBSITE, $catX->id );
    $this->assertEquals($fee_web, $feeVo); //on Website, still default website
    $feeVo = $finder->find(Module::OUTLET, $catX->id );
    $this->assertEquals($fees_out_v2, $feeVo); //on Outlet, venue specific

    
    // ****** User specific
    $fees_web_seller = $this->createSpecificFee('user', 4.51, 4.52, 4.53, Module::WEBSITE, 'seller');Utils::clearLog();
    $this->assertEquals($fees_web_seller, $finder->find(Module::WEBSITE, $catA->id ));
    $this->assertEquals($fees_web_seller, $finder->find(Module::WEBSITE, $catB->id ));
    $this->assertEquals($fee_web, $finder->find(Module::WEBSITE, $catX->id )); //on Website, use default website
    $this->assertEquals($fee_global, $finder->find(Module::BOX_OFFICE, $catX->id )); //global
    
    //now it would appear that I can define many fees for the same item, but only one is is_default=1
    $fees_web_seller2 = $this->createSpecificFee('user', 4.61, 4.62, 4.63, Module::WEBSITE, 'seller');
    $this->assertEquals($fees_web_seller2, $finder->find(Module::WEBSITE, $catA->id ));
    
    // ******* Find Event specific
    $fees_web_evt1 = $this->createSpecificFee('event', 2.3, 2.4, 2.5, Module::WEBSITE, 'seller', 'tacos');
    $this->assertEquals($fees_web_evt1, $finder->find(Module::WEBSITE, $catA->id )); //on Website, use de event one
    $this->assertEquals($fee_web, $finder->find(Module::WEBSITE, $catX->id )); //on Website, use default website
    $this->assertEquals($fee_global, $finder->find(Module::BOX_OFFICE, $catX->id )); //global
    
    // ****** Category specific
    $fees_web_catA = $this->createSpecificFee('category', 0.15, 0.16, 0.17, Module::WEBSITE, 'seller', 'tacos', $catA->id );
    $this->assertEquals($fees_web_catA, $finder->find(Module::WEBSITE, $catA->id )); //on Website, use the category one
    $this->assertEquals($fees_web_evt1, $finder->find(Module::WEBSITE, $catB->id )); //on Website, use the event one
    $this->assertEquals($fee_web, $finder->find(Module::WEBSITE, $catX->id )); //on Website, use default website
    $this->assertEquals($fee_global, $finder->find(Module::BOX_OFFICE, $catX->id )); //global
    
  }
  
  
    
  function testDetectFee(){
    $this->clearAll();

    $this->db->beginTransaction();
    $seller = $this->createUser('seller');

    $evt = $this->createEvent('Tacos Night', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'tacos');
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    
    $this->db->commit();
    Utils::clearLog();
    
    //return;
    
    //Define fees
    $fee_web = $this->createModuleFee("Website Default Fee", 1.25, 3.5, 11.25, Module::WEBSITE);
    $fee_box = $this->createModuleFee("Box Office Default Fee", 2.25, 2.35, 2.45, Module::BOX_OFFICE);
    $fee_out = $this->createModuleFee("Outlet Default Fee", 7.11, 7.12, 7.13, Module::OUTLET);
    
    $ffinder = new FeeFinder();

    $this->assertEquals($fee_web, $ffinder->find(Module::WEBSITE, $catA->id ));
    $this->assertEquals($fee_box, $ffinder->find(Module::BOX_OFFICE, $catA->id ));
    $this->assertEquals($fee_out, $ffinder->find(Module::OUTLET, $catA->id ));
  }
  
  /**
   * Simple setup to try out the current specific fee logic 
   */
  function testSpecificFee(){
      $this->clearAll();
      
      $seller = $this->createUser('seller');
      
      $evt = $this->createEvent('Specific stories', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
      $this->setEventId($evt, 'aaa');
      $catA = $this->createCategory('ADULT', $evt->id, 100);
      $catB = $this->createCategory('KID', $evt->id, 150);
      
      $fc = $this->createSpecificFee('', 1.1, 2.2, 3.3, Module::WEBSITE);
      $fo = $this->createSpecificFee('', 9.1, 9.2, 9.3, Module::OUTLET);
      
      $finder = new FeeFinder();
      $this->assertEquals($fc, $finder->find(Module::WEBSITE, $catA->id));
      $this->assertEquals($fo, $finder->find(Module::OUTLET, $catB->id));
      
  }
  
  function testNewRules(){
      $this->clearAll();
      
      $seller = $this->createUser('seller');
      
      $evt = $this->createEvent('Cancelling Traveling', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
      $this->setEventId($evt, 'aaa');
      $catA = $this->createCategory('ADULT', $evt->id, 100);
      $catB = $this->createCategory('KID', $evt->id, 150);
      
      //$fc = $this->createSpecificFee($catA->id, 'category', 1.1, 2.2, 3.3, Module::WEBSITE);
      Utils::clearLog();
      
      $finder = new FeeFinder();
      
      //let's create from most global to most specific. on each case we should find the correct fee
      $global = $this->currentGlobalFee();
      $this->assertEquals($global, $finder->find(Module::WEBSITE, $catA->id));
      //return;

      //this time we'll use full column definition to index each fee
      $fee = $this->createSpecificFee('', 1.1, 1.2, 1.3, Module::WEBSITE);//Utils::clearLog();
      $this->assertEquals($fee, $finder->find(Module::WEBSITE, $catA->id));
      
      //New, look for a user specific fee first
      $fee = $this->createSpecificFee('Seller specific', 11.1, 11.2, 11.3, null, $seller->id);Utils::clearLog();
      $this->assertEquals($fee, $finder->find(Module::WEBSITE, $catA->id));
      
      //global is still global
      $this->assertEquals($global, $this->currentGlobalFee());//return;
      
      $fee = $this->createSpecificFee('', 2.1, 2.2, 2.3, Module::WEBSITE, $seller->id);
      $this->assertEquals($fee, $finder->find(Module::WEBSITE, $catA->id));
      
      $fee = $this->createSpecificFee('', 3.1, 3.2, 3.3, Module::WEBSITE, $seller->id, $evt->id);
      $this->assertEquals($fee, $finder->find(Module::WEBSITE, $catA->id));
      
      $fee = $this->createSpecificFee('', 4.1, 4.2, 4.3, Module::WEBSITE, $seller->id, $evt->id, $catA->id );
      $this->assertEquals($fee, $finder->find(Module::WEBSITE, $catA->id));
      
      //$this->assertEquals($fc, $finder->find(Module::WEBSITE, $catA->id));
      //$this->assertEquals($fo, $finder->find(Module::OUTLET, $catB->id));
  }
 



  
 
}