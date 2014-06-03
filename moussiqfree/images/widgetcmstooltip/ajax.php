<?php
// Because we can't be sure where this widget was called from
$path = str_replace(strstr(dirname(__FILE__), DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR), '', dirname(__FILE__));

include($path . '/config/config.inc.php');
include(_PS_ROOT_DIR_ . '/init.php');

if (Tools::getIsset('tcms'))
{
    $cms = Tools::getValue('tcms');
    
    if (Validate::isLoadedObject($cmsObj = new CMS($cms, $cookie->id_lang)))
    {
        $smarty->assign('tcms', $cmsObj);
        
        die($smarty->display(dirname(__FILE__) . '/templates/tooltip.tpl'));
    }
}
?>