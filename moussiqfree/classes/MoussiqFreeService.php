<?php
class MoussiqFreeService extends ObjectModel
{
	public $id;
	public $name;
    public $id_lang;
	public $id_group;
	public $id_store;
    public $id_shop;
	public $id_country;
	public $id_state;
    public $condition;
    public $id_carrier;
	public $categories;
    public $export_inactive;
    public $delimiter;
    public $enclosure;
    public $header;
	public $export_engine;
    public $last_upd;
    public $template;
	public $status = true;
		
 	protected 	$fieldsRequired = array('name', 'template', 'export_engine');
 	protected 	$fieldsSize = array('name' => 128);
 	protected 	$fieldsValidate = array('name' => 'isGenericName');

	protected 	$table = 'moussiqfree_service';
	protected 	$identifier = 'id_moussiqfree_service';
    
	public function getFields()
	{
		parent::validateFields();
        
        if ($this->id)
            $fields[$this->identifier] = (int)($this->id);
		
        $fields['name']            = pSQL($this->name);
        $fields['template']        = base64_encode($this->template);
        $fields['id_lang']         = $this->getIdOrNull('id_lang');
		$fields['id_group']        = $this->getIdOrNull('id_group');
		$fields['id_store']        = $this->getIdOrNull('id_store');
        $fields['id_shop']         = $this->getIdOrNull('id_shop');
        $fields['id_country']      = $this->getIdOrNull('id_country');
		$fields['id_state']        = $this->getIdOrNull('id_state');
        $fields['condition']       = pSQL($this->condition);
		$fields['id_carrier']      = $this->getIdOrNull('id_carrier');
        $fields['header']          = $this->getIdOrNull('header');
        $fields['enclosure']       = $this->getIdOrNull('enclosure');
        $fields['export_inactive'] = $this->getIdOrNull('export_inactive');
        $fields['delimiter']       = pSQL($this->delimiter);
		$fields['export_engine']   = pSQL($this->export_engine);
		$fields['last_upd']		   = isset($this->last_upd) ? pSQL($this->last_upd) : time();
        $fields['status']          = intval($this->status);
		return $fields;
	}
	
	private function getState()
	{
		$result = null;
		
		if ((int)$this->state == 0)
			return $result;

		if (Country::containsStates((int)$this->id_country))
		{
			$state = new State((int)$this->id_state);
			
			if ( ! Validate::isLoadedObject($state))
				$result = null;
			elseif ( ! $state->id_country == (int)$this->id_country)
				$result = null;
			else
				$result = (int)$state->id;
		}
		
		return $result;
	}
	
	public function updateCategories()
	{
		$categories = Tools::getValue('categoryBox', false);
		
		$preparedCategories = array();
		
		if ($categories)
			foreach ($categories as $category)
				if (array_key_exists('id_category', $category))
					$preparedCategories[] = $category;
		
		Db::getInstance()->Execute('DELETE FROM `' . _DB_PREFIX_ . 'moussiqfree_service_categories` WHERE `id_moussiqfree_service` = ' . (int)$this->id);
		
		if (sizeof($preparedCategories))
		{
			foreach ($preparedCategories as $category)
			{
				Db::getInstance()->Execute('
					INSERT INTO `' . _DB_PREFIX_ . 'moussiqfree_service_categories`
					(`id_moussiqfree_service`, `id_category`) VALUES
					(' . (int)$this->id . ', ' . (int)$category['id_category'] . ')');
			}
		}
	}
	
	public static function getCategories($serviceId)
	{
		$categories = array();
		
		if (Validate::isUnsignedId($serviceId))
		{
			$query = Db::getInstance()->ExecuteS('SELECT `id_category` FROM `' . _DB_PREFIX_ . 'moussiqfree_service_categories` WHERE `id_moussiqfree_service` = ' . (int)$serviceId);
			
			if ($query && sizeof($query))
				foreach ($query as $category)
					$categories[$category['id_category']] = $category;
		}

		return $categories;
	}
    
    private function getIdOrNull($field)
    {
        return (isset($this->{$field}) && (int)$this->{$field} >= 0) ? (int)($this->{$field}) : null;
    }
    
    public function save($nullValues = true, $autodate = true)
    {
        return parent::save($nullValues, $autodate);
    }
    
    public function update($nullValues = true)
    {
        if (parent::update($nullValues)) 
        {
			$this->updateCategories();
            require_once(dirname(__FILE__) . '/Export.php');
			
			if (is_object($engine = Export::setExportEngine($this->name, $this->id)))
			{
				$engine->startImport();
			}
            
            return true;
        }
        
		return false;
    }
	
	
	public function delete()
	{
		Db::getInstance()->Execute('DELETE FROM `' . _DB_PREFIX_ . 'moussiqfree_service_categories` WHERE `id_moussiqfree_service` = ' . (int)$this->id);
		
		return parent::delete();
	}
    
    
    public function add($autodate = false, $nullValues = true)
    {
        if (parent::add($autodate, $nullValues))
		{
			$this->updateCategories();
			
			return true;
		}
		
		return false;
    }
    
    public function toggleStatus()
    {
        if ( ! Validate::isUnsignedId($this->id)) 
            die(Tools::displayError());
        
        return (Db::getInstance()->Execute('
        UPDATE `' . _DB_PREFIX_ . $this->table . '`
        SET `status` = !`status`
        WHERE `' . $this->identifier . '` = ' . (int)($this->id)));
    }
}
?>