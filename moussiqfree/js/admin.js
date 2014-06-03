function ajaxStates(countrySelector, stateSelector, stateWrapper, selectedState, countryPreselected)
{
    countryPreselected = countryPreselected || $(countrySelector).val();
    
    $.ajax({
        url: "ajax.php",
        cache: false,
        data: "ajaxStates=1&id_country=" + countryPreselected + "&id_state=" + $(stateSelector).val(),
        success: function(html) {
            if (html == 'false')
            {
                $(stateWrapper).fadeOut();
                $(stateSelector + ' option[value=0]').attr("selected", "selected");
            }
            else
            {
                $(stateSelector).html(html);
                $(stateWrapper).fadeIn();
                $(stateSelector + ' option[value=' + selectedState + ']').attr("selected", "selected");
            }
        }
    });
}
    
function createStateSelector(insertAfter, wrapperId, labelText, stateSelectId)
{
    var wrapper = $(document.createElement('div')).attr('id', wrapperId).hide();
    
    wrapper = wrapper.append(
        $(document.createElement('label')).html(labelText))
        .append(
            $(document.createElement('div')).addClass('margin-form')
                .append($(document.createElement('select')).attr('id', stateSelectId).attr('name', stateSelectId))
        )
        
    return wrapper.insertAfter(insertAfter);
}

function initStateSelect(countrySelector, stateSelectId, preselectedState, preselectedCountry)
{
    var mainSetting = $(countrySelector);
    
    if (mainSetting.length > 0)
    {
        createStateSelector(mainSetting.parent('.margin-form'), 'contains_states', labelStateSelect, stateSelectId);
        
        ajaxStates(countrySelector, '#' + stateSelectId, 'div#contains_states', preselectedState, preselectedCountry);
        
        mainSetting.live('change', function(){
            var countryVal = $(this).val();
            
            if (countryVal == -1)
                countryVal = preselectedCountry;
                
            ajaxStates(countrySelector, '#id_state', 'div#contains_states', preselectedState, countryVal); 
        });
    }
}

$(document).ready(function(){
    initStateSelect('select[name=EXPORT_COUNTRY]', 'EXPORT_STATE', stateDefault);
});