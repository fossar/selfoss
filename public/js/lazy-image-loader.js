/**
 * load images on showing an message entry
 */
(function($){
$.fn.lazyLoadImages = function() {
    $(this).find('img').each(function(i, self) {
        $(self).attr('src', $(self).attr('ref'));
    });
}
})(jQuery);

(function($){
$.fn.mobileImgHelpers = function() {
    $(this).find('img').each(function(i, self) {
        if ($(self).parent().hasClass("entry-icon"))
            return;
        $(this).load(function () {
            var timg = new Image();
            timg.src = $(this).attr("src");
            var width = timg.width;
            if (!$(this).attr("alt") && (width <= $(this).width()))
                return;
            $(self).wrapAll("<div class='mobileimgdiv'></div>");
            $(self).parent().append("<br>");
            if ($(this).width() < width) {
                $(self).parent().append("<a href='#' class='zoom'>Zoom</a> ");
            }
            if ($(this).attr("alt")) {
                $(self).parent().append("<a href='#' class='alttext'>Alt Text</a> ");
                $(self).parent().append("<br><div class=alttextdiv style='display:none;'>"+$(this).attr("alt")+"</div>");
            }
            $(self).parent().children(".zoom").click(function (e) {
                e.preventDefault();
                $(this).parent().children("img").css("max-width",width + "px");
            });
            $(self).parent().children(".alttext").click(function (e) {
                e.preventDefault();
                $(this).parent().children(".alttextdiv").toggle();
            });
        });
    });
}
})(jQuery);
