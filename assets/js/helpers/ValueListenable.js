export class ValueChangeEvent extends Event {
    constructor(value) {
        super('change');
        this.value = value;
    }
}

/**
 * Object storing a value and allowing subscribing to its changes.
 */
export class ValueListenable extends EventTarget {
    constructor(value) {
        super();

        this.value = value;
    }

    update(value) {
        if (this.value !== value) {
            this.value = value;

            const event = new ValueChangeEvent(value);
            this.dispatchEvent(event);
        }
    }
}
