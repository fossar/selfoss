import { createRoot } from 'react-dom/client';
import { getInstanceInfo, login, logout } from './requests/common';
import { getAllTags } from './requests/tags';
import * as ajax from './helpers/ajax';
import { ValueListenable } from './helpers/ValueListenable';
import { HttpError, TimeoutError } from './errors';
import { Configuration } from './model/Configuration';
import { LoadingState } from './requests/LoadingState';
import { App, createApp } from './templates/App';

/**
 * base javascript application
 *
 * @package    public_js
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 */
const selfoss = {
    /**
     * The main App component.
     * @var App
     */
    app: null,

    /**
     * React component for entries page.
     */
    entriesPage: null,

    serviceWorkerInitialized: false,

    /**
     * Whether lightbox is open.
     */
    lightboxActive: new ValueListenable(false),

    /**
     * initialize application
     */
    async init(): Promise<void> {
        // Load off-line mode enabledness.
        selfoss.db.enableOffline.update(
            window.localStorage.getItem('enableOffline') === 'true',
        );

        // Ignore stored config when off-line mode is disabled, since it is likely stale.
        const storedConfig = selfoss.db.enableOffline.value
            ? localStorage.getItem('configuration')
            : null;
        let oldConfiguration = null;
        try {
            oldConfiguration =
                storedConfig !== null ? JSON.parse(storedConfig) : null;
        } catch {
            // We will try to obtain a new configuration anyway.
        }

        // Fall back to the last cached config on failure.
        let configurationToUse = oldConfiguration;
        try {
            const { configuration } = await getInstanceInfo();
            configurationToUse = configuration;

            // We are on-line, prune the user files when changed.
            if ('caches' in window && 'serviceWorker' in navigator) {
                if (
                    oldConfiguration === null ||
                    oldConfiguration.userCss !== configuration.userCss
                ) {
                    await caches.delete('userCss');
                }
                if (
                    oldConfiguration === null ||
                    oldConfiguration.userJs !== configuration.userJs
                ) {
                    await caches.delete('userJs');
                }
            }
        } finally {
            if (configurationToUse) {
                await selfoss.initMain(configurationToUse);
            } else {
                // TODO: Add a more proper error page
                document.body.innerHTML = selfoss.app._('error_configuration');
            }
        }
    },

    async initMain(configuration: Configuration): Promise<void> {
        selfoss.config = configuration;

        if (selfoss.db.enableOffline.value) {
            selfoss.setupServiceWorker();
        }

        if (configuration.language !== null) {
            document.documentElement.setAttribute(
                'lang',
                configuration.language,
            );
        }
        document
            .querySelector('meta[name="application-name"]')
            .setAttribute('content', configuration.htmlTitle);

        const feedLink = document.createElement('link');
        feedLink.setAttribute('rel', 'alternate');
        feedLink.setAttribute('type', 'application/rss+xml');
        feedLink.setAttribute('title', 'RSS Feed');
        feedLink.setAttribute('href', 'feed');
        document.head.appendChild(feedLink);

        if (configuration.userCss !== null) {
            const link = document.createElement('link');
            link.setAttribute('rel', 'stylesheet');
            link.setAttribute('href', `user.css?v=${configuration.userCss}`);
            document.head.appendChild(link);
        }
        if (configuration.userJs !== null) {
            const script = document.createElement('script');
            script.setAttribute('src', `user.js?v=${configuration.userJs}`);
            document.body.appendChild(script);
        }

        // init offline if supported
        selfoss.dbOffline.init();

        if (configuration.authEnabled) {
            selfoss.loggedin.update(
                window.localStorage.getItem('onlineSession') == 'true',
            );
        }

        selfoss.attachApp(configuration);
    },

    /**
     * Create basic DOM structure of the page.
     */
    attachApp(configuration: Configuration): void {
        document.getElementById('js-loading-message')?.remove();

        const mainUi = document.createElement('div');
        document.body.appendChild(mainUi);
        mainUi.classList.add('app-toplevel');

        // BrowserRouter expects no slash at the end.
        const basePath = new URL(document.baseURI).pathname.replace(/\/$/, '');

        const root = createRoot(mainUi);
        root.render(
            createApp({
                basePath,
                appRef: (app: App) => {
                    selfoss.app = app;
                },
                configuration,
            }),
        );
    },

    loggedin: new ValueListenable(false),

    setSession(): void {
        window.localStorage.setItem('onlineSession', 'true');
        selfoss.loggedin.update(true);
    },

    clearSession(): void {
        window.localStorage.removeItem('onlineSession');
        selfoss.loggedin.update(false);
    },

    hasSession(): boolean {
        return selfoss.loggedin.value;
    },

    /**
     * Try to log in using given credentials
     */
    login(props: {
        configuration: Configuration;
        username: string;
        password: string;
        enableOffline: boolean;
    }): Promise<void> {
        const { configuration, username, password, enableOffline } = props;

        selfoss.db.enableOffline.update(enableOffline);
        window.localStorage.setItem(
            'enableOffline',
            selfoss.db.enableOffline.value,
        );
        if (!selfoss.db.enableOffline.value) {
            selfoss.db.clear();
        }

        const credentials = {
            username,
            password,
        };
        return login(credentials).then(() => {
            selfoss.setSession();
            // init offline if supported and not inited yet
            selfoss.dbOffline.init();
            if (
                (!selfoss.db.storage || selfoss.db.broken) &&
                selfoss.db.enableOffline.value
            ) {
                // Initialize database in offline mode when it has not been initialized yet or it got broken.
                selfoss.dbOffline.init();

                // Store config for off-line use.
                localStorage.setItem(
                    'configuration',
                    JSON.stringify(configuration),
                );

                // Cache user files manually since service worker is not aware of them.
                if ('caches' in window && 'serviceWorker' in navigator) {
                    caches
                        .open('userCss')
                        .then((cache) =>
                            cache.add(`user.css?v=${configuration.userCss}`),
                        );
                    caches
                        .open('userJs')
                        .then((cache) =>
                            cache.add(`user.js?v=${configuration.userJs}`),
                        );
                }

                selfoss.setupServiceWorker();
            }
        });
    },

    setupServiceWorker(): void {
        if (
            !('serviceWorker' in navigator) ||
            selfoss.serviceWorkerInitialized
        ) {
            return;
        }

        selfoss.serviceWorkerInitialized = true;

        navigator.serviceWorker.addEventListener('controllerchange', () => {
            window.location.reload();
        });

        navigator.serviceWorker
            .register(new URL('../selfoss-sw-offline.ts', import.meta.url), {
                type: 'module',
            })
            .then((reg) => {
                selfoss.listenWaitingSW(reg, (reg) => {
                    selfoss.app.notifyNewVersion(() => {
                        if (reg.waiting) {
                            reg.waiting.postMessage('skipWaiting');
                        }
                    });
                });
            });
    },

    async logout(): Promise<void> {
        selfoss.clearSession();

        selfoss.db.clear(); // will not work after a failure, since storage is nulled
        window.localStorage.clear();
        if ('serviceWorker' in navigator) {
            if ('caches' in window) {
                caches
                    .keys()
                    .then((keys) => keys.forEach((key) => caches.delete(key)));
            }

            navigator.serviceWorker.getRegistrations().then((registrations) => {
                registrations.forEach((reg) => {
                    reg.unregister();
                });
            });
            selfoss.serviceWorkerInitialized = false;
        }

        try {
            await logout();

            if (!selfoss.config.publicMode) {
                selfoss.navigate('/sign/in');
            }
        } catch (error) {
            selfoss.app.showError(
                selfoss.app._('error_logout') + ' ' + error.message,
            );
        }
    },

    /**
     * Checks whether the current user is allowed to perform read operations.
     */
    isAllowedToRead(): boolean {
        return (
            selfoss.hasSession() ||
            !selfoss.config.authEnabled ||
            selfoss.config.publicMode
        );
    },

    /**
     * Checks whether the current user is allowed to perform update-tier operations.
     */
    isAllowedToUpdate(): boolean {
        return (
            selfoss.hasSession() ||
            !selfoss.config.authEnabled ||
            selfoss.config.allowPublicUpdate
        );
    },

    /**
     * Checks whether the current user is allowed to perform write operations.
     */
    isAllowedToWrite(): boolean {
        return selfoss.hasSession() || !selfoss.config.authEnabled;
    },

    /**
     * Checks whether the current user is allowed to perform write operations.
     */
    isOnline(): boolean {
        return selfoss.db.online;
    },

    /**
     * indicates whether a mobile device is host
     *
     * @return true if device resolution smaller equals 1024
     */
    isMobile(): boolean {
        // first check useragent
        if (/iPhone|iPod|iPad|Android|BlackBerry/.test(navigator.userAgent)) {
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
    isTablet(): boolean {
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
    isSmartphone(): boolean {
        if (document.body.clientWidth <= 640) {
            return true;
        }
        return false;
    },

    /**
     * Override these functions to customize selfoss behaviour.
     */
    extensionPoints: {
        /**
         * Called when an article is first expanded.
         * @param _contents HTML element containing the article contents
         */
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        processItemContents(_contents: HTMLElement) {},
    },

    /**
     * refresh stats.
     *
     * @param all new all stats
     * @param unread new unread stats
     * @param starred new starred stats
     */
    refreshStats(all: number, unread: number, starred: number): void {
        selfoss.app.setAllItemsCount(all);
        selfoss.app.setStarredItemsCount(starred);

        selfoss.refreshUnread(unread);
    },

    /**
     * refresh unread stats.
     *
     * @param unread new unread stats
     */
    refreshUnread(unread: number): void {
        selfoss.app.setUnreadItemsCount(unread);
    },

    /**
     * refresh current tags.
     */
    reloadTags(): void {
        selfoss.app.setTagsState(LoadingState.LOADING);

        getAllTags()
            .then((data) => {
                selfoss.app.setTags(data);
                selfoss.app.setTagsState(LoadingState.SUCCESS);
            })
            .catch((error) => {
                selfoss.app.setTagsState(LoadingState.FAILURE);
                selfoss.app.showError(
                    selfoss.app._('error_load_tags') + ' ' + error.message,
                );
            });
    },

    handleAjaxError(error: Error, tryOffline: boolean = true): Promise<void> {
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

    listenWaitingSW(
        reg: ServiceWorkerRegistration,
        callback: (reg: ServiceWorkerRegistration) => void,
    ): void {
        const awaitStateChange = (): void => {
            reg.installing.addEventListener('statechange', (event) => {
                if (event.target.state === 'installed') {
                    callback(reg);
                }
            });
        };

        if (!reg) {
            return;
        } else if (reg.waiting) {
            return callback(reg);
        } else if (reg.installing) {
            awaitStateChange();
            reg.addEventListener('updatefound', awaitStateChange);
        }
    },

    // Include helpers for user scripts.
    ajax,
};

export default selfoss;
