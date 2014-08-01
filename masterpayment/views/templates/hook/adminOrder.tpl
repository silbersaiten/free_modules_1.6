<br />
<a name="masterpayment_refund"></a>
<fieldset>
	<legend><img src="{$mod_dir}logo.gif" alt="" />{l s='MasterPayment Refund' mod='masterpayment'}</legend>
	{if $msg}{$msg}{/if}
	<form method="post" action="#masterpayment_refund">
		<input type="hidden" name="id_order" value="{$order->id}" />
		
		<label>{l s='Amount' mod='masterpayment'}:</label>
		<div class="margin-form"><input name="amount" value="{$amount}" size="5"/> {$currency->sign}</div>
		
		<label>{l s='Comment' mod='masterpayment'}:</label>
		<div class="margin-form"><textarea name="comment" cols="34" rows="5"></textarea></div>

		<label>&nbsp;</label>
		<div class="margin-form"><input type="submit" class="button" name="submitMasterPaymentRefund" value="{l s='Refund' mod='masterpayment'}" onclick="return confirm('{l s='Are you sure you want to refund?' mod='masterpayment'}');" /></div>
	</form>
</fieldset>