<?php
abstract class Export
{
    public           $service_id;
    public           $name;
    public           $extension;
    protected        $filename;
    protected        $_header;
    protected static $_serviceData;
    protected static $_billingMode;
    protected static $_shippingCarrierData;
    protected static $_specificPrices;
    protected static $_productPriorities;
    
    protected static $_productCategories;
    protected static $_exportCategories;
    protected static $_exportStartTime;
    protected static $_existingPictures;
    protected static $_existingCategories;
    protected static $_taxRates;
    protected static $_exportCountry;
    protected static $_exportCurrency;
    protected static $_exportState;
    protected static $_exportCondition;
    protected static $_exportGroup;
    protected static $_exportStore;
    protected static $_exportShop;
    protected static $_exportLanguage;
    protected static $_carrierId;
    protected static $_carrierTax;
    protected static $_carrierMethod;
    protected static $_zone;
    protected static $_sef;
    private static $_exportLanguageObj;
    
    protected static $_link;
    
    protected $_fileContents;
    
    
    public function __construct($id_service, $filename = false)
    {
        $this->name = Tools::isEmpty($this->name) ? 'Unknown Export Engine' : $this->name;
        
        if (Validate::isUnsignedId($id_service))
        {
            $this->service_id = (int)$id_service;
            
            if ( ! $filename)
                $filename = sha1($this->getServiceName() . _COOKIE_KEY_);
                
            $this->filename = _PS_ROOT_DIR_ . '/modules/moussiqfree/export/' . $filename . '.' . $this->extension;
            
            self::$_link = new Link();
            
            self::$_serviceData = self::getCsvDefaults((int)$id_service);
        }
    }
    
    private function getServiceName()
    {
        return Db::getInstance()->getValue('SELECT `name` FROM `' . _DB_PREFIX_ . 'moussiqfree_service` WHERE `id_moussiqfree_service` = ' . $this->service_id);
    }
    
    public static function setExportEngine($engineName, $serviceId)
    {
        $enginePath = _PS_ROOT_DIR_ . '/modules/moussiqfree/engines/' . $engineName . '.php';
        
        if ( ! file_exists($enginePath))
        {
            return false;
        }
        
        require_once($enginePath);
        
        if (class_exists($engineName, false))
            return new $engineName($serviceId);
        
        return false;
    }

    public function getCurrencyByCountry($id_country) {
        return Db::getInstance()->getValue('SELECT `id_currency` FROM `' . _DB_PREFIX_ . 'country` WHERE `id_country` = ' . $id_country);
    }


    
    public function startImport()
    {
        global $cookie;
        
        @ini_set('max_execution_time', 0);
        ini_set('memory_limit','256M');
        
        self::$_exportStartTime      = time();
        
        $this->_header               = self::$_serviceData['header'] == 1;
        
        self::$_sef                  = (int)(Configuration::get('PS_REWRITING_SETTINGS'));
        self::$_exportStore          = (int)self::$_serviceData['id_store'];
        self::$_exportShop          = (int)self::$_serviceData['id_shop'];
        self::$_exportGroup          = (int)self::$_serviceData['id_group'];
        self::$_exportLanguage       = (int)self::$_serviceData['id_lang'];
        self::$_carrierId            = (int)self::$_serviceData['id_carrier'];
        self::$_carrierTax           = self::getShippingTax(self::$_carrierId, self::$_exportShop);
        $carrier = new Carrier((int)self::$_carrierId);
        self::$_carrierMethod        = $carrier->getShippingMethod();
        self::$_exportCountry        = (int)self::$_serviceData['id_country'];
        self::$_exportCurrency       = (int)self::getCurrencyByCountry(self::$_exportCountry);
        self::$_exportState          = (int)self::$_serviceData['id_state'];
        self::$_exportCondition      = self::$_serviceData['condition'];
        self::$_zone                 = Country::getIdZone(self::$_exportCountry);
        self::$_billingMode          = (int)(Configuration::get('PS_SHIPPING_METHOD'));
        self::$_shippingCarrierData  = self::getCarrierShippingRanges();
        self::$_existingPictures     = self::getExistingPictures();
        self::$_existingCategories   = self::getExistingCategories(self::$_exportLanguage);
        self::$_specificPrices       = self::getSpecificPrices(self::$_exportShop);
        self::$_productPriorities    = self::getProductsPriorities();
        self::$_taxRates             = self::getTaxes();
        self::$_exportCategories     = MoussiqFreeService::getCategories($this->service_id);
        self::$_productCategories    = self::getProductCategories();
        
        self::$_exportLanguageObj = new Language(self::$_exportLanguage);

        if (self::$_exportCurrency == 0) {
            self::$_exportCurrency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
        }
        
        if (sizeof(self::$_productCategories))
        {
            $chunk_size = 3000;
            $current_size = 0;
            $this->beforeImport(self::$_serviceData['template'], array());
            do {
                $products = self::getProducts(self::$_exportLanguage, $current_size, $chunk_size, 'date_add', 'ASC', false, self::$_serviceData['export_inactive'] == true ? false : true, self::$_exportCountry, self::$_exportShop, self::$_exportCondition);
                $current_size += $chunk_size;

    
                $fileName = $this->filename;
                $fileDir  = dirname(__FILE__) . '/../export/';

                if ( ! self::checkDir($fileDir))
                {
                    $this->_errors[] = Tools::displayError('The directory is not writeable');

                    return false;
                }

                foreach ($products as $product)
                {
                    if (array_key_exists($product['id_product'], self::$_productCategories))
                    {
                        $product['categories'] = self::$_productCategories[$product['id_product']];
                        $product['reduction']  = self::getProductSpecificPrice($product['id_product'], self::$_exportStore, Configuration::get('PS_CURRENCY_DEFAULT'), self::$_exportCountry, self::$_exportGroup);

                        $product['quantity'] = (int)StockAvailable::getQuantityAvailableByProduct($product['id_product'], null, self::$_exportShop);

                        //to fix, get cover id_image
                        $product['id_image'] = self::getProductCoverWs($product['id_product']);
                        $product['id_product_image'] = $product['id_product'];

                        $features = self::collectFeatures(self::$_exportLanguage, $product['id_product']);
                        if (is_array($features)) {
                            foreach($features as $id_feature => $feature) {
                                $product['ft'.$id_feature] = trim($feature);
                            }
                        }


                        $this->addProductLine($product, self::$_serviceData['template']);
                    }
                }
            } while ($chunk_size == count($products));
            $this->postProcess();
            
            return $this->saveFile();
        }
    }

    public static function getProductCoverWs($id_product) {
        $result = Product::getCover($id_product);
        return $result['id_image'];
    }

    public static function getProducts($id_lang, $start, $limit, $order_by, $order_way, $id_category = false,
                                       $only_active = false, $country_id=null, $id_shop=null, $condition=null)
    {

        $front = true;

        if (!Validate::isOrderBy($order_by) || !Validate::isOrderWay($order_way))
            die (Tools::displayError());
        if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd')
            $order_by_prefix = 'p';
        else if ($order_by == 'name')
            $order_by_prefix = 'pl';
        else if ($order_by == 'position')
            $order_by_prefix = 'c';

        if (strpos($order_by, '.') > 0)
        {
            $order_by = explode('.', $order_by);
            $order_by_prefix = $order_by[0];
            $order_by = $order_by[1];
        }

        $product_condition = '';
        if ($condition != null && $condition != -1) {
            $cond = explode(':', $condition);
            if (is_array($cond)) {
                $str = array();
                foreach($cond as $c) {
                    $str[] = "'".$c."'";
                }
                $product_condition = ' AND product_shop.`condition` in ('.implode(', ', $str).') ';
            } else {
                $product_condition = ' AND product_shop.`condition` = \''.$cond.'\' ';
            }
        }

        $sql = 'SELECT p.*, product_shop.*, pl.* , t.`rate` AS tax_rate, m.`name` AS manufacturer_name, s.`name` AS supplier_name
				FROM `'._DB_PREFIX_.'product` p
				'.self::addShopSqlAssociation('product', 'p', true, null, false, $id_shop).'
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl', $id_shop).')
				LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON (product_shop.`id_tax_rules_group` = tr.`id_tax_rules_group`
		 		  AND tr.`id_country` = '.$country_id.'
		 		  AND tr.`id_state` = 0)
	  		 	LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.`id_tax` = tr.`id_tax`)
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
				LEFT JOIN `'._DB_PREFIX_.'supplier` s ON (s.`id_supplier` = p.`id_supplier`)'.
            ($id_category ? 'LEFT JOIN `'._DB_PREFIX_.'category_product` c ON (c.`id_product` = p.`id_product`)' : '').'
				WHERE pl.`id_lang` = '.(int)$id_lang.
            ($id_category ? ' AND c.`id_category` = '.(int)$id_category : '').
            ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').
            ($only_active ? ' AND product_shop.`active` = 1' : '').
            $product_condition.
            ' ORDER BY '.(isset($order_by_prefix) ? pSQL($order_by_prefix).'.' : '').'`'.pSQL($order_by).'` '.pSQL($order_way).
            ($limit > 0 ? ' LIMIT '.(int)$start.','.(int)$limit : '');
        $rq = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if ($order_by == 'price')
            Tools::orderbyPrice($rq, $order_way);
        return ($rq);
    }


    public static function addShopSqlAssociation($table, $alias, $inner_join = true, $on = null, $force_not_default = false, $id_shop=null)
    {
        $table_alias = $table.'_shop';
        if (strpos($table, '.') !== false)
            list($table_alias, $table) = explode('.', $table);

        $asso_table = Shop::getAssoTable($table);
        if ($asso_table === false || $asso_table['type'] != 'shop')
            return;
        $sql = (($inner_join) ? ' INNER' : ' LEFT').' JOIN '._DB_PREFIX_.$table.'_shop '.$table_alias.'
		ON ('.$table_alias.'.id_'.$table.' = '.$alias.'.id_'.$table;
        if ((int)$id_shop)
            $sql .= ' AND '.$table_alias.'.id_shop = '.(int)$id_shop;
        elseif (Shop::checkIdShopDefault($table) && !$force_not_default)
            $sql .= ' AND '.$table_alias.'.id_shop = '.$alias.'.id_shop_default';
        else
            $sql .= ' AND '.$table_alias.'.id_shop IN ('.implode(', ', Shop::getContextListShopID()).')';
        $sql .= (($on) ? ' AND '.$on : '').')';
        //echo '****'.$sql.'****';
        return $sql;
    }



    public function beforeImport($template, $products) {
        return true;
    }
    
    
    public function saveFile()
    {
        if ( ! $handle = fopen($this->filename, 'wb')) 
        {
            $this->_errors[] = $this->l('Unable to open or create the CSV file');
            
            return false;
        }
        
        fwrite($handle, $this->_fileContents);
        fclose($handle);
        @chmod($this->filename, 0777);

        return true;
    }
    
    
    public function postProcess(){
        return true;
    }
    
    
    public function addProductLine($product, $template, $params = false)
    {
        return $this->prepareProductInfo($product, $template, $params);
    }
    
    
    public function prepareProductInfo($product, $template, $params = false)
    {
        $preparedInfo = array();
        $url          = $_SERVER['SCRIPT_NAME'];
        $context      = Context::getContext();

        foreach ($template->fields as $field) 
        {
            $fieldName      = self::prepareTemplateMiscFields($field->field);
            $fieldTitle     = self::prepareTemplateMiscFields($field->fieldTitle, $field->field);
            $before         = self::prepareTemplateMiscFields($field->before);
            $after          = self::prepareTemplateMiscFields($field->after);
            $value          = self::prepareTemplateMiscFields($field->value);
            $codFee         = self::prepareTemplateMiscFields($field->fee, false, true);
            $withTax        = (int)(self::prepareTemplateMiscFields($field->withTax, false, true)) == 1;
            $withShipping   = (int)(self::prepareTemplateMiscFields($field->withShipping, false, true)) == 1;
            $withReduction  = (int)(self::prepareTemplateMiscFields($field->withReduction, false, true)) == 1;
            if (isset($field->allPictures)) {
                $allPictures  = (int)(self::prepareTemplateMiscFields($field->allPictures, false, true)) == 1;
            } else {
                $allPictures = false;
            }
            
            $preparedValue  = array(
                'title'         => $fieldTitle,
                'before'        => $before,
                'after'         => $after,
                'originalValue' => $value
            );

            if ($product) 
            {
                $taxRate    = array_key_exists($product['id_tax_rules_group'], self::$_taxRates) ? self::$_taxRates[$product['id_tax_rules_group']] : 0;
                $fieldPrice = $priceNoTax = $price = $product['price'];
                $weight     = $product['weight'];
                $taxedPrice = self::getPriceWithTax($price, $taxRate);
                
                if ($withTax)
                    $fieldPrice = self::getPriceWithTax($price, $taxRate);


                // attribute price without TAX in db
                if (isset($product['attribute_price'])) 
                {
                    $taxedPrice+= self::getPriceWithTax($product['attribute_price'], $taxRate);
                    $priceNoTax+= $product['attribute_price'];
                    $fieldPrice = ($withTax == 1) ? $taxedPrice : $priceNoTax;
                    $weight     = $weight + $product['attribute_weight'];
                }
                
                $taxedReduction = $reductionPrice = 0;
                
                if (array_key_exists('reduction', $product) && $product['reduction'] !== false)
                {
                    $reduction = $product['reduction'];
                    
                    $reductionPrice = self::getProductReduction(
                        $reduction['reduction_type'], 
                        $reduction['reduction'], 
                        $fieldPrice, 
                        $taxRate
                    );
                    
                    $taxedReduction = ! $withTax ? self::getPriceWithTax($reductionPrice, $taxRate) : $reductionPrice;
                }

                $shippingPrice = self::calculateCarrierShippingPrice(
                    ($taxedPrice - $reductionPrice),
                    $product['weight']
                );
                
                $shippingPrice = $withTax ? self::getPriceWithTax($shippingPrice, self::$_carrierTax) : $shippingPrice;
                
                $skipFields = self::getFromParams($params, 'skip');
                $formatAsNumbers = self::getFromParams($params, 'numbers');
                $stripNewLines = self::getFromParams($params, 'nolinebreak');

                if (array_key_exists($fieldName, $product) && ( ! $skipFields || ! in_array($fieldName, $skipFields))) 
                {
                    switch ($fieldName)
                    {
                        case 'tax_rate':
                                $product[$fieldName] = $taxRate;
                            break;
                        case 'condition':
                                $product[$fieldName] = MoussiqFree::getTranslation($product[$fieldName]);
                            break;
                    }
                    if ($formatAsNumbers && in_array($fieldName, $formatAsNumbers)) 
                       $product[$fieldName] = self::formatNumber($product[$fieldName]);
                    
                    if ($stripNewLines && in_array($fieldName, $stripNewLines)) 
                        $product[$fieldName] = self::stripNewLines($product[$fieldName]);
                
                    $preparedValue['value'] = strip_tags($product[$fieldName]);
                } 
                else 
                {
                    switch($fieldName) 
                    {
                        case 'price_with_tax':
                            $preparedValue['value'] = self::formatNumber(Tools::convertPrice($taxedPrice, self::$_exportCurrency));
                            break;
                            
                        case 'price':
                            $preparedValue['value'] = $fieldPrice;

                            if ($withReduction && isset($reductionPrice))
                                $preparedValue['value'] = $preparedValue['value'] - $reductionPrice;

                            if ($withShipping)
                                $preparedValue['value'] = $preparedValue['value'] + $shippingPrice;

                            $preparedValue['value'] = self::formatNumber(Tools::convertPrice($preparedValue['value'], self::$_exportCurrency));
                            break;
                            
                        case 'reduction_price':
                            $preparedValue['value'] = self::formatNumber(Tools::convertPrice($reductionPrice, self::$_exportCurrency));
                            break;
                            
                        case 'total_tax':
                            $preparedValue['value'] = self::formatNumber(Tools::convertPrice($taxedPrice - $priceNoTax, self::$_exportCurrency));
                            break;
                            
                        case 'weight':
                            $preparedValue['value'] = self::formatNumber($weight);
                            break;
                            
                        case 'product_link':
                            $link = self::getProductLink(
                                $product,
                                self::$_exportShop,
                                $context
                            );
                            
                            $preparedValue['value'] = $link;
                            break;
                            
                        case 'picture_link':
                            $link = self::getPictureLink($product['link_rewrite'], $product['id_product_image'], $product['id_product'], array_key_exists('id_image', $product) ? $product['id_image'] : false, $allPictures);
                            
                            $preparedValue['value'] = $link;
                            break;
                            
                        case 'shipping_price':
                            $preparedValue['value'] = self::formatNumber(Tools::convertPrice($shippingPrice, self::$_exportCurrency));
                            break;
                            
                        case 'with_shipping_price':
                            $preparedValue['value'] = self::formatNumber(Tools::convertPrice($shippingPrice + $fieldPrice, self::$_exportCurrency));
                            break;
                            
                        case 'shipping_with_fee':
                            $preparedValue['value'] = self::formatNumber(Tools::convertPrice($shippingPrice + $codFee, self::$_exportCurrency));
                            break;
                            
                        case 'category_name':
                            if (sizeof($product['categories']) && array_key_exists($product['id_category_default'], $product['categories']))
                            {
                                $preparedValue['value'] = $product['categories'][$product['id_category_default']];
                            } else {
                                $preparedValue['value'] = '';
                            }
                            break;
                            
                        case 'empty_field':
                            $preparedValue['value'] = ($value !== false) ? $value : '';
                            break;
                            
                        default:
                            $preparedValue['value'] = '';
                            break;
                    } // switch
                }

                $preparedInfo[] = array('field' => $fieldName, 'data' => $preparedValue);
            } // if product
        } // template fields
        
        return $preparedInfo;
    }
    
    
    private static function getFromParams($params, $key)
    {
        if ($params && is_array($params) && array_key_exists($key, $params) && is_array($params[$key]))
            return $params[$key];
            
        return false;
    }
    
    
    static public function findCorrectDefault($dbValue, $configValue, $returnBool = false)
    {
        if ( ! $returnBool) 
            return ($dbValue == '' ? $configValue : $dbValue);
        
        return (intval(($dbValue == '') ? $configValue : $dbValue) == 1) ? true : false;
    }
    
    
    /*
     * Get default template settings (as some settings can have "use default" 
     * value, this method has to actually look what the default value is)
     *
     * @access public
     *
     * @scope  static
     *
     * @param  integer  $serviceId      - Service ID to get the data for
     *
     * @return array
     */
    static public function getCsvDefaults($serviceId)
    {
        $sql = '
        SELECT *
        FROM `' . _DB_PREFIX_ . 'moussiqfree_service`
        WHERE `id_moussiqfree_service` = ' . (int)($serviceId);
        
        if ( ! $result = Db::getInstance()->getRow($sql)) 
            return false;
        
        $configuration = Configuration::getMultiple(array(
            'EXPORTFREE_COUNTRY'  ,
            'EXPORTFREE_STATE'    ,
            'EXPORTFREE_CARRIER'  ,
            'EXPORTFREE_STORE'    ,
            'EXPORTFREE_SHOP'     ,
            'EXPORTFREE_GROUP'    ,
            'EXPORTFREE_DELIMITER',
            'EXPORTFREE_HEADER'   ,
            'EXPORTFREE_INACTIVE' ,
            'EXPORTFREE_LANGUAGE' ,
            'EXPORTFREE_ENCLOSURE',
        ));
        
        $data = $result;

        $data['id_country']      = (int)(self::findCorrectDefault($result['id_country']     , $configuration['EXPORTFREE_COUNTRY']));
        $data['id_state']        = (int)(self::findCorrectDefault($result['id_state']     , $configuration['EXPORTFREE_STATE']));
        $data['id_carrier']      = (int)(self::findCorrectDefault($result['id_carrier']     , $configuration['EXPORTFREE_CARRIER']));
        $data['id_store']        = (int)(self::findCorrectDefault($result['id_store']     , $configuration['EXPORTFREE_STORE']));
        $data['id_shop']        = (int)(self::findCorrectDefault($result['id_shop']     , $configuration['EXPORTFREE_SHOP']));
        $data['id_group']        = (int)(self::findCorrectDefault($result['id_group']     , $configuration['EXPORTFREE_GROUP']));
        $data['header']          = (int)(self::findCorrectDefault($result['header']         , $configuration['EXPORTFREE_HEADER']));
        $data['id_lang']         = (int)(self::findCorrectDefault($result['id_lang']        , $configuration['EXPORTFREE_LANGUAGE']));
        $data['export_inactive'] =        self::findCorrectDefault($result['export_inactive'], $configuration['EXPORTFREE_INACTIVE'], true);
        $data['name']            = pSQL($result['name']);
        $data['template']        = ExportTools::jsonDecode(addcslashes(base64_decode($result['template']), "\\"));
        
        if ( ! Country::containsStates($data['id_country']))
            $data['id_state'] = false;

        return $data;
    }
    
    
    /*
     * Transliteration method. A "just in case" thing, basically :)
     *
     * @access public
     *
     * @scope  static
     *
     * @param  string   $string         - A string to transliterate
     *
     * @return string
     */
    static public function transliterate($string)
    {
        if (function_exists('mb_strtolower')) 
            $string = mb_strtolower($string);
        else 
            $string = strtolower($string);
        
        $tr = array(
            "і" => "i" , "ґ" => "g"  ,
            "ё" => "yo", "№" => "#"  ,
            "є" => "e" , "ї" => "yi" ,
            "а" => "a" , "б" => "b"  ,
            "в" => "v" , "г" => "g"  ,
            "д" => "d" , "е" => "e"  ,
            "ж" => "zh", "з" => "z"  ,
            "и" => "i" , "й" => "y"  ,
            "к" => "k" , "л" => "l"  ,
            "м" => "m" , "н" => "n"  ,
            "о" => "o" , "п" => "p"  ,
            "р" => "r" , "с" => "s"  ,
            "т" => "t" , "у" => "u"  ,
            "ф" => "f" , "х" => "h"  ,
            "ц" => "ts", "ч" => "ch" ,
            "ш" => "sh", "щ" => "sch",
            "ъ" => "'" , "ы" => "yi" ,
            "ь" => ""  , "э" => "e"  ,
            "ю" => "yu", "я" => "ya" ,
            "ä" => "ae", "ü" => "ue" ,
            "ö" => "oe", "ß" => "s"
        );
        
        $replace = array(
            '\\', '/' , '|' , ',', 
            '.' , '!' ,  '@', '#', 
            '$' , '%' , '^' , '&', 
            '*' , "\n", "\r", "\t", 
            "\r\n",'<', '>' , '?'
        );
        
        return str_replace($replace, '', strtr($string, $tr));
    }
    
    
    /*
     * Formats number to be "user friendly"
     *
     * @access public
     *
     * @scope  static
     *
     * @param  float    $price          - Number to format
     *
     * @return string                   Formatted number
     */
    static public function formatNumber($number)
    {
        return number_format(floatval($number), 2, '.', '');
    }
    
    
    /*
     * Takes price and tax rate, applies tax to price and returns the result.
     *
     * @access public
     *
     * @scope  static
     *
     * @param  float    $price          - Price
     * @param  float    $taxRate        - Tax rate
     *
     * @return float
     */
    static public function getPriceWithTax($price, $taxRate)
    {
        return floatval($price * (1 + ($taxRate / 100)));
    }
    
    
    /*
     * Takes taxed price and tax rate and returns price before tax was applied.
     *
     * @access public
     *
     * @scope  static
     *
     * @param  float    $price          - Taxed price 
     * @param  float    $taxRate        - Tax rate
     *
     * @return float
     */
    static public function removeTaxFromPrice($price, $taxRate)
    {
        return floatval($price / (1 + ($taxRate / 100)));
    }
    
    
    /*
     * Strips all sorts of new lines from a given string.
     *
     * @access public
     *
     * @scope  static
     *
     * @param  string   $str            - String to strip new lines from
     *
     * @return string
     */
    static public function stripNewLines($str)
    {
        return (string)str_replace(array("\r", "\r\n", "\n"), '', $str);
    }
    
    
    /*
     * Checks if a directory is writeable
     *
     * @access public
     *
     * @scope  static
     *
     * @param  string   $dir            - Path to directory
     *
     * @return boolean
     */
    static public function checkDir($dir)
    {
        return is_writable($dir);
    }
    
    
    /*
     * Returns a link to a specified product thickbox picture.
     *
     * @access public
     *
     * @scope  static
     *
     * @param  integer  $productId      - ID of the product. 
     * @param  array    $pictures       - An array of existing pictures
     *
     * @return string
     */
    static public function getPictureLink($link_rewrite, $productId, $productcombId, $idImage = false, $allPictures = 0)
    {

        $legend = null;
        if ($idImage)
        {
            //echo '+'.$productId.' - '.$productcombId.' - '.$idImage.'<br>';
            if (($allPictures) && ($productId == $productcombId)) {
                //echo $productId.' - '.$idImage.'<br>';
                $links = array();
                //print_r(self::$_existingPictures[$productId]);
                foreach (self::$_existingPictures[$productId] as $key=>$value) {
                    $pictureName = $productId . '-' . self::$_existingPictures[$productId][$key]['id_image'];
                    $legend      = self::$_existingPictures[$productId][$key]['legend'];

                    if ( ! Validate::isLinkRewrite($legend))
                        $legend = strtolower(Tools::link_rewrite($legend));
                    $links[] = strtolower(self::getImageLink($link_rewrite, $pictureName, 'thickbox'));
                }
                return implode(',', $links);
            } else {
                $pictureName = $productId . '-' . $idImage;
                $legend = null;
                return strtolower(self::getImageLink($link_rewrite, $pictureName, 'thickbox'));
            }
        }
        elseif (array_key_exists($productId, self::$_existingPictures))
        {
            if ($allPictures && ($productId == $productcombId)) {
                $links = array();
                foreach (self::$_existingPictures[$productId] as $key=>$value) {
                    $pictureName = $productId . '-' . self::$_existingPictures[$productId][$key]['id_image'];
                    $legend      = self::$_existingPictures[$productId][$key]['legend'];

                    if ( ! Validate::isLinkRewrite($legend))
                        $legend = strtolower(Tools::link_rewrite($legend));
                    $links[] = strtolower(self::getImageLink($link_rewrite, $pictureName, 'thickbox'));
                }
                return implode(',', $links);
            } else {
                $pictureName = $productId . '-' . self::$_existingPictures[$productId][0]['id_image'];
                $legend      = self::$_existingPictures[$productId][0]['legend'];

                if ( ! Validate::isLinkRewrite($legend))
                    $legend = strtolower(Tools::link_rewrite($legend));
                return strtolower(self::getImageLink($link_rewrite, $pictureName, 'thickbox'));
            }
        }
        return '';

    }

    static public function getImageLink($name, $ids, $type = null)
    {
        $allow = (int)Configuration::get('PS_REWRITING_SETTINGS');
        //echo   '+'.$name.', '.$ids.', '.$type.'=';
        $not_default = false;
        // legacy mode or default image
        $theme = ((Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_.$ids.($type ? '-'.$type : '').'-'.(int)Context::getContext()->shop->id_theme.'.jpg')) ? '-'.Context::getContext()->shop->id_theme : '');

        if (!file_exists(_PS_PROD_IMG_DIR_.$ids.($type ? '-'.$type : '').$theme.'.jpg')) {
            $type = $type.'_default';
        }

        if ((Configuration::get('PS_LEGACY_IMAGES')
            && (file_exists(_PS_PROD_IMG_DIR_.$ids.($type ? '-'.$type : '').$theme.'.jpg')))
            || ($not_default = strpos($ids, 'default') !== false))
        {
            if ($allow == 1 && !$not_default)
                $uri_path = __PS_BASE_URI__.$ids.($type ? '-'.$type : '').$theme.'/'.$name.'.jpg';
            else
                $uri_path = _THEME_PROD_DIR_.$ids.($type ? '-'.$type : '').$theme.'.jpg';
        }
        else
        {
            // if ids if of the form id_product-id_image, we want to extract the id_image part
            $split_ids = explode('-', $ids);
            $id_image = (isset($split_ids[1]) ? $split_ids[1] : $split_ids[0]);
            $theme = ((Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_.Image::getImgFolderStatic($id_image).$id_image.($type ? '-'.$type : '').'-'.(int)Context::getContext()->shop->id_theme.'.jpg')) ? '-'.Context::getContext()->shop->id_theme : '');
            if ($allow == 1)
                $uri_path = __PS_BASE_URI__.$id_image.($type ? '-'.$type : '').$theme.'/'.$name.'.jpg';
            else
                $uri_path = _THEME_PROD_DIR_.Image::getImgFolderStatic($id_image).$id_image.($type ? '-'.$type : '').$theme.'.jpg';
        }

        return _PS_BASE_URL_.$uri_path;
    }
    
    
    /*
     * Returns link to product in store. Depending on store settings, will 
     * return search engine friendly link or a usual one.
     *
     * @access public
     *
     * @scope  static
     *
     * @param  boolean  $sef            - Search engine friendly link (on/off)
     * @param  integer  $productId      - ID of the product. 
     * @param  string   $rewrite        - Product's "link_rewrite"
     * @param  string   $ean13          - Product's ean code (used in sef links)
     *
     * @return string
     */
    static public function getProductLink($product, $id_shop = null, $context = false) {
        if ( ! $context) {
            $context = Context::getContext();
        }

        $dispatcher = Dispatcher::getInstance();

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && $id_shop !== null) {
            $shop = new Shop($id_shop);
        } else {
            $shop = $context->shop;
        }
        return $context->link->getProductLink($product['id_product_orig'], null, null, null, self::$_exportLanguageObj->id, $id_shop, $product['id_product_attribute']);
    }


    static public function calculateCarrierShippingPrice($price, $weight)
    {
        if (self::$_carrierMethod == Carrier::SHIPPING_METHOD_WEIGHT) {
            $freeShipping = self::getFreeWeightShippingRequirements();

            if ( ! $freeShipping === false && $weight > $freeShipping) {
                return 0;
            }

            foreach(self::$_shippingCarrierData as $range)
                if ($weight >= $range['from'] && $weight < $range['to'])
                    return (float)($range['price']);
        }
        if (self::$_carrierMethod == Carrier::SHIPPING_METHOD_PRICE) {
            $freeShipping = self::getFreePriceShippingRequirements();

            if ( ! $freeShipping === false && $price > $freeShipping) {
                return 0;
            }

            foreach(self::$_shippingCarrierData as $range)
                if ($price >= $range['from'] && $price < $range['to'])
                    return (float)($range['price']);
        }

        return 0;
    }
    
    
    /*
     * Returns product reduction price.
     *
     * @access public
     *
     * @scope  static
     *
     * @param  float    $rprice         - Reduction price from database (can be 0)
     * @param  float    $rpercent       - Reduction percent from database (can be 0)
     * @param  string   $date_from      - "Reduction from" date
     * @param  string   $date_to        - "Reduction to" date
     * @param  float    $product_price  - Product price without reduction
     * @param  float    $taxrate        - Product's tax rate
     *
     * @return float
     */
    static public function getProductReduction($type, $reduction, $product_price, $taxrate)
    {
        if ($type == 'amount' && $reduction > 0) 
        {
            if ($reduction >= $product_price) 
                $ret = $product_price;
            else 
                $ret = $reduction;
        } 
        elseif ($type == 'percentage' && $reduction > 0) 
        {
            $reduction*= 100;
            
            if ($reduction >= 100) 
                $ret = $product_price;
            else 
                $ret = $product_price * $reduction / 100;
        }

        $ret = isset($ret) ? $ret : 0;

        return $ret;
    }
    
    
    /*
     * Returns "free shipping starts at" value. Either price or weight.
     *
     * @access public
     *
     * @scope  static
     *
     * @param  integer  $billing        - 0 for price range or 1 for weight range
     *
     * @return mixed                    (Float or boolean false)
     */
    static public function getFreeShippingRequirements($billing)
    {
        if ($billing == 0) 
            $result = Configuration::get('PS_SHIPPING_FREE_PRICE');
        else 
            $result = Configuration::get('PS_SHIPPING_FREE_WEIGHT');
        
        return $result > 0 ? floatval($result) : false;
    }

    static public function getFreePriceShippingRequirements()
    {
        $result = Configuration::get('PS_SHIPPING_FREE_PRICE');
        return $result > 0 ? floatval($result) : false;
    }

    static public function getFreeWeightShippingRequirements()
    {
        $result = Configuration::get('PS_SHIPPING_FREE_WEIGHT');
        return $result > 0 ? floatval($result) : false;
    }
    
    /*
     * Collects all existing categories with names in given language
     *
     * @access public
     *
     * @scope  static
     *
     * @param  integer  $langId         - Language id
     *
     * @return array
     */
    static public function getExistingCategories($langId)
    {
        $sql = '
        SELECT c.`id_category`,
              cl.`name`
        FROM `' . _DB_PREFIX_ . 'category` c
        
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
            ON  (
                    c.`id_category` = cl.`id_category`
                )
                
        WHERE cl.`id_lang` = ' . intval($langId);
        
        $result = Db::getInstance()->ExecuteS($sql);
        
        $categories = array();
        
        if ($result) 
        {
            foreach ($result as $category) 
                $categories[$category['id_category']] = $category['name'];
        }
        
        return $categories;
    }


    static public function getTaxes()
    {
        if (substr(_PS_VERSION_, 0, 3) == '1.6') {
            $taxRules = Db::getInstance()->ExecuteS('
                SELECT *
                FROM `' . _DB_PREFIX_ . 'tax_rule`
                WHERE `id_country` = ' . self::$_exportCountry . '
                AND `id_state` IN (0, ' . self::$_exportState . ')
                ORDER BY `id_state` DESC'
            );

            $rows = array();

            if ($taxRules && sizeof ($taxRules))
            {
                foreach ($taxRules as $taxRule)
                {
                    if ( ! array_key_exists($taxRule['id_tax_rules_group'], $rows))
                        $rows[$taxRule['id_tax_rules_group']] = array();

                    $rows[$taxRule['id_tax_rules_group']][] = $taxRule;
                }
            }

            $taxes = array();

            $continueCurrent = false;

            foreach ($rows AS $taxRuleGroup => $groupTaxes)
            {
                foreach ($groupTaxes as $row)
                {

                    if ( ! array_key_exists($taxRuleGroup, $taxes))
                        $taxes[$taxRuleGroup] = array();

                    if ($row['id_state'] != 0)
                    {
                        switch($row['behavior'])
                        {
                            case PS_STATE_TAX:
                                $taxes[$taxRuleGroup] = new Tax($row['id_tax'], self::$_exportLanguage);

                                $continueCurrent = true;
                                break;

                            case PS_BOTH_TAX:
                                $taxes[$taxRuleGroup][] = new Tax($row['id_tax'], self::$_exportLanguage);
                                break;

                            case PS_PRODUCT_TAX:
                                break;
                        }

                        if ($continueCurrent)
                            continue 2;
                    }
                    else
                        $taxes[$taxRuleGroup][] = new Tax((int)$row['id_tax'], self::$_exportLanguage);
                }
            }

            $result = array();

            if (sizeof($taxes))
            {
                foreach ($taxes as $taxRule => $groupTaxes)
                {
                    if (is_object($groupTaxes))
                        $result[$taxRule] = (float)$groupTaxes->rate;
                    elseif (is_array($groupTaxes) && sizeof($groupTaxes))
                    {
                        $rate = 0;
                        foreach ($groupTaxes as $tax)
                        {
                            if (is_object($tax))
                            {
                                $rate+= (float)$tax->rate;
                            }
                        }

                        $result[$taxRule] = $rate;
                    }
                }
            }

            return $result;
        } else {
            $taxRules = Db::getInstance()->ExecuteS('
                SELECT *
                FROM `' . _DB_PREFIX_ . 'tax_rule`
                WHERE `id_country` = ' . self::$_exportCountry . '
                AND `id_state` IN (0, ' . self::$_exportState . ')
                ORDER BY `id_county` DESC, `id_state` DESC'
            );

            $rows = array();

            if ($taxRules && sizeof ($taxRules))
            {
                foreach ($taxRules as $taxRule)
                {
                    if ( ! array_key_exists($taxRule['id_tax_rules_group'], $rows))
                        $rows[$taxRule['id_tax_rules_group']] = array();

                    $rows[$taxRule['id_tax_rules_group']][] = $taxRule;
                }
            }

            $taxes = array();

            $continueCurrent = false;

            foreach ($rows AS $taxRuleGroup => $groupTaxes)
            {
                foreach ($groupTaxes as $row)
                {

                    if ( ! array_key_exists($taxRuleGroup, $taxes))
                        $taxes[$taxRuleGroup] = array();

                    if ($row['id_county'] != 0)
                    {
                        switch($row['county_behavior'])
                        {
                            case County::USE_BOTH_TAX:
                                $taxes[$taxRuleGroup][] = new Tax($row['id_tax'], self::$_exportLanguage);
                                break;

                            case County::USE_COUNTY_TAX:
                                $taxes[$taxRuleGroup] = array(new Tax($row['id_tax'], self::$_exportLanguage));

                                $continueCurrent = true;
                                break;

                            case County::USE_STATE_TAX:
                                break;
                        }

                        if ($continueCurrent)
                            continue 2;
                    }

                    elseif ($row['id_state'] != 0)
                    {
                        switch($row['state_behavior'])
                        {
                            case PS_STATE_TAX:
                                $taxes[$taxRuleGroup] = new Tax($row['id_tax'], self::$_exportLanguage);

                                $continueCurrent = true;
                                break;

                            case PS_BOTH_TAX:
                                $taxes[$taxRuleGroup][] = new Tax($row['id_tax'], self::$_exportLanguage);
                                break;

                            case PS_PRODUCT_TAX:
                                break;
                        }

                        if ($continueCurrent)
                            continue 2;
                    }
                    else
                        $taxes[$taxRuleGroup][] = new Tax((int)$row['id_tax'], self::$_exportLanguage);
                }
            }

            $result = array();

            if (sizeof($taxes))
            {
                foreach ($taxes as $taxRule => $groupTaxes)
                {
                    if (is_object($groupTaxes))
                        $result[$taxRule] = (float)$groupTaxes->rate;
                    elseif (is_array($groupTaxes) && sizeof($groupTaxes))
                    {
                        $rate = 0;
                        foreach ($groupTaxes as $tax)
                        {
                            if (is_object($tax))
                            {
                                $rate+= (float)$tax->rate;
                            }
                        }

                        $result[$taxRule] = $rate;
                    }
                }
            }

            return $result;
        }
    }
    
    
    /*
     * Collects all existing product pictures for future export
     *
     * @access public
     *
     * @scope  static
     *
     * @return array
     */
    static public function getExistingPictures()
    {
        $sql = '
        SELECT i.*, il.`legend`
        FROM `' . _DB_PREFIX_ . 'image` i
        LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (i.`id_image` = il.`id_image`)
        WHERE il.`id_lang` = ' . self::$_exportLanguage . '
        ORDER BY i.`id_product`,
                 i.`position`';
        
        $result = Db::getInstance()->ExecuteS($sql);
        
        $pictures = array();
        
        foreach ($result as $picture) 
        {
            $pictures[$picture['id_product']][] = array('id_image' => $picture['id_image'], 'legend' => $picture['legend']);
        }
        
        return $pictures;
    }
    
    public static function getProductsPriorities()
    {
        $priorities = array();
        
        $result = Db::getInstance()->ExecuteS('
            SELECT * FROM 
            (
                SELECT * FROM `' . _DB_PREFIX_ . 'specific_price_priority` ORDER BY `id_specific_price_priority` DESC
            ) AS `order_tmp`
            
            GROUP BY `id_product`');
        
        if ($result && sizeof($result))
            foreach ($result as $priority)
                $priorities[$priority['id_product']] = $priority['priority'];
        
        return $priorities;
    }
    
    public static function getProductPriority($id_product)
    {
        $priority = (is_array(self::$_productPriorities) && array_key_exists($id_product, self::$_productPriorities)) ? self::$_productPriorities[$id_product] : Configuration::get('PS_SPECIFIC_PRICE_PRIORITIES');

        return preg_split('/;/', $priority);
    }
    
    public static function getProductSpecificPrice($id_product, $id_shop, $id_currency, $id_country, $id_group)
    {
		if (array_key_exists($id_product, self::$_specificPrices))
		{
			$scores = array();
			$priorities = self::getProductPriority($id_product);

			foreach (self::$_specificPrices[$id_product] as $key => $value)
			{
				$score = 0;

				if ((($value['from'] != '0000-00-00 00:00:00' && self::$_exportStartTime >= strtotime($value['from'])) ||
						$value['from'] == '0000-00-00 00:00:00')  &&

					(($value['to'] != '0000-00-00 00:00:00' && self::$_exportStartTime <= strtotime($value['to'])) ||
						$value['to'] == '0000-00-00 00:00:00') ){
					$score+=1;
				}

				if ($value['from_quantity'] >= 0 && $value['from_quantity'] <= 1) {
					$score+=1;
				}

				if ($id_product_attribute > 0 && $value['id_product_attribute'] == $id_product_attribute) {
					$score+=1;
				} elseif ($id_product_attribute > 0 && $value['id_product_attribute'] == 0) {
					// leave score
				} else if ($id_product_attribute > 0 && $value['id_product_attribute'] != $id_product_attribute) {
					$score = -1;
				} else if ($id_product_attribute == 0 && $value['id_product_attribute'] > 0) {
					$score = -1;
				}

				foreach (($priorities) as $k => $field)
					if ((int)${$field} == (int)$value[$field])
						$score+= pow(2, $k + 1);

				if ($score >= 0) {
					$scores[$key] = $score;
				}
			}


			if ( ! sizeof($scores))
				return false;


			$max = (array_keys($scores, max($scores)));
			return self::$_specificPrices[$id_product][$max[0]];
		}

		return false;
    }
    
    public static function getSpecificPrices($id_shop = 0)
    {
        $prices = array();
        if (MoussiqFree::tableExists(_DB_PREFIX_ . 'specific_price' ))
        {
            $result = Db::getInstance()->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'specific_price` WHERE `id_shop` IN (0, '.(int)$id_shop.') ORDER BY from_quantity');
            
            if ($result && sizeof($result))
            {
                foreach ($result as $specificPrice)
                {
                    if ( ! array_key_exists($specificPrice['id_product'], $prices))
                        $prices[$specificPrice['id_product']] = array();
                        
                    array_push($prices[$specificPrice['id_product']], $specificPrice);
                }
                return $prices;
            }
        }
        
        return $prices;
    }
    
    protected static function getProductCategories()
    {
        if (sizeof(self::$_exportCategories))
        {
            $categories = Db::getInstance()->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'category_product` WHERE `id_category` IN (' . implode(',', array_keys(self::$_exportCategories)) . ')');
        }
        else
        {
            $categories = Db::getInstance()->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'category_product`');
        }
        $prepared = array();
        
        if ($categories && sizeof($categories))
        {
            foreach ($categories as $category)
            {
                if ( ! array_key_exists($category['id_product'], $prepared))
                    $prepared[$category['id_product']] = array();
                    
                $prepared[$category['id_product']][$category['id_category']] =
                    (isset(self::$_existingCategories[$category['id_category']])?self::$_existingCategories[$category['id_category']]:'');
            }
        }
        return $prepared;
    }

    static private function getCarrierShippingRanges()
    {
        $carrier = new Carrier((int)self::$_carrierId);

        // Get only carriers that are compliant with shipping method
        if (($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_WEIGHT && $carrier->getMaxDeliveryPriceByWeight((int)self::$_zone) === false)
            || ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_PRICE && $carrier->getMaxDeliveryPriceByPrice((int)self::$_zone) === false))
        {
            return array();
        }

        if ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_WEIGHT) {
            $table = 'range_weight';
        }
        if ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_PRICE) {
            $table = 'range_price';
        }

        if ( ! in_array($table, array('range_price', 'range_weight')))
            return array();

        $sql = '
        SELECT d.`id_' . $table . '` ,
               d.`id_carrier`        ,
               d.`id_zone`           ,
               d.`price`             ,
               r.`delimiter1`        ,
               r.`delimiter2`
        FROM `' . _DB_PREFIX_ . 'delivery` d

            LEFT JOIN `' . _DB_PREFIX_ . $table . '` r
            ON  r.`id_' . $table . '` = d.`id_' . $table . '`

        WHERE
                d.`id_' . $table . '` IS NOT NULL
            AND d.`id_' . $table . '` != 0
            AND d.price > 0
            AND d.id_carrier = '.(int)self::$_carrierId.'
            AND d.id_zone = '.(int)self::$_zone;

        $result = Db::getInstance()->ExecuteS($sql);

        $priceRanges = array();

        $i = 0;
        foreach ($result as $range)
        {
            $priceRanges[$i]['price'] = $range['price'];
            $priceRanges[$i]['from']  = $range['delimiter1'];
            $priceRanges[$i]['to']    = $range['delimiter2'];

            $i++;
        }

        return $priceRanges;
    }
    
    
    /*
     * Get tax rate for a specific carrier
     *
     * @access public
     *
     * @scope  static
     *
     * @param  integer  $carrier        - Carrier is for which to select tax rate
     *                                    for
     *
     * @return float
     */
    static public function getShippingTax($carrier, $id_shop)
    {
        if ( ! Validate::isUnsignedId($carrier))
            return 0;

        $sql = '
        SELECT t.`rate`
        FROM `' . _DB_PREFIX_ . 'carrier_tax_rules_group_shop` ctrgs
            LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr
            ON (ctrgs.`id_tax_rules_group` = tr.`id_tax_rules_group`)
            LEFT JOIN `' . _DB_PREFIX_ . 'tax` t
            ON  (
                    t.`id_tax` = tr.`id_tax`
                )
        WHERE ctrgs.`id_carrier` = ' . (int)($carrier) .' AND ctrgs.`id_shop` IN(0, '.(int)$id_shop.') ';

        $result = Db::getInstance()->getRow($sql);

        return isset($result['rate']) ? (float)($result['rate']) : 0;
    }
    
    

    static public function collectFeatures($id_lang, $id_product)
    {
        $sql = '
        SELECT fp.`id_feature`, fvl.`value`
			FROM `'._DB_PREFIX_.'feature_product` fp
			LEFT JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl
            ON  fp.`id_feature_value`=fvl.`id_feature_value`
			WHERE fp.`id_product` = '.(int)$id_product.' AND fvl.`id_lang`='.(int)$id_lang;
        $result = Db::getInstance()->ExecuteS($sql);
        $features = array();
        foreach ($result as $v) {
            $features[$v['id_feature']] = $v['value'];
        }
        return $features;
    }
    
    
    public static function getTaxRules()
    {
        
    }
    
    
    /*
     * Prepares additional template fields (eg. "Value Before", "Value After")
     * for export. 
     *
     * @access public
     *
     * @scope  static
     *
     * @param  mixed    $value          - Initial value, whatever was given by 
     *                                    the user
     * @param  mixed    $defValue       - A default value to put if the initial 
     *                                    value was empty.
     * @param  bool     $returnFloat    - If set to true, the second parameter
     *                                    is ignored, method returns float value
     *                                    of the first parameter 
     *
     * @return mixed
     */
    static public function prepareTemplateMiscFields($value, $defValue = false, $returnFloat = false)
    {
        if ( ! $returnFloat) 
            return stripslashes((strlen($value) == 0 || $value === 'undefined') ? $defValue : $value);
        
        return (float)(stripslashes($value));
    }
}
?>
