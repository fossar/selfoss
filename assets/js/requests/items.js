import * as ajax from '../helpers/ajax';

/**
 * Get list of entries.
 */
export function getEntries(params) {
    const { controller, promise } = ajax.get('', {
        body: ajax.makeSearchParams({
            ...params,
            fromDatetime: params.fromDatetime ? params.fromDatetime.toISOString() : params.fromDatetime
        })
    });

    return { controller, promise };
}

/**
 * Mark items with given ids as read.
 */
export function markAll(ids) {
    return ajax.post('mark', {
        headers: {
            'content-type': 'application/json; charset=utf-8'
        },
        body: JSON.stringify(ids)
    }).promise.then(response => response.json());
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
        datetime: new Date(entry.datetime),
        updatetime: entry.updatetime ? new Date(entry.updatetime) : entry.updatetime
    };
}

/**
 * Converts some values like dates in response into a objects.
 */
function enrichItemsResponse(data) {
    return {
        ...data,
        lastUpdate: data.lastUpdate ? new Date(data.lastUpdate) : data.lastUpdate,
        // in getItems
        entries: data.entries?.map(enrichEntry),
        // in sync
        newItems: data.newItems?.map(enrichEntry)
    };
}

/**
 * Get all items matching given filter.
 */
export function getItems(filter) {
    const { controller, promise } = ajax.get('', {
        body: ajax.makeSearchParams({
            ...filter,
            fromDatetime: filter.fromDatetime ? filter.fromDatetime.toISOString() : filter.fromDatetime
        })
    });

    return {
        controller,
        promise: promise.then(response => response.json()).then(enrichItemsResponse)
    };
}

/**
 * Synchronize changes between client and server.
 */
export function sync(updatedStatuses, syncParams) {
    let params = {
        ...syncParams,
        updatedStatuses: syncParams.updatedStatuses ? syncParams.updatedStatuses.map((status) => {
            return {
                ...status,
                datetime: status.datetime.toISOString()
            };
        }) : syncParams.updatedStatuses
    };

    if ('since' in params) {
        params.since = params.since.toISOString();
    }
    if ('itemsNotBefore' in params) {
        params.itemsNotBefore = params.itemsNotBefore.toISOString();
    }

    const { controller, promise } = ajax.fetch('items/sync', {
        method: updatedStatuses ? 'POST' : 'GET',
        body: ajax.makeSearchParams(params)
    });

    return {
        controller,
        promise: promise.then(response => response.json()).then(enrichItemsResponse)
    };
}
