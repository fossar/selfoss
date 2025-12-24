import Dexie from 'dexie';
import { ResponseItem } from '../requests/items';

export type StatusName = 'unread' | 'starred';

export interface Status {
    id?: number; // Primary key. Optional (autoincremented).
    entryId: number;
    name: StatusName;
    value: boolean;
    datetime: Date;
}

export interface Stamp {
    name: string;
    datetime: Date;
}

export type StatName = 'unread' | 'starred' | 'total';

export interface Stat {
    name: StatName;
    value: number;
}

export interface Tag {
    name: string;
}

export interface Source {
    id: number;
    first: string;
}

export class OfflineDb extends Dexie {
    // Declare implicit table properties.
    // (Just to inform Typescript. Instanciated by Dexie in stores() method.)
    entries!: Dexie.Table<ResponseItem>;
    statusq!: Dexie.Table<Status, number>;
    stamps!: Dexie.Table<Stamp>;
    stats!: Dexie.Table<Stat>;
    tags!: Dexie.Table<Tag>;
    sources!: Dexie.Table<Source>;

    constructor() {
        super('selfoss');
        this.version(1).stores({
            entries: '&id,*datetime,[datetime+id]',
            statusq: '++id,*entryId',
            stamps: '&name,datetime',
            stats: '&name',
            tags: '&name',
            sources: '&id',
        });
    }
}
