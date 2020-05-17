/**
 * Set of named counters to be incremented/decremented individually.
 */
export default class Counters {
    /**
     * @type {Object.<string, number>}
     */
    #values = {};

    /**
     * Set the value of counter.
     *
     * @param {string} key
     * @param {number} value
     */
    #set(key, value) {
        this.#values[key] = value;
    }

    /**
     * Get the value of counter called `key`.
     *
     * @param {string} key
     * @return {number} value
     */
    get(key) {
        if (!(key in this.#values)) {
            return 0;
        }

        return this.#values[key];
    }

    /**
     * Increment the value of counter called `key` and return it.
     *
     * @param {string} key
     * @return {number} new value
     */
    decrement(key) {
        let newValue = this.get(key) - 1;
        this.#set(key, newValue);

        return newValue;
    }

    /**
     * Increment the value of counter called `key` and return it.
     *
     * @param {string} key
     * @return {number} new value
     */
    increment(key) {
        let newValue = this.get(key) + 1;
        this.#set(key, newValue);

        return newValue;
    }

    /**
     * Return all the values as list of objects.
     *
     * @param {string} keyName – key of the keys in the result objects
     * @param {string} valueName – key of the values in the result objects
     * @return {Array.<{{ [keyName]: string, [valueName]: number }}>} list of counters
     */
    entries(keyName = 'key', valueName = 'value') {
        return Object.entries(this.#values).map(([key, value]) => ({
            [keyName]: key,
            [valueName]: value
        }));
    }
}
