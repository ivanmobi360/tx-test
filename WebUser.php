<?php
class WebUser{
    public $db, $username, $id
    , $date = false //date override
    ;

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

    function logout(){
        $_SESSION = array();
        \model\Usersmanager::clear();
        \tool\Cookie::clean();
        \tool\Session::clean();
    }


    protected function clearRequest(){
        tool\Request::clear();
    }

}