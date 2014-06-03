<!-- Block Newsletter module-->
<script type="text/javascript">
	var newsletterPath = '{$module_dir}';
</script>
<script type="text/javascript" src="{$module_dir}js/newsletter.js"></script>
{if $nlGnactivation}
<script type="text/javascript">
	{literal}
	$(document).ready(function(){
		{/literal}
		displayFancyMessage('{$type}', '{$msg}');
		{literal}
	});
	{/literal}
</script>
{/if}
<!-- /Block Newsletter module-->
