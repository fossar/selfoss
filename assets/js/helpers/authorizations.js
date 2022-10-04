import { useListenableValue } from './hooks';


export function useLoggedIn() {
    return useListenableValue(selfoss.loggedin);
}

export function useAllowedToRead() {
    useLoggedIn();
    return selfoss.isAllowedToRead();
}

export function useAllowedToUpdate() {
    useLoggedIn();
    return selfoss.isAllowedToUpdate();
}

export function useAllowedToWrite() {
    useLoggedIn();
    return selfoss.isAllowedToWrite();
}
