import * as ajax from '../helpers/ajax';

/**
 * Get tags for all items.
 */
export function getAllTags() {
    return ajax.get('tags').promise.then((response) => response.json());
}

/**
 * Update tag colour.
 */
export function updateTag(tag, color) {
    return ajax.post('tags/color', {
        body: ajax.makeSearchParams({
            tag,
            color,
        }),
    }).promise;
}
