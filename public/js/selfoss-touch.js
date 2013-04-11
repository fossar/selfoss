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
            next = null;
        
        // find next/prev item
        if(direction == 'next') {
            next = $('#entry' + cur_id).next();
        }else{
            next = $('#entry' + cur_id).prev();
        }
        
        if(next.attr('id') == undefined && direction == 'next') {
            // get more items
            $('.stream-more').click();
            $(document).ajaxComplete(function() {
                next = $('#entry' + cur_id).next();
                next.find('h2').click();
            });
        }else{
            next.find('h2').click();
        }
        
    },
  
}
