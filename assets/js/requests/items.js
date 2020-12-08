import * as ajax from '../helpers/ajax';

/**
 * Get list of entries.
 */
export function getEntries(params) {
    const { controller, promise } = ajax.get('', {
        body: ajax.makeSearchParams(params)
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
 * Get all items matching given filter.
 */
export function getItems(filter) {
    const { controller, promise } = ajax.get('', {
        body: ajax.makeSearchParams(filter)
    });

    return {
        controller,
        promise: promise.then(response => response.json())
    };
}

/**
 * Synchronize changes between client and server.
 */
export function sync(updatedStatuses, syncParams) {
    const { controller, promise } = ajax.fetch('items/sync', {
        method: updatedStatuses ? 'POST' : 'GET',
        body: ajax.makeSearchParams(syncParams)
    });

    return {
        controller,
        promise: promise.then(response => response.json())
    };
}
