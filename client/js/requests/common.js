import { LoginError } from '../errors';
import * as ajax from '../helpers/ajax';

export class PasswordHashingError extends Error {
    constructor(message) {
        super(message);
        this.name = 'PasswordHashingError';
    }
}

/**
 * Gets information about selfoss instance.
 */
export function getInstanceInfo() {
    return ajax
        .get('api/about', {
            // we want fresh configuration each time
            cache: 'no-store',
        })
        .promise.then((response) => response.json());
}

/**
 * Signs in user with provided credentials.
 */
export function login(credentials) {
    return ajax
        .post('login', {
            body: new URLSearchParams(credentials),
        })
        .promise.then((response) => response.json())
        .then((data) => {
            if (data.success) {
                return Promise.resolve();
            } else {
                return Promise.reject(new LoginError(data.error));
            }
        });
}

/**
 * Salt and hash a password.
 */
export function hashPassword(password) {
    return ajax
        .post('api/private/hash-password', {
            body: new URLSearchParams({ password }),
        })
        .promise.then((response) => response.json())
        .then((data) => {
            if (data.success) {
                return Promise.resolve(data.hash);
            } else {
                return Promise.reject(new PasswordHashingError(data.error));
            }
        });
}

/**
 * Import OPML file.
 */
export function importOpml(file) {
    const data = new FormData();
    data.append('opml', file);

    return ajax
        .post('opml', {
            body: data,
            failOnHttpErrors: false,
        })
        .promise.then(
            ajax.rejectUnless(
                (response) =>
                    response.status === 200 ||
                    response.status === 202 ||
                    response.status === 400,
            ),
        )
        .then((response) =>
            response.json().then((data) => ({ response, data })),
        );
}

/**
 * Terminates the active user session.
 */
export function logout() {
    return ajax.delete_('api/session/current').promise;
}
