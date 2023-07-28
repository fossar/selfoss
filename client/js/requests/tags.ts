import * as ajax from '../helpers/ajax';

export type TagWithUnread = {
    tag: string;
    color: string;
    unread: number;
};

/**
 * Get tags for all items.
 */
export function getAllTags(): Promise<Array<TagWithUnread>> {
    return ajax.get('tags').promise.then((response) => response.json());
}

/**
 * Update tag colour.
 */
export function updateTag(tag: string, color: string): Promise<Response> {
    return ajax.post('tags/color', {
        body: ajax.makeSearchParams({
            tag,
            color,
        }),
    }).promise;
}
