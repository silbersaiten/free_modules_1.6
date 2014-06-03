<?php
class ExportTools
{
	public static function jsonEncode($json)
	{
		if (function_exists('json_encode'))
			return json_encode($json);
		elseif (method_exists('Tools', 'jsonEncode'))
			return Tools::jsonEncode($json);
		else
		{
			include_once(dirname(__FILE__).'/json.php');
			$pearJson = new Services_JSON();
			return $pearJson->encode($data);
		}
	}
    
	public static function jsonDecode($json)
	{
		if (function_exists('json_decode'))
			return json_decode($json);
		elseif (method_exists('Tools', 'jsonDecode'))
			return Tools::jsonDecode($json);
		else
		{
			include_once(dirname(__FILE__).'/json.php');
			$pearJson = new Services_JSON(($assoc) ? SERVICES_JSON_LOOSE_TYPE : 0);
			return $pearJson->decode($json);
		}
	}

    /*
     * A helper method that turns letter abbreviations into correct delimiters.
     *
     * @access public
     *
     * @scope  static
     *
     * @param  string   $delimiter
     *
     * @return string
     */
    static public function delimiterByKeyWord($delimiter)
    {
        $replacePairs = array(
            'tab' => "\t",
            'com' => ',' ,
            'exc' => '!',
            'car' => '^',
            'bar' => '|',
            'td'  => '~'
        );
        
        $newDelimiter = strtr($delimiter, $replacePairs);
        
        if ($newDelimiter !== false) 
            return $newDelimiter;
        
        return $delimiter;
    }
    
    
    /*
     * Basically just a switch for now - will return double quote character
     * if $enclosure is 1 or a single quote is it's 2. In any other case 
     * will return double quote.
     *
     * @access public
     *
     * @scope  static
     *
     * @param  integer  $enclosure
     *
     * @return string
     */
    static public function getEnclosureFromId($enclosure)
    {
        switch (intval($enclosure)) 
        {
            case 1:
                return '"';
                break;
            case 2:
                return '\'';
                break;
            default:
                return '"';
                break;
        }
    }
}
?>
