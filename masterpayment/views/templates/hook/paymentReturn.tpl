{capture name='shop_name'}<span class="bold">{$shop_name}</span>{/capture}	
{if $status == 'ok'}
	{capture name='done_text'}{l s='Your order on {shop_name} is complete.' mod='masterpayment'}{/capture}
	<p>{$smarty.capture.done_text|replace:'{shop_name}':$smarty.capture.shop_name}</p>
	<p><span class="bold">{l s='Your order will be sent as soon as possible.' mod='masterpayment'}</span></p>
	<p>{l s='For any questions or for further information, please contact our' mod='dibs'} <a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='masterpayment'}</a></p>
{elseif $status == 'pending'}
	{capture name='pending_text'}{l s='Your order on {shop_name} is pending.' mod='masterpayment'}{/capture}
	<p>{$smarty.capture.pending_text|replace:'{shop_name}':$smarty.capture.shop_name}</p>
	<p><span class="bold">{l s='Your order will be shipped as soon as possible.' mod='masterpayment'}</span></p>
	<p>{l s='For any questions or for further information, please contact our' mod='masterpayment'} <a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='masterpayment'}</a></p>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, you can contact our' mod='masterpayment'} 
		<a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='masterpayment'}</a>
	</p>
{/if}
