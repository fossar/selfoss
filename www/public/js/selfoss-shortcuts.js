selfoss.shortcuts = {


	/**
	 * init shortcuts
	 */
	init: function() { 
        var options = {"disable_in_input": true};
        
        // next
        shortcut.add('Space', function() { selfoss.shortcuts.nextprev('next', true, false); return false; }, options);
        shortcut.add('n', function() { selfoss.shortcuts.nextprev('next', false); return false; }, options);
        shortcut.add('j', function() { selfoss.shortcuts.nextprev('next', true); return false; }, options);
        
        // prev
        shortcut.add('Shift+Space', function() { selfoss.shortcuts.nextprev('prev', true); return false; }, options);
        shortcut.add('p', function() { selfoss.shortcuts.nextprev('prev', false); return false; }, options);
        shortcut.add('k', function() { selfoss.shortcuts.nextprev('prev', true); return false; }, options);
        
        // star/unstar
        shortcut.add('s', function() {
            $('.entry.selected .entry-starr').click();
        }, options);
        
		// mark/unmark
		shortcut.add('m', function() {
            $('.entry.selected .entry-unread').click();
        }, options);
		
        // open target
        shortcut.add('v', function() {
            window.open($('.entry.selected .entry-source').attr('href'));
        }, options);
		
		// mark all as read
        shortcut.add('Ctrl+m', function() {
            $('#nav-mark').click();
        }, options);
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
        if(open && current.find('.entry-thumbnail').length==0) {
            var content = current.find('.entry-content');
            // load images not on mobile devices
			if(selfoss.isMobile()==false)
				content.lazyLoadImages();
            content.show();
			current.find('.entry-toolbar').show();
        }
        
        // scroll to element
        selfoss.shortcuts.autoscroll(current);
    },
	
	
	/**
     * autoscroll
     */
    autoscroll: function(next) {
		var viewportHeight = $(window).height();
		var viewportScrollTop = $(document).scrollTop();
		
		// scroll down
		if(viewportScrollTop + viewportHeight < next.position().top + next.height() + 80) {
			if(next.height() > viewportHeight) {
                $(document).scrollTop(next.position().top);
            } else {
                $(document).scrollTop(viewportScrollTop + next.height());
			}
		}
		
		// scroll up
        if(next.position().top <= viewportScrollTop) {
			$(document).scrollTop(next.position().top);
		}
    },
	
}