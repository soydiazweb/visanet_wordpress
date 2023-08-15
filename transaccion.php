<?php

define( 'MERCHANT_ID','AQUI COLOCAR');
define( 'TRANSACTION_KEY','AQUI COLOCAR');

//URL LIVE
//define('WSDL_URL','https://ics2ws.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.196.wsdl');

//URL TEST
//define('WSDL_URL','https://ics2wstesta.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.196.wsdl');

class ExtendedClient extends SoapClient {	
    function __construct($wsdl, $options = null) {
     parent::__construct($wsdl, $options);
    }
    // This section inserts the UsernameToken information in the outgoing SOAP message.
    function __doRequest($request, $location, $action, $version, $one_way = 0) {
        $user = MERCHANT_ID;
        $password = TRANSACTION_KEY;
        $soapHeader = "<SOAP-ENV:Header xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:wsse=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd\"><wsse:Security SOAP-ENV:mustUnderstand=\"1\"><wsse:UsernameToken><wsse:Username>$user</wsse:Username><wsse:Password Type=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText\">$password</wsse:Password></wsse:UsernameToken></wsse:Security></SOAP-ENV:Header>";
        $requestDOM = new DOMDocument('1.0');
        $soapHeaderDOM = new DOMDocument('1.0');
        try {
            $requestDOM->loadXML($request);
            $soapHeaderDOM->loadXML($soapHeader);
            $node = $requestDOM->importNode($soapHeaderDOM->firstChild, true);
            $requestDOM->firstChild->insertBefore($node, $requestDOM->firstChild->firstChild);
            $request = $requestDOM->saveXML();
        } catch (DOMException $e) {
            //die( 'Error adding UsernameToken: ' . $e->code);
        }
        return parent::__doRequest($request, $location, $action, $version);
    }
}


$response = '';
//inicio try
try {
    $soapClient = new ExtendedClient(WSDL_URL, array());
    $request = new stdClass();
    $request->merchantID = MERCHANT_ID;
    $Valueid = $_POST['payment']["id"];
    // Before using this example, replace the generic value with your own.
    $request->merchantReferenceCode = $Valueid;
    // To help us troubleshoot any problems that you may encounter,
    // please include the following information about your PHP application.
    $request->clientLibrary = "PHP";
    $request->clientLibraryVersion = phpversion();
    $request->clientEnvironment = php_uname();
    // This section contains a sample transaction request for the authorization
    // service with complete billing, payment card, and purchase (two items) information.
    $ccAuthService = new stdClass();
    $ccAuthService->run = "true";
    $request->ccAuthService = $ccAuthService;
    //ccCaptureService
    $ccCaptureService = new stdClass();
    $ccCaptureService->run = "true";
    $request->ccCaptureService = $ccCaptureService;
    //AfsService 
    $AfsService = new stdClass();
    $AfsService->run = "true";
    $request->afsService = $AfsService;
    //DeviceFingerprintID 
    $request->deviceFingerprintID = $_POST['payment']["device"];

    $paySubscriptionCreateService = new stdClass();
    $paySubscriptionCreateService->run = 'true';
    $request->paySubscriptionCreateService = $paySubscriptionCreateService;

    $billTo = new stdClass();
    $billTo->firstName = $_POST['billing']["first_name"];
    $billTo->lastName =  $_POST['billing']["last_name"];
    $billTo->street1 = 'Guatemala';
    $billTo->phoneNumber = $_POST['billing']["phone"];
    $billTo->city = 'Guatemala';
    //$billTo->postalCode = "01004";
    $billTo->country = 'Guatemala';
    $billTo->email = $_POST['billing']["email"];
    $billTo->ipAddress = $_SERVER['REMOTE_ADDR'];
    $request->billTo = $billTo;
    
 
    $card = new stdClass();
    $card->accountNumber = $_POST['payment']["tarjeta"];
    $card->expirationMonth = $_POST['payment']["mes"];
    $card->expirationYear = $_POST['payment']["anio"];
    $card->cvNumber = $_POST['payment']["cvv"];
    $request->card = $card;
     
    $purchaseTotals = new stdClass();
    $purchaseTotals->currency = 'GTQ';
    $request->purchaseTotals = $purchaseTotals;
 
    $item0 = new stdClass();
    $item0->unitPrice = $_POST['payment']["total"];
    $item0->quantity = "1";
    $item0->id = "Donacion sitio web | Pedido ".$Valueid;
    $item0->productSKU = $Valueid;
    $item0->productName = 'Donacion recurrente';
    $request->item = array($item0);


    $recurringSubscriptionInfo = new stdClass();
    $recurringSubscriptionInfo->frequency = $_POST['payment']["donacion"];
    $recurringSubscriptionInfo->amount = $_POST['payment']["total"];
    $recurringSubscriptionInfo->automaticRenew = 'true';
    if($_POST['payment']["donacion"] == 'annually'){
    	$recurringSubscriptionInfo->numberOfPayments = '2';
    }else{
    	$recurringSubscriptionInfo->numberOfPayments = '12';
    }
    $recurringSubscriptionInfo->startDate = date('Ymd');
    $request->recurringSubscriptionInfo = $recurringSubscriptionInfo;

    $reply = $soapClient->runTransaction($request);

    //validacion si la transaccion fue aprobada
    //Aprobada 00
    $TransaccionAprobada = '';
    if(isset($reply->ccAuthReply->processorResponse)){
        $TransaccionAprobada = $reply->ccAuthReply->processorResponse;
    }
    //reasonCode 100
    $TransaccionReasonCode = '';
    if(isset($reply->reasonCode)){
        $TransaccionReasonCode = $reply->reasonCode;
    }
    //Accept
    $TextResponse = '';
    if(isset($reply->decision)){
        $TextResponse = $reply->decision;
    }
    //AuthorizationCode
    $AuthorizacioCode = '';
    if(isset($reply->ccAuthReply->authorizationCode)){
        $AuthorizacioCode = $reply->ccAuthReply->authorizationCode;    
    }
    //avsresponse
    if(isset($reply->ccAuthReply->avsresponse)){
        $avsresponse = $reply->ccAuthReply->avsresponse;    
    }else{
        $avsresponse = '';
    }
    $transactionId = '';
    if(isset($reply->merchantReferenceCode)){
        $transactionId = $reply->merchantReferenceCode;
    }
    //Save device_finger_print by order
    $cvCodeVal = '';
    if(isset($reply->ccAuthReply->cvCode)){
        $cvCodeVal = $reply->ccAuthReply->cvCode;
    }else{
        $cvCodeVal = '0';
    }    

    if($TransaccionAprobada == "00" && $TransaccionReasonCode == 100 && $TextResponse == "ACCEPT"){
        $response = 'APPROVED';
    }else{
    	$response = 'ERROR';
    }

    echo $response;

}catch (SoapFault $exception) {
    
    $response = 'ERROR';
    echo $response;

}//finaliza catch