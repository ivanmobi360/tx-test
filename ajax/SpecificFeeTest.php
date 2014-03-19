<?php
/**
 * @author Ivan Rodriguez
 * admin360
 */
namespace ajax;


use model\Module;

use model\FeeVO;

use tool\FeeFinder;

use tool\Request;

use Utils;

class SpecificFeeTest extends \DatabaseBaseTest {
  
    protected $finder;
    
    function fixture(){
        $seller = $this->createUser('seller');
        
        $evt = $this->createEvent('My new event', $seller->id, $this->createLocation('derp'), $this->dateAt('+7 day'));
        $this->setEventId($evt, 'aaa');
        $cat = $this->createCategory('Sala', $evt->id, 100.00);
        
        $this->createUser('seller2');
        $evt = $this->createEvent('Otro evento', 'seller2', $this->createLocation(), $this->dateAt('+7 day'));
        $this->setEventId($evt, 'bbb111bb');
        $catX = $this->createCategory('Lluvia', $evt->id, 100.00);
        
        $this->createUser('foo');
        
        $this->finder = new FeeFinder();
        return $cat;
    }
  
  function testSeller(){
      $this->clearAll();
      $this->fixture();
    
      // *************************************
      Utils::clearLog();
      Request::clear();
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'save-fee',
          'level' => 'promoter',
          'id' => 'seller',
          'moduleid' => '1',
          'type' => 'tf',
          'name' => 'asd',
          'fixed' => '3.1',
          'percentage' => '3.2',
          'max' => '3.3',
              'module_id' => '1',
              'user_id' => 'seller',
              'event_id' => '0',
              'category_id' => '0',
        );
      $ajax = new SpecificFee();
      $ajax->Process();
  
      //$this->assertRows(1, 'specific_fee');
      $ff = $this->finder->findExact(1, 'seller' );
      $this->assertEquals(new FeeVO(3.1,3.2,3.3), $ff->getVO()); //it should have created a merchant level fee
      //return;
      
      //load list
      Utils::clearLog();
      Request::clear();
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'load-list-by-module',
          'level' => 'promoter',
          'id' => 'seller',
        );
      
      $ajax = new SpecificFee();
      $ajax->Process();
      
  
  }
  
  function testCategory(){
      $this->clearAll();
      $cat = $this->fixture();
      
      Utils::clearLog();
      Request::clear();
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'save-fee',
          'level' => 'category',
          'id' => $cat->id, // '330',
          'moduleid' => '1',
          'type' => 'tf',
          'name' => 'some name',
          'fixed' => '9.1',
          'percentage' => '9.2',
          'max' => '9.3',
              
          //have to send this
          'module_id' => '1',
          'category_id' => $cat->id,//'330',
          'user_id' => 'seller',
          'event_id' => 'aaa'
                  
              
        );
      $ajax = new SpecificFee();
      $ajax->Process();
      
      $this->assertEquals(new FeeVO(9.1, 9.2, 9.3), $this->finder->find(1, $cat->id)->getVO());
      $this->assertEquals(new FeeVO(9.1, 9.2, 9.3), $this->finder->findExact(1, 'seller', 'aaa', $cat->id)->getVO());
      
  }
  
  //this is the module page one
  function testLoadList(){
      $this->clearAll();
      $this->fixture();
      $this->insertModuleLevelFee();
      
      Utils::clearLog();
      Request::clear();
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'load-list',
          'level' => 'module',
          'id' => '1',
          'moduleid' => '1',
        );
      $ajax = new SpecificFee();
      $ajax->Process();
  }
  
  protected function insertModuleLevelFee(){
      Utils::clearLog();
      Request::clear();
      $_POST = array (
              'action' => 'specific-fee',
              'option' => 'save-fee',
              'level' => 'module',
              'id' => '1',
              'moduleid' => '1',
              'type' => 'tf',
              'name' => 'zeeed',
              'fixed' => '6.1',
              'percentage' => '6.2',
              'max' => '6.3',
      );
      $ajax = new SpecificFee();
      $ajax->Process();
  }

  function testDefault(){
      $this->clearAll();
      $this->fixture();
      $global_fee = $this->currentGlobalFee();
      
      //***************** Module level *******************************
      //default can be set at many levels. find what is causing to clear the global fee
      $this->createSpecificFee('m1', 1, 1, 1, Module::WEBSITE);
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      $f2 = $this->createSpecificFee('m2', 2, 2, 2, Module::WEBSITE);
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      $this->createSpecificFee('m3', 3, 3, 3, Module::WEBSITE);
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      
      
      //let's switch the default one
      $this->assertFalse(\model\Fee::load($f2->id)->isDefault());Utils::clearLog();
      $this->makeModuleDefault($f2->id);
      $this->assertTrue(\model\Fee::load($f2->id)->isDefault());
      
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      //return;
      
      //***************** Seller level *******************************
      //default can be set at many levels. find what is causing to clear the global fee
      $this->createSpecificFee('s1', 1, 1, 1, Module::WEBSITE, 'seller');
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      $f2 = $this->createSpecificFee('s2', 2, 2, 2, Module::WEBSITE, 'seller');
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      $this->createSpecificFee('s3', 3, 3, 3, Module::WEBSITE, 'seller');
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      //let's switch the default one
      $this->assertFalse(\model\Fee::load($f2->id)->isDefault());
      $this->makeSellerDefault($f2->id);
      $this->assertTrue(\model\Fee::load($f2->id)->isDefault());
      
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      
      //***************** Event level *******************************
      //default can be set at many levels. find what is causing to clear the global fee
      $this->createSpecificFee('e1', 1, 1, 1, Module::WEBSITE, 'seller', 'aaa');
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      $f2 = $this->createSpecificFee('e2', 2, 2, 2, Module::WEBSITE, 'seller', 'aaa');
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      $this->createSpecificFee('e3', 3, 3, 3, Module::WEBSITE, 'seller', 'aaa');
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      //let's switch the default one
      $this->assertFalse(\model\Fee::load($f2->id)->isDefault());
      $this->makeEventDefault($f2->id);
      $this->assertTrue(\model\Fee::load($f2->id)->isDefault());
      
      $this->assertEquals($global_fee, $this->currentGlobalFee());
      
      
  }
  
  protected function makeModuleDefault($id){
      Request::clear();
      $_POST = array (
              'action' => 'specific-fee',
              'option' => 'default-fee',
              'level' => 'module',
              'id' => '1',
              'moduleid' => '1',
              'feeid' => $id,
              'type' => 'tf',
              'module_id' => '1'
      );
      $ajax = new SpecificFee();
      $ajax->Process();
  }
  
  protected function makeSellerDefault($id){
      Request::clear();
      $_POST = array (
              'action' => 'specific-fee',
              'option' => 'default-fee',
              'level' => 'promoter',
              'id' => 'seller',
              'moduleid' => '1',
              'feeid' => $id,
              'type' => 'tf',
              'module_id' => '1',
              'user_id' => 'seller',
              'event_id' => '0',
              'category_id' => '0',
            );
      $ajax = new SpecificFee();
      $ajax->Process();
  }
  
  protected function makeEventDefault($id){
      Request::clear();
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'default-fee',
          'level' => 'event',
          'id' => 'aaa',
          'moduleid' => '1',
          'feeid' => $id,
          'type' => 'tf',
          'module_id' => '1',
          'user_id' => 'seller',
          'event_id' => 'aaa',
          'category_id' => '0',
        );
      $ajax = new SpecificFee();
      $ajax->Process();
  }
  
  protected function makeSuperGlobalDefault($id){
      $this->clearRequest();
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'default-fee',
          'level' => 'super',
          'id' => '0',
          'moduleid' => '0',
          'feeid' => $id, //'10',
          'type' => 'tf',
        );
      $ajax = new SpecificFee();
      $ajax->Process();
  }
  
  
  //If the fee is in use, it should not be editable
  function testEditFee(){
      $this->clearAll();
      $cat = $this->fixture();
      
      Utils::clearLog();
      
      $fee = $this->createSpecificFee('no change', 1.1, 2.2, 3.3, Module::WEBSITE);
      $this->editFee($fee->id, 1.11);
      $this->assertEquals(1.11, \model\Fee::load($fee->id)->getVO()->fixed);
      
      
      //use the fee
      $this->buyTickets('foo', $cat->id);
      
      //expect transaction with used fee
      $this->assertEquals(1, $this->db->get_one("SELECT count(id) FROM ticket_transaction WHERE fee_id=? AND category_id=?", array($fee->id, $cat->id)));
      
      
      //vefify it can't be changed
      $res = $this->editFee($fee->id, 3.33);
      $this->assertFalse($res['success']);
      $this->assertEquals(1.11, \model\Fee::load($fee->id)->getVO()->fixed); //no change, because the fee is in use
      
  }
  
  protected function editFee($id, $newval){
      Request::clear();
      $_POST = array (
              'action' => 'specific-fee',
              'option' => 'update-fee',
              'feeid' => $id,
              'name' => 'no change',
              'fixed' => $newval,
              'percentage' => '2.2',
              'max' => '3.30',
              'module_id' => '1',
      );
      $ajax = new SpecificFee();
      $ajax->Process();
      return $ajax->res;
  }
  
  function testDeleteFee(){
      $this->clearAll();
      $cat = $this->fixture();
      
      $fee = $this->createSpecificFee('no change', 1.1, 2.2, 3.3, Module::WEBSITE);
      $this->deleteFee($fee->id);
      $this->assertFalse(\model\Fee::load($fee->id)); //deleted
      
      $fee = $this->createSpecificFee('no change', 1.1, 2.2, 3.3, Module::WEBSITE); //recreate
      
      Utils::log(__METHOD__ . " fee created:" . $fee->id);
      
      //use the fee
      $this->buyTickets('foo', $cat->id);
      
      //expect transaction with used fee
      $this->assertEquals(1, $this->db->get_one("SELECT count(id) FROM ticket_transaction WHERE fee_id=? AND category_id=?"
                                               , array($fee->id, $cat->id)));
      
      
      //vefify it can't be changed
      $res = $this->deleteFee($fee->id);
      $this->assertFalse($res['success']);
      $this->assertNotNull(\model\Fee::load($fee->id)); //sound and safe
  }
  
  protected function deleteFee($id){
      Request::clear();
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'delete-fee',
          'feeid' => $id,
          'module_id' => '1',
        );
      $ajax = new SpecificFee();
      $ajax->Process();
      return $ajax->res;
  }
  
  function testUserFee(){
      $this->clearAll();
      $cat = $this->fixture();
      Utils::clearLog();     
      //$fee = $this->createSpecificFee('no change', 1.1, 2.2, 3.3, Module::WEBSITE);
  }
  
  /**
   * TX Bug: "when i set urban as default super global fee
     mathson: the promoter fee is deactivated" - Mathias
   * 
   * We must determine why a Promoter Specific fee is showing in the "By Module" tab
   * 
   */
  function testPromoterBug(){
      $this->clearAll();
      $this->fixture();
      
      Utils::clearLog();
      
      $new_fee = $this->createSpecificFee('BUG001', 14, 0, 14, null, 'seller');
      
      
      
      $_POST = array (
          'action' => 'specific-fee',
          'option' => 'load-list',
          'level' => 'super',
          'id' => '0',
          'moduleid' => '0',
          'module_id' => '0',
          'user_id' => '0',
          'event_id' => '0',
          'category_id' => '0',
        );
      
      $ajax = new SpecificFee();
      $ajax->Process();
      $res = $ajax->res;
      
      //ensure promoter-specific fee is not listed
      foreach($res['tcfees'] as $fee){
          if($fee['name'] == 'BUG001')
              $this->fail("Promoter-specific fee should not be listed");
      }
      
      
      //Bug 002: Now let's set some super global fee as default
      //Our created promoter-specific fee should still be default 1
      Utils::clearLog();
      $this->assertEquals(1, $this->db->get_one("SELECT is_default FROM fee WHERE id=?", $new_fee->id));
      $this->makeSuperGlobalDefault(10);
      $this->assertEquals(1, $this->db->get_one("SELECT is_default FROM fee WHERE id=?", $new_fee->id));
      
      /*$greg = $this->createUser('50233e09', 'Greg Test');
      $evt = $this->createEvent('Golf stuff', $greg->id, $this->createLocation(), $this->dateAt('+7 day'));
      $this->setEventId($evt, 'xxyyzzaa');
      $cat = $this->createCategory('VOP', $evt->id, 100.00);*/
  }
  
}
