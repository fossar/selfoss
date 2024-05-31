import * as ajax from '../helpers/ajax';
import { unescape } from 'html-escaper';

function safeDate(datetimeString) {
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
export function markAll(ids) {
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
export function starr(id, starr) {
    return ajax.post(`${starr ? 'starr' : 'unstarr'}/${id}`).promise;
}

/**
 * Mark item with given id as (un)read.
 */
export function mark(id, read) {
    return ajax.post(`${read ? 'unmark' : 'mark'}/${id}`).promise;
}

/**
 * Converts some values like dates in an entry into a objects.
 */
function enrichEntry(entry) {
    return {
        ...entry,
        link: unescape(entry.link),
        datetime: safeDate(entry.datetime),
        updatetime: entry.updatetime
            ? safeDate(entry.updatetime)
            : entry.updatetime,
    };
}

/**
 * Converts some values like dates in response into a objects.
 */
function enrichItemsResponse(data) {
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

/**
 * Get all items matching given filter.
 */
export function getItems(filter, abortController) {
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

/**
 * Synchronize changes between client and server.
 */
export function sync(updatedStatuses, syncParams) {
    const params = {
        ...syncParams,
        updatedStatuses: syncParams.updatedStatuses
            ? syncParams.updatedStatuses.map((status) => {
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
