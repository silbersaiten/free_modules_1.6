
{capture name=path}<a href="order.php">{l s='Your shopping cart' mod='masterpayment'}</a><span class="navigation-pipe">{$navigationPipe}</span>{$paymentName} {l s='payment' mod='masterpayment'}{/capture}

<h2>{$paymentName} {l s='payment' mod='masterpayment'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $isValidCurrency}
<form action="{$cfg['MP_GATEWAY_URL']}" method="post" name="masterpayment" {if $cfg['MP_MODE'] == 'iframe'}target="masterpayment_gateway_iframe"{/if}>
{foreach $params as $name => $value}
        <input type="hidden" name="{$name}" value="{$value}" />
{/foreach}
</form>

{if $cfg['MP_MODE'] == 'iframe'}<iframe id="masterpayment_gateway_iframe" name="masterpayment_gateway_iframe"></iframe>{/if}

<script language="JavaScript">
document.masterpayment.submit();
</script>

{else}	
<p class="warning">
        {l s='Chosen currency was not authorized for this payment module!' mod='masterpayment'}
        <br />
        {l s='Please select different currency.' mod='masterpayment'}
</p>
{/if}

