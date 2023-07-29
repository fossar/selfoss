import Dexie from 'dexie';

export interface Entry {
    id: number;
    datetime: Date;
    unread: boolean;
    starred: boolean;
}

export interface Status {
    id?: number; // Primary key. Optional (autoincremented).
    entryId: number;
    name: string;
    value: boolean;
    datetime: Date;
}

export interface Stamp {
    name: string;
    datetime: Date;
}

export interface Stat {
    name: string;
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
    entries!: Dexie.Table<Entry>;
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
