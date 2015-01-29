<!-- manufacturer_tab_info -->
<ul id="idTabManu" class="bullet">
	<div class="block_content">
			{if $productmanufacturer_jpg}<img class="labelimg" src="{$productmanufacturer_jpg}" alt="" /> {/if}
		<div class="rte shortDesc">
			{$productmanufacturer->short_description}
		</div>
		<div class="rte fullDesc">
			{$productmanufacturer->description}
		</div>
	</div>
	<div class="clear"></div>
</ul>