<?php
require_once(dirname(__FILE__) . '/classes/MoussiqFreeService.php');
/*
 * Moussiq FREE Export Module
 *
 * @version 1.5.4.1
 * @date 08-01-2014
 *
 * @author    silbersaiten <support@silbersaiten.de>
 * @copyright Silbersaiten <www.silbersaiten.de>
 *
 * Support: http://www.silbersaiten.de/
 *
 */
class MoussiqFree extends Module
{
    private $_html = '';
    private $_postErrors = array();
    private static $_tblCache = array();
    private static $_miscTranslations;
    
    public function __construct()
    {
        $this->name    = 'moussiqfree';
        $this->tab     = 'export';
		$this->author  = 'silbersaiten';
        $this->version = '2.0';
		
        parent::__construct();
        
        $this->displayName = $this->l('Moussiq FREE');
        $this->description = $this->l('Exports your products into a csv file for various price comparison engines');
        
        self::$_miscTranslations = array(
            'new'         => $this->l('New'),
            'used'        => $this->l('Used'),
            'refurbished' => $this->l('Refurbished')
        );


    }
    
    public function install()
    {
    
        if ( ! parent::install() || ! $this->registerHook('backOfficeHeader')) 
            return false;
        
        $queries = array(
        'CREATE TABLE `' . _DB_PREFIX_ . 'moussiqfree_service`
            (
                `id_moussiqfree_service` INT(10) unsigned NOT NULL AUTO_INCREMENT  ,
                `name`               VARCHAR(128) NOT NULL                     ,
                `id_lang`            INT(10)      DEFAULT NULL                 ,
                `id_group`           INT(10)      DEFAULT NULL                 ,
                `id_store`           INT(10)      DEFAULT NULL                 ,
                `id_shop`            INT(10)      DEFAULT NULL                 ,
                `id_country`         INT(10)      DEFAULT NULL                 ,
                `id_state`           INT(10)      DEFAULT NULL                 ,
                `condition`          VARCHAR(255) DEFAULT NULL                 ,
                `id_carrier`         INT(10)      DEFAULT NULL                 ,
                `export_inactive`    TINYINT(1)   DEFAULT NULL                 ,
                `delimiter`          VARCHAR(3)   DEFAULT NULL                 ,
                `enclosure`          TINYINT(1)   DEFAULT NULL                 ,
                `header`             TINYINT(1)   DEFAULT NULL                 ,
                `export_engine`      VARCHAR(255) DEFAULT NULL                 ,
                `last_upd`           INT(11)      NOT NULL                     ,
                `template`           TEXT                                      ,
                `status`             TINYINT(1) unsigned NOT NULL DEFAULT "0"  ,
                PRIMARY KEY ( `id_moussiqfree_service` )
            )
            ENGINE=MyISAM DEFAULT CHARSET=utf8',
            
            'CREATE TABLE `' . _DB_PREFIX_ . 'moussiqfree_service_categories` (
              `id_moussiqfree_service`   INT(10)      unsigned NOT NULL    ,
              `id_category`          INT(10)      unsigned NOT NULL    ,
              KEY `moussiqfree_category_index` (`id_moussiqfree_service`, `id_category`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );
        
        foreach ($queries as $query)
        {
            if ( ! Db::getInstance()->Execute($query))
            {
                $this->uninstall();
                
                return false;
            }
        }

        $carriers = Carrier::getCarriers(intval(Configuration::get('PS_LANG_DEFAULT')), true);
        
        if (sizeof($carriers) == 0) 
            return false;
        
        $defaultCountry = (int)(Configuration::get('PS_COUNTRY_DEFAULT'));
        $defaultState = 0;
                            
        if (Country::containsStates($defaultCountry))
        {
            $states = State::getStatesByIdCountry($defaultCountry);
            
            if ($states && sizeof($states))
            {
                $defaultState = $states[0]['id_state'];
            }
        }
        $defaultCondition = 'new:used:refurbished';
        
        Configuration::updateValue('EXPORTFREE_LANGUAGE', (int)(Configuration::get('PS_LANG_DEFAULT')));
        Configuration::updateValue('EXPORTFREE_DELIMITER', ',');
        Configuration::updateValue('EXPORTFREE_ENCLOSURE', 1);
        Configuration::updateValue('EXPORTFREE_HEADER', 1);
        Configuration::updateValue('EXPORTFREE_COUNTRY', (int)$defaultCountry);
        Configuration::updateValue('EXPORTFREE_STATE', (int)$defaultState);
        Configuration::updateValue('EXPORTFREE_CONDITION', $defaultCondition);
        Configuration::updateValue('EXPORTFREE_CARRIER', (int)(Configuration::get('PS_CARRIER_DEFAULT')));
        Configuration::updateValue('EXPORTFREE_STORE', (int)Db::getInstance()->getValue('SELECT `id_store` FROM `' . _DB_PREFIX_ . 'store` ORDER BY `id_store` ASC'));
        Configuration::updateValue('EXPORTFREE_SHOP', (int)Db::getInstance()->getValue('SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'shop` ORDER BY `id_shop` ASC'));
        Configuration::updateValue('EXPORTFREE_INACTIVE', 0);
        Configuration::updateValue('EXPORTFREE_GROUP', (int)Db::getInstance()->getValue('SELECT `id_group` FROM `' . _DB_PREFIX_ . 'group` ORDER BY `id_group` ASC'));


        $this->createPredefServices();

        return $this->installModuleTab('AdminMoussiqFree', 'Moussiq Free');
    }

    public function createPredefServices($dir = 'templates_predef') {
        // create predefined services from templates_predef directory
        $cdir =  dirname(__FILE__);
        $sdir = dir($cdir.'/'.$dir);
        while(($item = $sdir->read()) !== false) {
            if(!is_dir($cdir.'/'.$dir.'/'.$item)) {
                $file = $cdir.'/'.$dir.'/'.$item;

                $extension = strrchr($file, '.');

                if ($extension == '.mtpl') {
                    $template = file($file, FILE_SKIP_EMPTY_LINES);

                    $defaultLanguage = (int)Configuration::get('PS_LANG_DEFAULT');
                    $defaultCarrier  = 0;
                    $defaultCountry  = (int)Configuration::get('PS_COUNTRY_DEFAULT');
                    $defaultState    = 0;
                    $defaultCondition = 'new:used:refurbished';
                    $defaultStore    = (int)Db::getInstance()->getValue('SELECT `id_store` FROM `' . _DB_PREFIX_ . 'store`');
                    $defaultGroup    = (int)Db::getInstance()->getValue('SELECT `id_group` FROM `' . _DB_PREFIX_ . 'group`');

                    $engine = 'ExportCSV';

                    if (Country::containsStates($defaultCountry))
                    {
                        $states = State::getStatesByIdCountry($defaultCountry);

                        if (sizeof($states))
                        {
                            $defaultState = (int)$states[0]['id_state'];
                        }
                    }

                    $carriers = Carrier::getCarriers($defaultLanguage, true);

                    if ($carriers && sizeof($carriers))
                    {
                        $defaultCarrier = (int)$carriers[0]['id_carrier'];
                    }

                    $additionalProperties = array(
                        'id_lang'       => $defaultLanguage,
                        'id_country'    => $defaultCountry,
                        'id_state'      => $defaultState,
                        'id_carrier'    => $defaultCarrier,
                        'id_store'      => $defaultStore,
                        'id_group'      => $defaultGroup,
                        'export_engine' => $engine,
                        'condition' => $defaultCondition
                    );
                    if (sizeof($template) > 0) {
                        $obj = new MoussiqFreeService();

                        foreach ($template as $line)
                        {
                            $line   = explode(':', $line);
                            $field  = pSQL(base64_decode($line[0]));

                            if ($field == 'template')
                                $line[1] = base64_decode($line[1]);

                            $value = base64_decode($line[1]);
                            //print_r(pSQL(base64_decode($line[1])));
                            //print_r($value);

                            if (property_exists($obj, $field))
                                $obj->{$field} = $value;

                            foreach ($additionalProperties as $prop => $val)
                            {
                                if (property_exists($obj, $prop))
                                    $obj->{$prop} = $val;
                            }
                        }

                        if ( ! $obj->save())                  {
                            return false;
                        }
                    }
                }
            }
        }
    }
    
	public function uninstall()
	{
		$sql = '
		SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `module` = "' . pSQL($this->name) . '"';
		
		$result = Db::getInstance()->ExecuteS($sql);
		
		if ($result && sizeof($result))
		{
			foreach ($result as $tabData)
			{
				$tab = new Tab($tabData['id_tab']);
				
				if (Validate::isLoadedObject($tab))
					$tab->delete();
			}
		}
		
		if (self::tableExists(_DB_PREFIX_ . 'moussiqfree_service'))
			Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . 'moussiqfree_service`');
            
		if (self::tableExists(_DB_PREFIX_ . 'moussiqfree_service_categories'))
			Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . 'moussiqfree_service_categories`');
            
        Configuration::deleteByName('EXPORTFREE_LANGUAGE');
        Configuration::deleteByName('EXPORTFREE_DELIMITER');
        Configuration::deleteByName('EXPORTFREE_ENCLOSURE');
        Configuration::deleteByName('EXPORTFREE_HEADER');
        Configuration::deleteByName('EXPORTFREE_COUNTRY');
        Configuration::deleteByName('EXPORTFREE_STATE');
        Configuration::deleteByName('EXPORTFREE_STORE');
        Configuration::deleteByName('EXPORTFREE_CARRIER');
        Configuration::deleteByName('EXPORTFREE_INACTIVE');
		
		return parent::uninstall();
	}
    
	/*
	 * Checks if table exists in the database
	 *
	 * @access private
	 * @scope static
	 * @param string $table    - Table name to check
	 *
	 * @return boolean
	 */
    public static function tableExists($table, $useCache = true)
    {
        if ( ! sizeof(self::$_tblCache) || ! $useCache)
        {
            $tmp = Db::getInstance()->ExecuteS('SHOW TABLES');
        
            foreach ($tmp as $entry)
            {
                reset($entry);
                
                $tableTmp = strtolower($entry[key($entry)]);
                
                if ( ! array_search($tableTmp, self::$_tblCache))
                    self::$_tblCache[] = $tableTmp;
            }
        }
        
        return array_search(strtolower($table), self::$_tblCache) ? true : false;
    }
    
	/*
	 * Copies Moussiq logo to img/t, so that it would display in the backoffice
	 * like other tabs do.
	 *
	 * @access private
	 * @param string $class    - Class name, like "AdminCatalog"
	 *
	 * @return boolean
	 */
    private function copyLogo($class)
    {
        return @copy(dirname(__FILE__) . '/logo.gif', _PS_IMG_DIR_ . 't/' . $class . '.gif');
    }
    
	
	/*
	 * Creates a "subtab" in "Catalog" tab.
	 *
	 * @access private
	 * @param string $class    - Class name, like "AdminCatalog"
	 * @param string $name     - Tab title
	 *
	 * @return boolean
	 */
    private function installModuleTab($class, $name)
    {
		$sql = '
		SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "AdminCatalog"';
		
		$tabParent = (int)(Db::getInstance()->getValue($sql));
		
        if ( ! is_array($name))
            $name = self::getMultilangField($name);
            
        if (self::fileExistsInModulesDir('logo.gif') && is_writeable(_PS_IMG_DIR_ . 't/'))
            $this->copyLogo($class);
                
        $tab = new Tab();
        $tab->name       = $name;
        $tab->class_name = $class;
        $tab->module     = $this->name;
        $tab->id_parent  = $tabParent;
        
        return $tab->save();
    }
    
	/*
	 * Turns a string into an array with language IDs as keys. This array can
	 * be used to create multilingual fields for prestashop
	 *
	 * @access private
	 * @scope static
	 * @param mixed $field    - A field to turn into multilingual
	 *
	 * @return array
	 */
    private static function getMultilangField($field)
    {
        $languages = Language::getLanguages();
        $res = array();
        
        foreach ($languages as $lang)
            $res[$lang['id_lang']] = $field;
            
        return $res;
    }
    
	/*
	 * Tests if a file exists in /modules/moussiq
	 *
	 * @access private
	 * @scope static
	 * @param string $file    - A file to look for
	 *
	 * @return array
	 */
    private static function fileExistsInModulesDir($file)
    {
        return file_exists(dirname(__FILE__) . '/' . $file);
    }
    
    
    
    public static function getTranslation($string)
    {
        return array_key_exists($string, self::$_miscTranslations) ? self::$_miscTranslations[$string] : $string;
    }
    
    public function hookBackOfficeHeader($params)
    {
        global $currentIndex;
        
        if ((Tools::getIsset('tab') && Tools::getValue('tab') == 'AdminMoussiqFree')
            || (Tools::getIsset('controller') && (Tools::getValue('controller') == 'adminmoussiqfree' ||Tools::getValue('controller') == 'AdminMoussiqFree')))
        {
            return '
            <script type="text/javascript">
                var labelStateSelect = "' . $this->l('State') . '",
                    stateDefault = ' . (int)Configuration::get('EXPORTFREE_STATE') . ';
            </script>
            <script type="text/javascript" src="' . _MODULE_DIR_ . '/' . $this->name . '/js/admin.js"></script>';
        }
    }
    
    public function getContent()
    {
        global $currentIndex, $cookie;
        
        $tab = 'AdminMoussiqFree';
        
        Tools::redirectAdmin(str_replace(strrchr($currentIndex, '?'), '', $currentIndex) . '?tab=' . $tab . '&token=' . Tools::getAdminTokenLite($tab));
    }

    public function _outputErrors()
    {
        if (sizeof($this->_postErrors)) 
            foreach ($this->_postErrors as $error) 
                echo $this->displayError($error);
    }
}
?>
