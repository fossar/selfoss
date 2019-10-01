import locales from './locales';
import selfoss from './selfoss-base';

/**
 * ui change functions
 */
selfoss.ui = {
    /**
     * Currently selected entry.
     * The id in the location.hash should imply the selected entry.
     * It will also be used for keyboard navigation (for finding previous/next).
     * @private
     * @type {?jQuery Element}
     */
    selectedEntry: null,

    /**
     * Create basic DOM structure of the page.
     */
    init: function() {
        if ($('body').is('#hashpashwordbody, #opmlbody')) {
            // we do not want to create UI for non-app pages
            return;
        }

        $('body').append(`<div id="loginform">
            <form action="" method="post">
            <ul id="login">
                <li><h1>${selfoss.config.htmlTitle} login</h1></li>
                <li><label for="username">${selfoss.ui._('login_username')}</label> <input type="text" name="username" id="username" accesskey="u" autocomplete="username" required></li>
                <li><label for="password">${selfoss.ui._('login_password')}</label> <input type="password" name="password" id="password" accesskey="p" autocomplete="current-password"></li>
                <li><label for="enableoffline">${selfoss.ui._('login_offline')}</label> <input type="checkbox" name="enableoffline" id="enableoffline" accesskey="o"></li>
                <li class="error" aria-live="assertive"></li>
                <li class="button"><label>&nbsp;</label><input type="submit" accesskey="l" value="${selfoss.ui._('login')}" /></li>
            </ul>
            </form>
        </div>

        <div id="mainui">
            <!-- menu open for smartphone -->
            <div id="nav-mobile" role="navigation">
                <div id="nav-mobile-logo">
                    <div id="nav-mobile-count" class="unread-count offlineable">
                        <span class="offline-count offlineable"></span>
                        <span class="count"></span>
                    </div>
                </div>
                <button id="nav-mobile-settings" accesskey="t" aria-label="${selfoss.ui._('settingsbutton')}"></button>
            </div>

            <!-- navigation -->
            <div id="nav" role="navigation">
                <div id="nav-logo"></div>
                <button accesskey="a" id="nav-mark">${selfoss.ui._('markread')}</button>

                <div id="nav-filter-wrapper">
                <h2><button type="button" id="nav-filter-title" class="nav-filter-expanded" aria-expanded="true">${selfoss.ui._('filter')}</button></h2>
                <ul id="nav-filter" aria-labeledby="nav-filter-title">
                    <li>
                        <a id="nav-filter-newest" class="nav-filter-newest" href="#">
                            ${selfoss.ui._('newest')}
                            <span class="offline-count offlineable" title="${selfoss.ui._('offline_count')}"></span>
                            <span class="count" title="${selfoss.ui._('online_count')}"></span>
                        </a>
                    </li>
                    <li>
                        <a id="nav-filter-unread" class="nav-filter-unread" href="#">
                            ${selfoss.ui._('unread')}
                            <span class="unread-count offlineable">
                                <span class="offline-count offlineable" title="${selfoss.ui._('offline_count')}"></span>
                                <span class="count" title="${selfoss.ui._('online_count')}"></span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a id="nav-filter-starred" class="nav-filter-starred" href="#">
                            ${selfoss.ui._('starred')}
                            <span class="offline-count offlineable" title="${selfoss.ui._('offline_count')}"></span>
                            <span class="count" title="${selfoss.ui._('online_count')}"></span>
                        </a>
                    </li>
                </ul>
                </div>

                <hr>

                <div id="nav-tags-wrapper">
                <h2><button type="button" id="nav-tags-title" class="nav-tags-expanded" aria-expanded="true">${selfoss.ui._('tags')}</button></h2>
                <ul id="nav-tags" aria-labeledby="nav-tags-title">
                    <li><a class="active nav-tags-all" href="#">${selfoss.ui._('alltags')}</a></li>
                </ul>
                <h2><button type="button" id="nav-sources-title" class="nav-sources-collapsed" aria-expanded="false">${selfoss.ui._('sources')}</button></h2>
                <ul id="nav-sources" aria-labeledby="nav-sources-title">
                </ul>
                </div>

                <hr>

                <!-- navigation search input just for smartphone version -->
                <div id="nav-search" class="offlineable" role="search">
                    <input aria-label="${selfoss.ui._('search_label')}" type="search" id="nav-search-term" accesskey="s"> <input type="button" id="nav-search-button" value="${selfoss.ui._('searchbutton')}" accesskey="e">
                    <hr>
                </div>

                <div class="nav-toolbar">
                    <button id="nav-refresh" title="${selfoss.ui._('refreshbutton')}" aria-label="${selfoss.ui._('refreshbutton')}" accesskey="r"></button>
                    <button id="nav-settings" title="${selfoss.ui._('settingsbutton')}" aria-label="${selfoss.ui._('settingsbutton')}" accesskey="t"></button>
                    <button id="nav-logout" title="${selfoss.ui._('logoutbutton')}" aria-label="${selfoss.ui._('logoutbutton')}" accesskey="l"></button>
                    <button id="nav-login" title="${selfoss.ui._('loginbutton')}" aria-label="${selfoss.ui._('loginbutton')}" accesskey="l"></button>
                </div>
            </div>

            <!-- search -->
            <div id="search" role="search" class="offlineable">
                <input aria-label="${selfoss.ui._('search_label')}" type="search" id="search-term" accesskey="s">
                <button id="search-remove" title="${selfoss.ui._('searchremove')}" accesskey="h" aria-label="${selfoss.ui._('searchremove')}"><img src="images/remove.png" aria-hidden="true" alt=""></button>
                <button id="search-button" title="${selfoss.ui._('searchbutton')}" aria-label="${selfoss.ui._('searchbutton')}" accesskey="e"><img src="images/search.png" alt=""></button>
            </div>

            <ul id="search-list">
            </ul>

            <!-- content -->
            <div id="content" role="main">
            </div>

            <div id="stream-buttons">
                <p aria-live="assertive" class="stream-empty">${selfoss.ui._('no_entries')}</p>
                <button class="stream-button stream-more" accesskey="m" aria-label="${selfoss.ui._('more')}"><span>${selfoss.ui._('more')}</span></button>
                <button class="stream-button mark-these-read" aria-label="${selfoss.ui._('markread')}</span>"><span>${selfoss.ui._('markread')}</span></button>
                <button class="stream-button stream-error" aria-live="assertive" aria-label="${selfoss.ui._('streamerror')}">${selfoss.ui._('streamerror')}</button>
            </div>
        </div>`);

        // Cannot add these to the append above, since jQuery automatically cache-busts links, which would prevent them from loading off-line.
        if (selfoss.config.userCss !== null) {
            let link = document.createElement('link');
            link.setAttribute('rel', 'stylesheet');
            link.setAttribute('href', `user.css?v=${selfoss.config.userCss}`);
            document.head.appendChild(link);
        }
        if (selfoss.config.userJs !== null) {
            let script = document.createElement('script');
            script.setAttribute('src', `user.js?v=${selfoss.config.userJs}`);
            document.body.appendChild(script);
        }
    },

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


    /**
     * Expand given entries.
     * @param {jQuery wrapped Element(s)} entry element(s)
     */
    entryExpand: function(entry) {
        if (!entry) {
            return;
        }

        entry.addClass('expanded');
        $('.entry-title > .entry-title-link', entry).attr('aria-expanded', 'true');
    },


    /**
     * Collapse given entries.
     * @param {jQuery wrapped Element(s)} entry element(s)
     */
    entryCollapse: function(entry) {
        if (!entry) {
            return;
        }

        entry.removeClass('expanded');
        $('.entry-title > .entry-title-link', entry).attr('aria-expanded', 'false');
    },


    /**
     * Collapse all expanded entries.
     */
    entryCollapseAll: function() {
        selfoss.ui.entryCollapse($('.entry.expanded'));
    },


    /**
     * Is given entry expanded?
     * @param {jQuery wrapped Element} entry element
     * @return {bool} whether it is expanded
     */
    entryIsExpanded: function(entry) {
        return entry.hasClass('expanded');
    },


    /**
     * Toggle expanded state of given entry.
     * @param {?jQuery wrapped Element} entry element
     */
    entryToggleExpanded: function(entry) {
        if (!entry) {
            return;
        }

        if (selfoss.ui.entryIsExpanded(entry)) {
            selfoss.ui.entryCollapse(entry);
        } else {
            selfoss.ui.entryExpand(entry);
        }
    },


    /**
     * Activate entry as if it were clicked.
     * This will open it, focus it and based on the settings, mark it as read.
     * @param {jQuery wrapped Element} entry element
     */
    entryActivate: function(entry) {
        entry.find('.entry-title > .entry-title-link').click();
    },


    /**
     * Deactivate entry, as if it were clicked.
     * This will close it and maybe something more.
     * @param {?jQuery wrapped Element} entry element
     */
    entryDeactivate: function(entry) {
        if (entry === null) {
            return;
        }

        if (selfoss.isSmartphone()) {
            entry = entry.get(0);

            entry.closeFullScreen();
        } else {
            if (selfoss.ui.entryIsExpanded(entry)) {
                entry.find('.entry-title > .entry-title-link').click();
            }
        }
    },


    /**
     * Make the given entry currently selected one.
     * @param {jQuery wrapped Element} entry element
     */
    entrySelect: function(entry) {
        if (selfoss.ui.selectedEntry !== null) {
            selfoss.ui.selectedEntry.removeClass('selected');
            $('.entry-title > .entry-title-link', selfoss.ui.selectedEntry).attr('aria-current', 'false');
        }

        selfoss.ui.selectedEntry = entry;

        if (entry) {
            $('.entry-title > .entry-title-link', selfoss.ui.selectedEntry).attr('aria-current', 'true');
            entry.addClass('selected');
        }
    },


    /**
     * Get the currently selected entry.
     * @return {?jQuery wrapped Element}
     */
    entryGetSelected: function() {
        return selfoss.ui.selectedEntry;
    },


    entryStar: function(id, starred, domNode) {
        var button = $('#entry' + id + ' .entry-starr, #entrr' + id + ' .entry-starr',
            domNode);

        // update button
        if (starred) {
            button.addClass('active');
            button.html(selfoss.ui._('unstar'));
        } else {
            button.removeClass('active');
            button.html(selfoss.ui._('star'));
        }
    },


    entryMark: function(id, unread, domNode) {
        var button = $('#entry' + id + ' .entry-unread, #entrr' + id + ' .entry-unread',
            domNode);
        var parent = $('#entry' + id + ', #entrr' + id, domNode);

        // update button and entry style
        if (unread) {
            button.addClass('active');
            button.html(selfoss.ui._('mark'));
            parent.addClass('unread');
        } else {
            button.removeClass('active');
            button.html(selfoss.ui._('unmark'));
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
            if ($('.entry').length > 0) {
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


    /**
    * Obtain a localized message for given key, substituting placeholders for values, when given.
    * @param string key
    * @param ?array parameters
    * @return string
    */
    _: function(identifier, params) {
        const fallbackLanguage = 'en';
        const langKey = `lang_${identifier}`;

        let translated = locales[selfoss.config.language][langKey] || locales[fallbackLanguage][langKey] || `#untranslated:${identifier}`;

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
            message = message + '. <button type="button">' + actionText + '</button>';
        }

        var messageContainer = $('#message');
        messageContainer.html(message);

        if (action) {
            messageContainer.find('button').unbind('click').click(action);
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
            var tagsCountEl = $('#nav-tags > li > a > span.tag')
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
