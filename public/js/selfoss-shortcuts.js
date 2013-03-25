selfoss.shortcuts = {


    /**
     * init shortcuts
     */
    init: function() { 
        // next
        $(document).bind('keydown', 'space', function() { selfoss.shortcuts.nextprev('next', true, false); return false; });
        $(document).bind('keydown', 'n', function() { selfoss.shortcuts.nextprev('next', false); return false; });
        $(document).bind('keydown', 'j', function() { selfoss.shortcuts.nextprev('next', true); return false; });
        
        // prev
        $(document).bind('keydown', 'shift+space', function() { selfoss.shortcuts.nextprev('prev', true); return false; });
        $(document).bind('keydown', 'p', function() { selfoss.shortcuts.nextprev('prev', false); return false; });
        $(document).bind('keydown', 'k', function() { selfoss.shortcuts.nextprev('prev', true); return false; });
        
        // star/unstar
        $(document).bind('keydown', 's', function() {
            $('.entry.selected .entry-starr').click();
        });
        
        // mark/unmark
        $(document).bind('keydown', 'm', function() {
            $('.entry.selected .entry-unread').click();
        });
        
        // open target
        $(document).bind('keydown', 'v', function() {
            window.open($('.entry.selected .entry-source').attr('href'));
        });
        
        // Reload the current view
        $(document).bind('keydown', 'r', function() {
            selfoss.reloadList();
        });
        
        // mark all as read
        $(document).bind('keydown', 'ctrl+m', function() {
            $('#nav-mark').click();
        });

        // throw (mark as read & open next)
        $(document).bind('keydown', 't', function() {
            $('.entry.selected.unread .entry-unread').click();
            selfoss.shortcuts.nextprev('next', true);
            return false;
        });

        // throw (mark as read & open previous)
        $(document).bind('keydown', 'Shift+t', function() {
            $('.entry.selected.unread .entry-unread').click();
            selfoss.shortcuts.nextprev('prev', true);
            return false;
        });
    },
    
    
    /**
     * get next/prev item
     * @param direction
     */
    nextprev: function(direction, open) {
        if(typeof direction == "undefined" || (direction!="next" && direction!="prev"))
            direction = "next";
       
        // helper functions
        var scroll = function(value) {
            // scroll down (negative value) and up (positive value)
            $('#content').scrollTop($('#content').scrollTop()+value);
        } 
        // select current        
        var old = $('.entry.selected');
        
        // select next/prev and save it to "current"
        if(direction=="next") {
            if(old.length==0) {
                current = $('.entry:eq(0)');
            } else {
                current = old.next().length==0 ? old : old.next();
            }
            
        } else {
            if(old.length==0) {
                return;
            }
            else {
                current = old.prev().length==0 ? old : old.prev();
            }
        }

        // remove active
        old.removeClass('selected');
        old.find('.entry-content').hide();
        old.find('.entry-toolbar').hide();
        
        if(current.length==0)
            return;

        current.addClass('selected');
        
        // load more
        if(current.hasClass('stream-more'))
            current.click().removeClass('selected').prev().addClass('selected');
        
        // open?
        if(open) {
            var content = current.find('.entry-content');
            // load images not on mobile devices
            if(selfoss.isMobile()==false)
                content.lazyLoadImages();
            content.show();
            current.find('.entry-toolbar').show();
            selfoss.events.entriesToolbar(current);
            // automark as read
            if($('body').hasClass('auto_mark_as_read') && current.hasClass('unread'))
                current.find('.entry-unread').click();
        }
        
        // scroll to element
        $(window).scrollTop(current.position().top);
    },
    
}
