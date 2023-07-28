import { useEffect, useState } from 'react';
import { useLocation } from 'react-router';
import { useMediaMatch } from 'rooks';
import { ValueChangeEvent, ValueListenable } from './ValueListenable';

/**
 * Changes its return value whenever the value of forceReload field
 * in the location state increases.
 */
export function useShouldReload(): number {
    const location = useLocation();
    const forceReload = location?.state?.forceReload;
    const [oldForceReload, setOldForceReload] = useState<number | undefined>(
        forceReload,
    );

    if (oldForceReload !== forceReload) {
        setOldForceReload(forceReload);
    }

    // The location state is not persisted during navigation
    // so forceReload would change to undefined on successive navigation,
    // triggering an unwanter reload.
    // We use a separate counter to prevent that.
    const [reloadCounter, setReloadCounter] = useState(0);
    if (forceReload !== undefined && forceReload !== oldForceReload) {
        const newReloadCounter = reloadCounter + 1;

        setReloadCounter(newReloadCounter);
        return newReloadCounter;
    }

    return reloadCounter;
}

export function useIsSmartphone(): boolean {
    return useMediaMatch('(max-width: 641px)');
}

export function useListenableValue<T>(valueListenable: ValueListenable<T>): T {
    const [value, setValue] = useState<T>(valueListenable.value);

    useEffect(() => {
        const listener = (event: ValueChangeEvent<T>) => {
            setValue(event.value);
        };

        // It might happen that values change between creating the component and setting up the event handlers.
        listener(new ValueChangeEvent(valueListenable.value));

        valueListenable.addEventListener('change', listener);

        return () => {
            valueListenable.removeEventListener('change', listener);
        };
    }, [valueListenable]);

    return value;
}
