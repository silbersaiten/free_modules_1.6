<?php
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/blocknewslettergermanext.php');

$instance = new BlocknewsletterGermanext();

if (Tools::getIsset('nw_email'))
    $instance->ajaxCall($_POST);