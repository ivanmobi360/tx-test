<?php
/**
 * These tests were used to prove the problems
 * with the static approach. It is not needed since they won't hear reason.
 * Declared abastract to skip
 *
 */
abstract class DatabaseInstanceTest extends DatabaseBaseTest{
  
  function xtestInstance(){
    
    //apparently I'm able to do this?
    $db1 = new \Database();
    $db1->init('localhost', 'mymobievents_test', 'root', '');
    
    $db2 = new \Database();
    $db2->init('localhost', 'mymobievents', 'root', '');
    
    $this->assertEquals('mymobievents', $db2->getDbName());
    $this->assertEquals('mymobievents_test', $db1->getDbName());  //obviously overrided
    
  }
  
  function xtestStatic(){
    
    //apparently I'm able to do this?
    $db1 = new \Database();
    $db1::init('localhost', 'mymobievents_test', 'root', '');
    
    $db2 = new \Database();
    $db2::init('localhost', 'mymobievents', 'root', '');
    
    $this->assertEquals('mymobievents', $db2::getDbName());
    $this->assertEquals('mymobievents_test', $db1::getDbName()); //obviously overrided
    
  }
  
  function xtestExtend(){
    
    $stage = new \StageDatabase();
    $stage::init('localhost', 'stage', 'root', '');
    
    $prod = new \Database();
    $prod::init('localhost', 'production', 'root', '');
    
    
    
    $this->assertEquals('production', $prod::getDbName());
    $this->assertEquals('stage', $stage::getDbName()); //False. The configuration of \StageDatabase got overriden by \Database.
                                                   // I can't have code with both classes operating, they'll override each other at class level.
                                                   // A call to \StageDatabase::execute("TRUNCATE TABLE users") would delete the data on the 'production' database
                                                   // This is not the correct use of inheritance anyway  
                                                   
    //static access?
    
  }
  
  function xtestStaticExtend(){
    
    \StageDatabase::init('localhost', 'stage', 'root', '');
    \Database::init('localhost', 'production', 'root', '');

    
    $this->assertEquals('production', \Database::getDbName());
    $this->assertEquals('stage', \StageDatabase::getDbName()); //failure
  }
  
}

// Extend to the rescue?
class StageDatabase extends \Database{
  //custom code here
  static protected $dbHost = null; // host de la base
	static protected $dbBase = null; // nom de la base
	static protected $dbUser = null; // login utilisateur
	static protected $dbUserPass = null; // mdp utilisateur
	static protected $link = null; // ressource de connection
	
	
	static protected $trans_depth;
}