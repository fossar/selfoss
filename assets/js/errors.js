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

export class HttpError extends Error {
    constructor(message) {
        super(message);
        this.name = 'HttpError';
    }
}

export class LoginError extends Error {
    constructor(message) {
        super(message);
        this.name = 'LoginError';
    }
}
