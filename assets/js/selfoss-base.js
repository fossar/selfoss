import ReactDOM from 'react-dom';
import { getInstanceInfo, login, logout } from './requests/common';
import * as sourceRequests from './requests/sources';
import { getAllTags } from './requests/tags';
import * as ajax from './helpers/ajax';
import { ValueListenable } from './helpers/ValueListenable';
import { HttpError, TimeoutError } from './errors';
import { LoadingState } from './requests/LoadingState';
import { createApp } from './templates/App';

/**
 * base javascript application
 *
 * @package    public_js
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 */
var selfoss = {
    /**
     * The main App component.
     * @var App
     */
    app: null,

    /**
     * the html title configured
     */
    htmlTitle: 'selfoss',

    /**
     * React component for entries page.
     */
    entriesPage: null,

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
                document.body.innerHTML = selfoss.app._('error_configuration');
            }
        });
    },


    initMain: function(configuration) {
        selfoss.config = configuration;

        if ('serviceWorker' in navigator) {
            selfoss.windowLoaded.then(function() {
                navigator.serviceWorker.register(new URL('../selfoss-sw-offline.js', import.meta.url))
                    .then(function(reg) {
                        selfoss.listenWaitingSW(reg, function(reg) {
                            selfoss.app.notifyNewVersion(function() {
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

        document.body.classList.toggle('publicupdate', configuration.allowPublicUpdate);
        document.body.classList.toggle('publicmode', configuration.publicMode);
        document.body.classList.toggle('authenabled', configuration.authEnabled);
        document.body.classList.toggle('loggedin', !configuration.authEnabled);

        if (configuration.language !== null) {
            document.documentElement.setAttribute('lang', configuration.language);
        }
        document.querySelector('meta[name="application-name"]').setAttribute('content', configuration.htmlTitle);

        const feedLink = document.createElement('link');
        feedLink.setAttribute('rel', 'alternate');
        feedLink.setAttribute('type', 'application/rss+xml');
        feedLink.setAttribute('title', 'RSS Feed');
        feedLink.setAttribute('href', 'feed');
        document.head.appendChild(feedLink);

        if (configuration.userCss !== null) {
            let link = document.createElement('link');
            link.setAttribute('rel', 'stylesheet');
            link.setAttribute('href', `user.css?v=${configuration.userCss}`);
            document.head.appendChild(link);
        }
        if (configuration.userJs !== null) {
            let script = document.createElement('script');
            script.setAttribute('src', `user.js?v=${configuration.userJs}`);
            document.body.appendChild(script);
        }

        // init offline if supported
        selfoss.dbOffline.init();

        selfoss.attachApp();

        if (configuration.authEnabled) {
            selfoss.loggedin.addEventListener('change', function loggedinChanged(event) {
                document.body.classList.toggle('loggedin', event.value);
            });

            selfoss.loggedin.update(window.localStorage.getItem('onlineSession') == 'true');
        }

        if (selfoss.hasSession() || !configuration.authEnabled || configuration.publicMode) {
            selfoss.initUi();
        } else {
            selfoss.history.push('/sign/in');
        }
    },


    /**
     * Create basic DOM structure of the page.
     */
    attachApp: function() {
        document.getElementById('js-loading-message')?.remove();

        const mainUi = document.createElement('div');
        document.body.appendChild(mainUi);
        mainUi.classList.add('app-toplevel');

        // BrowserRouter expects no slash at the end.
        const basePath = (new URL(document.baseURI)).pathname.replace(/\/$/, '');

        ReactDOM.render(
            createApp(basePath, (app) => {
                selfoss.app = app;
            }),
            mainUi
        );
    },


    initUiDone: false,


    initUi: function() {
        if (!selfoss.initUiDone) {
            selfoss.initUiDone = true;

            // read the html title configured
            selfoss.htmlTitle = selfoss.config.htmlTitle;

            // init shares
            selfoss.shares.init(selfoss.config.share);

            // init FancyBox
            selfoss.initFancyBox();

            // setup periodic server status sync
            window.setInterval(selfoss.db.sync, 60 * 1000);
        }
    },


    loggedin: new ValueListenable(false),


    setSession: function() {
        window.localStorage.setItem('onlineSession', true);
        selfoss.loggedin.update(true);
    },


    clearSession: function() {
        window.localStorage.removeItem('onlineSession');
        selfoss.loggedin.update(false);
    },


    hasSession: function() {
        return selfoss.loggedin.value;
    },

    /**
     * Try to log in using given credentials
     * @return Promise<undefined>
     */
    login: function({username, password, offlineEnabled}) {
        selfoss.db.enableOffline.update(offlineEnabled);
        window.localStorage.setItem('enableOffline', selfoss.db.enableOffline.value);
        if (!selfoss.db.enableOffline.value) {
            selfoss.db.clear();
        }

        const credentials = {
            username,
            password
        };
        return login(credentials).then((data) => {
            if (data.success) {
                selfoss.setSession();
                selfoss.history.push('/');
                // init offline if supported and not inited yet
                selfoss.dbOffline.init();
                selfoss.initUi();
                if ((!selfoss.db.storage || selfoss.db.broken) && selfoss.db.enableOffline.value) {
                    // Initialize database in offline mode when it has not been initialized yet or it got broken.
                    selfoss.dbOffline.init();
                }
                return Promise.resolve();
            } else {
                return Promise.reject(new Error(data.error));
            }
        });
    },


    logout: function() {
        selfoss.clearSession();
        if (!document.body.classList.contains('publicmode')) {
            selfoss.history.push('/sign/in');
        }

        logout().catch((error) => {
            selfoss.app.showError(selfoss.app._('error_logout') + ' ' + error.message);
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
        if (document.body.clientWidth <= 1024) {
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
        if (document.body.clientWidth <= 640) {
            return true;
        }
        return false;
    },


    /**
     * refresh stats.
     *
     * @return void
     * @param {Number} new all stats
     * @param {Number} new unread stats
     * @param {Number} new starred stats
     */
    refreshStats: function(all, unread, starred) {
        selfoss.app.setAllItemsCount(all);
        selfoss.app.setStarredItemsCount(starred);

        selfoss.refreshUnread(unread);
    },


    /**
     * refresh unread stats.
     *
     * @return void
     * @param {Number} new unread stats
     */
    refreshUnread: function(unread) {
        selfoss.app.setUnreadItemsCount(unread);
    },


    /**
     * refresh current tags.
     *
     * @return void
     */
    reloadTags: function() {
        selfoss.app.setTagsState(LoadingState.LOADING);

        getAllTags().then((data) => {
            selfoss.app.setTags(data);
            selfoss.app.setTagsState(LoadingState.SUCCESS);
        }).catch((error) => {
            selfoss.app.setTagsState(LoadingState.FAILURE);
            selfoss.app.showError(selfoss.app._('error_load_tags') + ' ' + error.message);
        });
    },


    /**
     * anonymize links
     *
     * @return void
     * @param parent element
     */
    anonymize: function(parent) {
        var anonymizer = selfoss.config.anonymizer;
        if (anonymizer !== null) {
            parent.querySelectorAll('a').forEach((link) => {
                if (typeof link.getAttribute('href') !== 'undefined' && !link.getAttribute('href').startsWith(anonymizer)) {
                    link.setAttribute('href', anonymizer + link.getAttribute('href'));
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
        let images = content.querySelectorAll('a[href$=".jpg"], a[href$=".jpeg"], a[href$=".png"], a[href$=".gif"], a[href$=".jpg:large"], a[href$=".jpeg:large"], a[href$=".png:large"], a[href$=".gif:large"]');
        images.forEach((el) => {
            el.setAttribute('data-fancybox', 'gallery-' + id);
            $(el).off('click');
        });
        images.forEach((el) => el.setAttribute('data-type', 'image'));
    },


    /**
     * Initialize FancyBox globally
     */
    initFancyBox: function() {
        $.fancybox.defaults.hash = false;
    },


    /**
     * Triggers fetching news from all sources.
     * @return Promise<undefined>
     */
    reloadAll: function() {
        if (!selfoss.db.online) {
            return Promise.resolve();
        }

        return sourceRequests.refreshAll().then(() => {
            // probe stats and prompt reload to the user
            selfoss.dbOnline.sync().then(function() {
                if (selfoss.app.state.unreadItemsCount > 0) {
                    selfoss.app.showMessage(selfoss.app._('sources_refreshed'), [
                        {
                            label: selfoss.app._('reload_list'),
                            callback() {
                                document.querySelector('#nav-filter-unread').click();
                            }
                        }
                    ]);
                }
            });
        }).catch((error) => {
            selfoss.app.showError(selfoss.app._('error_refreshing_source') + ' ' + error.message);
        });
    },


    handleAjaxError: function(error, tryOffline = true) {
        if (!(error instanceof HttpError || error instanceof TimeoutError)) {
            return Promise.reject(error);
        }

        const httpCode = error?.response?.status || 0;

        if (tryOffline && httpCode != 403) {
            return selfoss.db.setOffline();
        } else {
            return Promise.reject(error);
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
