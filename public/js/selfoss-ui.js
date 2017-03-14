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


    refreshItemStatuses: function(entryStatuses) {
        $('.entry').each(function(index, item) {
            var id = $(this).data('entry-id');
            var newStatus = false;
            entryStatuses.some(function(entryStatus) {
                if( entryStatus.id == id )
                    newStatus = entryStatus;
                return newStatus;
            });
            if( newStatus ) {
                selfoss.ui.entryStarr(id, newStatus.starred);
                selfoss.ui.entryMark(id, newStatus.unread);
            }
        });
    },


    refreshStreamButtons: function(entries, hasEntries, hasMore) {
        var entries = (typeof entries !== 'undefined') ? entries : false;
        var hasEntries = (typeof hasEntries !== 'undefined') ? hasEntries : false;
        var hasMore = (typeof hasMore !== 'undefined') ? hasMore : false;

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
    },


    /**
     * show error
     *
     * @return void
     * @param message string
     */
    showError: function(message) {
        selfoss.ui.showMessage(message, 'undefined', 'undefined', true);
    },


    showMessage: function(message, actionText, action, error) {
        var actionText = (typeof actionText !== 'undefined') ? actionText: false;
        var action = (typeof action !== 'undefined') ? action : false;
        var error = (typeof error !== 'undefined') ? error: false;

        if(typeof(message) == 'undefined') {
            var message = "Oops! Something went wrong";
        }

        if( actionText && action ) {
            message = message + '. <a>' + actionText + '</a>';
        }

        var messageContainer = $('#message');
        messageContainer.html(message);

        if( action ) {
            messageContainer.find('a').unbind('click').click(action);
        }

        if( error ) {
            messageContainer.addClass('error');
        } else {
            messageContainer.removeClass('error');
        }

        messageContainer.show();
        window.setTimeout(function() {
            messageContainer.click();
        }, 15000);
        messageContainer.unbind('click').click(function() {
            messageContainer.fadeOut();
        });
    }


};
