import { LoadingState } from '../requests/LoadingState';

export class TagsChangeEvent extends Event {
    constructor(tags) {
        super('change');
        this.tags = tags;
    }
}

export class TagsStateChangeEvent extends Event {
    constructor(state) {
        super('statechange');
        this.state = state;
    }
}

/**
 * Object storing list of tags and their information.
 */
export class TagsRepository extends EventTarget {
    /**
     * @param {Object[]} tags
     * @param {LoadingState} state
     */
    constructor({
        tags = [],
        state = LoadingState.INITIAL
    }) {
        super();

        this.tags = tags;
        this.state = state;
    }

    update(tags) {
        const event = new TagsChangeEvent(tags);

        let changed = false;

        if (!(this.tags.length === 0 && tags.length === 0)) {
            // Only empty arrays can be considered equal,
            changed = true;
            this.tags = tags;
        }

        if (changed) {
            this.dispatchEvent(event);
        }
    }

    setState(state) {
        const event = new TagsStateChangeEvent(state);

        if (this.state !== state) {
            this.dispatchEvent(event);
        }
    }
}
