/**
 * Object describing how feed items are filtered in the view.
 * @enum {string}
 */
export const FilterType = {
    NEWEST: 'newest',
    UNREAD: 'unread',
    STARRED: 'starred'
};

/**
 * Object describing how feed items are filtered in the view.
 */
export class Filter {
    /**
     * @param {number} offset
     * @param {?Date} fromDatetime
     * @param {?number} fromId
     * @param {number} itemsPerPage
     * @param {string} search
     * @param {FilterType.*} type
     * @param {?string} tag
     * @param {?number} source
     * @param {bool} sourcesNav
     * @param {number[]} extraIds
     */
    constructor({
        offset = 0,
        fromDatetime = undefined,
        fromId = undefined,
        itemsPerPage = 0,
        search = '',
        type = FilterType.NEWEST,
        tag = null,
        source = null,
        sourcesNav = false,
        extraIds = []
    }) {
        this.offset = offset;
        this.fromDatetime = fromDatetime;
        this.fromId = fromId;
        this.itemsPerPage = itemsPerPage;
        this.search = search;
        this.type = type;
        this.tag = tag;
        this.source = source;
        this.sourcesNav = sourcesNav;
        this.extraIds = extraIds;
    }

    update(newProps) {
        if (Object.keys(newProps).includes('offset')) {
            this.offset = newProps.offset;
        }
        if (Object.keys(newProps).includes('fromDatetime')) {
            this.fromDatetime = newProps.fromDatetime;
        }
        if (Object.keys(newProps).includes('fromId')) {
            this.fromId = newProps.fromId;
        }
        if (Object.keys(newProps).includes('itemsPerPage')) {
            this.itemsPerPage = newProps.itemsPerPage;
        }
        if (Object.keys(newProps).includes('search')) {
            this.search = newProps.search;
        }
        if (Object.keys(newProps).includes('type')) {
            this.type = newProps.type;
        }
        if (Object.keys(newProps).includes('tag')) {
            this.tag = newProps.tag;
        }
        if (Object.keys(newProps).includes('source')) {
            this.source = newProps.source;
        }
        if (Object.keys(newProps).includes('sourcesNav')) {
            this.sourcesNav = newProps.sourcesNav;
        }
        if (Object.keys(newProps).includes('extraIds')) {
            this.extraIds = newProps.extraIds;
        }
    }
}
