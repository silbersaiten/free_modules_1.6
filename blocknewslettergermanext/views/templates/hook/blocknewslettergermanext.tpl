<!-- Block Newsletter module-->
<div id="newsletter_block_left" class="block newsletter_gn">
	<h4>{l s='Newsletter' mod='blocknewslettergermanext'}</h4>
	<div class="block_content">
		<form method="post" class="germanextNewsletter">
	        <div class="form-group">
                <p>
                    <input id="newsletter-input" type="text" class="inputNew form-control grey newsletter-input gnnemail" name="nw_email" size="18" value="{if isset($nw_email)}{$nw_email}{else}{l s='your e-mail' mod='blocknewslettergermanext'}{/if}" onfocus="javascript:if(this.value=='{l s='your e-mail' mod='blocknewslettergermanext'}')this.value='';" onblur="javascript:if(this.value=='')this.value='{l s='your e-mail' mod='blocknewslettergermanext'}';" />
                    <button type="submit" class="btn btn-default button button-small" name="submitNewsletter" ><span>{l s='Ok' mod='blocknewslettergermanext'}</span></button>
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
			</div>
		</form>
	</div>
</div>

<!-- /Block Newsletter module-->
