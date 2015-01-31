/**
 * NTScroll2Top
 *
 * @category  Module
 * @author    silbersaiten <info@silbersaiten.de>
 * @support   silbersaiten <support@silbersaiten.de>
 * @copyright 2015 silbersaiten
 * @version   1.0.0
 * @link      http://www.silbersaiten.de
 * @license   See joined file licence.txt
 */
$(document).ready(function() {
    var scroll2 = $('#scroll2Top a');
    var view = $(window);

    scroll2.click(function(){
        $("html, body").animate({ scrollTop: 0 }, 300);
        return false;
    });

    view.bind("scroll", function(e) {
        var heightView = view.height();
        if (scroll2.offset())
            var btnPlace = scroll2.offset().top;
        else
            var btnPlace = 0;
        if (heightView < btnPlace)
            scroll2.show();
        else
            scroll2.hide();
    });
});