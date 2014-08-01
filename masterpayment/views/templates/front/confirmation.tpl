{capture name=path}{l s='Order confirmation' mod='masterpayment'}{/capture}

<h1>{l s='Order confirmation' mod='masterpayment'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{include file="$tpl_dir./errors.tpl"}

{$HOOK_ORDER_CONFIRMATION}
{$HOOK_PAYMENT_RETURN}

<br />
{if $is_guest}
	<p>{l s='Your order ID is:' mod='masterpayment'} <span class="bold">{$id_order_formatted}</span> . {l s='Your order ID has been sent via email.' mod='masterpayment'}</p>
	<a href="{$link->getPageLink('guest-tracking', true, NULL, "id_order={$reference_order}&email={$email}")}" title="{l s='Follow my order' mod='masterpayment'}"><img src="{$img_dir}icon/order.gif" alt="{l s='Follow my order' mod='masterpayment'}" class="icon" /></a>
	<a href="{$link->getPageLink('guest-tracking', true, NULL, "id_order={$reference_order}&email={$email}")}" title="{l s='Follow my order' mod='masterpayment'}">{l s='Follow my order' mod='masterpayment'}</a>
{else}
	<a href="{$link->getPageLink('history', true)}" title="{l s='Back to orders' mod='masterpayment'}"><img src="{$img_dir}icon/order.gif" alt="{l s='Back to orders' mod='masterpayment'}" class="icon" /></a>
	<a href="{$link->getPageLink('history', true)}" title="{l s='Back to orders' mod='masterpayment'}">{l s='Back to orders' mod='masterpayment'}</a>
{/if}