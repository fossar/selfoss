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
    
        $$('#fullscreen-entry').swipeLeft(function(){
            // Next entry
            selfoss.touch.entrynav('next');
        });
        
        $$('#fullscreen-entry').swipeRight(function(){
            // Next entry
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
        
        var current = $('#fullscreen-entry').find('.fullscreen'),
            cur_id = current.attr('id').substr(5),
            next = null,
            auto_read = $('#config').data('auto_mark_as_read');
        
        // find next/prev item
        if(direction == 'next') {
            next = $('#entry' + cur_id).next();
        }else{
            next = $('#entry' + cur_id).prev();
        }
        
        // mark read
        if(auto_read == "1" && next.hasClass('unread')) {
            next.removeClass('unread');
        }
        
        // show entry
        if(next.attr('id') == undefined && direction == 'next') {
            // get more items, we're at the end of the current list
            $('.stream-more').click();
            
            // TODO: Find a way to open next entry after ajax load without extra swipe
            
        }else if(next.attr('id') == undefined && direction == 'prev') {
            // There is nothing before the first entry, close current entry
            current.find('.entry-close').click();
        }else{
            // show next/prev entry
            next.click();
        }
        
    },
  
}