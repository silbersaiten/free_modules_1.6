<?php

if (!defined('_PS_VERSION_'))
	exit;

class NTScroll2Top extends Module
{
	private $_html = '';
	private $_postErrors = array();

	public function __construct()
	{
		$this->name = 'ntscroll2top';
		$this->tab = 'front_office_features';
		$this->version = '0.1';
		$this->author = 'silbersaiten';
		$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Displays scroll button to top.');
		$this->description = $this->l('Displays scroll button to top on bottom of page.');
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('displayFooter') || !$this->registerHook('header'))
			return false;
		return true;
	}


	public function hookDisplayHeader($params)
	{
		$this->hookHeader($params);
	}

	public function hookHeader($params)
	{
		$this->context->controller->addCSS(($this->_path).'css/ntscroll2top.css', 'all');
        $this->context->controller->addJS(($this->_path).'js/ntscroll2top.js', 'all');
	}

	public function hookDisplayFooter($params)
	{
		return $this->display(__FILE__, 'views/templates/hook/ntscroll2top.tpl');
	}
}