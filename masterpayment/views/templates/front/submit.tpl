
{capture name=path}<a href="order.php">{l s='Your shopping cart' mod='masterpayment'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Order summary' mod='masterpayment'}{/capture}

<h2>{l s='Order summary' mod='masterpayment'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3><img src="{$this_masterpayment_path}views/img/p/{$paymentMethod}.png" class="middle" /> {$paymentName}</h3>

<form action="" method="post">
	<h4>{l s='Here is a short summary of your order:' mod='masterpayment'}</h4>
	<p> - {l s='The total amount of your order is' mod='masterpayment'} <span id="amount_{$currency->id}" class="price">{convertPriceWithCurrency price=$total currency=$currency}</span>{if $use_taxes == 1} {l s='(tax incl.)' mod='masterpayment'}{/if}</p>
	<p> - {l s='This payment method accept the following currencies:' mod='masterpayment'}&nbsp;<b>{$validCurrencyNames}</b></p>
	<p>&nbsp;</p>

	{if $isValidCurrency}
	<p><b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='masterpayment'}.</b></p>
	{else}	
	<p class="warning">
		{l s='Chosen currency was not authorized for this payment module!' mod='masterpayment'}
		<br />
		{l s='Please select different currency.' mod='masterpayment'}
	</p>
	{/if}

    <p class="cart_navigation clearfix" id="cart_navigation">
        <a
                class="button-exclusive btn btn-default"
                href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
            <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='masterpayment'}
        </a>
        {if $isValidCurrency}
            <button
                class="button btn btn-default button-medium"
                type="submit"
                name="confirmOrder">
            <span>{l s='I confirm my order' mod='bankwire'}<i class="icon-chevron-right right"></i></span>
        </button>{/if}
    </p>
</form>