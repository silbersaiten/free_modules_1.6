<?php
if ( ! defined('_PS_VERSION_'))
{
	exit;
}
	
class BlocknewsletterGermanext extends Module
{
    private $_mailTemplates = array(
        'newsletter_activation',
        'newsletter_conf',
        'newsletter_voucher'    
    );
	
	const REGISTERED_GN_NEWSLETTER   = 1;
	const NOT_REGISTERED_CUSTOMER    = -1;
	const REGISTERED_AND_SUBSCRIBED  = 2;
	const REGISTERED_NOT_SUBSCRIBED  = -3;
    
    public function __construct()
    {
		global $cookie;
		
        $this->name = 'blocknewslettergermanext';
		$this->version = '1.6';
		$this->author = 'silbersaiten';
        $this->tab  = 'Silbersaiten';
        
        parent::__construct();
        
        $this->displayName = $this->l('Germanext Newsletter block');
        $this->description = $this->l('Adds a block for newsletter subscription');
        $this->confirmUninstall = $this->l('Are you sure you want to delete all your contacts ?');
        $this->version = '1.5';
        
        $this->error = false;
        $this->valid = false;
        $this->_file = 'export_' . md5(_COOKIE_KEY_) . '.csv';
        $this->_postValid = array();
        
        // Getting data...
        $_countries = Country::getCountries((int)$cookie->id_lang);

        // ...formatting array
        $countries[0] = $this->l('All countries');
		
        foreach ($_countries as $country)
		{
            $countries[$country['id_country']] = $country['name'];
		}

        // And filling fields to show !
        $this->_fieldsExport = array(
            'COUNTRY'           => array(
                'title'         => $this->l('Customers\' country'),
                'desc'          => $this->l('Operate a filter on customers\' country.'),
                'type'          => 'select',
                'value'         => $countries,
                'value_default' => 0
            ),
            'SUSCRIBERS'        => array(
                'title'         => $this->l('Newsletter\'s suscribers'),
                'desc'          => $this->l('Filter newsletter subscribers.'),
                'type'          => 'select',
                'value'         => array(
                    0 => $this->l('All customers'), 
                    2 => $this->l('Subscribers'), 
                    1 => $this->l('Non-subscribers')
                ),
                'value_default' => 2
            ),
            'OPTIN'             => array(
                'title'         => $this->l('Opted-in subscribers'),
                'desc'          => $this->l('Filter opted-in subscribers.'),
                'type'          => 'select',
                'value'         => array(
                    0 => $this->l('All customers'), 
                    2 => $this->l('Subscribers'), 
                    1 => $this->l('Non-subscribers')
                ),
                'value_default' => 0
            ),
        );
    }
 
 
    public function install()
    {
        if ( ! parent::install() 
        || ! $this->registerHook('leftColumn') 
        || ! $this->registerHook('footer') 
        || ! $this->registerHook('header'))
            return false;
		
		$engine = defined('_MYSQL_ENGINE_') ? _MYSQL_ENGINE_ : 'MyISAM';
        
        $queries = array(
            'CREATE TABLE `' . _DB_PREFIX_ . 'newslettergermanext`
                (
                    `id`                         INT(6) NOT NULL AUTO_INCREMENT,
                    `email`                      VARCHAR(255) NOT NULL         ,
                    `newsletter_date_add`        DATETIME NULL                 ,
                    `ip_registration_newsletter` VARCHAR(15) NOT NULL          ,
                    `http_referer`               VARCHAR(255) NULL             ,
                    PRIMARY KEY(`id`)
                )
                ENGINE=' . $engine . ' DEFAULT CHARSET=utf8',
            
            'CREATE TABLE `' . _DB_PREFIX_ . 'newslettergermanext_request`
                (
                    `id`                         INT(6) NOT NULL AUTO_INCREMENT,
                    `email`                      VARCHAR(255) NOT NULL         ,
                    `newsletter_date_add`        DATETIME NULL                 ,
                    `ip_registration_newsletter` VARCHAR(15) NOT NULL          ,
                    `newsletter_hash`            VARCHAR(255) NOT NULL         ,
                    `http_referer`               VARCHAR(255) NULL             ,
                    PRIMARY KEY(`id`)
                )
                ENGINE=' . $engine . ' DEFAULT CHARSET=utf8'
        );
        
        foreach ($queries as $query)
		{
            if ( ! Db::getInstance()->Execute($query)) 
            {
                $this->uninstall();
                
                return false;
            }
		}
        
        Configuration::updateValue('NWGN_ACTIVATION_EMAIL', 1);
        Configuration::updateValue('NWGN_CONFIRMATION_NEW_PAGE', 0);
        Configuration::updateValue('NWGN_CONFIRMATION_EMAIL', 1);
        Configuration::updateValue('NWGN_VOUCHER_CODE', '');
        Configuration::updateValue('NWGN_INACTIVE_INTERVAL', 3);
        
        
        return true;
    }
 
 
    public function uninstall()
    {
        if ( ! parent::uninstall())
		{
            return false;
		}
        
        $tables = array('newslettergermanext', 'newslettergermanext_request');
        
        foreach ($tables as $table)
		{
            if ( ! Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . $table . '`'))
			{
                return false;
			}
		}
        
        Configuration::deleteByName('NWGN_ACTIVATION_EMAIL');
        Configuration::deleteByName('NWGN_CONFIRMATION_NEW_PAGE');
        Configuration::deleteByName('NWGN_CONFIRMATION_EMAIL');
        Configuration::deleteByName('NWGN_VOUCHER_CODE');
        Configuration::deleteByName('NWGN_INACTIVE_INTERVAL');
  
        return true;
    }
    
    
    /*
    * Checks if mail template for a given language exists 
    *
    * @access private
    *
    * @scope static
    *
    * @param integer $languageId        - Language ID
    */
    private static function checkMailTemplate($languageId, $template)
    {
        $filePath = dirname(__FILE__) . '/mails/' . Language::getIsoById($languageId) . '/';
        
        return (
            file_exists($filePath . $template . '.html') & 
            file_exists($filePath . $template . '.txt')
        );
    }
    
    
    /*
    * Checks if all the necessary mail templates for all available languages
    * are present in module's "mail" directory
    *
    * @access private
    *
    * @return void (outputs warning messages for missing templates)
    */
    private function checkMailTemplates()
    {
        $languages = Language::getLanguages();
        
        foreach ($this->_mailTemplates as $mailTemplate)
		{
            foreach ($languages as $language)
            {
                if ( ! self::checkMailTemplate($language['id_lang'], $mailTemplate))
				{
                    $this->setWarning(
                        sprintf(
                            '%s "%s" %s %s %s', 
                            $this->l('Missing'),
                            $mailTemplate,
                            $this->l('mail template for'),
                            $language['name'],
                            $this->l('language.')
                        )
                    );
				}
            }
		}
    }
 
 
    public function getContent()
    {
        $this->deleteInactivatedRequests();
        $this->_html = '<h2>' . $this->displayName . '</h2>';

        $this->_postProcess();
        $this->_displayForm();
        $this->displayExport();
        
        return $this->_html;
    }
    
    
    public function _postProcess()
    {
        $this->checkMailTemplates();
        
        if (Tools::getIsset('submitExport') && Tools::getIsset('action'))
        {
            if (Tools::getValue('action') == 'customers')
			{
                $result = $this->_getCustomers();
			}
            else
			{
                $result = $this->_getBlockNewsletter();
			}
            
            if ( ! $nb = (int)Db::getInstance()->NumRows())
			{
                $this->_html .= $this->displayError($this->l('No customers were found with these filters !'));
			}
            elseif ($fd = @fopen(dirname(__FILE__) . '/export/' . strval(preg_replace('#\.{2,}#', '.', Tools::getValue('action'))) . '_' . $this->_file, 'w'))
            {
                foreach ($result as $tab)
				{
                    $this->_my_fputcsv($fd, $tab);
				}
                    
                fclose($fd);
                $this->_html.= $this->displayConfirmation(
                $this->l('The .CSV file has been successfully exported').
                ' (' . $nb . ' ' . $this->l('customers found') . ')<br />> 
                <a href="../modules/' . $this->name . '/export/' . strval(Tools::getValue('action')) . '_' . $this->_file . '"><b>' . $this->l('Download the file') . ' ' . $this->_file . '</b></a>
                <br />
                <ol style="margin-top: 10px;">
                    <li style="color: red;">' . $this->l('WARNING: If you try to open this the .csv file with Excel, do no forget to choose UTF-8 encoding or you\'ll may see strange characters') . '</li>
                </ol>');
            }
            else
			{
                $this->_html .= $this->displayError($this->l('Error: cannot write to') . ' ' . dirname(__FILE__) . '/' . strval(Tools::getValue('action')) . '_' . $this->_file . ' !');
			}
        }
        
        if (Tools::isSubmit('submitUpdate')) 
        {
            $reqActivation       = Tools::getValue('reqActivation');
            $confirmationEmail   = Tools::getValue('confirmationEmail');
            $confirmationNewPage = Tools::getValue('confirmationNewPage');
            $inactiveInterval    = Tools::getValue('inactiveInterval');
            $voucher             = Tools::getValue('voucher');
            
            $errors = false;
            
            if ($reqActivation != 0 && $reqActivation != 1) 
            {
                $errors = true;
                $this->setWarning($this->l('Require activation: wrong choice'));
            }
            
            if ($confirmationEmail != 0 && $confirmationEmail != 1) 
            {
                $errors = true;
                $this->setWarning($this->l('Confirmation email: wrong choice'));
            }
            
            if ($confirmationNewPage != 0 && $confirmationNewPage != 1) 
            {
                $errors = true;
                $this->setWarning($this->l('Confirmation in a new page: wrong choice'));
            }
            
            if ($inactiveInterval < 0 || $inactiveInterval >= 20) 
            {
                $errors = true;
                $this->setWarning($this->l('Inactive interval must be within 0-20 range'));
            }
            
            if ($voucher != '' && ! Validate::isDiscountName($voucher)) 
            {
                $errors = true;
                $this->setWarning($this->l('Voucher code is invalid'));
            }
            
            if ( ! $errors) 
            {
                Configuration::updateValue('NWGN_ACTIVATION_EMAIL', (int)$reqActivation);
                Configuration::updateValue('NWGN_CONFIRMATION_NEW_PAGE', (int)$confirmationNewPage);
                Configuration::updateValue('NWGN_CONFIRMATION_EMAIL', (int)$confirmationEmail);
                Configuration::updateValue('NWGN_VOUCHER_CODE', pSQL($voucher));
                Configuration::updateValue('NWGN_INACTIVE_INTERVAL', (int)$inactiveInterval);
                
                $this->_html.= parent::displayConfirmation($this->l('Updated successfully'));
            }
        }
    }
    
    private function _getCustomers()
	{
		$rq = Db::getInstance()->ExecuteS('
        SELECT c.`id_customer`            ,
            c.`lastname`                  ,
            c.`firstname`                 ,
            c.`email`                     ,
            c.`ip_registration_newsletter`,
            c.`newsletter_date_add`
        FROM `' . _DB_PREFIX_ . 'customer` c
        WHERE 1 ' . ((Tools::getValue('SUSCRIBERS') != 0) ? '
        AND c.`newsletter` = ' . (int)(Tools::getValue('SUSCRIBERS') - 1) : '') . ' ' . ((Tools::getValue('OPTIN') != 0) ? '
        AND c.`optin`      = ' . (int)(Tools::getValue('OPTIN') - 1) : '') . ' ' . ((Tools::getValue('COUNTRY') != 0) ? '
        AND
            (SELECT COUNT(a.`id_address`) AS nb_country
                FROM `' . _DB_PREFIX_ . 'address` a
                WHERE a.`id_customer` = c.`id_customer`
                AND a.`id_country`    = ' . (int)(Tools::getValue('COUNTRY')) . '
            )
            >= 1' : '') . '
        GROUP BY c.`id_customer`');
        
		$header = array(
		    'id_customer', 
		    'lastname', 
		    'firstname', 
		    'email', 
		    'ip_address', 
		    'newsletter_date_add'
		);
		
		$result = (is_array($rq) ? array_merge(array($header), $rq) : $header);
		
		return $result;
	}

	private function _getBlockNewsletter()
	{
		$rq = Db::getInstance()->ExecuteS('
		SELECT *
		FROM `' . _DB_PREFIX_ . 'newslettergermanext`');
		$header = array(
		    'id_customer', 
		    'email', 
		    'newsletter_date_add', 
		    'ip_address'
		);
		
		$result = (is_array($rq) ? array_merge(array($header), $rq) : $header);
		
		return $result;
	}
	
	
	private function _my_fputcsv($fd, $array)
	{
		$line = implode(';', $array) . "\n";

		if ( ! fwrite($fd, $line, 4096))
		{
			$this->_postErrors[] = $this->l('Error: cannot write to') . ' ' . dirname(__FILE__) . '/' . $this->_file . ' !';
		}
	}
    
    
    private function setWarning($message)
    {
        $this->_html.= '<div class="alert">' . $message . '</div>';
    }
 
 
    private function _displayForm()
    {
        $this->_html .= '
        <form method="post" action="' . $_SERVER['REQUEST_URI'] . '">
            <fieldset>
                <legend><img src="' . $this->_path . 'logo.gif" />' . $this->l('Settings') . '</legend>
                
                <label>' . $this->l('Require activation by email') . '</label>
                <div class="margin-form">
                    <input type="radio" name="reqActivation" id="reqActivation_on" value="1" ' . (Tools::getValue('reqActivation', Configuration::get('NWGN_ACTIVATION_EMAIL')) ? 'checked="checked" ' : '') . '/>
                    <label class="t" for="reqActivation_on"> <img src="' . $this->_path . 'img/enabled.png" alt="'.$this->l('Enabled').'" title="' . $this->l('Enabled') . '" /></label>
                    <input type="radio" name="reqActivation" id="reqActivation_off" value="0" ' . (!Tools::getValue('reqActivation', Configuration::get('NWGN_ACTIVATION_EMAIL')) ? 'checked="checked" ' : '') . '/>
                    <label class="t" for="reqActivation_off"> <img src="' . $this->_path . 'img/delete.png" alt="' . $this->l('Disabled') . '" title="' . $this->l('Disabled') . '" /></label>
                    <p class="clear">' . $this->l('Choose whether your visitors should activate their subscription using a unique link sent to them by email') . '</p>
                </div>
                
                <label>' . $this->l('Send subscription confirmation letter') . '</label>
                <div class="margin-form">
                    <input type="radio" name="confirmationEmail" id="confirmationEmail_on" value="1" ' . (Tools::getValue('confirmationEmail', Configuration::get('NWGN_CONFIRMATION_EMAIL')) ? 'checked="checked" ' : '') . '/>
                    <label class="t" for="confirmationEmail_on"> <img src="' . $this->_path . 'img/enabled.png" alt="'.$this->l('Enabled').'" title="' . $this->l('Enabled') . '" /></label>
                    <input type="radio" name="confirmationEmail" id="confirmationEmail_off" value="0" ' . (!Tools::getValue('confirmationEmail', Configuration::get('NWGN_CONFIRMATION_EMAIL')) ? 'checked="checked" ' : '') . '/>
                    <label class="t" for="confirmationEmail_off"> <img src="' . $this->_path . 'img/delete.png" alt="' . $this->l('Disabled') . '" title="' . $this->l('Disabled') . '" /></label>
                    <p class="clear">' . $this->l('Send a confirmation letter to visitor after successfull subscription?') . '</p>
                </div>
                
                <label>' . $this->l('Display confirmation in a new page') . '</label>
                <div class="margin-form">
                    <input type="radio" name="confirmationNewPage" id="confirmationNewPage_on" value="1" ' . (Tools::getValue('confirmationNewPage', Configuration::get('NWGN_CONFIRMATION_NEW_PAGE')) ? 'checked="checked" ' : '') . '/>
                    <label class="t" for="confirmationNewPage_on"> <img src="' . $this->_path . 'img/enabled.png" alt="'.$this->l('Enabled').'" title="' . $this->l('Enabled') . '" /></label>
                    <input type="radio" name="confirmationNewPage" id="confirmationNewPage_off" value="0" ' . (!Tools::getValue('confirmationNewPage', Configuration::get('NWGN_CONFIRMATION_NEW_PAGE')) ? 'checked="checked" ' : '') . '/>
                    <label class="t" for="confirmationNewPage_off"> <img src="' . $this->_path . 'img/delete.png" alt="' . $this->l('Disabled') . '" title="' . $this->l('Disabled') . '" /></label>
                    <p class="clear">' . $this->l('Display confirmation messages in a new page') . '</p>
                </div>
                
                <label>' . $this->l('Inactive subscriptions deletion interval') . '</label>
                <div class="margin-form">
                    <input type="text" size="3" name="inactiveInterval" value="' . (Tools::getValue('inactiveInterval', Configuration::get('NWGN_INACTIVE_INTERVAL'))) . '" /> day(s)
                    <p class="clear">' . $this->l('If a request has not been activated during specified amount of days, it will be deleted automatically. Set 0 to disable.') . '</p>
                </div>
                
                <label>' . $this->l('Welcome voucher code') . '</label>
                <div class="margin-form">
                    <input type="text" name="voucher" value="' . (Tools::getValue('voucher', Configuration::get('NWGN_VOUCHER_CODE'))) . '" />
                    <p class="clear">' . $this->l('Leave blank for disabling') . '</p>
                </div>
                <center><input type="submit" name="submitUpdate" value="' . $this->l('Update') . '" class="button" /></center>
            </fieldset>
        </form>';
        
        return $this->_html;
    }
    
    private function displayExport()
    {
        $this->_html .= '
		<fieldset style="margin-top: 1em;">
		    ' . $this->l('There are two sorts for this module:') . '
		    <p>
		        <ol>
			        <li>
				        1. ' . $this->l('Persons who have subscribed using the BlockNewsletter block in the front office.') . '<br />
                        ' . $this->l('This will be a list of email addresses for persons coming to your store and not becoming a customer but wanting to get your newsletter. Using the "Export Newsletter Subscribers" below will generate a .CSV file based on the BlockNewsletter subscribers data.') . '<br /><br />'.'
                    </li>
                    <li>
                        2. ' . $this->l('Customers that have checked "yes" to receive a newsletter in their customer profile.') . '<br />
                        ' . $this->l('The "Export Customers" section below filters which customers you want to send a newsletter.') . '
                    </li>
                </ol>
		    </p>
        </fieldset>
        
        <fieldset style="margin-top: 1em;">
            <legend>
                <img src="' . $this->_path . 'img/subscribers.png" /> 
                ' . $this->l('Export Newsletter Subscribers') . '
            </legend>
            
            <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
			    <input type="hidden" name="action" value="blockNewsletter">
			    ' . $this->l('Generate a .CSV file based on BlockNewsletter subscribers data.') . '.<br /><br />';
		$this->_html.= '
		        <br />
		        <center>
		            <input type="submit" class="button" name="submitExport" value="' . $this->l('Export .CSV file') . '" />
		        </center>
            </form>
        </fieldset>
        
		<fieldset style="margin-top: 1em;">
		    <legend>
		        <img src="' . $this->_path . 'img/customers.png" /> 
		        ' . $this->l('Export customers') . '
		    </legend>
		    
            <form action="'.$_SERVER['REQUEST_URI'].'" method="post">
			    <input type="hidden" name="action" value="customers">
			    ' . $this->l('Generate an .CSV file from customers account data') . '.<br /><br />';
			    
		        foreach ($this->_fieldsExport as $key => $field)
		        {
			        $this->_html.= '
			        <label style="margin-top:15px;">' . $field['title'] . ' </label>
			        <div class="margin-form" style="margin-top:15px;">
				        ' . $field['desc'] . '<br /><br />';
				        
			        switch ($field['type'])
			        {
				        case 'select':
					        $this->_html.= '<select name="' . $key . '">';
							
					        foreach ($field['value'] as $k => $value)
							{
						        $this->_html.= '<option value="' . $k . '"' . (($k == Tools::getValue($key, $field['value_default'])) ? ' selected="selected"' : '') . '>' . $value . '</option>';
							}
							
					        $this->_html.= '</select>';
					        break;
				        default:
					        break;
			        }
			        
			        if (isset($field['example']) && ! empty($field['example']))
					{
				        $this->_html.= '<p style="clear: both;">' . $field['example'] . '</p>';
					}
				        
			        $this->_html.= '
			        </div>';
		        }
		        
		$this->_html .= '
		        <br />
		        <center>
		            <input type="submit" class="button" name="submitExport" value="' . $this->l('Export .CSV file') . '" />
		        </center>
            </form>
        </fieldset>';
    }
 
 
    private function isNewsletterRegistered($customerEmail)
    {
        $emailSelectSql = '
        SELECT `email`
        FROM `' . _DB_PREFIX_ . 'newslettergermanext` 
        WHERE `email` = "' . pSQL($customerEmail) . '"';
		
        if (Db::getInstance()->getRow($emailSelectSql))
		{
            return self::REGISTERED_GN_NEWSLETTER;
		}
        
        $newsLetterSql = '
        SELECT `newsletter`
        FROM `' . _DB_PREFIX_ . 'customer`
        WHERE `email` = "' . pSQL($customerEmail) . '"';
		
        if ( ! $registered = Db::getInstance()->getRow($newsLetterSql))
		{
            return self::NOT_REGISTERED_CUSTOMER;
		}
  
        if ($registered['newsletter'] == '1')
		{
            return self::REGISTERED_AND_SUBSCRIBED;
		}
        elseif ($registered['newsletter'] == '0')
		{
            return self::REGISTERED_NOT_SUBSCRIBED;
		}
    }
 
 
    private function newsletterRegistration($params)
    {
        $requireActivation = (int)(Configuration::get('NWGN_ACTIVATION_EMAIL')) == 1 ? true : false;
		$action = isset($params['nw_action']) ? $params['nw_action'] : false;
		$email  = isset($params['nw_email']) ? $params['nw_email'] : false;
		
        if ( ! Validate::isEmail(pSQL($email)))
		{
            $this->error = $this->l('Invalid e-mail address');
			
			return false;
		}
        /* Unsubscription */
        elseif ($action == 1) 
        {
            $registerStatus = $this->isNewsletterRegistered(pSQL($email));
   
            if ($registerStatus < 1)
			{
                $this->error = $this->l('E-mail address not registered');
				
				return false;
			}
            /* If the user ins't a customer */
            elseif ($registerStatus == self::REGISTERED_GN_NEWSLETTER) 
            {
                
                $unsubscribeSql = '
                DELETE FROM `' . _DB_PREFIX_ . 'newslettergermanext` 
                WHERE `email` = "' . pSQL($email) . '"';
                
                if ( ! Db::getInstance()->Execute($unsubscribeSql))
				{
                    $this->error = $this->l('Error during unsubscription');
					
					return false;
				}
                    
                $this->valid = $this->l('Unsubscription successful');
				
				return true;
            }
            /* If the user is a customer */
            elseif ($registerStatus == self::REGISTERED_AND_SUBSCRIBED) 
            {
                $unsubscribeSql = '
                UPDATE `' . _DB_PREFIX_ . 'customer` 
                SET `newsletter` = 0 
                WHERE `email` = "' . pSQL($email) . '"';
                
                if ( ! Db::getInstance()->Execute($unsubscribeSql))
				{
                    $this->error = $this->l('Error during unsubscription');
					
					return false;
				}

                $this->valid = $this->l('Unsubscription successful');
				
				return true;
            }
        }
        elseif ($action == 0) 
        {
            /* Subscription */
            $registerStatus = $this->isNewsletterRegistered(pSQL($email));
   
            if ($registerStatus > 0)
			{
                return $this->error = $this->l('E-mail address already registered');
			}
            
            if ($requireActivation) 
            {
                global $cookie;
                
                if ($registerStatus == -2) 
                {
                    if ($this->deleteRequest($email))
					{
                        return $this->registerRequest($email, $_SERVER['REMOTE_ADDR'], (int)($cookie->id_guest));
					}
                        
                    $this->error = $this->l('Could not delete your previous subscription request, please contact administrator');
					
					return false;
                } 
                else
				{
                    return $this->registerRequest($email, $_SERVER['REMOTE_ADDR'], (int)($cookie->id_guest));
				}
            } 
            else 
            {
                if ($registerStatus == self::NOT_REGISTERED_CUSTOMER) 
                {
                    /* Unregistered user */
                    global $cookie;
                    
                    return $this->addUnregisteredUser($email, $_SERVER['REMOTE_ADDR'], (int)($cookie->id_guest));
                }
                elseif($registerStatus == self::REGISTERED_NOT_SUBSCRIBED)
				{
                    /* Registered user */
                    return $this->addRegisteredUser($email, $_SERVER['REMOTE_ADDR']);
				}
            }
        }
    }
 
 
    private function sendVoucher($email)
    {
        global $cookie;
  
        if ($discount = Configuration::get('NWGN_VOUCHER_CODE'))
		{
            return Mail::send(
				(int)$cookie->id_lang,
				'newsletter_voucher',
				$this->l('Newsletter voucher'),
				array('{discount}' => $discount),
				$email,
				NULL,
				NULL,
				NULL,
				NULL,
				NULL,
				dirname(__FILE__).'/mails/'
			);
		}
  
        return false;
    }
    
    
    private function registerRequest($email, $remoteAddres, $idGuest)
    {
        $context = Context::getContext();
		
        $hash = md5(uniqid(microtime(), 1)) . getmypid();
		
        $requestSql = '
        INSERT INTO `' . _DB_PREFIX_ . 'newslettergermanext_request` 
        VALUES 
        (
            "", "' . pSQL($email) . '", NOW(), "' . pSQL($remoteAddres) . '", "' . pSQL($hash) . '",
            (
                SELECT c.`http_referer` 
                FROM `' . _DB_PREFIX_ . 'connections` c 
                WHERE c.`id_guest` = ' . $idGuest . ' 
                ORDER BY c.`date_add` DESC LIMIT 1
            )
        )';
        
        if (Db::getInstance()->Execute($requestSql)) 
        {
			$activationUrl = $context->link->getPageLink('index', false);
			
			$activationUrl.= (strpos($activationUrl, '?') === false ? '?' : '&') . 'newsletteractivate=' . $hash;
			
			if (Mail::send(
				(int)$context->language->id,
				'newsletter_activation',
				$this->l('Newsletter activation'),
				array('{activation_link}' => $activationUrl),
				$email,
				NULL,
				NULL,
				NULL,
				NULL,
				NULL,
				dirname(__FILE__).'/mails/')
			)
			{
				$this->valid = $this->l('You successfully subscribed to our newsletter. Please, activate your subscription by clicking a link in an email we sent you.');
			
				return true;
			}
        }
        
        $this->error = $this->l('Error during subscription');
		
		return false;
    }
    
    
    private function deleteRequest($email)
    {
        $deleteRequestSql = '
        DELETE FROM `' . _DB_PREFIX_ . 'newslettergermanext_request` WHERE `email` = "' . pSQL($email) . '"';
        
        return Db::getInstance()->Execute($deleteRequestSql);
    }
    
    
    private function activateSubscription($hash)
    {
        if ($result = $this->selectSubscriptionRequestDataByHash($hash)) 
        {
            $registerStatus = $this->isNewsletterRegistered(pSQL($result['email']));
            
            if ($registerStatus > 0)
			{
                $this->error = $this->l('Already activated');
				
				return false;
			}
            elseif ($registerStatus == self::NOT_REGISTERED_CUSTOMER)
			{
                return $this->addUnregisteredUser($result['email'], $result['ip_registration_newsletter'], null, $result['http_referer'], $result['newsletter_date_add']);
			}
            elseif($registerStatus == self::REGISTERED_NOT_SUBSCRIBED)
			{
                return $this->addRegisteredUser($result['email'], $result['ip_registration_newsletter'], $result['newsletter_date_add']);
			}
        }
        
        return false;
    }
    
    
    private function deleteInactivatedRequests()
    {
        $interval = (int)Configuration::get('NWGN_INACTIVE_INTERVAL');
        
        if ($interval > 0) 
        {
            $deleteRows = '
            SELECT  `id` 
            FROM  `' . _DB_PREFIX_ . 'newslettergermanext_request` 
            WHERE  `newsletter_date_add` + INTERVAL ' . $interval . ' DAY < CURRENT_DATE()';
            
            if ($rows = Db::getInstance()->ExecuteS($deleteRows)) 
            {
                if (sizeof($rows) > 0) 
                {
                    $rowsArray = array();
                    
                    foreach($rows as $row)
					{
                        $rowsArray[] = (int)($row['id']);
					}
                        
                    $deleteSql = '
                    DELETE FROM `' . _DB_PREFIX_ . 'newslettergermanext_request` WHERE `id` IN (' . implode(',', $rowsArray) . ')';
                    return Db::getInstance()->Execute($deleteSql);
                }
                
                return true;
            }
        }
        
        return true;
    }
    
    
    private function selectSubscriptionRequestDataByHash($hash) 
    {
        $selectData = '
        SELECT * FROM `' . _DB_PREFIX_ . 'newslettergermanext_request` 
        WHERE `newsletter_hash` = "' . pSQL($hash) . '"';
		
        if ($result = Db::getInstance()->getRow($selectData))
		{
            return $result;
		}

        return false;
    }
    
    
    private function addUnregisteredUser($email, $remoteAddr, $idGuest, $referer = null, $date = null)
    {
        // Passed only when activating a request
        if (isset($referer) && isset($date)) 
        {
            $insertNewsletterSql = '
            INSERT INTO `' . _DB_PREFIX_ . 'newslettergermanext` 
            VALUES 
            (
                "",
                "' . pSQL($email) . '",
                "' . $date . '",
                "' . pSQL($remoteAddr) . '",
                "' . pSQL($referer) . '"
            )';
        }
        else 
        {
            $referer = Db::getInstance()->getValue('
                SELECT c.`http_referer` 
                FROM `' . _DB_PREFIX_ . 'connections` c 
                WHERE c.`id_guest` = ' . $idGuest . ' 
                ORDER BY c.`date_add` DESC'
            );
            
            $insertNewsletterSql = '
            INSERT INTO `' . _DB_PREFIX_ . 'newslettergermanext` 
            VALUES 
            (
                "",
                "' . pSQL($email) . '", 
                NOW(), 
                "' . pSQL($remoteAddr) . '", 
                "' . pSQL($referer) . '"
            )';
        }
        
        if ( ! Db::getInstance()->Execute($insertNewsletterSql))
		{
            $this->error = $this->l('Error during subscription');
			
			return false;
		}

        $this->sendVoucher(pSQL($email));
		
        $this->valid = $this->l('Subscription successful');
		
		return true;
    }
    
    
    private function addRegisteredUser($email, $remoteAddr, $date = null)
    {
        $insertNewsletterSql = '
        UPDATE
			`' . _DB_PREFIX_ . 'customer` 
        SET
			`newsletter`                 = 1, 
			`newsletter_date_add`        = ' . (isset($date) ? '"' . $date . '"' : 'NOW()') . ', 
			`ip_registration_newsletter` = "' . pSQL($remoteAddr) . '" 
        WHERE
			`email` = "' . pSQL($email) . '"';
        
        if ( ! Db::getInstance()->Execute($insertNewsletterSql))
		{
            $this->error = $this->l('Error during subscription');
			
			return false;
		}
        
        $this->sendVoucher(pSQL($email));
        $this->valid = $this->l('Subscription successful');
		
		return true;
    }
 
 
    public function hookRightColumn($params)
    {
        return $this->hookLeftColumn($params);
    }
	
	
    public function hookHome($params)
    {
        return $this->hookLeftColumn($params);
    }
	
	
    public function hookFooter($params)
    {
        return $this->hookLeftColumn($params);
    }
	
	
	public function ajaxCall($params)
	{
		global $smarty, $cookie;
		
        $requireActivation = (int)(Configuration::get('NWGN_ACTIVATION_EMAIL')) == 1 ? true : false;
		$this->newsletterRegistration($params);

		if ($this->error)
		{
			die(Tools::jsonEncode(array(
				'type'  => 'error',
				'msg'   => $this->error
			)));
		}
		elseif ($this->valid)
		{
			$action = isset($params['nw_action']) ? $params['nw_action'] : false;
			
			if ( ! $requireActivation && Configuration::get('NWGN_CONFIRMATION_EMAIL') && $action && (int)$action == 0)
			{
				Mail::Send(
					(int)($params['cookie']->id_lang),
					'newsletter_conf',
					$this->l('Newsletter confirmation'),
					array(),
					pSQL($email),
					NULL,
					NULL,
					NULL,
					NULL,
					NULL,
					dirname(__FILE__).'/mails/'
				);
			}
			
			die(Tools::jsonEncode(array(
				'type'  => 'success',
				'msg'   => $this->valid
			)));
		}
	}
 
    function hookLeftColumn($params)
    {
        global $smarty, $cookie;
		
        $requireActivation = (int)(Configuration::get('NWGN_ACTIVATION_EMAIL')) == 1 ? true : false;
		$email = Tools::getValue('nw_email', false);
		
        if (Tools::isSubmit('submitNewsletter'))
		{
            $this->newsletterRegistration($_POST);
   
            if ($this->error)
                $smarty->assign(
					array(
						'color'           => 'red',
						'msg'             => $this->error,
						'nw_email'        => Tools::getValue('nw_email', false),
						'nw_error'        => true,
						'action'          => Tools::getValue('nw_action')
					)
				);
            elseif ($this->valid)
			{
				$action = Tools::getValue('nw_action', false);
				
                if ( ! $requireActivation && Configuration::get('NWGN_CONFIRMATION_EMAIL') && $action && (int)$action == 0)
				{
                    Mail::Send(
						(int)($params['cookie']->id_lang),
						'newsletter_conf',
						$this->l('Newsletter confirmation'),
						array(),
						pSQL($email),
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						dirname(__FILE__).'/mails/'
					);
                }
    
                $smarty->assign(
					array(
						'color' => 'green',
						'msg' => $this->valid,
						'nw_error' => false
					)
				);
            }
        }
  
        $smarty->assign(
			array(
				'this_path' => $this->_path
			)
		);
		
		return $this->display(__FILE__, 'blocknewslettergermanext.tpl');
    }
    
	public function footer($params)
	{
		return $this->hookLeftColumn($params);
	}


    public function hookHeader($params)
    {
        global $smarty;
		
		$smarty->assign('nlGnactivation', false);
		
		$this->context->controller->addJqueryPlugin('fancybox');
		$this->context->controller->addCSS($this->_path . 'blocknewslettergermanext.css', 'all');
		
        if ($hash = Tools::getValue('newsletteractivate'))
		{
			$smarty->assign('nlGnactivation', true);
			
            if ($requestData = $this->selectSubscriptionRequestDataByHash($hash))
			{
                if ( ! $this->activateSubscription($hash) === false)
				{
                    $this->deleteRequest($requestData['email']);
				}
            }
			else
			{
                $this->error = $this->l('Could not find your activation request');
			}
			
            if ($this->error)
			{
                $smarty->assign(array(
					'type' => 'error',
					'msg'  => $this->error)
				);
			}
			elseif ($this->valid)
			{
                if (Configuration::get('NWGN_CONFIRMATION_EMAIL'))
				{
                    Mail::Send(
						(int)($params['cookie']->id_lang),
						'newsletter_conf',
						$this->l('Newsletter confirmation'),
						array(),
						pSQL($requestData['email']),
						NULL,
						NULL,
						NULL,
						NULL,
						NULL,
						dirname(__FILE__).'/mails/'
					);
                }
				
                $smarty->assign(
					array(
						'type' => 'success',
						'msg'  => $this->valid
					)
				);
            }
        }
		
		return $this->display(__FILE__, 'header.tpl');
    }
 
 
    public function confirmation()
    {
        global $smarty;
		
        return $this->display(__FILE__, 'templates/newslettergermanext.tpl');
    }
 
 
    public function externalNewsletter(/*$params*/)
    {
        return $this->hookLeftColumn($params);
    }
}