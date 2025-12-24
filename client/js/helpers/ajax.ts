import formurlencoded from 'form-urlencoded';
import mergeDeepLeft from 'ramda/src/mergeDeepLeft';
import pipe from 'ramda/src/pipe';
import { HttpError, TimeoutError } from '../errors';

type FetchOptions = RequestInit & {
    abortController?: AbortController;
    timeout?: number;
    failOnHttpErrors?: boolean;
};

interface Fetch {
    (url: RequestInfo | URL, opts?: RequestInit): Promise<Response>;
}

type AbortableFetchResult = {
    controller: AbortController;
    promise: Promise<Response>;
};

interface AbortableFetch {
    (url: RequestInfo | URL, opts?: FetchOptions): AbortableFetchResult;
}

/**
 * Passing this function as a Promise handler will make the promise fail when the predicate is not true.
 */
export function rejectUnless(
    pred: (response: Response) => boolean,
): (response: Response) => Response {
    return (response: Response) => {
        if (pred(response)) {
            return response;
        } else {
            const err = new HttpError(response.statusText);
            err.response = response;
            throw err;
        }
    };
}

/**
 * fetch API considers a HTTP error a successful state.
 * Passing this function as a Promise handler will make the promise fail when HTTP error occurs.
 */
export function rejectIfNotOkay(response: Response): Response {
    return rejectUnless((response: Response) => response.ok)(response);
}

/**
 * Override fetch options.
 */
export const options =
    (newOpts: FetchOptions) =>
    (fetch: Fetch) =>
    (url: string, opts: FetchOptions = {}) =>
        fetch(url, mergeDeepLeft(opts, newOpts));

/**
 * Override just a single fetch option.
 */
export const option = <K extends keyof FetchOptions>(
    name: K,
    value: FetchOptions[K],
) => options({ [name]: value });

/**
 * Override just headers in fetch.
 */
export const headers = (value: HeadersInit) => option('headers', value);

/**
 * Override just a single header in fetch.
 */
export const header = (name: string, value: string) =>
    headers({ [name]: value });

/**
 * Lift a wrapper function so that it can wrap a function returning more than just a Promise.
 *
 * For example, a wrapper can be a function that takes a `fetch` function and returns another
 * `fetch` function with method defaulting to `POST`. This function allows us to lift the wrapper
 * so that it applies on modified `fetch` functions that return an object containing `promise` field
 * instead of a single Promise like AbortableFetch.
 */
export const liftToPromiseField =
    (wrapper: (f: Fetch) => Fetch) =>
    (f: AbortableFetch) =>
    (...params: Parameters<AbortableFetch>): AbortableFetchResult => {
        let rest: Omit<AbortableFetchResult, 'promise'>;
        const promise = wrapper((...innerParams) => {
            const { promise, ...innerRest } = f(...innerParams);
            rest = innerRest;
            return promise;
        })(...params);

        return { promise, ...rest };
    };

/**
 * Wrapper for fetch that makes it cancellable using AbortController.
 */
export function makeAbortableFetch(fetch: Fetch): AbortableFetch {
    return (url: string, opts: FetchOptions = {}) => {
        const controller = opts.abortController || new AbortController();
        const promise = fetch(url, {
            signal: controller.signal,
            ...opts,
        });

        return { controller, promise };
    };
}

/**
 * Wrapper for abortable fetch that adds timeout support.
 */
export function makeFetchWithTimeout(
    abortableFetch: AbortableFetch,
): AbortableFetch {
    return (url: string, opts: FetchOptions = {}): AbortableFetchResult => {
        // offline db consistency requires ajax calls to fail reliably,
        // so we enforce a default timeout on ajax calls
        const { timeout = 60000, ...rest } = opts;
        const { controller, promise } = abortableFetch(url, rest);

        if (timeout !== 0) {
            const newPromise = promise.catch((error) => {
                // Change error name in case of time out so that we can
                // distinguish it from explicit abort.
                if (error.name === 'AbortError' && 'timedOut' in promise) {
                    error = new TimeoutError(
                        `Request timed out after ${timeout / 1000} seconds`,
                    );
                }

                throw error;
            });

            setTimeout(() => {
                (promise as { timedOut?: boolean }).timedOut = true;
                controller.abort();
            }, timeout);

            return { controller, promise: newPromise };
        }

        return { controller, promise };
    };
}

/**
 * Wrapper for fetch that makes it fail on HTTP errors.
 */
export function makeFetchFailOnHttpErrors(fetch: Fetch): Fetch {
    return (url: string, opts: FetchOptions = {}): Promise<Response> => {
        const { failOnHttpErrors = true, ...rest } = opts;
        const promise = fetch(url, rest);

        if (failOnHttpErrors) {
            return promise.then(rejectIfNotOkay);
        }

        return promise;
    };
}

/**
 * Wrapper for fetch that converts URLSearchParams body of GET requests to query string.
 */
export function makeFetchSupportGetBody(fetch: Fetch): Fetch {
    return (url: string, opts: FetchOptions = {}) => {
        const { body, method, ...rest } = opts;

        let newUrl = url;
        let newOpts = opts;
        if (
            Object.keys(opts).includes('method') &&
            Object.keys(opts).includes('body') &&
            method.toUpperCase() === 'GET' &&
            body instanceof URLSearchParams
        ) {
            const [main, ...fragments] = newUrl.split('#');
            const separator = main.includes('?') ? '&' : '?';
            // append the body to the query string
            newUrl = `${main}${separator}${body.toString()}#${fragments.join('#')}`;
            // remove the body since it has been moved to URL
            newOpts = { method, ...rest };
        }

        return fetch(newUrl, newOpts);
    };
}

/**
 * Cancellable fetch with timeout support that rejects on HTTP errors.
 * In such case, the `response` will be member of the Error object.
 */
export const fetch: AbortableFetch = pipe(
    // Same as jQuery.ajax
    option('credentials', 'same-origin'),
    header('X-Requested-With', 'XMLHttpRequest'),

    makeFetchFailOnHttpErrors,
    makeFetchSupportGetBody,
    makeAbortableFetch,
    makeFetchWithTimeout,
)(window.fetch);

export const get: AbortableFetch = liftToPromiseField(option('method', 'GET'))(
    fetch,
);

export const post: AbortableFetch = liftToPromiseField(
    option('method', 'POST'),
)(fetch);

export const delete_: AbortableFetch = liftToPromiseField(
    option('method', 'DELETE'),
)(fetch);

/**
 * Using URLSearchParams directly handles dictionaries inconveniently.
 * For example, it joins arrays with commas or includes undefined keys.
 */
export function makeSearchParams(data: object): URLSearchParams {
    return new URLSearchParams(
        formurlencoded(data, {
            ignorenull: true,
        }),
    );
}
