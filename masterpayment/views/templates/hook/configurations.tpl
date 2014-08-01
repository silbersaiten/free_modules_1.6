
<script type="text/javascript">
    function masterpayment_toggle_frequency(obj)
    {
	if($(obj).val() == 'use_freq')
	    $('#masterpayment_frequency').slideDown();
	else
	    $('#masterpayment_frequency').slideUp();
    }
</script>

<h2>{l s='MasterPayments' mod='masterpayment'}</h2>

<p><img src="{$mod_dir}views/img/logo.png"/></p>
<p>{l s='Smart Payment Solutions' mod='masterpayment'}</p>
<p>{l s='For more information, visit' mod='masterpayment'} <a href="http://www.masterpayment.com" target="_blank">www.masterpayment.com</a></p>
<p style="clear: both;">&nbsp;</p>

<form method="post" action="">
    <fieldset>
	<legend><img title="" alt="" src="{$mod_dir}logo.gif">{l s='Configuration' mod='masterpayment'}</legend>

        <h2>{l s='General' mod='masterpayment'}</h2>

	<label>{l s='Merchant Name' mod='masterpayment'}</label>
	<div class="margin-form">
	   <input type="text" name="cfg[MP_MERCHANT_NAME]" value="{$cfg['MP_MERCHANT_NAME']}" size="40" />
           <p>{l s='The e-mail address you use for the Masterpayment account.' mod='masterpayment'}</p>
	</div>
	
	<label>{l s='Secret Key' mod='masterpayment'}</label>
	<div class="margin-form">
	   <input type="text" name="cfg[MP_SECRET_KEY]" value="{$cfg['MP_SECRET_KEY']}" size="40" />
	</div>

 	<label>{l s='Gateway URL' mod='masterpayment'}</label>
	<div class="margin-form">
	   <input type="text" name='cfg[MP_GATEWAY_URL]' value="{$cfg['MP_GATEWAY_URL']}" size="40" /> 
	</div>       
        
  	<label>{l s='Mode' mod='masterpayment'}</label>
	<div class="margin-form">
            {html_options name='cfg[MP_MODE]' options=$this->getModes() selected=$cfg['MP_MODE']}
	</div>

 	<label>{l s='Order Confirmation' mod='masterpayment'}</label>
	<div class="margin-form">
	    <label class="t"><input type="radio" value="1" name="cfg[MP_ORDER_CONFIRM]"{if (bool)$cfg['MP_ORDER_CONFIRM']} checked="checked"{/if}/> <img title="{l s='Enabled' mod='masterpayment'}" alt="{l s='Enabled' mod='masterpayment'}" src="../img/admin/enabled.gif"></label>
	    <label class="t"><input type="radio" value="0" name="cfg[MP_ORDER_CONFIRM]"{if !(bool)$cfg['MP_ORDER_CONFIRM']} checked="checked"{/if}/> <img title="{l s='Disabled' mod='masterpayment'}" alt="{l s='Disabled' mod='masterpayment'}" src="../img/admin/disabled.gif"></label>
           <p>{l s='Show order summary page with order confirm button' mod='masterpayment'}</p>
	</div>

 	<label>{l s='Create Order' mod='masterpayment'}</label>
	<div class="margin-form">
	    <label class="t"><input type="radio" value="1" name="cfg[MP_ORDER_CREATE]"{if (bool)$cfg['MP_ORDER_CREATE']} checked="checked"{/if}/> <img title="{l s='Enabled' mod='masterpayment'}" alt="{l s='Enabled' mod='masterpayment'}" src="../img/admin/enabled.gif"></label>
	    <label class="t"><input type="radio" value="0" name="cfg[MP_ORDER_CREATE]"{if !(bool)$cfg['MP_ORDER_CREATE']} checked="checked"{/if}/> <img title="{l s='Disabled' mod='masterpayment'}" alt="{l s='Disabled' mod='masterpayment'}" src="../img/admin/disabled.gif"></label>
           <p>{l s='Create order before payment completed with status "Awaiting MasterPayment payment"' mod='masterpayment'}</p>
	</div>
      
	<label>{l s='Payment Methods' mod='masterpayment'}</label>
	<div class="margin-form">
            {foreach $this->getPaymentMethods() as $method => $name}
                <label class="t"><input type="checkbox" value="{$method}" name="payment_methods[]"{if in_array($method, $payment_methods)} checked="checked"{/if}/> {$name}</label><br />
            {/foreach}
	</div>
                  
        <h2>{l s='Gateway' mod='masterpayment'}</h2>
        
	<label>{l s='Default language' mod='masterpayment'}</label>
	<div class="margin-form">
	   {html_options name='cfg[MP_LANGUAGE]' options=$this->getValidLanguages() selected=$cfg['MP_LANGUAGE']}
           <p>{l s='This language will be used if current shop language is not supported by MasterPayment.' mod='masterpayment'}</p>
	</div>
        
	<label>{l s='Style' mod='masterpayment'}</label>
	<div class="margin-form">
	   {html_options name='cfg[MP_GATEWAY_STYLE]' options=$this->getGatewayStyles() selected=$cfg['MP_GATEWAY_STYLE']}
           <p>{l s='The name of the gateway style.' mod='masterpayment'}</p>
	</div>
        
 	<label>{l s='Cancel option' mod='masterpayment'}</label>
	<div class="margin-form">
	    <label class="t"><input type="radio" value="1" name="cfg[MP_CANCEL_OPTION]"{if (bool)$cfg['MP_CANCEL_OPTION']} checked="checked"{/if}/> <img title="{l s='Enabled' mod='masterpayment'}" alt="{l s='Enabled' mod='masterpayment'}" src="../img/admin/enabled.gif"></label>
	    <label class="t"><input type="radio" value="0" name="cfg[MP_CANCEL_OPTION]"{if !(bool)$cfg['MP_CANCEL_OPTION']} checked="checked"{/if}/> <img title="{l s='Disabled' mod='masterpayment'}" alt="{l s='Disabled' mod='masterpayment'}" src="../img/admin/disabled.gif"></label>
           <p>{l s='If yes, cancel buttons will be shown on user input forms during payment process' mod='masterpayment'}</p>
	</div>       

        <h2>{l s='Specific' mod='masterpayment'}</h2>

        <label>{l s='Recurrent Period' mod='masterpayment'}</label>
	<div class="margin-form">
	   {html_options name='cfg[MP_RECURRENT_PERIOD]' options=$this->getRecurrentPeriods() selected=$cfg['MP_RECURRENT_PERIOD'] onchange="masterpayment_toggle_frequency(this);"}
           <p>{l s='Recurrent Period when payment method is "Recurring Lastschrift" or "Recurring Credit Card".' mod='masterpayment'}</p>
	</div>       
 
        <label>{l s='Installments Count' mod='masterpayment'}</label>
	<div class="margin-form">
	   <input type="text" name='cfg[MP_INSTALLMENTS_COUNT]' value="{$cfg['MP_INSTALLMENTS_COUNT']}" size="4" />
           <p>{l s='Installments count when payment method is "Ratenzahlung" or "Finanzierung".' mod='masterpayment'}</p>
	</div>
        
        <label>{l s='Installments Period' mod='masterpayment'}</label>
	<div class="margin-form">
	   {html_options name='cfg[MP_INSTALLMENTS_PERIOD]' options=$this->getInstallmentsPeriods() selected=$cfg['MP_INSTALLMENTS_PERIOD'] onchange="masterpayment_toggle_frequency(this);"}
	</div>       
        
        <div id="masterpayment_frequency" {if $cfg['MP_INSTALLMENTS_PERIOD'] != 'use_freq'}style="display: none;"{/if}>
            <label>{l s='Installments Frequency' mod='masterpayment'}</label>
            <div class="margin-form">
               <input type="text" name='cfg[MP_INSTALLMENTS_FREQ]' value="{$cfg['MP_INSTALLMENTS_FREQ']}" size="4" />
            </div>
        </div>

        <label>{l s='Payment Delay' mod='masterpayment'}</label>
	<div class="margin-form">
	   <input type="text" name='cfg[MP_PAYMENT_DELAY]' value="{$cfg['MP_PAYMENT_DELAY']}" size="4" />
	</div>

        <label>{l s='Due Days' mod='masterpayment'}</label>
	<div class="margin-form">
	   <input type="text" name='cfg[MP_DUE_DAYS]' value="{$cfg['MP_DUE_DAYS']}" size="4" />
	</div>  
        
        <p class="center"><input class="button" type="submit" value="{l s='Save' mod='masterpayment'}" name="saveConfigurations"></p>
        
    </fieldset>
</form>

<fieldset style="margin-top: 1em;">
    <legend><img title="" alt="" src="{$mod_dir}logo.gif">{l s='Gateway settings' mod='masterpayment'}</legend>
    
    <h3>{l s='Enter the following in your masterpayment gateway settings:'}</h3>
    
    <label>{l s='Transaction failure URL' mod='masterpayment'}</label>
    <div class="margin-form">
	<p><b>{$link->getModuleLink('masterpayment', 'error')}</b></p>
	<p class="desc clear">{l s='URL pattern for confirming transaction failure' mod='masterpayment'}</p>
    </div>
    
    <label>{l s='Transaction success URL' mod='masterpayment'}</label>
    <div class="margin-form">
	<p><b>{$link->getModuleLink('masterpayment', 'confirmation')}</b></p>
	<p class="desc clear">{l s='URL pattern for confirming transaction success' mod='masterpayment'}</p>
    </div>
    
    <label>{l s='Payment failure URL' mod='masterpayment'}</label>
    <div class="margin-form">
	<p><b>{$link->getModuleLink('masterpayment', 'error')}</b></p>
	<p class="desc clear">{l s='Redirect to URL on payment failure' mod='masterpayment'}</p>
    </div>

    <label>{l s='Payment cancellation URL' mod='masterpayment'}</label>
    <div class="margin-form">
	<p><b>{$link->getModuleLink('masterpayment', 'error')}</b></p>
	<p class="desc clear">{l s='Redirect to URL on payment cancelation' mod='masterpayment'}</p>
    </div>
    
    <label>{l s='Payment success URL' mod='masterpayment'}</label>
    <div class="margin-form">
	<p><b>{$link->getModuleLink('masterpayment', 'confirmation')}</b></p>
	<p class="desc clear">{l s='Redirect to URL on payment success' mod='masterpayment'}</p>
    </div>
    
    <label>{l s='Callback method' mod='masterpayment'}</label>
    <div class="margin-form">
	<p><b>POST</b></p>
    </div>
</fieldset>
