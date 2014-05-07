<?php

class ConfigurationException extends Exception { }
class BadHttpResponseCodeException extends Exception { }
class SslException extends Exception { }
class UnexpectedResponseException extends Exception { }
class RequestProcessingException extends Exception { }
class HttpsCommunicationException extends Exception { }

class PayPlugin
{
	public $config;
	private $logger;
	public $log_option;
	
	public function __construct($config_file = 'config.properties')
	{
		
		$this->config = $this->parse($config_file) + $this->parse(dirname(__FILE__).'/additional.ini');
		
		// Code block below is aimed to support configuration file
		// *******************************************************
		if ($this->config == false) {
			if (file_exists($config_file)) {				
				throw new ConfigurationException($config_file." was found. But an exception occurred while reading properties from it.");				
			}	else {
				throw new ConfigurationException("Unable to find configuration file ".$config_file);
			}
		}	
		else {
			if (empty($this->config["user.login"])) throw new ConfigurationException("Property user.login in ".$config_file." is empty. It must not be empty.");
			
			if ((empty($this->config["proxy.port"])) XOR (empty($this->config["proxy.host"]))) {			
				throw new ConfigurationException("If you want to specify proxy you must setup both proxy.host and proxy.port. You can specify one of them without the other one. (see ".$config_file.")");	
			}	else {
				if ((empty($this->config["proxy.port"])) == false) {					
					if (is_numeric($this->config["proxy.port"]) == false) throw new ConfigurationException("Unable to parse proxy.port=".$this->config["proxy.port"].". It must be an integer but it's not.");
				}
			}
			
			if (filter_var($this->config["ipay.api.url"], FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) == false) throw new ConfigurationException("Property ipay.api.url in ".$config_file." is not a valid url");
		}
		// **********************************************
		
		
		if ($this->config["logging"]) {
			$this->log_option = TRUE; 
		}	
		else 
		{	
			$this->log_option = FALSE;
		}
		
		// Logging
		require_once 'Log/Log.php';
		$this->logger = Log::singleton("file", $this->config["log.file"]);
	}
	
	public function Logging ($message)
	{
		if ($this->log_option) $this->logger->log($message, PEAR_LOG_INFO);
	}
	
	protected function ArrayToPost ($params)
	{	
		$first = true;
        $url = "";
		foreach($params as $key => $value) {
			if(!$first) $url .= '&amp';
			else $first = false;
			$url .= ''.urlencode($key).'='.urlencode($value);
		}
		return $url;
	}
	
	protected function ChangeKey ($array, $key, $newkey)
	{	
		$array[$newkey] = $array[$key];
		unset($array[$key]);
		return $array;
	}
	
	public function DoPost ($params, $do)
	{
		$this->Logging("Connecting to ".$this->config["ipay.api.url"].$do);
		
		require_once dirname(__FILE__).'/HTTP/Request2.php';		
		$request = new HTTP_Request2($this->config["ipay.api.url"].$do, HTTP_Request2::METHOD_POST);
		// Checking SSL is switched off
		$request->setConfig(array(
			'ssl_verify_peer'   => FALSE,
			'ssl_verify_host'   => FALSE
		));
		$request_keys = array("userName" => $this->config["user.login"], "password" => $this->config["user.password"]);
		$request_full = $request_keys + $params;
		
		$request->addPostParameter($request_full);
		
		$this->Logging("Sending request: ".$this->ArrayToPost($request_full));
		try {
			$response = $request->send();
                        if (200 != $response->getStatus()) {
				throw new BadHttpResponseCodeException("Unexpected HTTP status: " . $response->getStatus() . " " . $response->getReasonPhrase());
			}			
		}	catch (HTTP_Request2_Exception $e) {
			throw new HttpsCommunicationException($e->getMessage());
		}
		
		$this->Logging("Getting response: ".$response->getBody());
		$response_array = json_decode($response->getBody(), true);
		
		if (empty($response_array)) throw new UnexpectedResponseException("Plugin got the response from PaymentGate but can't understand what that response means");
		if (empty($response_array["errorCode"]) == false) throw new RequestProcessingException("Error code: ".$response_array["errorCode"]. " Message: ".$response_array["errorMessage"]);
		
		return $response_array;
		
	}

    function parse ($filename){
        $file = file( $filename );
        $result = array();
        if ( count( $file ) == 0 ) { return $result; }
        foreach( $file as $line ){
            $p = explode("=", $line);
            if (sizeof($p) <2) continue;
            $result[trim($p[0])] = trim($p[1]);
        }
        return $result;
    }

	//Connection parameters are passed as key-value array
	//Key of array	Type	Required	Description	
	//orderNumber 	AN..32 	yes 		Order identification number in the internet-store system. Unique 
	// 									for each store within the system
	//amount 		N..20 	yes 		Amount of the payment in copecks (or cents)
	//currency 	N3 			no 			Currency code in ISO 4217
	// 									If not specified is assumed to be 810 (Russian rubles)
	//returnUrl 	AN..512 yes 		URL where user would be redirected
	//description 	AN..1024  no 		Description of the order in free form
	//language  	A2		no 			Language in encoding ISO 639-1. If not specified is assumed to be 
	// 									used the language which specified in the settings of the 
	//									store as default language
	//jsonParams 	AN..1024 no 		Additional information fields for further storage as
	//									{"param":value,"param2":"value 2"}. These fields are used for passing payments 
	//									identificators which supposed to be written in bank�s accounting.
	//
	//Field OrderStatus can take the following values:
	//Name 		Description
	//0 Order is registered, but not paid
	//1 Order amount is pre-authorized
	//2 Order amount is authorized
	//3 Authorization cancelled
	//4 Transaction operation was refunded
	//5 Authorization was initiated through the ACS of issuing bank
	//6 Authorization rejected
	//********************************************************************************************************************
	//Method returns result as an array
	//Key of array     Type        Required    Description
	//
	//orderId           AN..64		No    	 	Order number in the payment system. Unique within 
	//											the  system.  Omitted if error was occurred while registration of the order. Error 
	//											details specified in ErrorCode
	//formUrl           AN..512		No          URL of the payment system where browser 
	//											would be redirected. Omitted if error was occurred while registration of the order. 
	//											Error details specified in ErrorCode
	//
	//errorCode         N3 	        No          Error code. Omitted if were no errors. 
	//
	//errorMessage     AN..512   	No.         Error description in language which was 
	//											specified in parameter "Language" in request. 
	public function RegisterRequest ($request_arr)
	{
		return $this->DoPost($request_arr, 'rest/register.do');
	}


	// Connection parameters are passed as key-value array
	// Key of array		Type	Required		Description	
	//orderId 			AN..64 	yes 				Order number in the payment system. Unique within the system.
	//language 			A2 		no 			Language in encoding ISO 639-1. If not specified Russian is assumed  
	//											Error message would be in this language
	//********************************************************************************************************************
	//Method returns values as an array
	//Key of array		Type	Required		Description
	//OrderStatus 		N2 		no 			Status of the order in the payment system. Value is to be chosen from the list shown below 
	//										None, if order was not found. 
	//ErrorCode			N3 		no 			Error code. May be not specified when there were no errors while execution.
	//ErrorMessage 		AN..512 no 			Error description in language which was passed through the parameter "language" in the request. 
	//OrderNumber 		AN..32 	yes 		Order identification number in the store system
	//Pan 				N..19 	no 			Masked card number that was used for payment. Set only after payment for the order. 
	//expiration 		N6 		no 			Expiration date for the card in format YYYYMM. Set only after payment for the order
	//cardholderName 	A..64 	no			Cardholder name. Set only after payment for the order.
	//Amount 			N..20 	yes 		Amount of the payment in copecks (or cents)
	//currency 			N3 		no 			Currency code for the payment in ISO 4217. If not specified is assumed to be 810 (Russian rubles). 
	//approvalCode 		N6 		no 			International payment system authorization code
	//authCode 			N3 		no 			authorization code  of processing system
	//Ip 				AN..20 	no 			User IP address who paid for the order
	public function StatusRequest ($request_arr)
	{	
		$request_arr = $this->ChangeKey($request_arr, "language", "rbsLanguage");
		return $this->DoPost($request_arr, 'rest/getOrderStatus.do');
	}
	
	// Connection parameters are passed as key-value array
	// Similar to the method RegisterRequest
	public function RegisterPreauthRequest ($request_arr)
	{
		return $this->DoPost($request_arr, 'rest/registerPreAuth.do');
	}
	
	//Connection parameters are passed as key-value array
	//Key of array	Required	Description
	//orderId		Yes			ID of the order
	//pan			Yes			Card number
	//cvc			Yes			CVC code
	//expiration 	Yes			Expiry date for the card
	//cardholder 	Yes			Cardholder name
	//cavv			Yes*		*Has to be specified only in case when finalizing ACS payment
	//eci			Yes*		*Has to be specified only in case when finalizing ACS payment
	//xid			Yes*		*Has to be specified only in case when finalizing ACS payment
	//postalCode    AN..9 NO	Postal code for AVS verification
    //address       AN..40 NO	Address for AVS verification
	public function AuthorizePaymentRequest ($request_arr)
	{
		$request_arr = $this->ChangeKey($request_arr, "orderId", "MDORDER");
		$request_arr = $this->ChangeKey($request_arr, "pan", '$PAN');
		$request_arr = $this->ChangeKey($request_arr, "cvc", '$CVC');
		$request_arr = $this->ChangeKey($request_arr, "expiration", '$EXPIRY');
		$request_arr = $this->ChangeKey($request_arr, "cardholder", "TEXT");		
		
		if (empty($request_arr["cavv"]) == false) $request_arr["JsonParams"] = json_encode(array("cavv" => $request_arr["cavv"], "eci" => $request_arr["eci"], "xid" => $request_arr["xid"]));
		
		$sessionStatusReq = array("MDORDER" => $request_arr["MDORDER"], "language" => $request_arr["language"]);
		$this->DoPost($sessionStatusReq, 'rest/getSessionStatus.do');
		return $this->DoPost($request_arr, 'rest/processform.do');
	}

    //Connection parameters are passed as key-value array
    //Key of array	            Required	Description
    //currency                  Yes
    //orderNumber               Yes         The number of the order in Merchant's internal system. max length is 32 symbols
    //amount                    Yes         The amount of payment in cents. For instance, if the price is 2$ 50 cents then you should put 250 here.
    //language                  Yes         The language in ISO 639-1
    //returnUr                  Yes         The url where Payment Gate redirects the client when his payment is finished. (doesn't matter successfully or not) returnUrl max length is 512 symbols
    //description               Yes         You can write anything you like here. description max length is 1024 symbols
    //terminalId                Yes         The number of the terminal that Payment Gate should use to process the payment. This number must be registered in Payment Gate.
    //isCup                     Yes
    //firstName                 Yes         first name
    //lastName                  Yes         last name
    //middleName                Yes         middle name
    //suffix                    Yes         Client's suffix (like Mr, Mrs)
    //mobilePhone               Yes         Client's mobile phone
    //homePhone                 Yes         Client's home phone number
    //officePhone               Yes         Client's office phone
    //activePhone               Yes         The actual phone that should be used to contact the client. Must be the equal to one of the others (Mobile, Home, Office).
    //email1                    Yes         The first email that should be used to send notifications
    //email2                    Yes         The second email that should be used to send notifications
    //description               Yes         Client description (any information)
    //useCourtesy               Yes         If a email should be send to a client some days earlier than recurrent payment takes place
    //sendEmailOnInit           Yes         If a email should be send to a client on installment transaction completion (successfully or not)
    //sendEmailOnManualPayment  Yes         If a mail should be send a client on each manual payment
    //periodType                Yes         DAILY, WEEKLY, BIWEEKLY, SEMIMONTHLY, MONTHLY, BIMONTHLY, QUARTERLY, SEMIANNUALLY, ANNUALLY
    //fixedDay1                 Yes         The first day of month for the payment. It can be specified for periodType = [Semimonthly, Monthly, Bimonthly, Quarterly, Semiannually, Annually]
    //fixedDay2                 Yes         The first day of month for the payment. It must be specified for periodType = [Semimonthly]
    //description               Yes         Any agreement description
    //courtesyPeriod            Yes         The period (in days) before each recurrent payment when the notification is sent to the client.
    //endRecur                  Yes         The date when Recurrent payments should be cancelled automatically
    public function registerRecurrentRequest($request_arr)
    {
        $request_arr = $this->ChangeKey($request_arr, "isRecurrent", "true");
        return $this->DoPost($request_arr, 'rest/register.do');
    }

    //Connection parameters are passed as key-value array
    //Key of array	Required	Description
    //orderId		Yes			ID of the order
    //amount		Yes			Card number
    public function complete($request_arr)
    {
        return $this->DoPost($request_arr, 'rest/deposit.do');
    }

    //Connection parameters are passed as key-value array
    //Key of array	Required	Description
    //orderId		Yes			ID of the order
    //amount		Yes			Card number
    public function refund($request_arr)
    {
        return $this->DoPost($request_arr, 'rest/refund.do');
    }

    //Connection parameters are passed as key-value array
    //Key of array	Required	Description
    //orderId		Yes			ID of the order
    public function reversal($request_arr)
    {
        return $this->DoPost($request_arr, 'rest/reverse.do');
    }

    //Connection parameters are passed as key-value array
    //Key of array	Required	Description
    //orderId		Yes			ID of the order
    //amount		Yes			Card number
    public function originalCredit($request_arr)
    {
        return $this->DoPost($request_arr, 'rest/ocredit.do');
    }
	
	//Connection parameters are passed as key-value array
    //Key of array	Required	Description
    //merchantOrderNumber		Yes			ID of the order
	//terminalId	Yes			The number of the terminal that Payment Gate should use to process the payment. This number must be registered in Payment Gate.
	//description	Yes			You can write anything you like here. description max length is 1024 symbols
	//amount	 	Yes			The amount of payment in cents. For instance, if the price is 2$ 50 cents then you should put 250 here.
	//currency	 	Yes			
	//returnUrl		Yes			The url where Payment Gate redirects the client when his payment is finished. (doesn't matter successfully or not) returnUrl max length is 512 symbols
	//language		Yes			The language in ISO 639-1
	//depositFlag	Yes			
	//pan			Yes			
	//cvc			Yes			
	//expiration	Yes			
	//cardholder	Yes			
	//eci			Yes			
	//cavv			Yes			
	//xid			Yes			
	//address		Yes			
	//postalCode	Yes			
	//customerIp	Yes			
	//isRecurrent	Yes			
	//isCup			Yes			
	//firstName		Yes			
	//lastName		Yes			
	//middleName	Yes			
	//suffix		Yes			
	//mobilePhone	Yes			
	//homePhone		Yes			
	//officePhone	Yes			
	//activePhone	Yes			
	//email1		Yes			
	//email2		Yes			
	//description	Yes			
	//useCourtesy	Yes			
	//sendEmailOnInit	Yes		
	//sendEmailOnManualPayment	Yes	
	//periodType	Yes			
	//fixedDay1		Yes			
	//fixedDay2		Yes			
	//description	Yes			
	//courtesyPeriod	Yes		
	//endRecur		Yes
	public function registerAndPay($request_arr)
	{
		return $this->DoPost($request_arr, 'rest/registerAndPay.do');
	}
	
}
