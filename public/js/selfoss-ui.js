/**
 * ui change functions
 */
selfoss.ui = {


    showLogin: function(error) {
        error = (typeof error !== 'undefined') ? error : '';

        $('#mainui').hide();
        $('#loginform').show();
        selfoss.ui.refreshTitle(0);
        $('#loginform .error').html(error);
        $('#username').focus();
    },


    showMainUi: function() {
        $('#loginform').hide();
        $('#mainui').show();

        selfoss.ui.refreshTitle();
        selfoss.events.resize();
        selfoss.events.navigation();
    },


    hideMobileNav: function() {
        if (selfoss.isSmartphone() && $('#nav').is(':visible')) {
            $('#nav-mobile-settings').click();
        }
    },


    refreshTitle: function(unread) {
        unread = (typeof unread !== 'undefined') ? unread : parseInt($('span.unread-count').html());

        if (unread > 0) {
            $(document).attr('title', selfoss.htmlTitle + ' (' + unread + ')');
        } else {
            $(document).attr('title', selfoss.htmlTitle);
        }
    },


    login: function() {
        $('body').addClass('loggedin').removeClass('notloggedin');
    },


    logout: function() {
        selfoss.ui.hideMobileNav();
        $('body').removeClass('loggedin').addClass('notloggedin');
    },


    entryStarr: function(id, starred) {
        var button = $('#entry' + id + ' .entry-starr, #entrr' + id + ' .entry-starr');

        // update button
        if (starred) {
            button.addClass('active');
            button.html($('#lang').data('unstar'));
        } else {
            button.removeClass('active');
            button.html($('#lang').data('star'));
        }
    },


    entryMark: function(id, unread) {
        var button = $('#entry' + id + ' .entry-unread, #entrr' + id + ' .entry-unread');
        var parent = $('#entry' + id + ', #entrr' + id);

        // update button and entry style
        if (unread) {
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
        $('.entry').each(function() {
            var id = $(this).data('entry-id');
            var newStatus = false;
            entryStatuses.some(function(entryStatus) {
                if (entryStatus.id == id) {
                    newStatus = entryStatus;
                }
                return newStatus;
            });
            if (newStatus) {
                selfoss.ui.entryStarr(id, newStatus.starred);
                selfoss.ui.entryMark(id, newStatus.unread);
            }
        });
    },


    refreshStreamButtons: function(entries, hasEntries, hasMore) {
        entries = (typeof entries !== 'undefined') ? entries : false;
        hasEntries = (typeof hasEntries !== 'undefined') ? hasEntries : false;
        hasMore = (typeof hasMore !== 'undefined') ? hasMore : false;

        $('.stream-button, .stream-empty').css('display', 'block').hide();
        if (entries) {
            if (hasEntries) {
                $('.stream-empty').hide();
                if (selfoss.isSmartphone()) {
                    $('.mark-these-read').show();
                }
                if (hasMore) {
                    $('.stream-more').show();
                }
            } else {
                $('.stream-empty').show();
                if (selfoss.isSmartphone()) {
                    $('.mark-these-read').hide();
                }
            }
        }
    },


    /*
     * This is a naive and partial implementation for parsing the
     * local-aware formatted strings from the Fat-Free Framework.
     * The full spec is at https://fatfreeframework.com/3.6/base#format and is
     * not fully implemented.
     */
    i18nFormat: function(translated, params) {
        var formatted = '';

        var curChar = undefined;
        var buffer = '';

        var state = 'out';
        var placeholder = undefined;
        var plural = undefined;
        var pluralKeyword = undefined;
        var pluralValue = undefined;

        for (var i = 0, len = translated.length; i < len; i++) {
            curChar = translated.charAt(i);
            switch (curChar) {
            case '{':
                if (placeholder) {
                    if (state == 'plural') {
                        pluralKeyword = buffer.trim();
                        if ($.inArray(pluralKeyword,
                            ['zero', 'one', 'other']) > -1) {
                            buffer = '';
                        } else {
                            pluralKeyword = undefined;
                        }
                    }
                } else {
                    formatted = formatted + buffer;
                    buffer = '';
                    placeholder = {};
                    state = 'index';
                }
                break;
            case '}':
            case ',':
                if (placeholder) {
                    if (state == 'index') {
                        placeholder.index = parseInt(buffer.trim());
                        placeholder.value = params[placeholder.index];
                        buffer = '';
                    } else if (state == 'type') {
                        placeholder.type = buffer.trim();
                        buffer = '';
                        if (placeholder.type == 'plural') {
                            plural = {};
                            state = 'plural';
                        }
                    }
                    if (curChar == '}') {
                        if (state == 'plural' && pluralKeyword) {
                            plural[pluralKeyword] = buffer;
                            buffer = '';
                            pluralKeyword = undefined;
                        } else {
                            if ('zero' in plural
                                    && placeholder.value === 0) {
                                pluralValue = plural.zero;
                            } else if ('one' in plural
                                            && placeholder.value == 1) {
                                pluralValue = plural.one;
                            } else {
                                pluralValue = plural.other;
                            }
                            formatted = formatted + pluralValue.replace('#', placeholder.value);
                            plural = undefined;
                            placeholder = undefined;
                            state = 'out';
                        }
                    } else if (curChar == ',' && state == 'index') {
                        state = 'type';
                    }
                }
                break;
            default:
                buffer = buffer + curChar;
                break;
            }
        }

        if (state != 'out') {
            return 'Error formatting \'' + translated + '\', bug report?';
        }

        formatted = formatted + buffer;

        return formatted;
    },


    /* i18n */
    _: function(identifier, params) {
        var translated = $('#lang').data(identifier);
        if (!translated) {
            translated = '#untranslated:' + identifier;
        }

        if (params) {
            translated = selfoss.ui.i18nFormat(translated, params);
        }

        return translated;
    },


    /**
     * show error
     *
     * @return void
     * @param message string
     */
    showError: function(message) {
        selfoss.ui.showMessage(message, undefined, undefined, true);
    },


    showMessage: function(message, actionText, action, error) {
        actionText = (typeof actionText !== 'undefined') ? actionText : false;
        action = (typeof action !== 'undefined') ? action : false;
        error = (typeof error !== 'undefined') ? error : false;

        if (typeof(message) == 'undefined') {
            message = 'Oops! Something went wrong';
        }

        if (actionText && action) {
            message = message + '. <a>' + actionText + '</a>';
        }

        var messageContainer = $('#message');
        messageContainer.html(message);

        if (action) {
            messageContainer.find('a').unbind('click').click(action);
        }

        if (error) {
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
    },


    refreshEntryDatetimes: function() {
        $('.entry').not('.timestamped').each(function() {
            var datetime = $(this).data('entry-datetime');
            if (datetime) {
                datetime = new Date(datetime);
                var ageInseconds = (new Date() - datetime) / 1000;
                var ageInMinutes = ageInseconds / 60;
                var ageInHours = ageInMinutes / 60;
                var ageInDays = ageInHours / 24;

                var datetimeStr = null;
                if (ageInHours < 1) {
                    datetimeStr = selfoss.ui._('minutes', [Math.round(ageInMinutes)]);
                } else if (ageInDays < 1) {
                    datetimeStr = selfoss.ui._('hours', [Math.round(ageInHours)]);
                } else {
                    $(this).addClass('timestamped');
                    datetimeStr = datetime.toLocaleString();
                }

                $('.entry-datetime', this).html(datetimeStr);
            }
        });
    }


};
