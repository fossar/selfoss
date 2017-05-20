/**
 * base javascript application
 *
 * @package    public_js
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 */
var selfoss = {

    /**
     * current filter settings
     * @var mixed
     */
    filter: {
        offset: 0,
        fromDatetime: undefined,
        fromId: undefined,
        itemsPerPage: 0,
        search: '',
        type: 'newest',
        tag: '',
        source: '',
        sourcesNav: false,
        extraIds: []
    },

    /**
     * instance of the currently running XHR that is used to reload the items list
     */
    activeAjaxReq: null,

    /**
     * the html title configured
     */
    htmlTitle: 'selfoss',

    /**
     * initialize application
     */
    init: function() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('selfoss-sw-offline.js')
                    .then(function(reg) {
                        selfoss.listenWaitingSW(reg, function(reg) {
                            selfoss.ui.notifyNewVersion(function() {
                                if (reg.waiting) {
                                    reg.waiting.postMessage('skipWaiting');
                                }
                            });
                        });
                    });
            });

            navigator.serviceWorker.addEventListener('controllerchange',
                function() {
                    window.location.reload();
                }
            );
        }

        // offline db consistency requires ajax calls to fail reliably,
        // so we enforce a default timeout on ajax calls
        jQuery.ajaxSetup({timeout: 60000 });

        jQuery(document).ready(function() {
            if (selfoss.hasSession() || !$('body').hasClass('authenabled')) {
                selfoss.ui.login();
                selfoss.initUi();
            } else if ($('body').hasClass('publicmode')) {
                selfoss.ui.logout();
                selfoss.initUi();
            } else {
                selfoss.ui.logout();
                selfoss.events.setHash('login', false);
            }

            $('#loginform').submit(selfoss.login);
        });
    },


    initUiDone: false,


    initUi: function() {
        if (!selfoss.initUiDone) {
            selfoss.initUiDone = true;

            // set items per page
            selfoss.filter.itemsPerPage = $('#config').data('items_perpage');

            // read the html title configured
            selfoss.htmlTitle = $('#config').data('html_title');

            // init shares
            selfoss.shares.init($('#config').data('share'));

            // init FancyBox
            selfoss.initFancyBox();

            // init offline if supported and events
            selfoss.dbOffline.init().catch(selfoss.events.init);

            // init shortcut handler
            selfoss.shortcuts.init();

            // setup periodic server status sync
            window.setInterval(selfoss.db.sync, 60 * 1000);

            window.setInterval(selfoss.ui.refreshEntryDatetimes, 60 * 1000);

            selfoss.ui.showMainUi();
        }
    },


    /**
     * returns an array of name value pairs of all form elements in given element
     *
     * @return void
     * @param element containing the form elements
     */
    getValues: function(element) {
        var values = {};

        $(element).find(':input').each(function(i, el) {
            // get only input elements with name
            if ($.trim($(el).attr('name')).length != 0) {
                values[$(el).attr('name')] = $(el).val();
                if ($(el).attr('type') == 'checkbox') {
                    values[$(el).attr('name')] = $(el).attr('checked') ? 1 : 0;
                }
            }
        });

        return values;
    },


    loggedin: false,


    setSession: function() {
        window.localStorage.setItem('onlineSession', true);
        selfoss.loggedin = true;
    },


    clearSession: function() {
        window.localStorage.removeItem('onlineSession');
        selfoss.loggedin = false;
    },


    hasSession: function() {
        selfoss.loggedin = window.localStorage.getItem('onlineSession') == 'true';
        return selfoss.loggedin;
    },


    login: function(e) {
        $('#loginform').addClass('loading');

        selfoss.db.enableOffline = $('#enableoffline').is(':checked');
        window.localStorage.setItem('enableOffline', selfoss.db.enableOffline);
        if (!selfoss.db.enableOffline) {
            selfoss.db.clear();
        }

        var f = $('#loginform form');
        $.ajax({
            type: 'POST',
            url: 'login',
            dataType: 'json',
            data: f.serialize(),
            success: function(data) {
                if (data.success) {
                    $('#password').val('');
                    selfoss.setSession();
                    selfoss.ui.login();
                    selfoss.ui.showMainUi();
                    selfoss.initUi();
                    if (selfoss.db.storage || !selfoss.db.enableOffline) {
                        selfoss.db.reloadList();
                    } else {
                        selfoss.dbOffline.init().catch(selfoss.events.init);
                    }
                    selfoss.events.initHash();
                } else {
                    selfoss.events.setHash('login', false);
                    selfoss.ui.showLogin(data.error);
                }
            },
            complete: function() {
                $('#loginform').removeClass('loading');
            }
        });
        e.preventDefault();
    },


    logout: function() {
        selfoss.clearSession();
        selfoss.ui.logout();
        if (!$('body').hasClass('publicmode')) {
            selfoss.events.setHash('login', false);
        }

        $.ajax({
            type: 'GET',
            url: 'logout',
            dataType: 'json',
            error: function(jqXHR, textStatus, errorThrown) {
                selfoss.ui.showError($('#lang').data('error_logout') + ' ' +
                                     textStatus + ' ' + errorThrown);
            }
        });
    },


    /**
     * insert error messages in form
     *
     * @return void
     * @param form target where input fields in
     * @param errors an array with all error messages
     */
    showErrors: function(form, errors) {
        $(form).find('span.error').remove();
        $.each(errors, function(key, val) {
            form.find("[name='" + key + "']").addClass('error').parent('li').append('<span class="error">' + val + '</span>');
        });
    },


    /**
     * indicates whether a mobile device is host
     *
     * @return true if device resolution smaller equals 1024
     */
    isMobile: function() {
        // first check useragent
        if ((/iPhone|iPod|iPad|Android|BlackBerry/).test(navigator.userAgent)) {
            return true;
        }

        // otherwise check resolution
        return selfoss.isTablet() || selfoss.isSmartphone();
    },


    /**
     * indicates whether a tablet is the device or not
     *
     * @return true if device resolution smaller equals 1024
     */
    isTablet: function() {
        if ($(window).width() <= 1024) {
            return true;
        }
        return false;
    },


    /**
     * indicates whether a tablet is the device or not
     *
     * @return true if device resolution smaller equals 1024
     */
    isSmartphone: function() {
        if ($(window).width() <= 640) {
            return true;
        }
        return false;
    },


    /**
     * reset filter
     *
     * @return void
     */
    filterReset: function() {
        selfoss.filter.offset = 0;
        selfoss.filter.fromDatetime = undefined;
        selfoss.filter.fromId = undefined;
        selfoss.filter.extraIds.length = 0;
    },


    /**
     * refresh stats.
     *
     * @return void
     * @param new all stats
     * @param new unread stats
     * @param new starred stats
     */
    refreshStats: function(all, unread, starred) {
        $('.nav-filter-newest span.count').html(all);
        $('.nav-filter-starred span.count').html(starred);

        selfoss.refreshUnread(unread);
    },


    /**
     * refresh unread stats.
     *
     * @return void
     * @param new unread stats
     */
    refreshUnread: function(unread) {
        $('.unread-count .count').html(unread);

        if (unread > 0) {
            $('.unread-count').addClass('unread');
        } else {
            $('.unread-count').removeClass('unread');
        }

        selfoss.ui.refreshTitle(unread);
    },


    /**
     * refresh current tags.
     *
     * @return void
     */
    reloadTags: function() {
        $('#nav-tags').addClass('loading');

        $.ajax({
            url: $('base').attr('href') + 'tagslist',
            type: 'GET',
            success: function(data) {
                $('#nav-tags li:not(:first)').remove();
                $('#nav-tags').append(data);
                selfoss.events.navigation();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                selfoss.ui.showError($('#lang').data('error_load_tags') + ' ' +
                                     textStatus + ' ' + errorThrown);
            },
            complete: function() {
                $('#nav-tags').removeClass('loading');
            }
        });
    },


    /**
     * refresh taglist.
     *
     * @return void
     * @param tags the new taglist as html
     */
    refreshTags: function(tags) {
        $('.color').spectrum('destroy');
        $('#nav-tags li:not(:first)').remove();
        $('#nav-tags').append(tags);
        if (selfoss.filter.tag) {
            if (!selfoss.db.isValidTag(selfoss.filter.tag)) {
                selfoss.ui.showError($('#lang').data('error_unknown_tag') + ' ' + selfoss.filter.tag);
            }

            $('#nav-tags li:first').removeClass('active');
            $('#nav-tags > li').filter(function() {
                if ($('.tag', this)) {
                    return $('.tag', this).html() == selfoss.filter.tag;
                } else {
                    return false;
                }
            }).addClass('active');
        } else {
            $('.nav-tags-all').addClass('active');
        }

        selfoss.events.navigation();
    },


    sourcesNavLoaded: false,

    /**
     * refresh sources list.
     *
     * @return void
     * @param sources the new sourceslist as html
     */
    refreshSources: function(sources) {
        $('#nav-sources li').remove();
        $('#nav-sources').append(sources);
        if (selfoss.filter.source) {
            if (!selfoss.db.isValidSource(selfoss.filter.source)) {
                selfoss.ui.showError($('#lang').data('error_unknown_source') + ' '
                                     + selfoss.filter.source);
            }

            $('#source' + selfoss.filter.source).addClass('active');
            $('#nav-tags > li').removeClass('active');
        }

        selfoss.sourcesNavLoaded = true;
        if ($('#nav-sources-title').hasClass('nav-sources-collapsed')) {
            $('#nav-sources-title').click(); // expand sources nav
        }

        selfoss.events.navigation();
    },


    /**
     * anonymize links
     *
     * @return void
     * @param parent element
     */
    anonymize: function(parent) {
        var anonymizer = $('#config').data('anonymizer');
        if (anonymizer.length > 0) {
            parent.find('a').each(function(i, link) {
                link = $(link);
                if (typeof link.attr('href') != 'undefined' && link.attr('href').indexOf(anonymizer) != 0) {
                    link.attr('href', anonymizer + link.attr('href'));
                }
            });
        }
    },


    /**
     * Setup fancyBox image viewer
     * @param content element
     * @param int
     */
    setupFancyBox: function(content, id) {
        // Close existing fancyBoxes
        $.fancybox.close();
        var images = $(content).find('a[href$=".jpg"], a[href$=".jpeg"], a[href$=".png"], a[href$=".gif"], a[href$=".jpg:large"], a[href$=".jpeg:large"], a[href$=".png:large"], a[href$=".gif:large"]');
        $(images).attr('data-fancybox', 'gallery-' + id).unbind('click');
        $(images).attr('data-type', 'image');
    },


    /**
     * Initialize FancyBox globally
     */
    initFancyBox: function() {
        $.fancybox.defaults.hash = false;
    },


    /**
     * Mark all visible items as read
     */
    markVisibleRead: function() {
        var ids = [];
        var tagUnreadDiff = [];
        var sourceUnreadDiff = [];
        var found = false;
        $('.entry.unread').each(function(index, item) {
            ids.push($(item).attr('id').substr(5));

            $('.entry-tags-tag', item).each(function(index, tagEl) {
                found = false;
                var tag = $(tagEl).html();
                tagUnreadDiff.forEach(function(tagCount) {
                    if (tagCount.tag == tag) {
                        found = true;
                        tagCount.count = tagCount.count - 1;
                    }
                });
                if (!found) {
                    tagUnreadDiff.push({tag: tag, count: -1});
                }
            });

            if (selfoss.sourcesNavLoaded) {
                found = false;
                var source = $(item).data('entry-source');
                sourceUnreadDiff.forEach(function(sourceCount) {
                    if (sourceCount.source == source) {
                        found = true;
                        sourceCount.count = sourceCount.count - 1;
                    }
                });
                if (!found) {
                    sourceUnreadDiff.push({source: source, count: -1});
                }
            }
        });

        // close opened entry and list
        selfoss.events.setHash();
        selfoss.filterReset();

        if (ids.length === 0 && selfoss.filter.type == 'unread') {
            $('.entry').remove();
            if (selfoss.filter.type == 'unread' &&
                parseInt($('.unread-count .count').html()) > 0) {
                selfoss.db.reloadList();
            } else {
                selfoss.ui.refreshStreamButtons(true);
            }
        }

        if (ids.length === 0) {
            return;
        }

        var content = $('#content');
        var articleList = content.html();
        var hadMore = $('.stream-more').is(':visible');

        selfoss.ui.beforeReloadList(true);

        var unreadstats = parseInt($('.nav-filter-unread span.count').html()) -
            ids.length;
        var displayed = false;
        var displayNextUnread = function() {
            if (!displayed) {
                displayed = true;
                selfoss.refreshUnread(unreadstats);
                selfoss.ui.refreshTagSourceUnread(tagUnreadDiff,
                    sourceUnreadDiff);

                selfoss.ui.hideMobileNav();

                selfoss.db.reloadList(false, false);
            }
        };

        if (selfoss.db.storage) {
            selfoss.refreshUnread(unreadstats);
            selfoss.dbOffline.entriesMark(ids, false).then(displayNextUnread);
        }

        $.ajax({
            url: $('base').attr('href') + 'mark',
            type: 'POST',
            dataType: 'json',
            data: {
                ids: ids
            },
            success: function() {
                selfoss.db.setOnline();
                displayNextUnread();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                selfoss.handleAjaxError(jqXHR.status).then(function() {
                    var statuses = [];
                    ids.forEach(function(id) {
                        statuses.push({
                            entryId: id,
                            name: 'unread',
                            value: false
                        });
                    });
                    selfoss.dbOffline.enqueueStatuses(statuses);
                }, function() {
                    content.html(articleList);
                    selfoss.ui.refreshStreamButtons(true, hadMore);
                    selfoss.ui.listReady();
                    selfoss.ui.showError($('#lang').data('error_mark_items') +
                                         ' ' + textStatus + ' ' + errorThrown);
                });
            }
        });
    },


    handleAjaxError: function(httpCode, tryOffline) {
        tryOffline = (typeof tryOffline !== 'undefined') ? tryOffline : true;

        if (tryOffline && httpCode != 403) {
            return selfoss.db.setOffline();
        } else {
            var handled  = $.Deferred();
            handled.reject();
            if (httpCode == 403) {
                selfoss.ui.logout();
                selfoss.ui.showLogin($('#lang').data('error_session_expired'));
            }
            return handled;
        }
    },


    listenWaitingSW: function(reg, callback) {
        function awaitStateChange() {
            reg.installing.addEventListener('statechange', function() {
                if (this.state === 'installed') {
                    callback(reg);
                }
            });
        }

        if (!reg) {
            return;
        } else if (reg.waiting) {
            return callback(reg);
        } else if (reg.installing) {
            awaitStateChange();
            reg.addEventListener('updatefound', awaitStateChange);
        }
    },


    /*
     * Handy function that can be used for debugging purposes.
     */
    nukeLocalData: function() {
        selfoss.db.clear(); // will not work after a failure, since storage is nulled
        window.localStorage.clear();
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            registrations.forEach(function(reg) {
                reg.unregister();
            });
        });
        selfoss.logout();
    }


};

selfoss.init();
