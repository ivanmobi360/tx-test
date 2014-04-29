<?php
class WebUser{
    public $db, $username, $id
    , $date = false //date override
    ;
    
    //factory method
    static function loginAs($db, $username, $password='123456'){
        $w = new self($db);
        $w->login($username, $password);
        return $w;
    }

    function __construct($db){
        $this->db = $db;
    }

    function login($username, $password='123456'){
        \model\Usersmanager::clear();
        //session_unset();
        $resUser = \model\Usersmanager::login($username, $password);
        $resUser = \model\Usersmanager::exists($resUser['id']);
        \tool\Session::setUser($resUser);

        $this->username = $username;
        $this->id = $resUser['id'];

    }

    function addToCart($category_id, $quantity, $promocode=''){
        $data = array( 'page'=>'Event', 'method'=>'add-cart', 'category_id'=> $category_id, 'quantity'=> $quantity, 'promocode'=> '' );
        $p = new ajax\Event();
        $p->setData($data);
        $p->addCart();
        //$p->Process();

        if ($p->res && 'failed' == Utils::getArrayParam('result', $p->res) ){
            throw new Exception(__METHOD__ . "failure : " . $p->res['msg']  );
        }

        Utils::log( __METHOD__ . " session so far: " .   print_r($_SESSION, true));

        $this->clearRequest();

        if(!empty($promocode)){
            $cat = new \model\Categories($category_id);
            $this->applyPromoCode($cat->event_id, $promocode);
        }

    }

    //Each row on the cart has a promocode input
    function applyPromoCode($event_id, $promocode){
        $_POST = array( 'page'=>'Cart', 'method'=>'verify-code', 'event_id'=> $event_id, 'code'=>$promocode);
        $p = new ajax\Cart();
        $p->Process();

        Utils::log(print_r($_SESSION, true));

        $this->clearRequest();
    }


    /**
     * 2014-04-21 workflow needs review. Apparently simulates an old payment workflow, unusable for cash payments.
     */
    function placeOrder($gateway=false, $date=false){
        $gateway = $gateway? $gateway: 'paypal';
        $_POST = array( 'method'=>'cart-payment', 'page'=>'Cart', 'name_pay'=>$gateway);
        $p = new \ajax\Cart();
        $p->Process();
        $this->clearRequest();
        $res = $p->res;
        $txn_id = $res['txn_id'];
        //$txn_id = $this->db->get_one("SELECT txn_id FROM ticket_transaction ORDER BY id DESC LIMIT 1");
        if($date){
            $this->db->update('ticket_transaction', array('date_processed'=>$date), 'txn_id=?', $txn_id);
        }
        return $txn_id;
    }

    /**
     * 
     * @deprecated TODO Update tests calling this code on as needed basis.
     */
    function payByCash($txn_id){

        $data = array(
                'txn_id' => $txn_id,
                'type_pay' => \model\DeliveryMethod::PAY_BY_CASH //'paybycash'
        );

        $_POST = $data;

        //Now see if controller reacts properly
        $cnt = new \controller\Payment();
        $this->clearRequest();
    }
    
    /**
     * Call this directly. No need to call placeOrder first. No need of existing txn_id
     * Copied over from TC
     */
    function payByCashBtn(){
        //$_GET = array('page'=>'317c'); // parameter not tested on TX
    
        $data = array(
                'pay_cash' => 'Pay By Cash'
                , 'pay_paypal' => 'on' //  sent within form, apparently ignored       
   
        );
    
        //$data = $this->addReminders($data); //tbd, this works on TC, but not tested here at the moment. consider porting later
    
        $_POST = $data;
    
        $cnt = new \controller\Checkout();
        //Utils::log(__METHOD__ . " completed checkout");
        $this->clearRequest();
    
        return $cnt->txn_id;
    
    }

    function getCart(){
        $cart = new \tool\Cart();
        $cart->load();
        return $cart;
    }

    function posAddItem($category_id, $qty=1){

        $_POST = array( 'page'=>'Cart', 'method'=>'add-item', 'category_id'=> $category_id, 'quantity'=> $qty);
        $p = new ajax\Cart();
        $p->Process();

        Utils::log(print_r($_SESSION, true));

        $this->clearRequest();
    }

    function posPay(){
        $_POST = array( 'page'=>'Cart', 'method'=>'pos-pay');
        $p = new ajax\Cart();
        $p->Process();

        Utils::log(print_r($_SESSION, true));

        $this->clearRequest();
    }

    function posPayWithCC($params){
        //apparently it does a pos-currency first?
        $data = array( 'page'=>'Cart', 'method'=>'pos-currency' );
        $p = new ajax\Cart();
        $res = $p->posGetCurrency();
        $amount = $res['total'];
        $currency = $res['row'];


        //do the cc call
        $data = array_merge(array('amount'=>$amount, 'currency'=>$currency  ), $params);
        $ajax = new ajax\Ccpay($data);
        $ajax->Process();
    }
    
    /**
     * Shortcut that does both things:
     * 1. User places transaction on TX side. Jumps to Moneris
     * 2. User pays on the Moneris side. IPN is generated and processed. Transaction is completed.
     */
    function payWithMoneris(){
        $total = $this->getCart()->getTotal(); //persisted here because subsequent calls to clearRequest delete the cart total
        //Jump to Moneris Website
        $txn_id = $this->placeMonerisTransaction();
        
        //Mimic going to Moneris website and filling out cc info. IPN sent by Moneris is processed by us.
        $xml = \Moneris\MonerisTestTools::completeMonerisTransaction($this->id, $txn_id, $total);
        
        return $txn_id;
    }
    
    /**
     * Use this to test Moneris' payments steps. Usually you would do the payWithMoneris instead.
     */
    public function placeMonerisTransaction(){
        //strictly based in session state, so user must be logged in for this to work
        //post to check out to see what happens.
        $this->clearRequest();
    
        $_POST = array(
                'sms-aaa-to' => '618994576'
                ,'sms-aaa-date' => '2013-06-03'
                ,'sms-aaa-time' => '09:00'
                ,'ema-aaa-to' => 'Foo@gmail.com'
                ,'ema-aaa-date' => '2013-06-01'
                ,'ema-aaa-time' => '09:00'
                ,'x' => '77'
                ,'y' => '41'
                ,'pay_mhp' => 'on'
        );
    
        $cnt = new \controller\Checkout(); //used just to inspect output js in log
        return $this->db->get_one("SELECT txn_id FROM ticket_transaction ORDER BY id DESC LIMIT 1");
    }
    
    

    function logout(){
        $_SESSION = array();
        \model\Usersmanager::clear();
        \tool\Cookie::clean();
        \tool\Session::clean();
    }


    protected function clearRequest(){
        $_POST = $_GET = array();
        tool\Request::clear();
    }

}