import React, { useCallback, useContext, useState } from 'react';
import PropTypes from 'prop-types';
import { Link } from 'react-router';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';
import {
    useAllowedToUpdate,
    useAllowedToWrite,
    useLoggedIn,
} from '../helpers/authorizations';
import { ConfigurationContext } from '../helpers/configuration';
import { LocalizationContext } from '../helpers/i18n';
import { useForceReload } from '../helpers/uri';

function handleReloadAll({ reloadAll, setReloading, setNavExpanded }) {
    setReloading(true);
    reloadAll().finally(() => {
        setNavExpanded(false);
        setReloading(false);
    });
}

function handleLogOut({ setNavExpanded }) {
    // only loggedin users
    if (!selfoss.hasSession() || !selfoss.isOnline()) {
        return;
    }

    selfoss.db.clear();
    selfoss.logout();
    setNavExpanded(false);
}

export default function NavToolBar({ reloadAll, setNavExpanded }) {
    const [reloading, setReloading] = useState(false);
    const forceReload = useForceReload();

    const refreshOnClick = useCallback(
        () => handleReloadAll({ reloadAll, setReloading, setNavExpanded }),
        [reloadAll, setNavExpanded],
    );

    const settingsOnClick = useCallback(() => {
        setNavExpanded(false);
    }, [setNavExpanded]);

    const logoutOnClick = useCallback(
        () => handleLogOut({ setNavExpanded }),
        [setNavExpanded],
    );

    const isLoggedIn = useLoggedIn();
    const canRefreshAll = useAllowedToUpdate();
    const canVisitSettings = useAllowedToWrite();
    const canLogOut = isLoggedIn;
    const configuration = useContext(ConfigurationContext);
    const canLogIn = !isLoggedIn && configuration.authEnabled;

    const _ = useContext(LocalizationContext);

    return (
        <div className="nav-toolbar">
            {canRefreshAll && (
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
            )}
            {canVisitSettings && (
                <Link
                    id="nav-settings"
                    title={_('settingsbutton')}
                    aria-label={_('settingsbutton')}
                    accessKey="t"
                    to="/manage/sources"
                    onClick={settingsOnClick}
                    state={forceReload}
                >
                    <FontAwesomeIcon icon={icons.settings} fixedWidth />
                </Link>
            )}
            {canLogOut && (
                <button
                    id="nav-logout"
                    title={_('logoutbutton')}
                    aria-label={_('logoutbutton')}
                    accessKey="l"
                    onClick={logoutOnClick}
                >
                    <FontAwesomeIcon icon={icons.signOut} fixedWidth />
                </button>
            )}
            {canLogIn && (
                <Link
                    id="nav-login"
                    title={_('loginbutton')}
                    aria-label={_('loginbutton')}
                    accessKey="l"
                    to="/sign/in"
                >
                    <FontAwesomeIcon icon={icons.logIn} fixedWidth />
                </Link>
            )}
        </div>
    );
}

NavToolBar.propTypes = {
    reloadAll: PropTypes.func.isRequired,
    setNavExpanded: PropTypes.func.isRequired,
};
