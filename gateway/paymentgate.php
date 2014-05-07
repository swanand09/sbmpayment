<?php
require_once ('PayPlugin.php');
$Pay = new PayPlugin();

$request = $_POST;
$currency = $request['currency'];
$amount = $request['amount'];

$url_arr = pathinfo("https://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
$returnurl = $url_arr["dirname"]."/paystatus.php"; // Your URL to going back from payment gate

$ordernumber = rand(1, 1226);
try {
    $response_reg = $Pay->registerRequest(array("orderNumber" => $ordernumber, "amount" => $amount, "currency" => $currency, "returnUrl" => $returnurl));
}
catch (Exception $e) {
    echo $e->getMessage();
    die();
}

header ('Location: '.$response_reg["formUrl"]);
?>