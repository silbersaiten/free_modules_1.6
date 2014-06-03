<?php
class WidgetProductTooltip extends Vovique
{
    private static $_additionalParams = array('w_' => 'width', 'v_' => 'voffset', 'h_' => 'hoffset');
    
    public function __construct()
    {
        $this->_wName = $this->l('CMS Tooltip Widget');
        $this->_wDescription = $this->l('Displays link to a specified product that shows a custom tooltip on mouse over it. Uses ajax to retrieve product data.');
        $this->_params = array(
            'id' => array(
                'description' => $this->l('CMS ID'),
                'validation'  => 'isUnsignedId',
                'required'    => true
            ),
            'width' => array(
                'description' => $this->l('Tooltip width'),
                'validation'  => 'isInt'
            ),
            'hoffset' => array(
                'description' => $this->l('Horizontal offset'),
                'validation'  => 'isInt'
            ),
            'voffset' => array(
                'description' => $this->l('Vertical offset'),
                'validation'  => 'isInt'
            ),
        );
    }
    
    private static function getShortCMSInfo($cms, $language)
    {
        return Db::getInstance()->getRow('
            SELECT `meta_title`,
                   `link_rewrite`
            FROM   `' . _DB_PREFIX_ . 'cms_lang`
            WHERE  `id_cms`  = ' . (int)$cms . '
            AND    `id_lang` = ' . (int)$language
        );
    }
    
    public function widgetShow($params)
    {
        global $smarty, $cookie;
        
        $linkTpl = '
        <a href="%1$s" class="widgetctooltip%4$s" rel="cms_%3$d">%2$s</a>';
        
        $additionalParams = '';
        
        foreach (self::$_additionalParams as $prefix => $param)
        {
            if (isset($params[$param]))
            {
                $additionalParams.= ' ' . $prefix . $params[$param];
            }
        }
        
        $cms = self::getShortCMSInfo($params['id'], $cookie->id_lang);
        
        if ( ! Tools::isEmpty($cms['meta_title']))
        {
            $widgetPath = $this->widgetUri();
            
            $this->setCss($widgetPath . 'css/style.css');
            $this->setJs($widgetPath . 'js/tooltip.js');

            $link = new Link();
            
            return sprintf($linkTpl, $link->getCMSLink($params['id'], $cms['link_rewrite']), $cms['meta_title'], $params['id'], $additionalParams);
        }
    }
}
?>