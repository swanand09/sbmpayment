<?php

	require_once ('PayPlugin.php');
try{
        $Pay = new PayPlugin();

	$request = $_GET;
	$orderId = $request['orderId'];
	
	$status = $Pay->StatusRequest(array("orderId" => $orderId));
	
	foreach ($status as $key => $value) {
		echo $key." ".$value."<br>";
	}
}catch (Exception $e) {
		echo $e->getMessage();
		die();
	}
	die();
?>
