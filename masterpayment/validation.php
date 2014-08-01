<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/lib/api.php');
require_once(dirname(__FILE__).'/masterpayment.php');

function dieWithError($error) {
	$l = date('Y-m-d H:i:s').' "'.$error.'" data:'.json_encode(array('post'=>$_POST, 'get'=>$_GET))."\n";
	file_put_contents(dirname(__FILE__).'/error.log', $l, FILE_APPEND);	
	die($error);
}

$module = new MasterPayment();

//Setup MasterPayment API to handle request
$api = new MasterPaymentApi();
$api->secretKey = Configuration::get('MP_SECRET_KEY');

//Get request status
$status = $api->getRequestStatus();

//Check status
if ($status == 'INVALID') {
	dieWithError('Invalid request');
}

//Extract cart ID from transaction ID
$id_cart = MasterPayment::decodeTxID($api->txId);

$totalAmount = ((float)$api->basketValue / 100);
$cart = new Cart($id_cart);

if ( ! Validate::isLoadedObject($cart)) {
	dieWithError('Cart not found');
}

$id_order = Order::getOrderByCartId($cart->id);
$order = $id_order ? new Order($id_order) : null;
$currency = new Currency($cart->id_currency);

//Check currency
if ($currency->iso_code != $api->currency) {
	dieWithError('Invalid currency');
}

//Check amount
$orderTotal = $cart->getOrderTotal();
//if($totalAmount != Tools::ps_round($orderTotal, 2))
//	dieWithError('Invalid amount' );

//Init MasterPayment Module
//$mp = new MasterPayment();

//Process request status
$order_state_name = '';
$message = null;
		
switch($status) {
	case 'SUCCESS':
		$order_state_name = 'PS_OS_PAYMENT';
		$message = $module->l('Payment processed successfully.');
		break;
	case 'SCHEDULED':
		$order_state_name = 'PS_OS_MASTERPAYMENT';
		$message = $module->l('Gateway invocation succeeded, payment is scheduled for automatic execution.');
		break;
	case 'PENDING':			
		$order_state_name = 'PS_OS_MASTERPAYMENT';
		$message = $module->l('Gateway invocation succeeded, payment is waiting for manual activation by merchant.');
		break;
	case 'FAILED':
		$order_state_name = 'PS_OS_ERROR';
		$message = $module->l('Payment has failed.');
		break;
	case 'REFUSED_RISK':
		$order_state_name = 'PS_OS_CANCELED';
		$message = $module->l('Payment was refused due to risk assessment.');
		break;
	case 'CANCELLED':
		$order_state_name = 'PS_OS_CANCELED';
		$message = $module->l('Payment cancelled by customer.');
		break;
	case 'REVOKED':
		$order_state_name = 'PS_OS_CANCELED';
		$message = $module->l('Payment revoked manually by merchant.');
		break;
	case 'TIMED_OUT':
		$order_state_name = 'PS_OS_CANCELED';
		$message = $module->l('Payment has timed out waiting for input from customer.');
		break;
	default:
		$status = 'UNKNOWN';
		$order_state_name = 'PS_OS_ERROR';
		$message = $module->l('Unknown transaction status notification.');
		break;
}

if ($order_state_name == 'PS_OS_PAYMENT' && $api->paymentType == 'rechnungskauf') {
	$order_state_name = 'MASTERPAYMENT_INVOICE_APPROVED';
}

//Get order state id
$id_order_state = Configuration::get($order_state_name);

//Update order state
if ($order && $order->getCurrentState() != $id_order_state) {
	$order->setCurrentState($id_order_state);
}

//Creates new order
if ( ! $order && in_array($status, array('SUCCESS', 'SCHEDULED', 'PENDING', 'FAILED', 'UNKNOWN'))) {
	$paymentMethods = $module->getPaymentMethods();
	$paymentName = isset($paymentMethods[$api->paymentType]) ? $paymentMethods[$api->paymentType] : $paymentMethods['none'];
	
	$module->registerPaymentInfo($cart->id, $api->paymentType);
	
	//create order
	$module->validateOrder
	(
	    $cart->id,
	    $id_order_state,
	    $totalAmount,
	    $paymentName,
	    $message,
	    array(), //$extraVars
	    $currency->id,
	    false,
	    $cart->secure_key
	);
}

//Add message to order
if($order && $message) {
	$msg = new Message();
	$msg->message = $message;
	$msg->id_order = $order->id;
	$msg->id_customer = $cart->id_customer;
	$msg->private = true;
	$msg->add();
}

exit;