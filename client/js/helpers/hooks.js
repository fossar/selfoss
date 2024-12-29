import { useEffect, useState } from 'react';
import { useLocation } from 'react-router';
import { useMediaMatch } from 'rooks';

/**
 * Changes its return value whenever the value of forceReload field
 * in the location state increases.
 */
export function useShouldReload() {
    const location = useLocation();
    const forceReload = location?.state?.forceReload;
    const [oldForceReload, setOldForceReload] = useState(forceReload);

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

export function useIsSmartphone() {
    return useMediaMatch('(max-width: 641px)');
}

/**
 * @param {ValueListenable}
 */
export function useListenableValue(valueListenable) {
    const [value, setValue] = useState(valueListenable.value);

    useEffect(() => {
        const listener = (event) => {
            setValue(event.value);
        };

        // It might happen that values change between creating the component and setting up the event handlers.
        listener({ value: valueListenable.value });

        valueListenable.addEventListener('change', listener);

        return () => {
            valueListenable.removeEventListener('change', listener);
        };
    }, [valueListenable]);

    return value;
}
