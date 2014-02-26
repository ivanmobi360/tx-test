<?php
/**
 * @author Ivan Rodriguez
 */
namespace Moneris;
use controller\Moneris as MonerisController;

use Utils;
class MonerisTestTools {

    static function createXml($buyer_id, $txn_id, $total, $seller='seller' ){
      $xml = file_get_contents(__DIR__ . '/responses/post_response2.xml');
      
      //lets override the template response
      $data = json_decode(json_encode((array)simplexml_load_string($xml)),1);
      $data['rvarcustom'] = \model\Payment::createCustom(array(
              'tixpro_customerid' => $buyer_id
              , 'tixpro_txnid' => $txn_id //$this->db->get_one("SELECT txn_id FROM ticket_transaction LIMIT 1")
              , 'tixpro_merchantid' => $seller
              , 'currency' => 'CAD'
      ));
      $data['charge_total'] = $total;
      $new_xml = new \SimpleXMLElement("<?xml version=\"1.0\"?><response></response>");
      self::array_to_xml($data, $new_xml);
      $xml = $new_xml->asXML();
      return $xml;
  }
  
  // function defination to convert array to xml
  static function array_to_xml($student_info, &$xml_student_info) {
      foreach($student_info as $key => $value) {
          if(is_array($value)) {
              if(!is_numeric($key)){
                  $subnode = $xml_student_info->addChild("$key");
                  self::array_to_xml($value, $subnode);
              }
              else{
                  self::array_to_xml($value, $xml_student_info);
              }
          }
          else {
              $xml_student_info->addChild("$key","$value");
          }
      }
  }
	
}