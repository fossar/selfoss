export class ValueChangeEvent<T> extends Event {
    public value: T;

    constructor(value: T) {
        super('change');
        this.value = value;
    }
}

/**
 * Object storing a value and allowing subscribing to its changes.
 */
export class ValueListenable<T> extends EventTarget {
    public value: T;

    constructor(value: T) {
        super();

        this.value = value;
    }

    update(value: T): void {
        if (this.value !== value) {
            this.value = value;

            const event = new ValueChangeEvent(value);
            this.dispatchEvent(event);
        }
    }
}
