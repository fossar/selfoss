import Dexie from 'dexie';

interface Entry {
    id: number;
    datetime: Date;
}

interface Status {
    id?: number; // Primary key. Optional (autoincremented).
    entryId: string;
    name: string;
    value: string;
    datetime: Date;
}

interface Stamp {
    name: string;
    datetime: Date;
}

interface Stat {
    name: string;
    value: number;
}

interface Tag {
    name: string;
}

interface Source {
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
