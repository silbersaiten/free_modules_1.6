{foreach $payment_methods as $method => $name}
<div class="row">
    <div class="col-xs-12 col-md-6">
        <p class="payment_module" id="masterpayment_{$method}">
            <a href="{$link->getModuleLink('masterpayment', 'submit', ['payment_method' => $method])}">
                <img src="{$mod_dir}views/img/p/{$method}.png" title="{$name}" alt="{$name}" />
                {$name}
            </a>
        </p>
    </div>
</div>
{/foreach}