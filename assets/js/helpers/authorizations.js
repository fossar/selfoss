import { useEffect, useState } from 'react';


export function useLoggedIn() {
    const [value, setValue] = useState(selfoss.loggedin.value);

    useEffect(() => {
        function loggedinChanged(event) {
            setValue(event.value);
        }

        selfoss.loggedin.addEventListener('change', loggedinChanged);

        return () => {
            selfoss.loggedin.removeEventListener('change', loggedinChanged);
        };
    }, []);

    return value;
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
