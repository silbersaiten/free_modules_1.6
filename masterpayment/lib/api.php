<?php

class MasterPaymentApi
{
    const gatewayURL = 'https://www.masterpayment.com/en/payment/gateway';
    const refundURL = 'https://masterpayment.com/refund.do';

    const STATUS_REFUNDED = 'REFUNDED';

    
    public $iframeMode = false;
    
    public $secretKey = null;
    
    public $merchantName = null;
    public $txId = null;
    public $orderId = 0;
    public $basketDescription = null;
    public $basketValue = 0;
    public $currency = 'EUR';
    public $language = 'EN';
    
    public $gatewayStyle = 'standart';
    public $paymentType = 'none';
    public $userIp = null;
    public $showCancelOption = 1;

    //Validation urls
    public $UrlPatternSuccess = null;
    public $UrlPatternFailure = null;
    
    //Done urls
    public $UrlRedirectSuccess = null;
    public $UrlRedirectFailure = null;
    public $UrlRedirectCancel = null;

    //Customer info
    public $userId = 0;
    public $sex = null;
    public $firstname = null;
    public $lastname = null;
    public $street = null;
    public $houseNumber = null;
    public $zipCode = null;
    public $city = null;
    public $country = null;
    public $birthdate = null;
    public $mobile = null;
    public $email = null;
    
    
    //Specific
    public $installmentsCount = 0;
    public $installmentsFreq = 0;
    public $installmentsPeriod = null;
    public $recurrentPeriod = null;    
    public $invoiceNo = null;
    public $paymentDelay = 0;
    public $dueDays = 0;
    public $createAsPending = 0;
    

    private $items = array();

    public function addItem($description, $amount, $price)
    {
        $c = count($this->items);
        $this->items[$c]['itemDescription'] = $description;
        $this->items[$c]['itemAmount'] = $amount;
        $this->items[$c]['itemPrice'] = $price;
    }

    function getParams()
    {
        $basic = array
        (
            'merchantName',
            'txId',
            'basketDescription',
            'basketValue',
            'currency',
            'language',
            'userId',
            'sex',
            'firstname',
            'lastname',
            'street',
            'houseNumber',
            'zipCode',
            'city',
            'country',
            'birthdate',
            'mobile',
            'email',
            'paymentType',
            'gatewayStyle',
            'UrlPatternSuccess',
            'UrlPatternFailure',
            'UrlRedirectSuccess',
            'UrlRedirectCancel',
            'UrlRedirectFailure',
            'showCancelOption'
        );
        
        $p = array();
        
        foreach($basic as $param)
            if($this->$param)
                $p[$param] = $this->$param;

        //Set User IP
        if(!$this->userIp)
            $p['userIp'] = $this->getIpAddress();
      
        //Specific params
        if(in_array($this->paymentType, array('ratenzahlung', 'finanzierung')))
        {
            $p['installmentsCount'] = $this->installmentsCount;

            if($this->installmentsPeriod)
                $p['installmentsPeriod'] = $this->installmentsPeriod;
            else
                $p['installmentsFreq'] = $this->installmentsFreq;
        }

	if(in_array($this->paymentType, array('elv_recurrent', 'cc_recurrent')))
	    $p['recurrentPeriod'] = $this->recurrentPeriod;
		
	if($this->paymentType == 'deferred_debit')
		$p['paymentDelay'] = $this->paymentDelay;

        if(in_array($this->paymentType, array('rechnungskauf', 'anzahlungskauf')))
        {
            $p['dueDays'] = $this->dueDays;
            $p['invoiceNo'] = $this->invoiceNo;
            $p['createAsPending'] = $this->createAsPending;
        }

        if($this->iframeMode)
        {
            $p['UrlRedirectSuccess'] = 'target-parent:'.$this->UrlRedirectSuccess;
            $p['UrlRedirectFailure'] = 'target-parent:'.$this->UrlRedirectFailure;
            $p['UrlRedirectCancel'] = 'target-parent:'.$this->UrlRedirectCancel;
        }
        
        
        //Add items
        foreach($this->items as $c => $item)
            foreach($item as $k => $v)
                $p['items['.$c.']['.$k.']'] = $v;
        
	//Controll key computation
	$checksum_data = array_filter($p);
	ksort($checksum_data);
	
	$checksum = implode('|', $checksum_data) . '|' . $this->secretKey;
	
	$p['controlKey'] = md5($checksum);
        
        return $p;
    }
    
    
    public function getRequestStatus()
    {
        $p = count($_POST) ? $_POST : $_GET;
        
        if(!isset($p['CTRL_KEY']) || !isset($p['STATUS']))
            return 'INVALID';

        $controlKey = md5(str_replace($p['CTRL_KEY'], '', implode('|', $p)) . $this->secretKey);

        if($controlKey == $p['CTRL_KEY'])
        {
            $this->txId = $p['TX_ID'];
            $this->paymentType = $p['METHOD'];
            $this->userId = $p['CUST_ID'];
            $this->currency = $p['CURRENCY'];
            $this->basketValue = $p['VALUE'];
            return $p['STATUS'];
        }
        
        return 'INVALID';
    }
    
    public function refundRequest(&$comment)
    {
        $error = null;
        
        if(!$this->txId || !$this->basketValue || !$this->merchantName || !$this->secretKey)
        {
            $comment = 'Invalid configurations';
            return 'INVALID';
        }
    
        //Set up request data
        $data = array
        (
            'merchantName' => $this->merchantName,
            'txId' => $this->txId,
            'amount' => $this->basketValue,
            'comment' => $comment
        );

 	$data = array_filter($data);
	ksort($data);	
	$data['controlKey'] = md5(implode('|', $data) . '|' . $this->secretKey);
   
        //Buid post query
        $query = http_build_query($data);
        
        //open connection
        $ch = curl_init();
        
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, self::refundURL);
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        //execute post
        $response = curl_exec($ch);
        
        //close connection
        curl_close($ch);
        
        if(!$response)
        {
            $comment = 'No response';
            return 'NO RESPONSE';
        }
        
        $xml = simplexml_load_string($response);

        if(!$xml)
        {
            $comment = 'Invalid response';
            return 'INVALID RESPONSE';
        }

        $comment = $xml->error;
        return $xml->status;
    }

    
    
    private function getIpAddress()
    {
        if (isset($_SERVER))
        {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            elseif(isset($_SERVER['HTTP_CLIENT_IP']))
                return $_SERVER['HTTP_CLIENT_IP'];
            else
                return $_SERVER['REMOTE_ADDR'];
        }
        else
        {
            if(getenv('HTTP_X_FORWARDED_FOR'))
                return getenv('HTTP_X_FORWARDED_FOR');
            elseif(getenv('HTTP_CLIENT_IP'))
                return getenv('HTTP_CLIENT_IP');
            else
                return getenv('REMOTE_ADDR');
        }
    }    
}


?>