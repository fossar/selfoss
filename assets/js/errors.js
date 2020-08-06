export class OfflineStorageNotAvailableError extends Error {
    constructor(message = 'Offline storage is not available') {
        super(message);
        this.name = 'OfflineStorageNotAvailableError';
    }
}

export class TimeoutError extends Error {
    constructor(message) {
        super(message);
        this.name = 'TimeoutError';
    }
}
