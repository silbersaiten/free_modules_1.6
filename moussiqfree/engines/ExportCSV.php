<?php
class ExportCSV extends Export
{
    public static $_delimiter;
    public static $_enclosure;
    
    public function __construct($id_service, $filename = false)
    {
        $this->name = 'CSV';
        $this->extension = 'csv';
        
        parent::__construct($id_service, $filename);

        self::$_delimiter = trim(self::$_serviceData['delimiter']) == '' ? Configuration::get('EXPORTFREE_DELIMITER') : self::$_serviceData['delimiter'];
        self::$_enclosure = trim(self::$_serviceData['enclosure']) == '' ? Configuration::get('EXPORTFREE_ENCLOSURE') : self::$_serviceData['enclosure'];

        self::$_delimiter = ExportTools::delimiterByKeyWord(self::$_delimiter);
        self::$_enclosure = ExportTools::getEnclosureFromId(self::$_enclosure);
    }
    
    public function postProcess()
    {
        $this->_fileContents = implode("\n", $this->_fileContents);
    }
    
    public function prepareProductInfo($product, $template, $params = false)
    {
        return parent::prepareProductInfo($product, $template, array(
            'numbers' => array(
                'price',
                'wholesale_price',
                'tax_rate'
            ),
            'nolinebreak' => array(
                'name',
                'description',
                'description_short'
            ),
            'skip' => array(
                'price',
                'weight'
            )
        ));
    }
    
    public function addProductLine($product, $template, $params = false)
    {
        $product = parent::addProductLine($product, $template, $params);
        $line = array();
        
        foreach ($product as $fieldData)
        {
            $field  = $fieldData['field'];
            $data   = $fieldData['data'];
            
            $value  = $data['value'];
            $before = $data['before'];
            $after  = $data['after'];
            
            $value = (($before !== false) ? $before : '') . $value . (($after !== false) ? $after : '');
            
            array_push($line, self::prepareValueForCsv($value));
        }

        $this->_fileContents[] = implode(self::$_delimiter, $line);
    }
    
    public function beforeImport($template, $products)
    {
        $this->_fileContents = array();
        
        if (self::$_serviceData['header'] == 1)
        {
            $tmpArr = array();
    
            foreach ($template->fields as $field) 
            {
                $fieldName  = stripslashes($field->field);
                $fieldTitle = stripslashes($field->fieldTitle);
                
                if (strlen($fieldTitle) == 0) 
                    $fieldTitle = $fieldName;
                
                $fieldTitle = self::prepareValueForCsv($fieldTitle);
                $tmpArr[] = $fieldTitle;
            }
            
            $this->_fileContents[] = implode(self::$_delimiter, $tmpArr);
        }
    }
    
    /*
     * Prepares a value for CSV input. (Simply enclosing a value in quotes or 
     * double quotes is not correct). This method tries to do it properly.
     *
     * @access public
     *
     * @scope  static
     *
     * @param  mixed    $value          - A value to prepare
     * @param  string   $delimiter      - CSV field delimiter
     * @param  string   $returnBool     - Enclosure character (quote or double 
     *                                    quote) 
     *
     * @return string                   Prepared value
     */
    static public function prepareValueForCsv($value)
    {
        if (stripos($value, self::$_enclosure) !== false) 
            $value = str_replace(self::$_enclosure, self::$_enclosure . self::$_enclosure, $value); 

        if ((stripos($value, self::$_delimiter) !== false) 
        || (stripos($value, self::$_enclosure) !== false) 
        || (stripos($value, "\n" !== false))) 
        { 
            $value = self::$_enclosure . $value . self::$_enclosure;
        } 
        
        return $value;
    }
}
?>