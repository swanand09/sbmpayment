<?php
	require_once ('PayPlugin.php');
	$Pay = new PayPlugin();

	$ordernumber = rand(1, 1226);

	$request = $_POST;
	$currency = $request['currency'];
	$amount = $request['amount'];
	$PAN = $request['pan'];
	$CVC = $request['cvc'];
	$EXPIRY = $request['year'].$request['month'];
	$year = $request['year'];
	$Month = $request['month'];
	$TEXT = $request['cardholder'];
	$cavv = $request['cavv'];
	$eci = $request['eci'];
	$xid = $request['xid'];

	$url_arr = pathinfo("https://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
	$returnurl = $url_arr["dirname"]."/paystatus.php"; // Your URL to going back from payment gate

	try {
	$response_reg = $Pay->registerRequest(array("orderNumber" => $ordernumber, "amount" => $amount, "currency" => $currency, "returnUrl" => $returnurl));
	$payment_arr = array("orderId" => $response_reg["orderId"], 'pan' => $PAN, 'cvc' => $CVC, 'expiration' => $EXPIRY, "cardholder" => $TEXT, "language" => "ru");
	if (empty($cavv) == false) $payment_arr = $payment_arr + array("cavv" => $cavv, "eci" => $eci, "xid" => $xid);
        $response_pay = $Pay->AuthorizePaymentRequest($payment_arr);
	}
	catch (Exception $e) {
		echo $e->getMessage();
		die();
	}
	
	foreach ($response_pay as $key => $value) {
		echo $key." ".$value."<br>";
	}
	
	die();
?>