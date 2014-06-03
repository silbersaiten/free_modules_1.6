var exporter={collectFields:function()
{var collection=$('ul#export').find('li').not('.ui-sortable-helper');return collection.length>0?collection:false;}
    ,renderControls:function()
    {
        var collection,controls,innerDiv,settingsDiv;collection=exporter.collectFields();
        if(!collection) {return false;}
        collection.each(function(i)
        {var controls=$(this).find('span.controls');if(!controls.length>0)
        {$(this).wrapInner($(document.createElement('div')).addClass('generalFieldInfo'));$(this).append($(document.createElement('div')).addClass('fieldSettings'));innerDiv=$(this).find('div.generalFieldInfo');settingsDiv=$(this).find('div.fieldSettings');innerDiv.append($(document.createElement('span')).addClass('controls delete').attr('title',FControlDelete));innerDiv.append($(document.createElement('span')).addClass('controls setup').attr('title',FControlEdit));helper.createSetupInputField(settingsDiv,fNameLabel,'fName');if(!$(this).hasClass('empty_field'))
        {helper.createSetupInputFields(settingsDiv,{fBefore:fBeforeLabel,fAfter:fAfterLabel},'input');}
        if($(this).hasClass('empty_field'))
        {helper.createSetupInputField(settingsDiv,fValueLabel,'fValue');}
        if($(this).hasClass('shipping_with_fee'))
        {helper.createSetupInputField(settingsDiv,fCodFee,'fCodFee');}
        if($(this).hasClass('price')){var fields={withTax:fWithTaxLabel,withShipping:fWithShippingLabel,withReduction:fWithReductionLabel};helper.createSetupInputFields(settingsDiv,fields,'checkbox');}
        if($(this).hasClass('picture_link')){var fields={allPictures:fUrlsOfAllPictures};helper.createSetupInputFields(settingsDiv,fields,'checkbox');}
        if($(this).hasClass('shipping_price')||$(this).hasClass('shipping_with_fee')||$(this).hasClass('with_shipping_price'))
        {helper.createSetupInputFields(settingsDiv,{withTax:fWithTaxLabel},'checkbox');}
        settingsDiv.append($(document.createElement('span')).addClass('closeSettings').attr('title',FCloseSettings).html(FCloseSettings))}});
    }

    ,delegate:function(type,delegate,action)
{return $(document).bind(type, function(evt)
{var target=$(evt.target);if(target.is(delegate))
{return action.apply(target,arguments)}});},
    purgeFields:function()
{if(collection=exporter.collectFields())
{collection.remove();}
$('textarea[name="template"]').val('');},
    moveFieldsToInput:function()
{exporter.fillEmptyFields();if(collection=exporter.collectFields())
{var targetInput=$('textarea[name="template"]'),outputString='{"fields": [';collection.each(function(i)
{var field=$(this).attr('class').split(' ')[0],fields=helper.getInputVal($(this),['fName','fBefore','fAfter','fValue','fCodFee','withTax','withShipping','withReduction', 'allPictures']);if(fields.fName.length==0)
{fields.fName=field;}
outputString+=helper.getjSonObject({field:field,fieldTitle:fields.fName,before:fields.fBefore,after:fields.fAfter,value:fields.fValue,withTax:fields.withTax,withShipping:fields.withShipping,withReduction:fields.withReduction,allPictures:fields.allPictures,fee:fields.fCodFee});});outputString=outputString.substr(0,parseInt(outputString.length-2));outputString+=']}';targetInput.val(outputString);}}
    ,populateFieldNames:function()
{var jsonTemplate=$('textarea[name="template"]').val();if(jsonTemplate=='')
{return false;}
if(collection=exporter.collectFields())
{var data=JSON.parse(jsonTemplate).fields;collection.each(function(i)
{var ownName=$(this).find('div.generalFieldInfo').find('span.ownName');if(data[i].fieldTitle=='undefined'||data[i].fieldTitle.length==0)
{data[i].fieldTitle=data[i].field;}
if(data[i].fieldTitle!=data[i].field)
{if(ownName.length==0)
{ownName=($(document.createElement('span')).addClass('ownName').insertAfter($(this).find('div.generalFieldInfo').find('span.fieldFancyName')));}
ownName.html(' ('+data[i].fieldTitle+')');}
helper.setInputVal($(this),{fName:data[i].fieldTitle,fBefore:data[i].before,fAfter:data[i].after,fValue:data[i].value,fCodFee:data[i].fee,withTax:data[i].withTax,withShipping:data[i].withShipping,withReduction:data[i].withReduction, allPictures:data[i].allPictures});});}}
    ,fillEmptyFields:function()
{if(collection=exporter.collectFields())
{var emptyFields=collection.find('input.fName[value=""]');emptyFields.each(function()
{var defaultName=$(this).parents('li').attr('class').split(' ')[0];$(this).val(defaultName);});}}
    ,updateFieldTitle:function()
{var parent=$(this).parents('li');var ownName=parent.find('span.ownName');var fieldName=parent.attr('class').split(' ')[0];if($(this).val()!=fieldName)
{if(ownName.length==0)
{ownName=($(document.createElement('span')).addClass('ownName')).insertAfter(parent.find('span.fieldFancyName'));}
ownName.html(' ('+$(this).val()+')');}
else
{if(ownName.length>0)
{ownName.fadeOut('fast',function(){$(this).remove();});}}}
    ,updateAll:function()
{exporter.renderControls();exporter.fillEmptyFields();exporter.moveFieldsToInput();}
    ,showSetup:function(element)
{var settingsDiv=$(element.target).parents('li').find('div.fieldSettings');var divsToHide=$('div.fieldSettings:visible').not(settingsDiv);if(divsToHide.length>0){divsToHide.slideUp('fast',function(){settingsDiv.slideDown('fast');});}
else
{settingsDiv.slideToggle('fast');}}
,hideSetup:function()
{$('div.fieldSettings:visible').slideUp('fast');}
,removeField:function(element)
{var clickedElement=$(element.target).parents('li');clickedElement.slideUp('fast',function(){$(this).remove();exporter.moveFieldsToInput();});}
,fieldsetHeights:function()
{var leftFieldset=$('fieldset.availFields'),rightFieldset=$('fieldset.targetFields'),fieldsList=rightFieldset.find('ul#export'),lHeight=leftFieldset.height(),rHeight=rightFieldset.height(),rTPadding=parseInt(rightFieldset.css('paddingTop')),rBPadding=parseInt(rightFieldset.css('paddingTop')),controlPH=rightFieldset.find('span.purgeFields').outerHeight(true),resHeight;resHeight=lHeight>rHeight?lHeight:rHeight;leftFieldset.css('height',resHeight+'px');rightFieldset.css('height',resHeight+'px');fieldsList.css('height',(resHeight-(rTPadding+rBPadding+controlPH))+'px');}
,init:function()
    {   exporter.fieldsetHeights();
        $('.fields > li').draggable({scroll:true, refreshPositions: true, helper:'clone',connectToSortable:'#export',opacity:0.9,cursor:'move'
        ,stop:function(event,ui){exporter.renderControls();}
        });
        $('#export').sortable({tolerance: "pointer",axis:'y',placeholder:'field_placeholder',cursor:'move'
        ,update:function(event,ui){exporter.updateAll();}
        ,receive:function(event,ui){exporter.updateAll();}

        });
        exporter.renderControls();
        exporter.populateFieldNames();
        exporter.delegate('click','span.controls.delete',exporter.removeField);
        exporter.delegate('click','span.controls.setup',exporter.showSetup);
        exporter.delegate('click','span.closeSettings',exporter.hideSetup);
        exporter.delegate('keyup','input.fName',exporter.updateFieldTitle);
        $('span.purgeFields').click(function(){exporter.purgeFields();});
        $("form#mousiqueMainFrm").submit(function(){exporter.updateAll();return true;});
    }
};
