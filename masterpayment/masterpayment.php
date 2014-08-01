<?php
if ( ! defined('_PS_VERSION_')) {
	exit;
}

define('MP_DIR', 'modules/masterpayment/');

class MasterPayment extends PaymentModule {
	const txPrefix = 'txn';
	private $_html = '';
	private $_currentMethod = false;
		
	public function __construct() {
		$this->name = 'masterpayment';
		$this->tab = 'payments_gateways';
		$this->version = '2.0.0';
		$this->author = 'Silbersaiten';

        $this->is_eu_compatible = 1;

		parent::__construct();

		$this->displayName = $this->l('MasterPayment');
		$this->description = $this->l('Accepts payments by MasterPayment.');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');

		if ( ! Configuration::get('MP_MERCHANT_NAME') || !Configuration::get('MP_SECRET_KEY')) {
			$this->warning = $this->l('Configurations required!');
		}

	}

	public function install() {
		if ( ! parent::install() ||
			! $this->registerHook('payment') ||
			! $this->registerHook('adminOrder') ||
			! $this->registerHook('leftColumn') ||
			! $this->registerHook('paymentReturn') ||
			! $this->registerHook('displayPDFInvoice') ||
			! $this->registerHook('actionValidateOrder') ||
            ! $this->registerHook('displayPaymentEU')
		) {
			return false;
		}

		//Save default configurations
		foreach($this->_getDefaults() as $key => $value) {
			Configuration::updateValue($key, $value);
		}
		
		Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'masterpayment_order` (
				`id_cart` int(10) unsigned NOT NULL,
				`id_order` int(10) unsigned NOT NULL,
				`payment_date` varchar(50) NOT NULL,
				`payment_method` varchar(50) NOT NULL,
			    PRIMARY KEY (`id_order`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
		);
		
		$this->createOrderState(
			'PS_OS_MASTERPAYMENT',
			'Awaiting MasterPayment payment',
			'lightblue',
			array(
				'unremovable' => true,
				'send_email'  => false,
				'delivery'    => false,
				'logable'     => false,
				'invoice'     => false
			)
		);
		
		$this->createOrderState(
			'MASTERPAYMENT_INVOICE_APPROVED',
			'Invoice Payment Approved',
			'#DDEEFF',
			array(
				'unremovable' => true,
				'send_email'  => false,
				'delivery'    => false,
				'logable'     => true,
				'invoice'     => true
			)
		);
	
		return true;
	}
	
	private function createOrderState($conf_name, $name, $color, $additional_params = false) {
		if ( ! Configuration::get($conf_name)) {
			$orderState = new OrderState();
			$orderState->name = array();
	    
			foreach (Language::getLanguages() as $language) {
				$orderState->name[$language['id_lang']] = $name;
			}
			
			$orderState->color       = $color;
			$orderState->hidden      = false;
	    
			if (is_array($additional_params) && sizeof($additional_params)) {
				foreach ($additional_params as $additional_param_name => $additional_param_value) {
					if (property_exists($orderState, $additional_param_name)) {
						$orderState->{$additional_param_name} = $additional_param_value;
					}
				}
			}
	    
			if ($orderState->add()) {
				Configuration::updateValue($conf_name, (int)$orderState->id);
				
				return true;
			}
			
			return false;
		}
		
		return true; // Already exists
	}

	public function uninstall() {
		if ( ! parent::uninstall()) {
			return false;
		}

		//Remove configurations	
		foreach($this->_getDefaults() as $key => $value) {
			Configuration::deleteByName($key);
		}
			
		Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . 'masterpayment_order`');

		return true;
	}
	
	
	private function _getDefaults() {
		return array(
			'MP_MERCHANT_NAME' => '',		
			'MP_SECRET_KEY' => '',
			'MP_GATEWAY_URL' => 'https://www.masterpayment.com/en/payment/gateway',

			'MP_MODE' => 'iframe',
			'MP_ORDER_CONFIRM' => 1,
			'MP_ORDER_CREATE' => 0,
	
			'MP_LANGUAGE' => 'EN',
			'MP_CURRENCY' => 'EUR',
			'MP_GATEWAY_STYLE' => 'standart',
			'MP_PAYMENT_METHODS' => 'none',
			'MP_CANCEL_OPTION' => 1,
			
			'MP_INSTALLMENTS_COUNT' => 6,
			'MP_INSTALLMENTS_PERIOD' => 'use_freq',
			'MP_INSTALLMENTS_FREQ' => 30,
			'MP_RECURRENT_PERIOD' => 'monthly',
			'MP_PAYMENT_DELAY' => 15,
			'MP_DUE_DAYS' => 14,
		);
	}
	
	public function getPaymentMethods() {
		return array(
			'none' 		=> $this->l('MasterPayment'),
			'credit_card' 	=> $this->l('Credit Card'),
			'debit_card' 	=> $this->l('Carte Bleue'),
//			'deferred_debit'=> $this->l('Deffered Debit'),
			'elv' 		=> $this->l('Lastschrift'),
			'elv_triggered' => $this->l('Gewinnspiele'),
			'phone' 	=> $this->l('Pay by Call'),
			'sofortbanking' => $this->l('Sofort Banking'),
			'anzahlungskauf'=> $this->l('Anzahlungskauf'),
			'finanzierung' 	=> $this->l('Finanzierung'),
			'ratenzahlung' 	=> $this->l('Ratenzahlung'),
			'rechnungskauf' => $this->l('Rechnungskauf'),
			'cc_recurrent' 	=> $this->l('Credit Card Recurring'),	//??
			'elv_recurrent' => $this->l('Lastschrift Recurrent'),	//??
		);		
	}
	
	public function getGatewayStyles() {
		return array(
			'standard' 	=> $this->l('Standard'),
			'lieferando' 	=> $this->l('Lieferando'),
			'motosino' 	=> $this->l('Motosino'),
			'gimahot' 	=> $this->l('Gimahot'),
			'avandeo' 	=> $this->l('Avandeo'),
			'harotec' 	=> $this->l('Harotec'),
			'mobile' 	=> $this->l('Mobile'),
			'afterbuy' 	=> $this->l('Afterbuy'),
			'afterbuy_shop'	=> $this->l('Afterbuy Shop')
		);
	}
	
	public function getValidLanguages() {
		return array(
			'EN' => $this->l('English'),
			'DE' => $this->l('German'),
			'IT' => $this->l('Italian'),
			'ES' => $this->l('Spanish'),
			'FR' => $this->l('French'),
			'PL' => $this->l('Polish')
		);
	}
	
	public function getValidCurrencies() {
		return array('EUR', 'GBP', 'CHF', 'USD');
	}
	
	public function getModes() {
		return array(
			'iframe'   => $this->l('iFrame'),
			'external' => $this->l('External'),
		);		
	}
	
	public function getInstallmentsPeriods() {
		return array(
			'use_freq'     => $this->l('Use frequency'),
			'monthly'      => $this->l('Monthly'),
			'end_of_month' => $this->l('End of month')
		);
	}
	
	public function getRecurrentPeriods() {
		return array(
			'weekly'    => $this->l('Weekly'),
			'monthly'   => $this->l('Monthly'),
			'quarterly' => $this->l('Quarterly'),
			'yearly'    => $this->l('Yearly')
		);		
	}
	
	public function getConfigurations() {
		return Configuration::getMultiple(array_keys($this->_getDefaults()));
	}
	
	public function getValidCurrency() {
		$valid = $this->getValidCurrencies();

		$currency = Context::getContext()->currency;

		if (in_array($currency->iso_code, $valid)) {
			return $currency;
		}
		else {
			$currencies = Currency::getCurrencies();
			
			foreach ($currencies as $c) {
				if (in_array($c['iso_code'], $valid)) {
					return 	Currency::getCurrencyInstance($c['id_currency']);
				}
			}
		}
		
		return null;
	}
	
	/*** Configurations ***/
	public function getContent() {		
		if (Tools::isSubmit('saveConfigurations')) {
			$cfg = Tools::getValue('cfg', array());
			$cfg['MP_PAYMENT_METHODS'] = implode(',', Tools::getValue('payment_methods', array()));
			
			foreach($cfg as $key => $value)
				Configuration::updateValue($key, $value);

			$this->_html .= $this->displayConfirmation($this->l('Configuration updated'));
		}

		$cfg = $this->getConfigurations();
		
		$this->tplAssign(array(
			'cfg'             => $cfg,
			'payment_methods' => explode(',', $cfg['MP_PAYMENT_METHODS'])
		));

		return $this->_html . $this->tplDisplay('configurations');
	}

	/*** Hooks ***/
	public function hookPayment($params) {
		//var_dump($this->getValidCurrency());
		//Check if is configured and have valid currency		
		if( ! Configuration::get('MP_MERCHANT_NAME') || !Configuration::get('MP_SECRET_KEY') || ! $this->getValidCurrency()) {
			return '';
		}
		
		$payment_methods = explode(',', Configuration::get('MP_PAYMENT_METHODS', array()));
		
		$this->tplAssign('payment_methods', array_intersect_key($this->getPaymentMethods(), array_flip($payment_methods)));
		
		return $this->tplDisplay('payment');
	}

    public function hookDisplayPaymentEU($params) {
        //var_dump($this->getValidCurrency());
        //Check if is configured and have valid currency
        if( ! Configuration::get('MP_MERCHANT_NAME') || !Configuration::get('MP_SECRET_KEY') || ! $this->getValidCurrency()) {
            return '';
        }

        $payment_methods = explode(',', Configuration::get('MP_PAYMENT_METHODS', array()));

        $payment_methods = array_intersect_key($this->getPaymentMethods(), array_flip($payment_methods));

        $result = array();
        foreach ($payment_methods as $payment_method => $payment_method_data) {
            array_push($result, array(
                'cta_text' => $this->l('Pay using') . ' ' . $payment_method_data,
                'logo' => $this->_path.'views/img/p/'.$payment_method.'.png',
                //'form' => $smarty->fetch(dirname(__FILE__) . '/expercash_eu.tpl')
                'action' => $this->context->link->getModuleLink('masterpayment', 'submit', array('payment_method' => $payment_method, 'confirmOrder'=>'true'), true)
            ));
        }

        return sizeof($result) ? $result : false;
    }

	public function hookPaymentReturn($params) {
		if ( ! $this->active) {
			return;
		}
		
		$order = $params['objOrder'];
		
		if ($order->module != $this->name) {
			return;
		}

		switch($order->getCurrentState()) {
			case Configuration::get('PS_OS_PAYMENT'):
				$this->tplAssign('status', 'ok');
				break;
			case Configuration::get('PS_OS_MASTERPAYMENT'):
			case Configuration::get('MASTERPAYMENT_INVOICE_APPROVED'):
				$this->tplAssign('status', 'pending');
				break;
			case Configuration::get('PS_OS_ERROR'):
			default:
				$this->tplAssign('status', 'failed');
				break;
		}

		return $this->tplDisplay('paymentReturn');
	}
	
	public function hookAdminOrder($params) {
		$order = new Order((int)$params['id_order']);
		$msg = null;

		if ($this->name != $order->module) {
			return;
		}

		$cart = new Cart($order->id_cart);
		$currency = new Currency($order->id_currency);
		
		if (Tools::isSubmit('submitMasterPaymentRefund')) {
			$amount = (float)Tools::getValue('amount', 0);
			
			if ($amount > 0 && $amount <= $order->total_paid) {
				require_once(dirname(__FILE__).'/lib/api.php');
				$api = new MasterPaymentApi();
				
				$api->merchantName = Configuration::get('MP_MERCHANT_NAME');
				$api->secretKey = Configuration::get('MP_SECRET_KEY');
				$api->basketValue = $amount * 100;
				$api->txId = self::encodeTxID($cart);
	
				$comment = Tools::getValue('comment', '');
				$status = $api->refundRequest($comment);
	
				if ($status == MasterPaymentApi::STATUS_REFUNDED) {
					// Update order state
					$order->setCurrentState(Configuration::get('PS_OS_REFUND'), $this->context->employee->id);
		
					// Add refund amount message
					$msg = new Message();
					$msg->message = $comment.' - '.$this->l('Refund amount').': '.Tools::displayPrice($amount, $currency);
					$msg->id_order = $order->id;
					$msg->id_customer = $cart->id_customer;
					$msg->private = true;
					$msg->add();
		
					// Redirect to order
					Tools::redirectAdmin('#');
				}
				else {
					$msg = '<p class="error">' . $comment . '</p>';
				}
			}
			else {
				$msg = '<p class="error">' . $this->l('Ivalid amount') . '</p>';
			}
		}
		
		$this->tplAssign('msg', $msg);
		$this->tplAssign('order', $order);
		$this->tplAssign('amount', Tools::ps_round($order->total_paid,  2));
		$this->tplAssign('currency', $currency);
		$this->_html .= $this->tplDisplay('adminOrder');
		
		return $this->_html;
	}
	
	public function hookRightColumn($params) {
		return $this->tplDisplay('column');
	}

	public function hookLeftColumn($params) {
		return $this->hookRightColumn($params);
	}
	
	public function hookDisplayPDFInvoice($params) {
		$order_invoice = $params['object'];
		
		if (!($order_invoice instanceof OrderInvoice)) {
			return;
		}

		$order = new Order((int)$order_invoice->id_order);

		if (Validate::isLoadedObject($order)) {
			$currency = new Currency($order->id_currency);
			
			$payment_method = Db::getInstance()->getValue('
				SELECT
					`payment_method`
				FROM
					`' . _DB_PREFIX_ . 'masterpayment_order`
				WHERE
					`id_cart` = ' . (int)$order->id_cart
			);

			if ($payment_method == 'rechnungskauf') {
				$this->context->smarty->assign(array(
					'order_total' => Tools::displayPrice($order->total_paid_tax_incl, $currency, false),
					'order_id'    => self::txPrefix . $order->id_cart
				));
				
				return $this->display(__FILE__, 'pdfinvoice.tpl');
			}
		}
	}
	
	
	/*** Misc ***/
	static public function encodeTxID($cart) {
		return self::txPrefix.$cart->id;
	}
	
	static public function decodeTxID($txID) {
		return intval(substr($txID, strlen(self::txPrefix), strlen($txID)));
	}
	
	public function tplAssign($var, $val = null) {
		$this->context->smarty->assign($var, $val);
	}
	
	public function tplDisplay($tpl) {
		$this->context->smarty->assign(array(
			'mod_dir' => $this->_path,
			'this'    => $this,
			'link'    => $this->context->link
		));
		
		return self::display(__FILE__, $tpl . '.tpl');
	}
	
	public function setCurrentMethod($method) {
		$methods = $this->getPaymentMethods();
		
		if ( ! array_key_exists($method, $methods)) {
			die(Tools::displayError());
		}
		
		$this->currentMethod = $method;
	}
	
	public function getCurrentMethod() {
		return $this->currentMethod;
	}
	
	public function registerPaymentInfo($id_cart, $payment_method) {
		Db::getInstance()->Execute('INSERT INTO `' . _DB_PREFIX_ . 'masterpayment_order` (`id_cart`, `payment_method`) VALUES (' . (int)$id_cart . ', "' . $payment_method . '")');
	}
	
	public function hookActionValidateOrder($params) {
		$order = $params['order'];
		$cart = $params['cart'];
		
		Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'masterpayment_order` SET `id_order` = ' . $order->id . ', `payment_date` = "' . $order->date_add . '" WHERE `id_cart` = ' . (int)$cart->id);
	}
	
	public function log($string) {
		$path = dirname(__FILE__) . '/log.txt';
		$handler = fopen($path, (file_exists($path) ? 'a' : 'w'));
		
		if ($handler) {
			fwrite($handler, '[' . date('Y-m-d H:i:s') . '] ' . $string . "\n");
			
			fclose($handler);
		}
	}
}