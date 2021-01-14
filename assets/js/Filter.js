/**
 * Object describing how feed items are filtered in the view.
 * @enum {string}
 */
export const FilterType = {
    NEWEST: 'newest',
    UNREAD: 'unread',
    STARRED: 'starred'
};

export class FilterChangeEvent extends Event {
    constructor(filter) {
        super('change');
        this.filter = filter;
    }
}

/**
 * Object describing how feed items are filtered in the view.
 */
export class Filter extends EventTarget {
    /**
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
        super();

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

    update(newProps, setHash = false) {
        const event = new FilterChangeEvent(this);
        event.setHash = setHash;

        let changed = false;

        if (Object.keys(newProps).includes('fromDatetime') && this.fromDatetime !== newProps.fromDatetime) {
            changed = true;
            this.fromDatetime = newProps.fromDatetime;
        }
        if (Object.keys(newProps).includes('fromId') && this.fromId !== newProps.fromId) {
            changed = true;
            this.fromId = newProps.fromId;
        }
        if (Object.keys(newProps).includes('itemsPerPage') && this.itemsPerPage !== newProps.itemsPerPage) {
            changed = true;
            this.itemsPerPage = newProps.itemsPerPage;
        }
        if (Object.keys(newProps).includes('search') && this.search !== newProps.search) {
            changed = true;
            this.search = newProps.search;
        }
        if (Object.keys(newProps).includes('type') && this.type !== newProps.type) {
            changed = true;
            this.type = newProps.type;
        }
        if (Object.keys(newProps).includes('tag') && this.tag !== newProps.tag) {
            changed = true;
            this.tag = newProps.tag;
        }
        if (Object.keys(newProps).includes('source') && this.source !== newProps.source) {
            changed = true;
            this.source = newProps.source;
        }
        if (Object.keys(newProps).includes('sourcesNav') && this.sourcesNav !== newProps.sourcesNav) {
            changed = true;
            this.sourcesNav = newProps.sourcesNav;
        }
        if (Object.keys(newProps).includes('extraIds') && !(this.extraIds.length === 0 && newProps.extraIds.length === 0)) {
            // Only empty arrays can be considered equal,
            changed = true;
            this.extraIds = newProps.extraIds;
        }

        if (changed) {
            this.dispatchEvent(event);
        }
    }
}
