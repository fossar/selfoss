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
    public entries!: Dexie.Table<ResponseItem>;
    public statusq!: Dexie.Table<Status, number>;
    public stamps!: Dexie.Table<Stamp>;
    public stats!: Dexie.Table<Stat>;
    public tags!: Dexie.Table<Tag>;
    public sources!: Dexie.Table<Source>;

    public constructor() {
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
