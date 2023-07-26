import { useListenableValue } from './hooks';
import { useMemo } from 'react';

export function useLoggedIn() {
    return useListenableValue(selfoss.loggedin);
}

export function useAllowedToRead() {
    const loggedIn = useLoggedIn();

    return useMemo(() => selfoss.isAllowedToRead(), [loggedIn]);
}

export function useAllowedToUpdate() {
    const loggedIn = useLoggedIn();

    return useMemo(() => selfoss.isAllowedToUpdate(), [loggedIn]);
}

export function useAllowedToWrite() {
    const loggedIn = useLoggedIn();

    return useMemo(() => selfoss.isAllowedToWrite(), [loggedIn]);
}
