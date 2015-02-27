{**
* Blocknewslettergermanext
*
* @category  Module
* @author    silbersaiten <info@silbersaiten.de>
* @support   silbersaiten <support@silbersaiten.de>
* @copyright 2015 silbersaiten
* @version   1.6.0
* @link      http://www.silbersaiten.de
* @license   See joined file licence.txt
*}
<!-- Block Newsletter module-->
<script type="text/javascript">
	var newsletterPath = '{$module_dir|escape:'html'}';
</script>
<script type="text/javascript" src="{$module_dir|escape:'html'}views/js/newsletter.js"></script>
{if $nlGnactivation}
<script type="text/javascript">
	{literal}
	$(document).ready(function(){
		{/literal}
		displayFancyMessage('{$type|escape:'html'}', '{$msg|escape:'html'}');
		{literal}
	});
	{/literal}
</script>
{/if}
<!-- /Block Newsletter module-->
