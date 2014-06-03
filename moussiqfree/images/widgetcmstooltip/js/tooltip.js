function varExists(value)
{
    return typeof value != 'undefined';
}

function createToolTip(object, params)
{
    var position = object.offset(),
        height   = object.outerHeight(),
        tooltip  = $(document.createElement('div')),
        hoffset  = varExists(params.hoffset) ? params.hoffset : 0,
        voffset  = varExists(params.voffset) ? params.voffset : 0;

    tooltip.addClass('widgettooltipContainer');
    
    tooltip.css({
        left: position.left + hoffset,
        top:  position.top + height + voffset
    });
    
    if (params.width != 'undefined')
        tooltip.css({width: params.width});
    
    tooltip.prepend($(document.createElement('div')).addClass('preload'));
    
    $('body').eq(0).append(tooltip.fadeIn('fast'));
    
    return tooltip;
}

function deleteTooltips()
{
    $('div.widgettooltipContainer').fadeOut('fast', function(){
        $(this).remove();
    });
}

$(document).ready(function(){
    var additionalParams = {
        width:   'w_',
        voffset: 'v_',
        hoffset: 'h_'
    };
    
    $('a.widgetctooltip').mouseover(function(){
        deleteTooltips();
        
        var link = $(this),
            cmsId = parseInt($(this).attr('rel').split('_')[1]),
            classes = link.attr('class').split(' '),
            tooltipParams = {};
            
        for (var i in additionalParams)
        {
            for (var y = 0; y < classes.length; y++)
            {
                if (classes[y].substr(0, additionalParams[i].length) === additionalParams[i])
                {
                    tooltipParams[i] = parseInt(classes[y].split('_')[1]);
                }
            }
        }
        
        if ( ! isNaN(productId))
        {
            var toolTip = createToolTip(link, tooltipParams);
            
			$.post(voviqueDir + 'widgetcmstooltip/ajax.php',
				{tcms: cmsId},
				function(data)
				{
                    toolTip.find('div.preload').fadeOut('fast', function(){
                        $(this).parent('div.widgettooltipContainer').slideUp('fast', function(){
                            $(this).empty().html(data).slideDown('fast');
                        });
                    });
				}
			);
        }
        
        $(this).mouseout(function(evt){
            deleteTooltips();
        });
    });
});