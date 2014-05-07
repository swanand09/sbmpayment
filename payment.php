<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/sbmpayment.php');

if (!$cookie->isLogged())
    Tools::redirect('authentication.php?back=order.php');
	
$sbmpayment = new sbmpayment();

echo $sbmpayment->execPayment($context);

include_once(dirname(__FILE__).'/../../footer.php');

?>