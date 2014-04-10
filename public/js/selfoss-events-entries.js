/**
 * initialize events for entries
 */
selfoss.events.entries = function(e) {

    $('.entry, .entry-title').unbind('click');

    // show/hide entry
    var target = selfoss.isMobile() ? '.entry' : '.entry-title';
    $(target).click(function() {
        var parent = ((target == '.entry') ? $(this) : $(this).parent());
        
        if(selfoss.isSmartphone()==false) {
            $('.entry.selected').removeClass('selected');
            parent.addClass('selected');
        }
        
        // prevent event on fullscreen touch
        if(parent.hasClass('fullscreen'))
            return;
        
        var autoMarkAsRead = $('#config').data('auto_mark_as_read')=="1" && parent.hasClass('unread');
        
        // anonymize
        selfoss.anonymize(parent.find('.entry-content'));
        
         // show entry in popup
        if(selfoss.isSmartphone()) {
            location.hash = "show";
            
            // hide nav
            if($('#nav').is(':visible')) {
                var scrollTop = $(window).scrollTop();
                scrollTop = scrollTop - $('#nav').height();
                scrollTop = scrollTop<0 ? 0 : scrollTop;
                $(window).scrollTop(scrollTop);
                $('#nav').hide();
            }
            
            // save scroll position and hide content
            var scrollTop = $(window).scrollTop();
            var content = $('#content');
            $(window).scrollTop(0);
            content.hide();
            
            // show fullscreen
            var fullscreen = $('#fullscreen-entry');
            fullscreen.html('<div id="entrr'+parent.attr('id').substr(5)+'" class="entry fullscreen">'+parent.html()+'</div>');
            fullscreen.show();

            // lazy load images in fullscreen
            if($('#config').data('load_images_on_mobile')=="1") {
                fullscreen.lazyLoadImages();
                fullscreen.find('.entry-loadimages').hide();
            }
            
            // set events for fullscreen
            selfoss.events.entriesToolbar(fullscreen);
            
            // set color of all tags by background color
            fullscreen.find('.entry-tags-tag').colorByBrightness();
    
            // set events for closing fullscreen
            fullscreen.find('.entry, .entry-close').click(function(e) {
                if(e.target.tagName.toLowerCase()=="a")
                    return;
                content.show();
                location.hash = "";
                $(window).scrollTop(scrollTop);
                fullscreen.hide();
            });
            
            // automark as read
            if(autoMarkAsRead) {
                fullscreen.find('.entry-unread').click();
            }
        // open entry content
        } else {
            var content = parent.find('.entry-content');
            
            // show/hide (with toolbar)
            if(content.is(':visible')) {
                parent.find('.entry-toolbar').hide();
                content.hide();
            } else {
                content.show();
                selfoss.events.entriesToolbar(parent);
                parent.find('.entry-toolbar').show();
                
                // automark as read
                if(autoMarkAsRead) {
                    parent.find('.entry-unread').click();
                }
            }
            
            // load images not on mobile devices
            if(selfoss.isMobile()==false || $('#config').data('load_images_on_mobile')=="1") {
                content.lazyLoadImages();
            }
        } 
    });

    // no source click
    if(selfoss.isSmartphone())
        $('.entry-source, .entry-icon').unbind('click').click(function(e) {e.preventDefault(); return false });
    
    // markread  (only loggedin users)
    if($('body').hasClass('loggedin')==true) {
        $('.entry-markread').unbind('click').click(function() {
            var lastItemId = $(this).attr('data-itemid');
            
            var ids = new Array();
            $('.entry.unread').each(function(index, item) {
                  var itemId = $(item).attr('id').substr(5);
                  ids.push( itemId );
                  if( itemId === lastItemId ){ return false; }
            });

            if(ids.length === 0){
                return;
            }
            
            // show loading
            var content = $('#content');
            var articleList = content.html();
            $('#content').addClass('loading').html("");
            
            $.ajax({
                url: $('base').attr('href') + 'mark',
                type: 'POST',
                dataType: 'json',
                data: {
                    ids: ids
                },
                success: function(response) {
                    $('.entry').removeClass('unread');
                    
                    // update unread stats
                    var unreadstats = parseInt($('.nav-filter-unread span').html()) - ids.length;
                    $('.nav-filter-unread span').html(unreadstats);
                    $('.nav-filter-unread span').removeClass('unread');
                    if(unreadstats>0)
                        $('.nav-filter-unread span').addClass('unread');
                    
                    // hide nav on smartphone
                    if( selfoss.isSmartphone() && $('#nav').is(':visible') )
                        $('#nav-mobile-settings').click();
                    
                    // refresh list
                    if( 'unread' === selfoss.filter.type ){
                      selfoss.filter.offset = 0;
                    }
                    selfoss.reloadList();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    content.html(articleList);
                    $('#content').removeClass('loading');
                    selfoss.showError('Can not mark all visible item: ' + errorThrown);
                }
            });
            
            return false;
        });
        
        $('.entry-markread').show();
    }
    
    // scroll load more
    $(window).unbind('scroll').scroll(function() {
        if($('#content').is(':visible')==false)
            return;
    
        var content = $('#content');
        if($('.stream-more').length > 0 
           && $('.stream-more').position().top < $(window).height() + $(window).scrollTop() 
           && $('.stream-more').hasClass('loading')==false)
            $('.stream-more').click();
    });
    
    // more
    $('.stream-more').unbind('click').click(function () {
        var streamMore = $(this);
        selfoss.filter.offset += selfoss.filter.itemsPerPage;
        
        streamMore.addClass('loading');
        $.ajax({
            url: $('base').attr('href'),
            type: 'GET',
            dataType: 'json',
            data: selfoss.filter,
            success: function(data) {
                $('.stream-more').replaceWith(data.entries);
                selfoss.events.entries();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                streamMore.removeClass('loading');
                selfoss.showError('Load more error: '+errorThrown);
            }
        });
    });
    
    // set color of all tags by background color
    $('.entry-tags-tag').colorByBrightness();
    
    // click a tag
    if(selfoss.isSmartphone()==false) {
        $('.entry-tags-tag').unbind('click').click(function() {
            var tag = $(this).html();
            $('#nav-tags .tag').each(function(index, item) {
                if($(item).html()==tag) {
                    $(item).click();
                    return false;
                }
            });
        });
    }
};
