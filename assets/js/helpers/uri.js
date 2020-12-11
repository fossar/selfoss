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
