$(document).ready(function(){
    exporter.init();
    $('span#toggleMousiqueSettings').click(function(){$('fieldset#mousiqueSettings').slideToggle('fast');});
    $('span#whatSCron').click(function(){$('p#wtfsCron').slideToggle('fast');});
});
