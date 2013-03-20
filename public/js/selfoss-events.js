selfoss.events = {

    /* last hash before hash change */
    lasthash: "",

    /**
     * init events when page loads first time
     */
    init: function() {
        selfoss.events.navigation();
        selfoss.events.entries();
        selfoss.events.search();
        
        // window resize
        $("#nav-tags-wrapper").mCustomScrollbar({
            advanced:{
                updateOnContentResize: true
            }
        });
        $(window).bind("resize", selfoss.events.resize);
        selfoss.events.resize();
        
        // hash change event
        window.onhashchange = selfoss.events.hashChange;
        
        // remove given hash (we just use it for history support)
        if(location.hash.trim().length!=0)
            location.hash = "";
    },
    
    
    /**
     * handle History change
     */
    hashChange: function() {
        // return to main page
        if(location.hash.length==0) {
            // from entry popup
            if(selfoss.events.lasthash=="#show" && $('#fullscreen-entry').is(':visible')) {
                $('#fullscreen-entry .entry-close').click();
            }
                
            // from sources
            if(selfoss.events.lasthash=="#sources") {
                $('#nav-filter li.active').click();
            }
                
            // from navigation
            if(selfoss.events.lasthash=="#nav" && $('#nav').is(':visible')) {
                $('#nav-mobile-settings').click();
            }
        }
        
        // load sources
        if(location.hash=="#sources") {
            $('#content').addClass('loading').html("");
            $.ajax({
                url: $('base').attr('href')+'sources',
                type: 'GET',
                success: function(data) {
                    $('#content').html(data);
                    selfoss.events.sources();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('Load sources error: '+errorThrown);
                },
                complete: function(jqXHR, textStatus) {
                    $('#content').removeClass('loading');
                }
            });
        }
        
        selfoss.events.lasthash = location.hash;
    },
    
    
    /**
     * set automatically the height of the tags and set scrollbar for div scrolling
     */
    resize: function() {
        // only set height if smartphone is false
        if(selfoss.isSmartphone()==false) {
            var start = $('#nav-tags-wrapper').position().top;
            var windowHeight = $(window).height();
            $('#nav-tags-wrapper').height(windowHeight - start - 100);
            $("#nav-tags-wrapper").mCustomScrollbar("update");
            $('#nav').show();
        } else {
            $('#nav-tags-wrapper').height("auto");
            $("#nav-tags-wrapper").mCustomScrollbar("disable",selfoss.isSmartphone());
        }
    }
};
