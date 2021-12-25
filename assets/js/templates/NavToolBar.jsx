import React from 'react';
import PropTypes from 'prop-types';
import { useHistory } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';
import { LocalizationContext } from '../helpers/i18n';

function handleReloadAll({ setReloading, setNavExpanded }) {
    setReloading(true);
    selfoss.reloadAll().finally(() => {
        setNavExpanded(false);
        setReloading(false);
    });
}

function handleSettings({ history, setNavExpanded }) {
    // only loggedin users
    if (selfoss.config.authEnabled && (!selfoss.loggedin.value || !selfoss.db.online)) {
        return;
    }

    // show sources
    history.push('/manage/sources');

    setNavExpanded(false);
}

function handleLogIn({ history }) {
    history.push('/sign/in');
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

    const _ = React.useContext(LocalizationContext);

    return (
        <div className="nav-toolbar">
            <button
                id="nav-refresh"
                title={_('refreshbutton')}
                aria-label={_('refreshbutton')}
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
                title={_('settingsbutton')}
                aria-label={_('settingsbutton')}
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
                title={_('logoutbutton')}
                aria-label={_('logoutbutton')}
                accessKey="l"
                onClick={logoutOnClick}
            >
                <FontAwesomeIcon icon={icons.signOut} fixedWidth />
            </button>
            <button
                id="nav-login"
                title={_('loginbutton')}
                aria-label={_('loginbutton')}
                accessKey="l"
                onClick={loginOnClick}
            >
                <FontAwesomeIcon icon={icons.logIn} fixedWidth />
            </button>
        </div>
    );
}

NavToolBar.propTypes = {
    setNavExpanded: PropTypes.func.isRequired,
};
