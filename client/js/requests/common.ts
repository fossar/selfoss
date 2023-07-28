import { LoginError } from '../errors';
import * as ajax from '../helpers/ajax';
import { Configuration } from '../model/Configuration';

export class PasswordHashingError extends Error {
    public name: string;

    constructor(message: string) {
        super(message);
        this.name = 'PasswordHashingError';
    }
}

export type TrivialResponse = {
    success: boolean;
};

type InstanceInfo = {
    version: string;
    apiversion: string;
    configuration: Configuration;
};

/**
 * Gets information about selfoss instance.
 */
export function getInstanceInfo(): Promise<InstanceInfo> {
    return ajax
        .get('api/about', {
            // we want fresh configuration each time
            cache: 'no-store',
        })
        .promise.then((response) => response.json());
}

type Credentials = {
    username: string;
    password: string;
};

/**
 * Signs in user with provided credentials.
 */
export function login(credentials: Credentials): Promise<void> {
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
export function hashPassword(password: string): Promise<string> {
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

export type OpmlImportData = {
    messages: string[];
};

/**
 * Import OPML file.
 */
export function importOpml(
    file: File,
): Promise<{ response: Response; data: OpmlImportData }> {
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
export function logout(): Promise<Response> {
    return ajax.delete_('api/session/current').promise;
}
