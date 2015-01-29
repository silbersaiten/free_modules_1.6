<?php
class ProductManufacturer extends Module
{
	 private static $_prodCache = array();
	 private static $_manCache = array();
	 
	 function __construct()
	 {
		  $this->name = 'productmanufacturer';
		  $this->tab = 'Products';
		  $this->version = '0.5';
  		  $this->author = 'silbersaiten';
  
		  parent::__construct();
  
		  $this->displayName = $this->l('Product Manufacturer');
		  $this->description = $this->l('Show manufacturer of product');
	 }


	 function install()
	 {
		  if (parent::install() == false 
			   || $this->registerHook('productTab') == false
			   || $this->registerHook('productTabContent') == false
			   || $this->registerHook('extraRight') == false)
			return false;
		  
		return true;
	 }
	 
	 private static function getProduct()
	 {
		  $productId = Tools::getValue('id_product', false);
		  
		  if ( ! $productId)
			   return false;
		  
		  if (array_key_exists($productId, self::$_prodCache))
			   return self::$_prodCache[$productId];
		  elseif (Validate::isLoadedObject($product = new Product((int)$productId)))
		  {
			   self::$_prodCache[$productId] = $product;
			   
			   return self::$_prodCache[$productId];
		  }
		  
		  return false;
	 }
	 
	 private static function getManufacturer($manufacturerId, $languageId = false)
	 {
		  if ( ! Validate::isUnsignedId($manufacturerId)
			  || ($languageId && ! Validate::isUnsignedId($languageId)))
			   return false;
		  
		  if (array_key_exists($manufacturerId, self::$_manCache))
			   return self::$_manCache[$manufacturerId];
		  elseif (Validate::isLoadedObject($manufacturer = new Manufacturer((int)$manufacturerId, $languageId ? $languageId : null)))
		  {
			   self::$_manCache[$manufacturerId] = $manufacturer;
			   
			   return self::$_manCache[$manufacturerId];
		  }
		  
		  return false;
	 }
	 
	 
	 private static function getManufacturerImage(Manufacturer $manufacturer, $type = 'medium')
	 {
		  if (file_exists(_PS_MANU_IMG_DIR_ . $manufacturer->id . '-' . $type . '_default.jpg')) {
			   return _THEME_MANU_DIR_ . $manufacturer->id . '-' . $type . '_default.jpg';
          }
		  return false;
	 }
	
	
	 public function hookProductTab($params)
	 {
         global $smarty, $cookie;
         return  false;
	 }
	
	
	 public function hookProductTabContent($params)
	 {
		global $smarty, $cookie;

        return  false;
	 }
	
	
	 public function hookExtraRight($params)
	 {
		  global $smarty, $cookie;
	  
		  if ( ! $product = self::getProduct())
			   return false;
	  
		  if (isset($product->id_manufacturer)
			  && intval($product->id_manufacturer) != 0
			  && $manufacturer = self::getManufacturer($product->id_manufacturer, (int)$cookie->id_lang))
		  {
			   $image = self::getManufacturerImage($manufacturer, 'medium');
			   
			   $smarty->assign(
					array(
						 'productmanufacturer'        => $manufacturer, 
 						 'productmanufacturer_name' => $manufacturer->name, 
						 'id_manufacturer'            => $manufacturer->id, 
						 'productmanufacturer_jpg'    => $image
					)
			   );
			 
			   return ($this->display(__FILE__, '/manufacturer_logo.tpl'));
		  }
		  
		  return false; 
	 }
}

?>