/**
 * toolbar of an single entry
 */
selfoss.events.entriesToolbar = function(parent) {
    if(typeof parent == "undefined")
        parent = $('#content');
    
    // prevent close on links
    parent.find('a').unbind('click').click(function(e) {
        window.open($(this).attr('href'));
        e.preventDefault();
        return false;
    });
    
    // load images
    parent.find('.entry-loadimages').unbind('click').click(function() {
        $(this).parents('.entry').lazyLoadImages();
        $(this).fadeOut();
        return false;
    });
    
    // open in new window
    parent.find('.entry-newwindow').unbind('click').click(function(e) {
        window.open($(this).parents(".entry").children(".entry-source").attr("href"));
        e.preventDefault();
        return false;
    });

    // next item on smartphone
    parent.find('.entry-toolbar .entry-next').unbind('click').click(function(e) {
        selfoss.shortcuts.nextprev('next', true);
        return false;
    });
    
    // next item on tablet
    parent.find('.entry-smartphone-share .entry-next').unbind('click').click(function(e) {
        var $selected = $('.entry.selected, .entry.fullscreen:visible');
        var id = $selected.attr('id').replace('entrr', 'entry');
        $selected.find('.entry-unread.active').click();
        $selected.find('.entry-title').click();
        $("#" + id).next('.entry').find('.entry-title').click();
        return false;
    });

    // configure shares
    var shares = selfoss.shares.getAll();
    for (var i = 0; i < shares.length; i++) {
        (function(share){
            parent.find('.entry-share' + share).unbind('click').click(function(e) {
              var entry = $(this).parents(".entry");
              selfoss.shares.share(share, entry.children(".entry-link").eq(0).attr("href"), entry.children(".entry-title").html());
              e.preventDefault();
              return false;
            });
        })(shares[i]);
    }

    // only loggedin users
    if($('body').hasClass('loggedin')==true) {
        // starr/unstarr
        parent.find('.entry-starr').unbind('click').click(function() {
            var parent = $(this).parents('.entry');
            var id = parent.attr('id').substr(5);
            var starr = $(this).hasClass('active')==false;
            var button = $("#entry"+id+" .entry-starr, #entrr"+id+" .entry-starr");
            
            // update button
            var setButton = function(starr) {
                if(starr) {
                    button.addClass('active');
                    button.html($('#lang').data('unstar'));
                } else {
                    button.removeClass('active');
                    button.html($('#lang').data('star'));
                }
            };
            setButton(starr);
            
            // update statistics in main menue
            var updateStats = function(starr) {
                var starred = parseInt($('.nav-filter-starred span').html());
                if(starr) {
                    starred++;
                } else {
                    starred--;
                }
                $('.nav-filter-starred span').html(starred);
            };
            updateStats(starr);
            
            $.ajax({
                url: $('base').attr('href') + (starr ? 'starr/' : 'unstarr/') + id,
                data: { ajax: true },
                type: 'POST',
                error: function(jqXHR, textStatus, errorThrown) {
                    // rollback ui changes
                    setButton(!starr);
                    updateStats(!starr);
                    selfoss.showError('Can not star/unstar item: '+
                                      textStatus+' '+errorThrown);
                }
            });
            
            return false;
        });
        
        // read/unread
        parent.find('.entry-unread').unbind('click').click(function() {
            var id = $(this).parents('.entry').attr('id').substr(5);
            var unread = $(this).hasClass('active')==true;
            var button = $("#entry"+id+" .entry-unread, #entrr"+id+" .entry-unread");
            var parent = $("#entry"+id+", #entrr"+id);

            // update button
            var setButton = function(unread) {
                if(unread) {
                    button.removeClass('active');
                    button.html($('#lang').data('unmark'));
                    parent.removeClass('unread');
                } else {
                    button.addClass('active');
                    button.html($('#lang').data('mark'));
                    parent.addClass('unread');
                }
            };
            setButton(unread);
            
            // update statistics in main menue and the currently active tag
            var updateStats = function(unread) {
                // update all unread counter
                var unreadstats = parseInt($('.nav-filter-unread span').html());
                if(unread) {
                    unreadstats--;
                } else {
                    unreadstats++;
                }
                selfoss.refreshUnread(unreadstats);
                    
                // update unread count on sources
                var sourceId = $('#entry'+id+' .entry-source').attr('class').substr(25);
                var sourceNav = $('#source'+sourceId+' .unread');
                var sourceCount = parseInt(sourceNav.html());
                if(typeof sourceCount != "number" || isNaN(sourceCount)==true)
                    sourceCount = 0;
                sourceCount = unread ? sourceCount-1 : sourceCount+1;
                if(sourceCount<=0) {
                    sourceCount = "";
                    $('#source'+sourceId+'').removeClass('unread');
                } else {
                    $('#source'+sourceId+'').addClass('unread');
                }
                sourceNav.html(sourceCount);
                
                // update unread on tags
                $('#entry'+id+' .entry-tags-tag').each( function(index) {
                    var tag = $(this).html();
                    
                    var tagsCountEl = $('#nav-tags > li > span.tag').filter(function(i){
                        return $(this).html()==tag; }
                    ).next();
                    
                    var unreadstats = 0;
                    if (tagsCountEl.html()!='')
                        unreadstats = parseInt(tagsCountEl.html());
                    
                    if (unread)
                        unreadstats--;
                    else
                        unreadstats++;
                    
                    if (unreadstats>0)
                        tagsCountEl.html(unreadstats);
                    else
                        tagsCountEl.html('');
                    
                } );
            };
            updateStats(unread);
            
            $.ajax({
                url: $('base').attr('href') + (unread ? 'mark/' : 'unmark/') + id,
                data: { ajax: true },
                type: 'POST',
                error: function(jqXHR, textStatus, errorThrown) {
                    // rollback ui changes
                    updateStats(!unread);
                    setButton(!unread);
                    selfoss.showError('Can not mark/unmark item: '+
                                      textStatus+' '+errorThrown);
                }
            });
            
            return false;
        });
    }
};
