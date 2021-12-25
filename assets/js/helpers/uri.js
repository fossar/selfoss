import { generatePath } from 'react-router-dom';
import { FilterType } from '../Filter';

/**
 * Converts URL segment to FilterType value.
 *
 * @param {string}
 *
 * @returns {FilterType.*}
 */
export function filterTypeFromString(type) {
    if (type == 'newest') {
        return FilterType.NEWEST;
    } else if (type == 'unread') {
        return FilterType.UNREAD;
    } else if (type == 'starred') {
        return FilterType.STARRED;
    } else {
        throw new Error(`Invalid filter type: “${type}”`);
    }
}

/**
 * Converts FilterType value to string usable in URL.
 *
 * @param {FilterType.*}
 *
 * @returns {string}
 */
export function filterTypeToString(type) {
    if (type == FilterType.NEWEST) {
        return 'newest';
    } else if (type == FilterType.UNREAD) {
        return 'unread';
    } else if (type == FilterType.STARRED) {
        return 'starred';
    }
}

export const ENTRIES_ROUTE_PATTERN = '/:filter(newest|unread|starred)/:category(all|tag-[^/]+|source-[0-9]+)/:id?';

export function makeEntriesLink(location, { filter, category, id, search }) {
    const queryString = new URLSearchParams(location.search);

    let path;
    if (location.pathname.match(/^\/(newest|unread|starred)\//) !== null) {
        const [, ...segments] = location.pathname.split('/');

        path = generatePath(ENTRIES_ROUTE_PATTERN, {
            filter: filter ?? segments[0],
            category: category ?? segments[1],
            id: typeof id !== 'undefined' ? id : segments[2]
        });
    } else {
        path = generatePath(ENTRIES_ROUTE_PATTERN, {
            // TODO: change default from config
            filter: filter ?? 'unread',
            category: category ?? 'all',
            id
        });
    }

    const searchParam = typeof search !== 'undefined' ? search : queryString?.get('search');

    return path + (searchParam ? `?search=${encodeURIComponent(searchParam)}` : '');
}

export function forceReload(location) {
    const state = location.state ?? {};

    return {
        ...state,
        forceReload: (state.forceReload ?? 0) + 1,
    };
}
