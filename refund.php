<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/sbmpayment.php');

if (!$cookie->isLogged())
    Tools::redirect('authentication.php?back=order.php');
	
$sbmpayment = new sbmpayment();
$idSbmOrder = $_POST["id_sbmorder"];
$amount     = $_POST["orderAmount"];
echo $sbmpayment->refundPayment($context,$idSbmOrder,$amount);



?>