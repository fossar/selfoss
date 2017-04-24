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
        $('#enableoffline').prop('checked', selfoss.db.enableOffline);
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
        unread = (typeof unread !== 'undefined') ? unread : parseInt($('.unread-count .count').html());

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


    setOffline: function() {
        $('.offlineable').addClass('offline');
        $('.offlineable').removeClass('online');
        $('#nav-tags li:not(:first)').remove();
        if (!$('#nav-sources-title').hasClass('nav-sources-collapsed')) {
            $('#nav-sources-title').click();
        }
        selfoss.events.navigation();
    },


    setOnline: function() {
        $('.offlineable').addClass('online');
        $('.offlineable').removeClass('offline');
        selfoss.events.navigation();
    },


    entryStar: function(id, starred, domNode) {
        var button = $('#entry' + id + ' .entry-starr, #entrr' + id + ' .entry-starr',
            domNode);

        // update button
        if (starred) {
            button.addClass('active');
            button.html($('#lang').data('unstar'));
        } else {
            button.removeClass('active');
            button.html($('#lang').data('star'));
        }
    },


    entryMark: function(id, unread, domNode) {
        var button = $('#entry' + id + ' .entry-unread, #entrr' + id + ' .entry-unread',
            domNode);
        var parent = $('#entry' + id + ', #entrr' + id, domNode);

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


    refreshEntryStatuses: function(entryStatuses) {
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
                selfoss.ui.entryStar(id, newStatus.starred);
                selfoss.ui.entryMark(id, newStatus.unread);
            }
        });
    },


    refreshStreamButtons: function(entries, hasMore) {
        entries = (typeof entries !== 'undefined') ? entries : false;
        hasMore = (typeof hasMore !== 'undefined') ? hasMore : false;

        $('.stream-button, .stream-empty').css('display', 'block').hide();
        if (entries) {
            if ($('.entry').not('.fullscreen').length > 0) {
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


    beforeReloadList: function(clear) {
        clear = (typeof clear !== 'undefined') ? clear : true;

        var content = $('#content');

        content.addClass('loading');
        if (clear) {
            content.html('');
        }

        $('#stream-buttons').hide();
    },


    listReady: function() {
        $('#content').removeClass('loading');
        $('#stream-buttons').show();
        selfoss.events.entries();
    },


    afterReloadList: function(cleared) {
        cleared = (typeof cleared !== 'undefined') ? cleared : true;

        selfoss.ui.listReady();

        if (cleared) {
            $(document).scrollTop(0);
        }

        selfoss.ui.refreshEntryDatetimes();
        selfoss.events.search();
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
                        } else if (plural) {
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
                        } else {
                            formatted = formatted + placeholder.value;
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


    notifyNewVersion: function(cb) {
        if (!cb) {
            cb = function() {
                window.location.reload();
            };
        }

        selfoss.ui.showMessage(selfoss.ui._('app_update'),
            selfoss.ui._('app_reload'),
            function() {
                cb();
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
    },


    refreshTagSourceUnread: function(tagCounts, sourceCounts, diff) {
        diff = (typeof diff !== 'undefined') ? diff : true;

        tagCounts.forEach(function(tagCount) {
            var tagsCountEl = $('#nav-tags > li > span.tag')
                .filter(function() {
                    return $(this).html() == tagCount.tag;
                }
                ).next();

            var unreadCount = 0;
            if (diff) {
                if (tagsCountEl.html() != '') {
                    unreadCount = parseInt(tagsCountEl.html());
                }
                unreadCount = unreadCount + tagCount.count;
            } else {
                unreadCount = tagCount.count;
            }

            if (unreadCount > 0) {
                tagsCountEl.html(unreadCount);
            } else {
                tagsCountEl.html('');
            }
        });

        if (selfoss.sourcesNavLoaded) {
            sourceCounts.forEach(function(sourceCount) {
                var sourceNav = $('#source' + sourceCount.source);
                var sourcesCountEl = $('span.unread', sourceNav);

                var unreadCount = 0;
                if (diff) {
                    if (sourcesCountEl.html() != '') {
                        unreadCount = parseInt(sourcesCountEl.html());
                    }
                    unreadCount = unreadCount + sourceCount.count;
                } else {
                    unreadCount = sourceCount.count;
                }

                if (unreadCount > 0) {
                    sourceNav.addClass('unread');
                    sourcesCountEl.html(unreadCount);
                } else {
                    sourceNav.removeClass('unread');
                    sourcesCountEl.html('');
                }
            });
        }
    },


    refreshOfflineCounts: function(offlineCounts) {
        for (var ck in offlineCounts) {
            if (offlineCounts.hasOwnProperty(ck)) {
                var selector = '#nav-filter-' + ck;
                if (ck == 'unread') {
                    selector = selector + ', #nav-mobile-count';
                }
                var widget = $(selector);
                var offlineWidget = $('span.offline-count', widget);

                if (offlineCounts[ck] == 'keep') {
                    offlineCounts[ck] = parseInt(offlineWidget.html());
                } else {
                    offlineWidget.html(offlineCounts[ck]);
                }

                if (parseInt($('span.count', widget).html()) !=
                    offlineCounts[ck]) {
                    offlineWidget.addClass('diff');
                } else {
                    offlineWidget.removeClass('diff');
                }
            }
        }
    }


};
