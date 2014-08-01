<p class="error err">{l s='An error occured when trying to process your order. Please check you information or try a different payment method.' mod='masterpayment'}</p>
<ul class="footer_links">
    <li class="fleft"><a href="{$link->getPageLink("$order_process", true)}"><img src="{$img_dir}icon/cart.gif" alt="" class="icon" /></a> <a href="{$link->getPageLink("$order_process", true)}">{l s='Back to cart' mod='masterpayment'}</a></li>
    <li class="fleft"><a href="{$link->getPageLink('my-account', true)}"><img src="{$img_dir}icon/my-account.gif" alt="" class="icon" /></a><a href="{$link->getPageLink('my-account', true)}">{l s='Back to Your Account' mod='masterpayment'}</a></li>
</ul>