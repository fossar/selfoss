export class SourcesChangeEvent extends Event {
    constructor(sources) {
        super('change');
        this.sources = sources;
    }
}

/**
 * Object storing list of sources and their information.
 */
export class SourcesRepository extends EventTarget {
    /**
     * @param {Object[]} sources
     */
    constructor({sources = []}) {
        super();

        this.sources = sources;
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
}
