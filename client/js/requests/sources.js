import * as ajax from '../helpers/ajax';

/**
 * Updates source with given ID.
 */
export function update(id, values) {
    return ajax
        .post(`source/${id}`, {
            headers: {
                'content-type': 'application/json; charset=utf-8',
            },
            body: JSON.stringify(values),
            failOnHttpErrors: false,
        })
        .promise.then(
            ajax.rejectUnless(
                (response) => response.ok || response.status === 400,
            ),
        )
        .then((response) => response.json());
}

/**
 * Triggers an update of the source with given ID.
 */
export function refreshSingle(id) {
    return ajax.post('source/' + id + '/update', {
        timeout: 0,
    }).promise;
}

/**
 * Triggers an update of all sources.
 */
export function refreshAll() {
    return ajax
        .get('update', {
            headers: {
                Accept: 'text/event-stream',
            },
            timeout: 0,
        })
        .promise.then((response) => response.text());
}

/**
 * Removes source with given ID.
 */
export function remove(id) {
    return ajax.delete_(`source/${id}`).promise;
}

/**
 * Gets all sources.
 */
export function getAllSources(abortController) {
    return ajax
        .get('sources', {
            abortController,
        })
        .promise.then((response) => response.json());
}

/**
 * Gets list of supported spouts and their paramaters.
 */
export function getSpouts() {
    return ajax.get('source').promise.then((response) => response.json());
}

/**
 * Gets parameters for given spout.
 */
export function getSpoutParams(spoutClass) {
    return ajax
        .get('source/params', {
            body: ajax.makeSearchParams({ spout: spoutClass }),
        })
        .promise.then((res) => res.json());
}

/**
 * Gets source unread stats.
 */
export function getStats() {
    return ajax
        .get('sources/stats')
        .promise.then((response) => response.json());
}
