import { TrivialResponse } from './common';
import * as ajax from '../helpers/ajax';
import { unescape } from 'html-escaper';
import { TagWithUnread } from './tags';
import { SourceWithUnread } from './sources';

function safeDate(datetimeString: string): Date {
    const date = new Date(datetimeString);

    if (isNaN(date.valueOf())) {
        throw new Error(`Invalid date detected: “${datetimeString}”`);
    } else {
        return date;
    }
}

/**
 * Mark items with given ids as read.
 */
export function markAll(ids: number[]): Promise<TrivialResponse> {
    return ajax
        .post('mark', {
            headers: {
                'content-type': 'application/json; charset=utf-8',
            },
            body: JSON.stringify(ids),
        })
        .promise.then((response) => response.json());
}

/**
 * Star or unstar item with given id.
 */
export function starr(id: number, starr: boolean): Promise<TrivialResponse> {
    return ajax.post(`${starr ? 'starr' : 'unstarr'}/${id}`).promise;
}

/**
 * Mark item with given id as (un)read.
 */
export function mark(id: number, read: boolean): Promise<TrivialResponse> {
    return ajax.post(`${read ? 'unmark' : 'mark'}/${id}`).promise;
}

export type TagColor = {
    foreColor: string;
    backColor: string;
};

type RawResponseItem = {
    id: number;
    title: string;
    strippedTitle: string;
    content: string;
    unread: boolean;
    starred: boolean;
    source: number;
    thumbnail: string;
    icon: string;
    uid: string;
    link: string;
    wordCount: number;
    lengthWithoutTags: number;
    datetime: string;
    updatetime: string | null;
    sourcetitle: string;
    author: string;
    tags: {
        [key: string]: TagColor;
    };
};

export type ResponseItem = {
    id: number;
    title: string;
    strippedTitle: string;
    content: string;
    unread: boolean;
    starred: boolean;
    source: number;
    thumbnail: string;
    icon: string;
    uid: string;
    link: string;
    wordCount: number;
    lengthWithoutTags: number;
    datetime: Date;
    updatetime: Date | null;
    sourcetitle: string;
    author: string;
    tags: {
        [key: string]: TagColor;
    };
};

/**
 * Converts some values like dates in an entry into a objects.
 */
function enrichItem(entry: RawResponseItem): ResponseItem {
    return {
        ...entry,
        link: unescape(entry.link),
        datetime: safeDate(entry.datetime),
        updatetime:
            entry.updatetime !== null ? safeDate(entry.updatetime) : null,
    };
}

type RawItemsResponse = {
    lastUpdate: string | null;
    entries: Array<RawResponseItem>;
    hasMore: boolean;
    all: number;
    unread: number;
    starred: number;
    tags: Array<TagWithUnread>;
    sources: Array<SourceWithUnread>;
};

type ItemsResponse = {
    lastUpdate: Date | null;
    entries: Array<ResponseItem>;
    hasMore: boolean;
    all: number;
    unread: number;
    starred: number;
    tags: Array<TagWithUnread>;
    sources: Array<SourceWithUnread>;
};

/**
 * Converts some values like dates in response into a objects.
 */
function enrichItemsResponse(data: RawItemsResponse): ItemsResponse {
    return {
        ...data,
        lastUpdate: data.lastUpdate !== null ? safeDate(data.lastUpdate) : null,
        entries: data.entries.map(enrichItem),
    };
}

type QueryFilter = {
    fromDatetime?: Date;
};

/**
 * Get all items matching given filter.
 */
export function getItems(
    filter: QueryFilter,
    abortController?: AbortController,
): Promise<ItemsResponse> {
    return ajax
        .get('', {
            body: ajax.makeSearchParams({
                ...filter,
                fromDatetime: filter.fromDatetime
                    ? filter.fromDatetime.toISOString()
                    : filter.fromDatetime,
            }),
            abortController,
        })
        .promise.then((response) => response.json())
        .then(enrichItemsResponse);
}

export type StatusUpdate = {
    id: number;
    unread?: boolean;
    starred?: boolean;
    datetime: Date;
};

export type SyncParams = {
    updatedStatuses?: Array<StatusUpdate>;
    tags?: boolean;
    sources?: boolean;
    itemsStatuses?: boolean;
    since?: Date;
    itemsHowMany?: number;
    itemsSinceId?: number;
    itemsNotBefore?: Date;
};

export type EntryStatus = {
    id: number;
    unread: boolean;
    starred: boolean;
};

export type NavTag = { tag: string; unread: number };

export type NavSource = { id: number; title: string; unread: number };

export type Stats = { total: number; unread: number; starred: number };

export type RawSyncResponse = {
    newItems?: RawResponseItem[];
    lastId?: number | null;
    lastUpdate: string | null;
    stats?: Stats;
    tags?: TagWithUnread[];
    sources?: SourceWithUnread[];
    itemUpdates?: EntryStatus[];
};

export type SyncResponse = {
    newItems?: ResponseItem[];
    lastId?: number | null;
    lastUpdate: Date | null;
    stats?: Stats;
    tags?: TagWithUnread[];
    sources?: SourceWithUnread[];
    itemUpdates?: EntryStatus[];
};

/**
 * Converts some values like dates in response into a objects.
 */
function enrichSyncResponse(data: RawSyncResponse): SyncResponse {
    return {
        ...data,
        lastUpdate: data.lastUpdate !== null ? safeDate(data.lastUpdate) : null,
        newItems: data.newItems?.map(enrichItem),
    };
}

/**
 * Synchronize changes between client and server.
 */
export function sync(
    updatedStatuses: Array<StatusUpdate>,
    syncParams: SyncParams,
): { controller: AbortController; promise: Promise<SyncResponse> } {
    const params = {
        ...syncParams,
        updatedStatuses: syncParams.updatedStatuses
            ? syncParams.updatedStatuses.map((status: StatusUpdate) => {
                  return {
                      ...status,
                      datetime: status.datetime.toISOString(),
                  };
              })
            : syncParams.updatedStatuses,

        since:
            'since' in syncParams ? syncParams.since.toISOString() : undefined,

        itemsNotBefore:
            'itemsNotBefore' in syncParams
                ? syncParams.itemsNotBefore.toISOString()
                : undefined,
    };

    const { controller, promise } = ajax.fetch('items/sync', {
        method: updatedStatuses ? 'POST' : 'GET',
        body: ajax.makeSearchParams(params),
    });

    return {
        controller,
        promise: promise
            .then((response: Response) => response.json())
            .then(enrichSyncResponse),
    };
}
