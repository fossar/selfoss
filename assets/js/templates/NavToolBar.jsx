import React from 'react';
import { useHistory } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';

function handleReloadAll({ setReloading, setNavExpanded }) {
    setReloading(true);
    selfoss.reloadAll().finally(() => {
        setNavExpanded(false);
        setReloading(false);
    });
}

function handleSettings({ history, setNavExpanded }) {
    // only loggedin users
    if (!selfoss.loggedin.value || !selfoss.db.online) {
        return;
    }

    // show sources
    history.push('/sources');

    setNavExpanded(false);
}

function handleLogIn({ history }) {
    history.push('/login');
}

function handleLogOut({ setNavExpanded }) {
    // only loggedin users
    if (!selfoss.loggedin.value || !selfoss.db.online) {
        return;
    }

    selfoss.db.clear();
    selfoss.logout();
    setNavExpanded(false);
}

export default function NavToolBar({ setNavExpanded }) {
    const [reloading, setReloading] = React.useState(false);

    const history = useHistory();

    const refreshOnClick = React.useCallback(
        () => handleReloadAll({ setReloading, setNavExpanded }),
        [setNavExpanded]
    );

    const settingsOnClick = React.useCallback(
        () => handleSettings({ history, setNavExpanded }),
        [history, setNavExpanded]
    );

    const logoutOnClick = React.useCallback(
        () => handleLogOut({ setNavExpanded }),
        [setNavExpanded]
    );

    const loginOnClick = React.useCallback(
        () => handleLogIn({ history }),
        [history]
    );

    return (
        <div className="nav-toolbar">
            <button
                id="nav-refresh"
                title={selfoss.ui._('refreshbutton')}
                aria-label={selfoss.ui._('refreshbutton')}
                accessKey="r"
                onClick={refreshOnClick}
            >
                <FontAwesomeIcon
                    icon={icons.reload}
                    fixedWidth
                    spin={reloading}
                />
            </button>
            <button
                id="nav-settings"
                title={selfoss.ui._('settingsbutton')}
                aria-label={selfoss.ui._('settingsbutton')}
                accessKey="t"
                onClick={settingsOnClick}
            >
                <FontAwesomeIcon
                    icon={icons.settings}
                    fixedWidth
                />
            </button>
            <button
                id="nav-logout"
                title={selfoss.ui._('logoutbutton')}
                aria-label={selfoss.ui._('logoutbutton')}
                accessKey="l"
                onClick={logoutOnClick}
            >
                <FontAwesomeIcon icon={icons.signOut} fixedWidth />
            </button>
            <button
                id="nav-login"
                title={selfoss.ui._('loginbutton')}
                aria-label={selfoss.ui._('loginbutton')}
                accessKey="l"
                onClick={loginOnClick}
            >
                <FontAwesomeIcon icon={icons.logIn} fixedWidth />
            </button>
        </div>
    );
}
