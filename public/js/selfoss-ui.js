/**
 * ui change functions
 */
selfoss.ui = {


    entryStarr: function(id, starred) {
        var button = $("#entry"+id+" .entry-starr, #entrr"+id+" .entry-starr");

        // update button
        if(starred) {
            button.addClass('active');
            button.html($('#lang').data('unstar'));
        } else {
            button.removeClass('active');
            button.html($('#lang').data('star'));
        }
    },


    entryMark: function(id, unread) {
        var button = $("#entry"+id+" .entry-unread, #entrr"+id+" .entry-unread");
        var parent = $("#entry"+id+", #entrr"+id);

        // update button and entry style
        if(unread) {
            button.addClass('active');
            button.html($('#lang').data('mark'));
            parent.addClass('unread');
        } else {
            button.removeClass('active');
            button.html($('#lang').data('unmark'));
            parent.removeClass('unread');
        }
    },


    refreshItemStatuses: function(entry_statuses) {
        $('.entry').each(function(index, item) {
            var id = $(this).data(('entry-id'));
            new_status = entry_statuses.find(function(entry_status) {
                return entry_status.id == id;
            });
            if( new_status ) {
                selfoss.ui.entryStarr(id, new_status.starr);
                selfoss.ui.entryMark(id, new_status.unread);
            }
        });
    },


    refreshStreamButtons: function(entries=false,
                                   hasEntries=false, hasMore=false) {
        $('.stream-button, .stream-empty').css('display', 'block').hide();
        if( entries ) {
            if( hasEntries ) {
                $('.stream-empty').hide();
                if( selfoss.isSmartphone() )
                    $('.mark-these-read').show();
                if( hasMore )
                    $('.stream-more').show();
            } else {
                $('.stream-empty').show();
                if( selfoss.isSmartphone() )
                    $('.mark-these-read').hide();
            }
        }
    }


};
