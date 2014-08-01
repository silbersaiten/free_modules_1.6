<?php

require_once(dirname(__FILE__).'/../../lib/api.php');

class MasterPaymentSubmitModuleFrontController extends ModuleFrontController {
    public $display_column_left = false;
//    public $ssl = true;

    public function setMedia() {
        parent::setMedia();
        $this->addCSS(__PS_BASE_URI__.MP_DIR.'views/css/gateway.css');
    }

    public function init() {
        parent::init();
        $this->display_column_left = false;
    }
    
    public function initContent() {  
	parent::initContent();  
	
	$cart = $this->context->cart;
	$link = $this->context->link;
	$cookie = $this->context->cookie;
	
	//Payment methods        
	$payment_method = Tools::getValue('payment_method', 'none');
	$payment_methods = $this->module->getPaymentMethods();
	
	if ( ! isset($payment_methods[$payment_method])) {
	    Tools::redirect('index.php?controller=order');
	}
	
	$payment_method_name = $payment_methods[$payment_method];
	
	//Currency
	$currency = $this->context->currency;
	$valid_currencies = $this->module->getValidCurrencies();
	$shop_currencies = Currency::getCurrencies();
	$currencies = array();
	
	foreach ($shop_currencies as $c) {
	    if (in_array($c['iso_code'], $valid_currencies)) {
		array_push($currencies, $c['name']);
	    }
	}
	    
	//Price
	$totalAmount = $cart->getOrderTotal();
	
	//Module configurations
	$cfg = $this->module->getConfigurations();        
	
	//Common tpl varialbles
	$this->context->smarty->assign(array(
	    'cfg'                => $cfg,
	    'currency'           => $currency,
	    'validCurrencyNames' => implode(', ', $currencies),
	    'isValidCurrency'    => in_array($currency->iso_code, $valid_currencies),
	    'total'              => $totalAmount,
	    'paymentMethod'      => $payment_method,
	    'paymentName'        => $payment_method_name,
	    'this_masterpayment_path'          => Tools::getShopDomain(true, true).__PS_BASE_URI__.MP_DIR,
	    'this_masterpayment_path_ssl'      => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.MP_DIR
	));        
	
	if ( ! (bool)$cfg['MP_ORDER_CONFIRM'] || Tools::isSubmit('confirmOrder') || Tools::getValue('content_only', 0)) {
	    if ((bool)$cfg['MP_ORDER_CREATE']) {
            $this->module->registerPaymentInfo($cart->id, $payment_method);
		
		//Create order
		$this->module->validateOrder(
		    $cart->id,
		    Configuration::get('PS_OS_MASTERPAYMENT'),
		    $totalAmount,
		    $this->module->displayName,
		    $this->module->l('Payment method').': '.$payment_method_name,
		    array(), //$extraVars
		    $currency->id,
		    false,
		    $cart->secure_key
		);
	    }
	    
	
	    $order = (int)$this->module->currentOrder ? new Order($this->module->currentOrder) : null;
	    $customer = new Customer((int)$cart->id_customer);
	    $address = new Address((int)$cart->id_address_invoice);
	
	    //Language
	    $language = strtoupper(Language::getIsoById($cookie->id_lang));
	    
	    //if language not found use default language
	    if ( ! in_array($language, array_keys($this->module->getValidLanguages()))) {
		$language = $cfg['MP_LANGUAGE'];
	    }
	
	    //URL's
	    $order_confirmation_url = $link->getModuleLink('masterpayment', 'confirmation', array('id_cart' => (int)$cart->id, 'id_module' => (int)$this->module->id, 'key' => $customer->secure_key));
	    $order_error_url = $link->getModuleLink('masterpayment', 'error');
	    $order_validation_url = Tools::getShopDomain(true, true).__PS_BASE_URI__.MP_DIR.'validation.php';
	    
	//	    $link->getModuleLink('masterpayment', 'validation', array());
	    
	    //MasterPayment API
	    $api = new MasterPaymentApi();
	    $api->iframeMode = ($cfg['MP_MODE'] == 'iframe');
	    $api->merchantName = $cfg['MP_MERCHANT_NAME'];
	    $api->secretKey = $cfg['MP_SECRET_KEY'];
	    $api->txId = MasterPayment::encodeTxID($cart);
	    $api->orderId = $this->module->currentOrder;
	    $api->basketDescription = str_replace(array('{order}', '{cart}', '{shop}'), array($this->module->currentOrder, $cart->id, Configuration::get('PS_SHOP_NAME')), $order ? $this->module->l('Shopping order #{order} - {shop}') : $this->module->l('Shopping cart #{cart} - {shop}'));
	    $api->basketValue = Tools::ps_round($totalAmount, 2) * 100;
	    $api->currency = $currency->iso_code;
	    $api->language = $language;
	    $api->paymentType = $payment_method;
	    $api->gatewayStyle = $cfg['MP_GATEWAY_STYLE'];
	    $api->UrlPatternSuccess = $order_validation_url;
	    $api->UrlPatternFailure = $order_error_url;
	    $api->UrlRedirectSuccess = $order_confirmation_url;
	    $api->UrlRedirectFailure = $order_error_url;
	    $api->UrlRedirectCancel = $order_error_url;
	    $api->showCancelOption = (int)$cfg['MP_CANCEL_OPTION'];
	
	    $api->userId = $customer->id;
	    $api->sex = ($customer->id_gender == 9) ? 'unknown' : ($customer->id_gender == 1) ? 'man' : 'woman';
	    $api->firstname = $customer->firstname;
	    $api->lastname = $customer->lastname;
	    $api->email = $customer->email;
        $api->street = $address->address1 .(($address->address2!='')?(' '.$address->address2):'');
	    $api->houseNumber = '.';
	    $api->zipCode = $address->postcode;
	    $api->city = $address->city;
	    $api->country = Country::getIsoById($address->id_country);
	    $api->birthdate = $customer->birthday;
	    $api->mobile = $address->phone ? $address->phone : $address->phone_mobile;
	
	    $api->installmentsCount = $cfg['MP_INSTALLMENTS_COUNT'];
	    $api->recurrentPeriod = $cfg['MP_RECURRENT_PERIOD'];
	    $api->paymentDelay = $cfg['MP_PAYMENT_DELAY'];
	    $api->dueDays = $cfg['MP_DUE_DAYS'];
	    $api->invoiceNo = $order ? Configuration::get('PS_INVOICE_PREFIX').$order->invoice_number : '';
	    $api->createAsPending = 1;
	
	    if ($cfg['MP_INSTALLMENTS_PERIOD'] == 'use_freq') {
		$api->installmentsFreq = $cfg['MP_INSTALLMENTS_FREQ'];
	    }
	    else {
		$api->installmentsPeriod = $cfg['MP_INSTALLMENTS_PERIOD'];
	    }
		
	    $this->context->smarty->assign('params', $api->getParams());
	
	    $this->setTemplate('gateway.tpl');
	}
	else {
	    $this->setTemplate('submit.tpl');
	}
    }
}
