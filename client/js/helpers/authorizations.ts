import { useListenableValue } from './hooks';
import { useMemo } from 'react';

export function useLoggedIn(): boolean {
    return useListenableValue(selfoss.loggedin);
}

export function useAllowedToRead(): boolean {
    const loggedIn = useLoggedIn();

    return useMemo(() => selfoss.isAllowedToRead(), [loggedIn]);
}

export function useAllowedToUpdate(): boolean {
    const loggedIn = useLoggedIn();

    return useMemo(() => selfoss.isAllowedToUpdate(), [loggedIn]);
}

export function useAllowedToWrite(): boolean {
    const loggedIn = useLoggedIn();

    return useMemo(() => selfoss.isAllowedToWrite(), [loggedIn]);
}
