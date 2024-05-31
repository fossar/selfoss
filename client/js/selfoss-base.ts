import { createRoot } from 'react-dom/client';
import { getInstanceInfo, login, logout } from './requests/common';
import { getAllTags } from './requests/tags';
import * as ajax from './helpers/ajax';
import { ValueListenable } from './helpers/ValueListenable';
import { HttpError, TimeoutError } from './errors';
import { Configuration } from './model/Configuration';
import { LoadingState } from './requests/LoadingState';
import { App, createApp } from './templates/App';
import DbOnline from './selfoss-db-online';
import DbOffline from './selfoss-db-offline';
import Db from './selfoss-db';
import { NavigateFunction } from 'react-router';

/**
 * base javascript application
 *
 * @package    public_js
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 */
class selfoss {
    /**
     * The main App component.
     */
    public static app: App | null = null;

    /**
     * React component for entries page.
     */
    public static entriesPage = null;

    private static serviceWorkerInitialized = false;

    /**
     * Whether lightbox is open.
     */
    public static lightboxActive = new ValueListenable(false);

    public static db: Db = new Db();
    public static dbOnline: DbOnline = new DbOnline();
    public static dbOffline: DbOffline = new DbOffline();

    static navigate: NavigateFunction | undefined = undefined;
    static config: Configuration;

    /**
     * initialize application
     */
    static async init(): Promise<void> {
        // Load off-line mode enabledness.
        this.db.enableOffline.update(
            window.localStorage.getItem('enableOffline') === 'true',
        );

        // Ignore stored config when off-line mode is disabled, since it is likely stale.
        const storedConfig = this.db.enableOffline.value
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
                await this.initMain(configurationToUse);
            } else {
                // TODO: Add a more proper error page
                document.body.innerHTML = this.app._('error_configuration');
            }
        }
    }

    static async initMain(configuration: Configuration): Promise<void> {
        this.config = configuration;

        if (this.db.enableOffline.value) {
            this.setupServiceWorker();
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
        this.dbOffline.init();

        if (configuration.authEnabled) {
            this.loggedin.update(
                window.localStorage.getItem('onlineSession') == 'true',
            );
        }

        this.attachApp(configuration);
    }

    /**
     * Create basic DOM structure of the page.
     */
    static attachApp(configuration: Configuration): void {
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
                    this.app = app;
                },
                configuration,
            }),
        );
    }

    public static loggedin = new ValueListenable(false);

    static setSession(): void {
        window.localStorage.setItem('onlineSession', 'true');
        this.loggedin.update(true);
    }

    static clearSession(): void {
        window.localStorage.removeItem('onlineSession');
        this.loggedin.update(false);
    }

    static hasSession(): boolean {
        return this.loggedin.value;
    }

    /**
     * Try to log in using given credentials
     */
    static login(props: {
        configuration: Configuration;
        username: string;
        password: string;
        enableOffline: boolean;
    }): Promise<void> {
        const { configuration, username, password, enableOffline } = props;

        this.db.enableOffline.update(enableOffline);
        window.localStorage.setItem(
            'enableOffline',
            this.db.enableOffline.value,
        );
        if (!this.db.enableOffline.value) {
            this.db.clear();
        }

        const credentials = {
            username,
            password,
        };
        return login(credentials).then(() => {
            this.setSession();
            // init offline if supported and not inited yet
            this.dbOffline.init();
            if (
                (!this.db.storage || this.db.broken) &&
                this.db.enableOffline.value
            ) {
                // Initialize database in offline mode when it has not been initialized yet or it got broken.
                this.dbOffline.init();

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

                this.setupServiceWorker();
            }
        });
    }

    static setupServiceWorker(): void {
        if (!('serviceWorker' in navigator) || this.serviceWorkerInitialized) {
            return;
        }

        this.serviceWorkerInitialized = true;

        navigator.serviceWorker.addEventListener('controllerchange', () => {
            window.location.reload();
        });

        navigator.serviceWorker
            .register(new URL('../selfoss-sw-offline.ts', import.meta.url), {
                type: 'module',
            })
            .then((reg) => {
                this.listenWaitingSW(reg, (reg) => {
                    this.app.notifyNewVersion(() => {
                        if (reg.waiting) {
                            reg.waiting.postMessage('skipWaiting');
                        }
                    });
                });
            });
    }

    static async logout(): Promise<void> {
        this.clearSession();

        this.db.clear(); // will not work after a failure, since storage is nulled
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
            this.serviceWorkerInitialized = false;
        }

        try {
            await logout();

            if (!this.config.publicMode) {
                selfoss.navigate('/sign/in');
            }
        } catch (error) {
            this.app.showError(
                this.app._('error_logout') + ' ' + error.message,
            );
        }
    }

    /**
     * Checks whether the current user is allowed to perform read operations.
     */
    static isAllowedToRead(): boolean {
        return (
            this.hasSession() ||
            !this.config.authEnabled ||
            this.config.publicMode
        );
    }

    /**
     * Checks whether the current user is allowed to perform update-tier operations.
     */
    static isAllowedToUpdate(): boolean {
        return (
            this.hasSession() ||
            !this.config.authEnabled ||
            this.config.allowPublicUpdate
        );
    }

    /**
     * Checks whether the current user is allowed to perform write operations.
     */
    static isAllowedToWrite(): boolean {
        return this.hasSession() || !this.config.authEnabled;
    }

    /**
     * Checks whether the current user is allowed to perform write operations.
     */
    static isOnline(): boolean {
        return this.db.online;
    }

    /**
     * indicates whether a mobile device is host
     *
     * @return true if device resolution smaller equals 1024
     */
    static isMobile(): boolean {
        // first check useragent
        if (/iPhone|iPod|iPad|Android|BlackBerry/.test(navigator.userAgent)) {
            return true;
        }

        // otherwise check resolution
        return this.isTablet() || this.isSmartphone();
    }

    /**
     * indicates whether a tablet is the device or not
     *
     * @return true if device resolution smaller equals 1024
     */
    static isTablet(): boolean {
        if (document.body.clientWidth <= 1024) {
            return true;
        }
        return false;
    }

    /**
     * indicates whether a tablet is the device or not
     *
     * @return true if device resolution smaller equals 1024
     */
    static isSmartphone(): boolean {
        if (document.body.clientWidth <= 640) {
            return true;
        }
        return false;
    }

    /**
     * Override these functions to customize selfoss behaviour.
     */
    public static extensionPoints = {
        /**
         * Called when an article is first expanded.
         * @param _contents HTML element containing the article contents
         */
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        processItemContents(_contents: HTMLElement) {},
    };

    /**
     * refresh stats.
     *
     * @param all new all stats
     * @param unread new unread stats
     * @param starred new starred stats
     */
    static refreshStats(all: number, unread: number, starred: number): void {
        this.app.setAllItemsCount(all);
        this.app.setStarredItemsCount(starred);

        this.refreshUnread(unread);
    }

    /**
     * refresh unread stats.
     *
     * @param unread new unread stats
     */
    static refreshUnread(unread: number): void {
        this.app.setUnreadItemsCount(unread);
    }

    /**
     * refresh current tags.
     */
    static reloadTags(): void {
        this.app.setTagsState(LoadingState.LOADING);

        getAllTags()
            .then((data) => {
                this.app.setTags(data);
                this.app.setTagsState(LoadingState.SUCCESS);
            })
            .catch((error) => {
                this.app.setTagsState(LoadingState.FAILURE);
                this.app.showError(
                    this.app._('error_load_tags') + ' ' + error.message,
                );
            });
    }

    static handleAjaxError(
        error: Error,
        tryOffline: boolean = true,
    ): Promise<void> {
        if (!(error instanceof HttpError || error instanceof TimeoutError)) {
            return Promise.reject(error);
        }

        const httpCode = error?.response?.status || 0;

        if (tryOffline && httpCode != 403) {
            return this.db.setOffline();
        } else {
            return Promise.reject(error);
        }
    }

    static listenWaitingSW(
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
    }

    // Include helpers for user scripts.
    public static ajax = ajax;
}

export default selfoss;
