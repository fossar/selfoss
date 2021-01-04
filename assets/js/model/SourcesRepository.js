import { LoadingState } from '../requests/LoadingState';

export class SourcesChangeEvent extends Event {
    constructor(sources) {
        super('change');
        this.sources = sources;
    }
}

export class SourcesStateChangeEvent extends Event {
    constructor(state) {
        super('statechange');
        this.state = state;
    }
}

/**
 * Object storing list of sources and their information.
 */
export class SourcesRepository extends EventTarget {
    /**
     * @param {Object[]} sources
     * @param {LoadingState} state
     */
    constructor({
        sources = [],
        state = LoadingState.INITIAL
    }) {
        super();

        this.sources = sources;
        this.state = state;
    }

    update(sources) {
        const event = new SourcesChangeEvent(sources);

        let changed = false;

        if (!(this.sources.length === 0 && sources.length === 0)) {
            // Only empty arrays can be considered equal,
            changed = true;
            this.sources = sources;
        }

        if (changed) {
            this.dispatchEvent(event);
        }
    }

    setState(state) {
        const event = new SourcesStateChangeEvent(state);

        if (this.state !== state) {
            this.dispatchEvent(event);
        }
    }
}
