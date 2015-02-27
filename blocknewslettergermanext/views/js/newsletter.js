/**
 * Blocknewslettergermanext
 *
 * @category  Module
 * @author    silbersaiten <info@silbersaiten.de>
 * @support   silbersaiten <support@silbersaiten.de>
 * @copyright 2015 silbersaiten
 * @version   1.6.0
 * @link      http://www.silbersaiten.de
 * @license   See joined file licence.txt
 */

function displayFancyMessage(type, message)
{
    $.fancybox({
        width: 300,
        content: '<div class="' + type + '">' + message + '</div>'
    });
}

$(document).ready(function(){
    $('form.germanextNewsletter').submit(function(){
        var parentForm = $(this);
        
        $.post(
            newsletterPath + 'ajax.php',
            parentForm.serialize(),
            function(data){
                displayFancyMessage(data.type, data.msg);
            },
            'json'
        );
        
        return false;
    });
});