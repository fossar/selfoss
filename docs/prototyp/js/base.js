var selfoss = {
    scroller: false,

    init: function() {
        jQuery(document).bind('elastic:beforeInitialize', function() {
            // smartphone version
            if($(window).width()<600) {
                $('body, html').addClass('mobile');
                $('#stream, nav').removeClass('on-3');
                $('#stream, nav').addClass('on-1');
            }
        });
        
        jQuery(document).bind('elastic:initialize', function() {
            $('nav, #stream').css('visibility','visible');
            
            if($('html').hasClass('mobile')==false) {
                $('#wrapper').css('height', $(window).height()+'px');
                $('#stream').css('margin-top', $(window).height()+'px');
                selfoss.scroller = new iScroll('wrapper', { hScroll: false });
                selfoss.setStreamPosition(true);
                $(window).resize(selfoss.resize);
            }
            
            selfoss.events();
        });
        
    },
    
    setStreamPosition: function(animate) {
        var windowHeight = $(window).height();
        var streamHeight = $('.stream-content').height();
        var newHeight = { marginTop: $('body').hasClass('mobile') ? 0 : 10 };
        
        if(streamHeight < windowHeight)
            newHeight = { marginTop: (windowHeight - streamHeight - 50)+'px' };
        
        if(typeof animate != "undefined")
            $('#stream').animate(newHeight);
        else
            $('#stream').css(newHeight);
        
        setTimeout(function () {
            Elastic.refresh($('#stream'));
            $('.full-height').each(function(item, el) {
                $(el).css({height: streamHeight+'px'});
            });
            if(selfoss.scroller!=false) {
                selfoss.scroller.refresh();
            }
        }, 0);
    },
    
    resize: function() {
        selfoss.setStreamPosition();
    },
    
    events: function() {
        $('.entry-title').unbind('click').click(function() {
            var next = $(this).next();
            if(next.hasClass('open'))
                next.removeClass('open').slideUp('fast',selfoss.setStreamPosition);
            else
                next.addClass('open').slideDown('fast',selfoss.setStreamPosition);
        });
    }
}
