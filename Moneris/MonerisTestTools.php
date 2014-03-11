<?php
/**
 * @author Ivan Rodriguez
 */
namespace Moneris;
use controller\Moneris as MonerisController;

use Utils;
class MonerisTestTools {

    static function createXml($buyer_id, $txn_id, $total, $seller_id='seller' ){
      $xml = file_get_contents(__DIR__ . '/responses/post_response2.xml');
      
      //lets override the template response
      $data = \tool\Xml::xmlToArray($xml);
      $data['rvarcustom'] = \model\Payment::createCustom(array(
              'tixpro_customerid' => $buyer_id
              , 'tixpro_txnid' => $txn_id
              , 'tixpro_merchantid' => $seller_id
              , 'currency' => 'CAD'
      ));
      $data['response_order_id'] = $txn_id;
      $data['charge_total'] = $total;
      $new_xml = new \SimpleXMLElement("<?xml version=\"1.0\"?><response></response>");
      self::array_to_xml($data, $new_xml);
      $xml_string = $new_xml->asXML();
      return $xml_string;
  }
  
  static function createCancelXml($buyer_id, $txn_id, $seller_id='seller' ){
      $xml = file_get_contents(__DIR__ . '/responses/cancel_response.xml');
  
      //lets override the template response
      $data = \tool\Xml::xmlToArray($xml);
      $data['rvarcustom'] = \model\Payment::createCustom(array(
              'tixpro_customerid' => $buyer_id
              , 'tixpro_txnid' => $txn_id
              , 'tixpro_merchantid' => $seller_id
              , 'currency' => 'CAD'
      ));
      $data['response_order_id'] = $txn_id;
      $data['charge_total'] = '';
      $new_xml = new \SimpleXMLElement("<?xml version=\"1.0\"?><response></response>");
      self::array_to_xml($data, $new_xml);
      $xml_string = $new_xml->asXML();
      return $xml_string;
  }
  
  static function completeMonerisTransaction($buyer_id, $txn_id, $total, $seller_id='seller'){
      $xml = self::createXml($buyer_id, $txn_id, $total, $seller_id);
      self::processIpnMessage($xml);
      return $xml;
  }
  
  static function processIpnMessage($xml){
      \tool\Request::clear(); $_GET = $_POST = array();
      $_GET['pt'] = 'm';
      $_POST['xml_response'] = $xml;
      $cnt = new \controller\Ipnlistener();
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