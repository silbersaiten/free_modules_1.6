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