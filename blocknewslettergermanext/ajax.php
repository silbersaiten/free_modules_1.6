<?php
/**
 * Blocknewslettergermanext
 *
 * @category  Module
 * @author    silbersaiten <info@silbersaiten.de>
 * @support   silbersaiten <support@silbersaiten.de>
 * @copyright 2015 silbersaiten
 * @version   1.6.0
 * @link      http://www.silbersaiten.de
 * @license   See joined file licence.txt
 */

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
require_once(dirname(__FILE__).'/blocknewslettergermanext.php');

$instance = new BlocknewsletterGermanext();

if (Tools::getIsset('nw_email'))
	$instance->ajaxCall($_POST);