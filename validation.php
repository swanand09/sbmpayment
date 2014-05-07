<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/sbmpayment.php');
			

/* Gather submitted payment card details */
$cardBrand          = $_POST["cardBrand"];
$cardholderName     = $_POST['cardholderName'];
$cardNumber         = $_POST['cardNumber'];
$cvc                = $_POST["cvc"];
$sbmOrderId         = $_POST["sbmOrderId"];
$cardExpiration     = $_POST["cardExpiration"];
$sbmpayment = new sbmpayment();
$total = $context->cart->getOrderTotal(true, Cart::BOTH);

$sbmpayment->writePaymentcarddetails($sbmOrderId, $cardholderName, $cvc ,$cardNumber,$cardExpiration);
if(empty($sbmpayment->transactionId)){    
  echo Tools::jsonEncode(array("error"=>$sbmpayment->sbmOrderMsgSta)); exit;  
}
$transactionArr =  array(
                          "transaction_id"  =>$sbmpayment->transactionId,
                          "card_number"     =>$cardNumber,
                          "card_brand"      =>$cardBrand,  
                          "card_expiration" =>$cardExpiration,
                          "card_holder"     =>$cardholderName
                         );
switch($sbmpayment->sbmOrderStatus){
    case 0:
        $sbmpayment->validateOrder($cart->id,  _PS_OS_ERROR_, $total, $sbmpayment->displayName, NULL, $transactionArr, $currency->id);
    break;
    case 1:
        $sbmpayment->validateOrder($cart->id,  _PS_OS_PREPARATION_, $total, $sbmpayment->displayName, NULL, $transactionArr, $currency->id);
    break;
    case 2:
        $sbmpayment->validateOrder($cart->id,  _PS_OS_PAYMENT_, $total, $sbmpayment->displayName, NULL, $transactionArr, $currency->id);        
    break;
    case 3:
        $sbmpayment->validateOrder($cart->id,  _PS_OS_CANCELED_, $total, $sbmpayment->displayName, NULL, $transactionArr, $currency->id);
    break;
    case 4:
        $sbmpayment->validateOrder($cart->id,  _PS_OS_REFUND_, $total, $sbmpayment->displayName, NULL, $transactionArr, $currency->id);
    break;
    case 6:
        $sbmpayment->validateOrder($cart->id,  _PS_OS_ERROR_, $total, $sbmpayment->displayName, NULL, $transactionArr, $currency->id);
    break;
}
$sbmpayment->updateOrderPayment($transactionArr);
$order = new Order($sbmpayment->currentOrder);

//Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$cart->id.'&id_module='.$sbmpayment->id.'&id_order='.$sbmpayment->currentOrder.'&key='.$order->secure_key);
echo Tools::jsonEncode(array(
                                "error"=> "none",
                                "redirectUrl"=>__PS_BASE_URI__.'order-confirmation.php?id_cart='.$cart->id.'&id_module='.$sbmpayment->id.'&id_order='.$sbmpayment->currentOrder.'&key='.$order->secure_key
                              )
                       );exit;

?>