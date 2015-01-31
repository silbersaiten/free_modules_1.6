<?php
/**
 * NTScroll2Top
 *
 * @category  Module
 * @author    silbersaiten <info@silbersaiten.de>
 * @support   silbersaiten <support@silbersaiten.de>
 * @copyright 2015 silbersaiten
 * @version   1.0.0
 * @link      http://www.silbersaiten.de
 * @license   See joined file licence.txt
 */

if (!defined('_PS_VERSION_'))
	exit;

class NTScroll2Top extends Module
{
	public function __construct()
	{
		$this->name = 'ntscroll2top';
		$this->tab = 'front_office_features';
		$this->version = '1.0.0';
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
		unset($params);
		$this->context->controller->addCSS(($this->_path).'css/ntscroll2top.css', 'all');
		$this->context->controller->addJS(($this->_path).'js/ntscroll2top.js', 'all');
	}

	public function hookDisplayFooter($params)
	{
		unset($params);
		return $this->display(__FILE__, 'views/templates/hook/ntscroll2top.tpl');
	}
}