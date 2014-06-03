<!-- Block Newsletter module-->
<div id="newsletter_gn_block_left" class="block">
	<h4>{l s='Newsletter' mod='blocknewslettergermanext'}</h4>
	<div class="block_content">
		<form method="post" class="germanextNewsletter">
			<p>
				<input type="text" class="gnnemail" name="nw_email" size="18" value="{if isset($nw_email)}{$nw_email}{else}{l s='your e-mail' mod='blocknewslettergermanext'}{/if}" onfocus="javascript:if(this.value=='{l s='your e-mail' mod='blocknewslettergermanext'}')this.value='';" onblur="javascript:if(this.value=='')this.value='{l s='your e-mail' mod='blocknewslettergermanext'}';" />
				<input type="submit" value="{l s='Ok' mod='blocknewslettergermanext'}" class="button_mini" name="submitNewsletter" />
			</p>
			{if isset($nw_groups)}
			<p>
				<select name="id_region_group">
					{foreach from=$nw_groups item=group}
					<option value="{$group.id_group}" {if isset($id_region_group) && $id_region_group == $group.id_group} selected="selected"{/if}>{$group.name}</option>
					{/foreach}
				</select>
			</p>
			{/if}
			<p>
				<select name="nw_action">
					<option value="0"{if isset($action) && $action == 0} selected="selected"{/if}>{l s='Subscribe' mod='blocknewslettergermanext'}</option>
					<option value="1"{if isset($action) && $action == 1} selected="selected"{/if}>{l s='Unsubscribe' mod='blocknewslettergermanext'}</option>
				</select>
			</p>
			<p class="unsubscribe_message">{l s='You can unsubscribe from this newsletter at any time' mod='blocknewslettergermanext'}</p>
		</form>
	</div>
</div>

<!-- /Block Newsletter module-->
