import formurlencoded from 'form-urlencoded';
import mergeDeepLeft from 'ramda/src/mergeDeepLeft.js';
import pipe from 'ramda/src/pipe.js';
import { HttpError, TimeoutError } from '../errors';

/**
 * Passing this function as a Promise handler will make the promise fail when the predicate is not true.
 */
export const rejectUnless = (pred) => (response) => {
    if (pred(response)) {
        return response;
    } else {
        const err = new HttpError(response.statusText);
        err.response = response;
        throw err;
    }
};

/**
 * fetch API considers a HTTP error a successful state.
 * Passing this function as a Promise handler will make the promise fail when HTTP error occurs.
 */
export const rejectIfNotOkay = (response) => {
    return rejectUnless((response) => response.ok)(response);
};

/**
 * Override fetch options.
 */
export const options =
    (newOpts) =>
    (fetch) =>
    (url, opts = {}) =>
        fetch(url, mergeDeepLeft(opts, newOpts));

/**
 * Override just a single fetch option.
 */
export const option = (name, value) => options({ [name]: value });

/**
 * Override just headers in fetch.
 */
export const headers = (value) => option('headers', value);

/**
 * Override just a single header in fetch.
 */
export const header = (name, value) => headers({ [name]: value });

/**
 * Lift a wrapper function so that it can wrap a function returning more than just a Promise.
 *
 * For example, a wrapper can be a function that takes a `fetch` function and returns another
 * `fetch` function with method defaulting to `POST`. This function allows us to lift the wrapper
 * so that it applies on modified `fetch` functions that return an object containing `promise` field
 * instead of a single Promise like AbortableFetch.
 *
 * @sig ((...params → Promise) → (...params → Promise)) → (...params → {promise: Promise, ...}) → (...params → {promise: Promise, ...})
 */
export const liftToPromiseField =
    (wrapper) =>
    (f) =>
    (...params) => {
        let rest;
        const promise = wrapper((...innerParams) => {
            const { promise, ...innerRest } = f(...innerParams);
            rest = innerRest;
            return promise;
        })(...params);

        return { promise, ...rest };
    };

/**
 * Wrapper for fetch that makes it cancellable using AbortController.
 * @return {controller: AbortController, promise: Promise}
 */
export const makeAbortableFetch =
    (fetch) =>
    (url, opts = {}) => {
        const controller = opts.abortController || new AbortController();
        const promise = fetch(url, {
            signal: controller.signal,
            ...opts,
        });

        return { controller, promise };
    };

/**
 * Wrapper for abortable fetch that adds timeout support.
 * @return {controller: AbortController, promise: Promise}
 */
export const makeFetchWithTimeout =
    (abortableFetch) =>
    (url, opts = {}) => {
        // offline db consistency requires ajax calls to fail reliably,
        // so we enforce a default timeout on ajax calls
        const { timeout = 60000, ...rest } = opts;
        const { controller, promise } = abortableFetch(url, rest);

        if (timeout !== 0) {
            const newPromise = promise.catch((error) => {
                // Change error name in case of time out so that we can
                // distinguish it from explicit abort.
                if (error.name === 'AbortError' && promise.timedOut) {
                    error = new TimeoutError(
                        `Request timed out after ${timeout / 1000} seconds`,
                    );
                }

                throw error;
            });

            setTimeout(() => {
                promise.timedOut = true;
                controller.abort();
            }, timeout);

            return { controller, promise: newPromise };
        }

        return { controller, promise };
    };

/**
 * Wrapper for fetch that makes it fail on HTTP errors.
 * @return Promise
 */
export const makeFetchFailOnHttpErrors =
    (fetch) =>
    (url, opts = {}) => {
        const { failOnHttpErrors = true, ...rest } = opts;
        const promise = fetch(url, rest);

        if (failOnHttpErrors) {
            return promise.then(rejectIfNotOkay);
        }

        return promise;
    };

/**
 * Wrapper for fetch that converts URLSearchParams body of GET requests to query string.
 */
export const makeFetchSupportGetBody =
    (fetch) =>
    (url, opts = {}) => {
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
            newOpts = { method, rest };
        }

        return fetch(newUrl, newOpts);
    };

/**
 * Cancellable fetch with timeout support that rejects on HTTP errors.
 * In such case, the `response` will be member of the Error object.
 * @return {controller: AbortController, promise: Promise}
 */
export const fetch = pipe(
    // Same as jQuery.ajax
    option('credentials', 'same-origin'),
    header('X-Requested-With', 'XMLHttpRequest'),

    makeFetchFailOnHttpErrors,
    makeFetchSupportGetBody,
    makeAbortableFetch,
    makeFetchWithTimeout,
)(window.fetch);

export const get = liftToPromiseField(option('method', 'GET'))(fetch);

export const post = liftToPromiseField(option('method', 'POST'))(fetch);

export const delete_ = liftToPromiseField(option('method', 'DELETE'))(fetch);

/**
 * Using URLSearchParams directly handles dictionaries inconveniently.
 * For example, it joins arrays with commas or includes undefined keys.
 */
export const makeSearchParams = (data) =>
    new URLSearchParams(
        formurlencoded(data, {
            ignorenull: true,
        }),
    );
