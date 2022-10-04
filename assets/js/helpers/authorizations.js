import { useListenableValue } from './hooks';


export function useLoggedIn() {
    return useListenableValue(selfoss.loggedin);
}

export function useAllowedToRead() {
    return selfoss.isAllowedToRead();
}

export function useAllowedToUpdate() {
    return selfoss.isAllowedToUpdate();
}

export function useAllowedToWrite() {
    return selfoss.isAllowedToWrite();
}
