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
        var autoHideReadOnMobile = $('#config').data('auto_hide_read_on_mobile')=="1" && parent.hasClass('unread');
        
        // anonymize
        selfoss.anonymize(parent.find('.entry-content'));
        
        var entryId = parent.attr('data-entry-id');

         // show entry in popup
        if(selfoss.isSmartphone()) {
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
            fullscreen.html('<div id="entrr'+parent.attr('data-entry-id')+'" class="entry fullscreen" data-entry-id="'+parent.attr('data-entry-id')+'">'+parent.html()+'</div>');
            fullscreen.show();
            selfoss.events.setHash('same', 'same', entryId);

            // lazy load images in fullscreen
            if($('#config').data('load_images_on_mobile')=="1") {
                fullscreen.lazyLoadImages();
                fullscreen.find('.entry-loadimages').hide();
            }
            
            // set events for fullscreen
            selfoss.events.entriesToolbar(fullscreen);

            // set events for closing fullscreen
            fullscreen.find('.entry, .entry-close').click(function(e) {
                if(e.target.tagName.toLowerCase()=="a")
                    return;
                if(autoHideReadOnMobile && ($('#entrr'+parent.attr('id').substr(5)).hasClass('unread')==false)) {
                    $('#'+parent.attr('id')).hide();
                }
                content.show();
                selfoss.events.setHash();
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
                selfoss.events.setHash();
            } else {
                if($('#config').data('auto_collapse')=="1"){
                    $('.entry-content, .entry-toolbar').hide();
                }
                content.show();
                selfoss.events.setHash('same', 'same', entryId);
                selfoss.events.entriesToolbar(parent);
                parent.find('.entry-toolbar').show();
                
                // automark as read
                if(autoMarkAsRead) {
                    parent.find('.entry-unread').click();
                }

                // setup fancyBox image viewer
                selfoss.setupFancyBox(content, parent.attr('id').substr(5));

                // scroll to article header
                if ($('#config').data('scroll_to_article_header') == '1' ||
                    selfoss.events.entryId ) {
                  parent.get(0).scrollIntoView();
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
        $('.entry-icon, .entry-datetime').unbind('click').click(function(e) {e.preventDefault(); return false });
    
    // scroll load more
    $(window).unbind('scroll').scroll(function() {
        if($('#config').data('auto_stream_more') == 0 ||
           $('#content').is(':visible')==false)
            return;
    
        var content = $('#content');
        if($('.stream-more').is(':visible')
           && $('.stream-more').position().top < $(window).height() + $(window).scrollTop() 
           && $('.stream-more').hasClass('loading')==false)
            $('.stream-more').click();
    });
    
    $('.mark-these-read').unbind('click').click(selfoss.markVisibleRead);

    $('.stream-error').unbind('click').click(selfoss.reloadList);

    // more
    $('.stream-more').unbind('click').click(function () {
        var streamMore = $(this);
        var lastEntry = $('.entry').not('.fullscreen').filter(':last');
        selfoss.events.setHash();
        selfoss.filter.extra_ids.length = 0;
        selfoss.filter.offset_from_datetime = lastEntry.data('entry-datetime');
        selfoss.filter.offset_from_id = lastEntry.data('entry-id');
        
        streamMore.addClass('loading');
        $.ajax({
            url: $('base').attr('href'),
            type: 'GET',
            dataType: 'json',
            data: selfoss.filter,
            success: function(data) {
                streamMore.removeClass('loading');
                lastEntry.after(data.entries);
                selfoss.ui.refreshStreamButtons(true, true, data.hasMore)
                selfoss.events.entries();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                streamMore.removeClass('loading');
                selfoss.showError('Load more error: '+
                                  textStatus+' '+errorThrown);
            }
        });
    });

    // click a source
    if (selfoss.isSmartphone() == false) {
        $('.entry-source').unbind('click').click(function(e) {
            var entry = $(this).parents('.entry');
            selfoss.events.setHash('same',
                                   'source-' + entry.attr('data-entry-source'));
        });
    }

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

    // updates a source
    $('#refresh-source').unbind('click').click(function () {
        // show loading
        var content = $('#content');
        var articleList = content.html();
        $('#content').addClass('loading').html("");

        $.ajax({
            url: $('base').attr('href') + 'source/' + selfoss.filter.source + '/update',
            type: 'POST',
            dataType: 'text',
            data: { ajax: true },
            success: function(response) {
                // hide nav on smartphone
                if(selfoss.isSmartphone()) {
                    $('#nav-mobile-settings').click();
                }
                // refresh list
                selfoss.reloadList();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                content.html(articleList);
                $('#content').removeClass('loading');
                alert('Can not refresh source: ' + errorThrown);
            }
        });
    });

    // open selected entry only if entry was request (i.e. if not streaming
    // more)
    if( selfoss.events.entryId && selfoss.filter.offset_from_id == null ) {
        $('#entry' + selfoss.events.entryId).children('.entry-title').click();
    }
};
