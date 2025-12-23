/**
 * db functions: client data repository (and offline storage)
 *
 * db is a dispatcher class and holds the logic for deciding whether selfoss
 * is running online with access to the server or offline.
 *
 * dbOnline contains AJAX calls that provide access to the server db.
 *
 * dbOffline is the entry point for the offline database held in the client.
 */

import selfoss from './selfoss-base';
import { OfflineStorageNotAvailableError } from './errors';
import { ValueListenable } from './helpers/ValueListenable';
import { OfflineDb } from './model/OfflineDb';

export default class Db {
    /** When an error occurs we disable the offline mode and mark the database as broken so it can be retried. */
    public broken: boolean = false;
    public storage: OfflineDb | null = null;
    public online: boolean = true;
    public enableOffline: ValueListenable<boolean> = new ValueListenable(
        window.localStorage.getItem('enableOffline') === 'true',
    );
    public userWaiting: boolean = true;

    /**
     * last db timestamp known client side
     */
    public lastUpdate: Date | null = null;

    public lastSync: number | null = null;

    setOnline(): void {
        if (!this.online) {
            this.online = true;
            this.sync();
            selfoss.reloadTags();
            selfoss.app.setOfflineState(false);
        }
    }

    tryOnline(): Promise<void> {
        return this.sync(true);
    }

    setOffline(): Promise<void> {
        if (this.storage && !this.broken) {
            selfoss.dbOnline._syncDone(false);
            this.online = false;
            selfoss.app.setOfflineState(true);

            return Promise.resolve();
        } else {
            const err = new OfflineStorageNotAvailableError();
            return Promise.reject(err);
        }
    }

    clear(): Promise<void> {
        if (this.storage) {
            window.localStorage.removeItem('offlineDays');
            const clearing = this.storage.delete();
            this.storage = null;
            this.lastUpdate = null;
            return clearing;
        } else {
            return Promise.resolve();
        }
    }

    isValidTag(name: string): boolean {
        return (
            selfoss.app.state.tags.length === 0 ||
            selfoss.app.state.tags.find((tag) => tag.tag === name) !== undefined
        );
    }

    isValidSource(id: number): boolean {
        return (
            selfoss.app.state.sources.length === 0 ||
            selfoss.app.state.sources.find((source) => source.id === id) !==
                undefined
        );
    }

    sync(force = false): Promise<void> {
        const lastUpdateIsOld =
            this.lastUpdate === null ||
            this.lastSync === null ||
            Date.now() - this.lastSync > 5 * 60 * 1000;
        const shouldSync =
            force || selfoss.dbOffline.needsSync || lastUpdateIsOld;
        if (selfoss.isAllowedToRead() && selfoss.isOnline() && shouldSync) {
            if (this.enableOffline.value) {
                return selfoss.dbOffline.sendNewStatuses();
            } else {
                return selfoss.dbOnline.sync();
            }
        } else {
            return Promise.resolve(); // ensure any chained function runs
        }
    }
}
