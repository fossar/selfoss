import { TrivialResponse } from './common';
import * as ajax from '../helpers/ajax';
import { unescape } from 'html-escaper';

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

type ResponseItem = {
    link: string;
    datetime: string;
    updatetime: string | null;
};

type EnrichedResponseItem = {
    link: string;
    datetime: string;
    updatetime: string | null;
};

/**
 * Converts some values like dates in an entry into a objects.
 */
function enrichEntry(entry: ResponseItem): EnrichedResponseItem {
    return {
        ...entry,
        link: unescape(entry.link),
        datetime: safeDate(entry.datetime),
        updatetime: entry.updatetime
            ? safeDate(entry.updatetime)
            : entry.updatetime,
    };
}

type RawItemsResponse = {
    lastUpdate?: string;
    entries?: Array<ResponseItem>;
    newItems?: Array<ResponseItem>;
};

type EnrichedItemsResponse = {
    lastUpdate?: string;
    entries?: Array<EnrichedItem>;
    newItems?: Array<EnrichedItem>;
};

/**
 * Converts some values like dates in response into a objects.
 */
function enrichItemsResponse(data: RawItemsResponse): EnrichedItemsResponse {
    return {
        ...data,
        lastUpdate: data.lastUpdate
            ? safeDate(data.lastUpdate)
            : data.lastUpdate,
        // in getItems
        entries: data.entries?.map(enrichEntry),
        // in sync
        newItems: data.newItems?.map(enrichEntry),
    };
}

type QueryFilter = {
    fromDatetime?: Date;
};

type GetItemsResponse = {
    entries: Array<EnrichedResponseItem>;
};

/**
 * Get all items matching given filter.
 */
export function getItems(
    filter: QueryFilter,
    abortController?: AbortController,
): Promise<GetItemsResponse> {
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

type StatusUpdate = {
    id: number;
    unread?: boolean;
    starred?: boolean;
    datetime: Date;
};

type SyncParams = {
    updatedStatuses: Array<StatusUpdate>;
};

/**
 * Synchronize changes between client and server.
 */
export function sync(
    updatedStatuses: Array<StatusUpdate>,
    syncParams: SyncParams,
): { controller: AbortController; promise: Promise<GetItemsResponse> } {
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
    };

    if ('since' in params) {
        params.since = params.since.toISOString();
    }
    if ('itemsNotBefore' in params) {
        params.itemsNotBefore = params.itemsNotBefore.toISOString();
    }

    const { controller, promise } = ajax.fetch('items/sync', {
        method: updatedStatuses ? 'POST' : 'GET',
        body: ajax.makeSearchParams(params),
    });

    return {
        controller,
        promise: promise
            .then((response) => response.json())
            .then(enrichItemsResponse),
    };
}
