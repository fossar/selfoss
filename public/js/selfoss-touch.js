/**
 * Selfoss Touch
 * Based on QuoJS
 * @link http://quojs.tapquo.com
 */
selfoss.touch = {

    /**
     * init swipes
     */
    init: function() {
    
        $$('#fullscreen-entry').swipeLeft(function(e) {
            e.preventDefault();
            
            // Next entry
            selfoss.touch.entrynav('next');
        });
        
        $$('#fullscreen-entry').swipeRight(function(e) {
            e.preventDefault();
            
            // prev entry
            selfoss.touch.entrynav('prev');
        });
    
    },
    
    
    /**
     * get next/prev item
     * @param direction
     */
    entrynav: function(direction) {
        if(typeof direction == "undefined" || (direction!="next" && direction!="prev"))
            direction = "next";
        
        var next = selfoss.touch.getcurrententry();
        
        // find next/prev item
        if(direction == 'next') {
            next = next.next();
        }else{
            next = next.prev();
        }
        
        // mark read
        selfoss.touch.markread(next);
        
        // show entry
        if(next.attr('id') == undefined && direction == 'next') {
            // get more items, we're at the end of the current list
            // next item will be loaded via succes in selfoss-events-entries.js
            $('.stream-more').click();
            
        }else if(next.attr('id') == undefined && direction == 'prev') {
            // there is nothing before the first entry, close current entry
            $('.entry-close:visible').click();
        }else{
            // show next/prev entry
            next.click();
        }
        
    },
    
    
    /**
     * get current visible entry
     */
    getcurrententry: function() {
        var cur_id = $('#fullscreen-entry').find('.fullscreen').attr('id').substr(5);
        return $('#entry' + cur_id);
    },
    
    
    /**
     * load next entry
     */
    shownextentry: function() {
        if(selfoss.isMobile()) {
            var entry = selfoss.touch.getcurrententry();
            if(entry.is(':visible') == false) {
                entry.next().click();
            }
            
        }
    },
    
    
    /**
     * mark element read
     */
    markread: function(elem) {
        var auto_read = $('#config').data('auto_mark_as_read');

        if(auto_read == "1" && elem.hasClass('unread')) {
            elem.removeClass('unread');
        }
    },
}