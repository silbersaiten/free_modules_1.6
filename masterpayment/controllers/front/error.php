<?php

require_once(dirname(__FILE__).'/../../lib/api.php');

class MasterPaymentErrorModuleFrontController extends ModuleFrontController {
    public $display_column_left = false;
    
    public function initContent() {
	parent::initContent();
	
	$this->context->smarty->assign('order_process', Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order');

	$this->setTemplate('error.tpl');
    }
}