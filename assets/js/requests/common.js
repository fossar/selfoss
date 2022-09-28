import * as ajax from '../helpers/ajax';

/**
 * Gets information about selfoss instance.
 */
export function getInstanceInfo() {
    return ajax.get('api/about', {
        // we want fresh configuration each time
        cache: 'no-store'
    }).promise.then(response => response.json());
}

/**
 * Signs in user with provided credentials.
 */
export function login(credentials) {
    return ajax.post('login', {
        body: new URLSearchParams(credentials)
    }).promise.then(response => response.json());
}

/**
 * Terminates the active user session.
 */
export function logout() {
    return ajax.delete_('api/session/current').promise;
}
