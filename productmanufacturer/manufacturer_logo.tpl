<!-- manufaturer_logo -->
<div class="manufactuerblock">
    <a href="{$link->getManufacturerLink($id_manufacturer)|escape:'html':'UTF-8'}" title="{l s='Products by this manufactuer' mod='productmanufacturer'} {$productmanufacturer->name}">
        {if $productmanufacturer_jpg}
        <img src="{$productmanufacturer_jpg}" alt="{$productmanufacturer->name}" /> <span> {l s='Products by this manufactuer' mod='productmanufacturer'} {$productmanufacturer_name}</span>
        {else}
        {$productmanufacturer->name}
        {/if}
    </a>
</div>
