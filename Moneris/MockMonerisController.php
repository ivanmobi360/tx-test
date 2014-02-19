<?php
/**
 * @author Ivan Rodriguez
 * This is the controller we're returned at after completing a payment on the Moneris side
 */
namespace Moneris;
use controller\Moneris as MonerisController;

use Utils;
class MockMonerisController  extends MonerisController {

    /**
     * For local tests only. This processes de posted data when we are redirected from Moneris, so the tickets are created. 
     * On production the same data is sent to our ipn listener asynchronously, so this step is not needed.
     */
    protected function handlePurchaseResponse(){
        if(!Utils::isLocal()) return; //Do not enable unless you're certain the same data won't arrive again on the ipn listener
        
        try{
            $payment_channel = new \model\PaymentChannel('m');
            $payment_channel->verify();
        
            $processor = new \model\IpnProcessor($payment_channel);
            $processor->process();
        }
        catch(\Exception $e){
            error_log($e);
        }
    }
	
}