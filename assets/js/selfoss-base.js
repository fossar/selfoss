import { Filter, FilterType } from './Filter';
import { TagsRepository } from './model/TagsRepository';
import { SourcesRepository } from './model/SourcesRepository';
import { getInstanceInfo, login, logout } from './requests/common';
import * as itemsRequests from './requests/items';
import { getAllTags } from './requests/tags';
import * as ajax from './helpers/ajax';
import { HttpError, TimeoutError } from './errors';
import { LoadingState } from './requests/LoadingState';

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
     * @var Filter
     */
    filter: new Filter({}),

    /**
     * tag repository
     * @var TagsRepository
     */
    tags: new TagsRepository({}),

    /**
     * source repository
     * @var SourcesRepository
     */
    sources: new SourcesRepository({}),

    /**
     * instance of the currently running XHR that is used to reload the items list
     */
    activeAjaxReq: null,

    /**
     * the html title configured
     */
    htmlTitle: 'selfoss',

    windowLoaded: new Promise((resolve) => {
        window.addEventListener('load', () => resolve());
    }),

    /**
     * initialize application
     */
    init: function() {
        var storedConfig = localStorage.getItem('configuration');
        var oldConfiguration = null;
        try {
            oldConfiguration = JSON.parse(storedConfig);
        } catch (e) {
            // We will try to obtain a new configuration anyway
        }

        getInstanceInfo().then(({configuration}) => {
            localStorage.setItem('configuration', JSON.stringify(configuration));

            if (oldConfiguration && 'caches' in window) {
                if (oldConfiguration.userCss !== configuration.userCss) {
                    caches.delete('userCss').then(() =>
                        caches.open('userCss').then(cache => cache.add(`user.css?v=${configuration.userCss}`))
                    );
                }
                if (oldConfiguration.userJs !== configuration.userJs) {
                    caches.delete('userJs').then(() =>
                        caches.open('userJs').then(cache => cache.add(`user.js?v=${configuration.userJs}`))
                    );
                }
            }

            selfoss.initMain(configuration);
        }).catch(() => {
            // on failure, we will try to use the last cached config
            if (oldConfiguration) {
                selfoss.initMain(oldConfiguration);
            } else {
                // TODO: Add a more proper error page
                $('body').html(selfoss.ui._('error_configuration'));
            }
        });
    },


    initMain: function(configuration) {
        selfoss.config = configuration;

        if ('serviceWorker' in navigator) {
            selfoss.windowLoaded.then(function() {
                // load script generated by Parcel plugin
                const workerPath = 'selfoss-sw-offline.js';
                navigator.serviceWorker.register(workerPath)
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

        $(function() {
            document.body.classList.toggle('publicupdate', configuration.allowPublicUpdate);
            document.body.classList.toggle('publicmode', configuration.publicMode);
            document.body.classList.toggle('authenabled', configuration.authEnabled);
            document.body.classList.toggle('loggedin', !configuration.authEnabled);
            document.body.classList.toggle('auto_mark_as_read', configuration.autoMarkAsRead);

            $('html').attr('lang', configuration.language);
            $('meta[name="application-name"]').attr('content', configuration.htmlTitle);
            $('head').append('<link rel="alternate" type="application/rss+xml" title="RSS Feed" href="feed" />');

            selfoss.ui.init();

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
            selfoss.filter.update({ itemsPerPage: selfoss.config.itemsPerPage });

            selfoss.filter.addEventListener('change', (event) => {
                if (event.setHash) {
                    selfoss.events.setHash();
                }
            });

            // read the html title configured
            selfoss.htmlTitle = selfoss.config.htmlTitle;

            // init shares
            selfoss.shares.init(selfoss.config.share);

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

        var f = new FormData(document.querySelector('#loginform form'));
        const credentials = {
            username: f.get('username'),
            password: f.get('password')
        };
        login(credentials).then((data) => {
            if (data.success) {
                $('#password').val('');
                selfoss.setSession();
                selfoss.ui.login();
                selfoss.ui.showMainUi();
                selfoss.initUi();
                if ((!selfoss.db.storage || selfoss.db.broken) && selfoss.db.enableOffline) {
                    // Initialize database in offline mode when it has not been initialized yet or it got broken.
                    selfoss.dbOffline.init().catch(selfoss.events.init);
                } else {
                    selfoss.db.reloadList();
                }
                selfoss.events.initHash();
            } else {
                selfoss.events.setHash('login', false);
                selfoss.ui.showLogin(data.error);
            }
        }).finally(() => {
            $('#loginform').removeClass('loading');
        });
        e.preventDefault();
    },


    logout: function() {
        selfoss.clearSession();
        selfoss.ui.logout();
        if (!$('body').hasClass('publicmode')) {
            selfoss.events.setHash('login', false);
        }

        logout().catch((error) => {
            selfoss.ui.showError(selfoss.ui._('error_logout') + ' ' + error.message);
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
        Object.entries(errors).forEach(([key, val]) => {
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
    filterReset: function(extras, notify = false) {
        selfoss.filter.update({
            offset: 0,
            fromDatetime: undefined,
            fromId: undefined,
            extraIds: [],
            ...extras
        }, notify);
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
        selfoss.tags.setState(LoadingState.LOADING);

        getAllTags().then((data) => {
            selfoss.tags.update(data);
            selfoss.tags.setState(LoadingState.SUCCESS);
        }).catch((error) => {
            selfoss.tags.setState(LoadingState.FAILURE);
            selfoss.ui.showError(selfoss.ui._('error_load_tags') + ' ' + error.message);
        });
    },

    sourcesNavLoaded: false,


    /**
     * anonymize links
     *
     * @return void
     * @param parent element
     */
    anonymize: function(parent) {
        var anonymizer = selfoss.config.anonymizer;
        if (anonymizer !== null) {
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
        var tagUnreadDiff = {};
        var sourceUnreadDiff = [];
        $('.entry.unread').each(function(index, item) {
            ids.push($(item).attr('data-entry-id'));

            $('.entry-tags-tag', item).each(function(index, tagEl) {
                var tag = $(tagEl).html();
                if (Object.keys(tagUnreadDiff).includes(tag)) {
                    tagUnreadDiff[tag] += -1;
                } else {
                    tagUnreadDiff[tag] = -1;
                }
            });

            if (selfoss.sourcesNavLoaded) {
                var source = $(item).data('entry-source');
                if (Object.keys(sourceUnreadDiff).includes(source)) {
                    sourceUnreadDiff[source] += -1;
                } else {
                    sourceUnreadDiff[source] = -1;
                }
            }
        });

        // close opened entry and list
        selfoss.filterReset({}, true);

        if (ids.length === 0 && selfoss.filter.type === FilterType.UNREAD) {
            $('.entry').remove();
            if (parseInt($('.unread-count .count').html()) > 0) {
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

        selfoss.ui.beforeReloadList();

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

        if (selfoss.db.enableOffline) {
            selfoss.refreshUnread(unreadstats);
            selfoss.dbOffline.entriesMark(ids, false).then(displayNextUnread);
        }

        itemsRequests.markAll(ids).then(function() {
            selfoss.db.setOnline();
            displayNextUnread();
        }).catch(function(error) {
            selfoss.handleAjaxError(error).then(function() {
                let statuses = ids.map(id => ({
                    entryId: id,
                    name: 'unread',
                    value: false
                }));
                selfoss.dbOffline.enqueueStatuses(statuses);
            }).catch(function(error) {
                content.html(articleList);
                selfoss.ui.refreshStreamButtons(true, hadMore);
                selfoss.ui.listReady();
                selfoss.ui.showError(selfoss.ui._('error_mark_items') + ' ' + error.message);
            });
        });
    },


    handleAjaxError: function(error, tryOffline = true) {
        if (!(error instanceof HttpError || error instanceof TimeoutError)) {
            throw error;
        }

        const httpCode = error?.response?.status || 0;

        if (tryOffline && httpCode != 403) {
            return selfoss.db.setOffline();
        } else {
            if (httpCode == 403) {
                selfoss.ui.logout();
                selfoss.ui.showLogin(selfoss.ui._('error_session_expired'));
            }
            throw error;
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
        if ('caches' in window) {
            caches.keys().then(keys => keys.forEach(key => caches.delete(key)));
        }
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            registrations.forEach(function(reg) {
                reg.unregister();
            });
        });
        selfoss.logout();
    },


    // Include helpers for user scripts.
    ajax

};

export default selfoss;
