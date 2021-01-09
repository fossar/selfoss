import React from 'react';
import ReactDOM from 'react-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

function handleReloadAll({ setReloading }) {
    setReloading(true);
    selfoss.reloadAll().finally(() => {
        setReloading(false);
    });
}

function handleSettings() {
    // only loggedin users
    if (!selfoss.loggedin.value || !selfoss.db.online) {
        return;
    }

    // show sources
    selfoss.events.setHash('sources', false);

    if (selfoss.isSmartphone()) {
        $('#nav-mobile-settings').click();
    }
}

function handleLogIn() {
    selfoss.events.setHash('login', false);
}

function handleLogOut() {
    // only loggedin users
    if (!selfoss.loggedin.value || !selfoss.db.online) {
        return;
    }

    selfoss.db.clear();
    selfoss.logout();
}

export function NavToolBar() {
    const [reloading, setReloading] = React.useState(false);

    return (
        <React.Fragment>
            <button
                id="nav-refresh"
                title={selfoss.ui._('refreshbutton')}
                aria-label={selfoss.ui._('refreshbutton')}
                accessKey="r"
                onClick={() => handleReloadAll({ setReloading })}
            >
                <FontAwesomeIcon
                    icon={['fas', 'sync-alt']}
                    fixedWidth
                    spin={reloading}
                />
            </button>
            <button
                id="nav-settings"
                title={selfoss.ui._('settingsbutton')}
                aria-label={selfoss.ui._('settingsbutton')}
                accessKey="t"
                onClick={handleSettings}
            >
                <FontAwesomeIcon
                    icon={['fas', 'cloud-upload-alt']}
                    fixedWidth
                />
            </button>
            <button
                id="nav-logout"
                title={selfoss.ui._('logoutbutton')}
                aria-label={selfoss.ui._('logoutbutton')}
                accessKey="l"
                onClick={handleLogOut}
            >
                <FontAwesomeIcon icon={['fas', 'sign-out-alt']} fixedWidth />
            </button>
            <button
                id="nav-login"
                title={selfoss.ui._('loginbutton')}
                aria-label={selfoss.ui._('loginbutton')}
                accessKey="l"
                onClick={handleLogIn}
            >
                <FontAwesomeIcon icon={['fas', 'key']} fixedWidth />
            </button>
        </React.Fragment>
    );
}

export function anchor(element) {
    ReactDOM.render(<NavToolBar />, element);
}
