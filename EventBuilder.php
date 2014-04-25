<?php
/**
 * Event Builder is an abstraction of the creation of the post data required
 * to create an event
 * @author Ivan Rodriguez
*/
class EventBuilder
{
    /**
     * @var \DatabaseBaseTest
     */
    protected $sys;
    
    /**
     * @var \MockUser
     */
    protected $user;
    
    protected $cats, $params, $cat_nb=0, $new_id=false;
    
    static function createInstance($sys, $user){
        return new static($sys, $user);
    }
    
    function __construct($sys, $user){
        $this->sys = $sys;
        $this->user = $user;
        $this->cats = [];
        $this->params = [];
    }
    
    function id($id){
        $this->new_id = $id;
        return $this;
    }
    
    
    function info($name, $location_id, $date_from, $time_from='', $date_to='', $time_to=''){
        $this->params = array_merge($this->params, [
                  'e_name' => $name
                , 'e_date_from' => $date_from
                , 'e_time_from' => $time_from
                , 'e_date_to' => $date_to
                , 'e_time_to' => $time_to
                , 'l_id' => $location_id //if 0, force creation
                ]);
        return $this;
    }
    
    function addCategory($catBuilder, &$holder=null){
        $this->cats[++$this->cat_nb] = ['ref' => &$holder, 'builder' => $catBuilder];
        return $this;
    }
    
    function param($name, $value){
        $this->params[$name]=  $value;
        return $this;
    }
    
    function create(){
        
        
        $web = new \WebUser($this->sys->db);
        $web->login($this->user->username);
        
        
        $this->sys->clearRequest();
        
        $_POST = $this->generatePost();
        
        $cnt = new \controller\Newevent();
        
        $id = $this->sys->getLastEventId();
        $evt = new \model\Events($id);
        
        //populate out cat parameters
        foreach($this->cats as $nb=>$catDef){
            $catDef['ref'] = $cnt->categories[$nb]; //usually we don't care any new event_id change later on
        }
        
        if($this->new_id){
            $this->sys->setEventId($evt, $this->new_id);
        }
        
        return $evt;
    }
    
    function generatePost(){
        $cats = $this->getCatData();

        $data = array_merge($this->baseData(), $this->params, $cats);
        
        return $data;
    }
    
    function getCatData(){
        $res = [];
        foreach($this->cats as $n => $catEntry){
            $res['cat_all'][] = $n;
            $res = array_merge($res, $catEntry['builder']->getData($n));
        }
        return $res;
    }
    
    
    
    protected function baseData(){
        return array (
  'MAX_FILE_SIZE' => '3000000',
  'is_logged_in' => '1',
  'e_name' => 'My Dinner Time',
  'e_capacity' => '25',
  'e_date_from' => $this->sys->dateAt("+5 day"),
  'e_time_from' => '',
  'e_date_to' => '',
  'e_time_to' => '',
  'e_description' => '<p>test</p>',
  'e_short_description' => '',
  'reminder_email' => '',
  'sms' => 
      array (
        'content' => '',
      ),
  'c_id' => '2',
  'l_latitude' => '52.9399159',
  'l_longitude' => '-73.5491361',
  'l_id' => '2',
  'dialog_video_title' => '',
  'dialog_video_content' => '',
  'id_ticket_template' => '6',
  'email_description' => 'on',
  'e_currency_id' => '1',
  'payment_method' => '7',
  'paypal_account' => '',
  'no_tax' => 'on',
  'tax_ref_hst' => '',
  'tax_ref_pst' => '',
  'tax_other_name' => '',
  'tax_other_percentage' => '',
  'tax_ref_other' => '',
  'ticket_type' => 'table',
    /*
  'cat_all' => 
  array (
    0 => '2',
    1 => '1',
    2 => '0',
  ),
                
  //table as seats              
  'cat_2_type' => 'table',
  'cat_2_name' => 'As Seats Table',
  'cat_2_description' => 'As Seats Table Description',
  'cat_2_capa' => '5',
  'cat_2_over' => '0',
  'cat_2_tcapa' => '10',
  'cat_2_price' => '2500.00',
  'cat_2_taxIsInc' => 'on',
  'cat_2_feeIsInc' => 'on',
  'cat_2_ccFeeIsInc' => 'on',
  'cat_2_single_ticket' => 'true',
  'cat_2_ticket_price' => '250.00',
  'cat_2_seat_name' => 'some seat',
  'cat_2_seat_desc' => 'some seat desc',
                
  //table category              
  'cat_1_type' => 'table',
  'cat_1_name' => 'Table Only',
  'cat_1_description' => 'A Table Only Category',
  'cat_1_capa' => '3',
  'cat_1_over' => '0',
  'cat_1_tcapa' => '10',
  'cat_1_price' => '100.00',
  'cat_1_taxIsInc' => 'on',
  'cat_1_feeIsInc' => 'on',
  'cat_1_ccFeeIsInc' => 'on',
  'cat_1_ticket_price' => '0.00',
  'cat_1_seat_name' => '',
  'cat_1_seat_desc' => '',
                
  //open category              
  'cat_0_type' => 'open',
  'cat_0_name' => 'Normal',
  'cat_0_description' => 'A description',
  'cat_0_multiplier' => '1',
  'cat_0_capa' => '33',
  'cat_0_over' => '0',
  'cat_0_price' => '100.00',
  'cat_0_taxIsInc' => '1',
  'cat_0_feeIsInc' => '1',
  'cat_0_ccFeeIsInc' => '1',
  */              
  'create' => 'do',
  'has_ccfee' => '1', 
);
    }
    
}

class CategoryBuilder{
    protected $params, $name, $price;
    static function newInstance($name, $price){
        return new static($name, $price);
    }
    
    function __construct($name, $price){
        $this->name = $name;
        $this->price = $price;
        $this->params = [];
    }
    
    
    function description($value){
        return $this->param('description', $value);
    }
    
    
    function capacity($value){
        return $this->param('capa', $value);
    }
    
    function multiplier($value){
        return $this->param('multiplier', $value);
    }
    
    function overbooking($value){
        return $this->param('over', $value);
    }
    
    function tax_inc($value){
        return $this->param('taxIsInc', $value);
    }
    
    function fee_inc($value){
        return $this->param('feeIsInc', $value);
    }
    
    function ccfee_inc($value){
        return $this->param('ccFeeIsInc', $value);
    }
    
    function addParams($params){
        array_merge($this->params, $params);
        return $this;
    }
    
    protected function base(){
        /*
        return array(
                'type' => 'open',
                'name' => 'Normal Category',
                'description' => 'A description',
                'sms' => '1',
                'multiplier' => '1',
                'capa' => '99',
                'over' => '0',
                'price' => '100.00',
                'copy_to_categ' => '',
                'copy_from_categ' => '-1',
                );
        */
        return array(
                'type' => 'open',
                'name' => 'Normal',
                'description' => 'A description',
                'multiplier' => '1',
                'capa' => '99',
                'over' => '0',
                'price' => '100.00',
                'taxIsInc' => '0',// '1',
                'feeIsInc' => '0', // '1',
                'ccFeeIsInc' => '0',// '1',
                );
        
    }
    function param($name, $value){
        $this->params[$name] =  $value;
        return $this;
    }
    
    function getData($n){
        $params = array_merge($this->base(), ['name'=>$this->name, 'price'=>$this->price],  $this->params);
        $res = [];
        $pre = 'cat_' . $n . '_';
        foreach($params as $key=>$value){
            /*if (in_array($key, ['copy_to_categ', 'copy_from_categ'])){
                $res[$key . '_' . $n ] = $value;
                continue;
            }*/
            $res[$pre . $key] = $value;
        }
        return $res;
    }
}

class TableCategoryBuilder extends CategoryBuilder{
    
    /** Alias of |capacity| */
    function nbTables($value){
        return $this->capacity($value);
    }
    
    /** Actually triggers the creation of a hidden category row to hold this capacity */
    function seatsPerTable($value){
        return $this->param('tcapa', $value);
    }
    
    /** "User can buy a single seat in a table" checkbox */
    function asSeats($value){
        if($value){
            return $this->param('single_ticket', 'true');
        }
        return $this;
    }
    
    /** aka 'ticket_price'. The price of each individual seat
     * Apparently it overrides any full table price setting
     */
    function seatPrice($value){
        return $this->param('ticket_price', $value);
    }
    
    function seatName($value){
        return $this->param('seat_name', $value);
    }
    
    function seatDesc($value){
        return $this->param('seat_desc', $value);
    }
    
    function linkPrices($value){
        return $this->param('link_prices', $value);
    }
    
    function getData($n){
        $this->param('type', 'table');
        $data = parent::getData($n);
        
        //Utils::log(__METHOD__ . " link_prices: " . $this->params['link_prices']);
        $link_prices = 'cat_' . $n .'_link_prices';
        if (empty($data[$link_prices])){
            //Utils::log(__METHOD__ . " clearing link_prices");
            unset($data[$link_prices]);
        }
        
        //Utils::log(__METHOD__ . var_export($data, true));
        
        return $data;
        
    }
    
    protected function base(){
        return array_merge(parent::base(), array(
                'ticket_price' => '0.00',
                'seat_name' => '',
                'seat_desc' => '',
                'link_prices' => '1',
                ));
    }
    
    
}
