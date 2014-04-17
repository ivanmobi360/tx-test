<?php
namespace Moneris;

use tool\CurlHelper;

use controller\Checkout;

use tool\Request;

use Utils;

/**
 * These tests rely on an existing db with existing transactions, so no clearing other than our table of interest, 
 * moneris_report, which is populated with csv files uploads at the moment. Sample files at moneris-report-input-csv
 * 
 * Test database at tixpro-local-2014-04-14a.zip, but schema might be out of date
 * 
 * @author Ivan Rodriguez
 *
 */
class MonerisTest extends \DatabaseBaseTest{
  
    //protected $old_db;  
    protected $test_db_name = 'tixpro-local-2014-04-14a.sql'; //maybe get it from config?
  
    protected function switchDb($name){
        $this->db->disconnect();
        \Database::init(DB_HOSTNAME, $name, DB_USERNAME, DB_PASSWORD);
    }
  
  
    function testUpload(){
        $this->switchDb($this->test_db_name);  
        //echo "connected to db " . $this->db->getDbName();
        $this->db->Query("TRUNCATE TABLE moneris_report");
        $this->assertRows(0, 'moneris_report');
        //return;
        $basename =  "rbunxcgi(7)"; //"rbunxcgi(2)";
        $file = __DIR__ . '/moneris-report-input-csv/' . $basename;
        /*$_FILES = array(
                'userfile' => array(
                        'name' => $basename,
                        'type' => 'application/octet-stream',
                        'size' => filesize($file),
                        'tmp_name' => $file,
                        'error' => 0
                )
        );
        
        $_POST = ['upload'=>'Submit'];
        
        $conv = new \controller\Moneris_report();*/
        
        $conv = new \tool\MonerisCsvConversion();
        $res = $conv->csv_to_array($file, ",");
        
        //$this->assertRows(4, 'moneris_report');
        
        //sample row
        $row = $this->db->auto_array("SELECT * FROM moneris_report LIMIT 1");
        $this->assertEquals(.25*$row['moneris_amount'], $row['hold_amount'], '', .001);
        $this->assertEquals($row['moneris_amount'],  $row['hold_amount'] + $row['total_deposit'], '', .001);
        
        //also expect total_fees and total_tickets_price
        Utils::log( "res: " . print_r($res, true));
        
        //for now hardcoded
        $this->assertEquals(78.28-2*35, $row['total_fees'], '', .001);
        
        //expect event_id
        $this->assertEquals('9d1cf350', $row['event_id']);
        
    }
  
  /**
   * http://jira.mobination.net:8080/browse/TIX-474
   * The new columns are quite simple and should be very easy to add.
One will be abbreviated as "*TF*" for Total Fees, and should be the total of all the fees added over the category price of the ticket. 
For example, if a ticket is sold for $55 and the final price seen by Moneris is $57.75, 
the *TF* should be $2.75 
Also, if a transaction has many tickets of different prices, the *TF* will still be the fees added on top of the total of all the category prices. 
So if a ticket of $25 and a ticket of $50 were bought and the price seen on the Moneris report is $78.91, 
the *TF* should be $3.91

The other will be abbreviated as "*TTP*" and stands for Total Ticket(s) Price. 
It is the total of the category price(s). So as the example above, if we bought 2 tickets of $50, the *TTP* should show $100. 
If for example we bought a ticket of $25 and one of $50 in the same transaction, we should see a *TTP* of $75.
   */
    function test474(){
        //echo $this->db->getDbName();
        $this->clearAll();
        $seller = $this->createUser('seller');
        $foo = $this->createUser('foo');
        
        $evt = $this->createEvent('Simple Event', $seller->id, $this->createLocation()->id, $this->dateAt("+5 day"));
        $this->setEventId($evt, 'aaa');
        $this->setEventPaymentMethodId($evt, self::MONERIS);
        $this->setEventParams($evt, array('has_tax'=>0));
        $cat = $this->createCategory('Normal', $evt->id, 35.00, 100, 0, array('tax_inc'=>1));
        
        //buy
        $web = new \WebUser($this->db);
        $web->login($foo->username);
        $web->addToCart($cat->id, 2); Utils::clearLog();
        $web->payWithMoneris();
      
    }
  

  

 
}