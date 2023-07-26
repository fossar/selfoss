import React, { useCallback, useContext, useState } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { SpinnerBig } from './Spinner';
import { useHistory, useLocation } from 'react-router-dom';
import { HttpError, LoginError } from '../errors';
import { ConfigurationContext } from '../helpers/configuration';
import { LocalizationContext } from '../helpers/i18n';

function handleLogIn({
    event,
    configuration,
    history,
    setLoading,
    username,
    password,
    enableOffline,
    returnLocation,
}) {
    event.preventDefault();

    setLoading(true);

    selfoss
        .login({ configuration, username, password, enableOffline })
        .then(() => {
            history.push(returnLocation);
        })
        .catch((err) => {
            const message =
                err instanceof LoginError
                    ? selfoss.app._('login_invalid_credentials')
                    : selfoss.app._('login_error_generic', {
                          errorMessage:
                              err instanceof HttpError
                                  ? `HTTP ${err.response.status} ${err.message}`
                                  : err.message,
                      });
            history.replace('/sign/in', {
                error: message,
            });
        })
        .finally(() => {
            setLoading(false);
        });
}

export default function LoginForm({ offlineEnabled }) {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const [enableOffline, setEnableOffline] = useState(offlineEnabled);

    const configuration = useContext(ConfigurationContext);
    const history = useHistory();
    const location = useLocation();
    const error = location?.state?.error;
    const returnLocation = location?.state?.returnLocation ?? '/';

    const formOnSubmit = useCallback(
        (event) =>
            handleLogIn({
                event,
                configuration,
                history,
                setLoading,
                username,
                password,
                enableOffline,
                returnLocation,
            }),
        [
            configuration,
            history,
            username,
            password,
            enableOffline,
            returnLocation,
        ],
    );

    const usernameOnChange = useCallback(
        (event) => setUsername(event.target.value),
        [],
    );

    const passwordOnChange = useCallback(
        (event) => setPassword(event.target.value),
        [],
    );

    const offlineOnChange = useCallback(
        (event) => setEnableOffline(event.target.checked),
        [setEnableOffline],
    );

    const _ = useContext(LocalizationContext);

    return (
        <React.Fragment>
            {loading ? <SpinnerBig label={_('login_in_progress')} /> : null}
            <form
                action=""
                className={classNames({ loading: loading })}
                method="post"
                onSubmit={formOnSubmit}
            >
                <ul id="login">
                    <li>
                        <h1>{configuration.htmlTitle} login</h1>
                    </li>
                    <li>
                        <label htmlFor="username">{_('login_username')}</label>{' '}
                        <input
                            type="text"
                            name="username"
                            id="username"
                            accessKey="u"
                            autoComplete="username"
                            onChange={usernameOnChange}
                            value={username}
                            autoFocus
                            required
                        />
                    </li>
                    <li>
                        <label htmlFor="password">{_('login_password')}</label>{' '}
                        <input
                            type="password"
                            name="password"
                            id="password"
                            accessKey="p"
                            autoComplete="current-password"
                            onChange={passwordOnChange}
                            value={password}
                        />
                    </li>
                    <li>
                        <label htmlFor="enableoffline">
                            {_('login_offline')}
                        </label>{' '}
                        <label>
                            <input
                                type="checkbox"
                                name="enableoffline"
                                id="enableoffline"
                                accessKey="o"
                                onChange={offlineOnChange}
                                checked={enableOffline}
                            />{' '}
                            <span className="badge-experimental">
                                {_('experimental')}
                            </span>
                        </label>
                    </li>
                    <li className="error" aria-live="assertive">
                        {error}
                    </li>
                    <li className="button">
                        <label>{' '}</label>
                        <input type="submit" accessKey="l" value={_('login')} />
                    </li>
                </ul>
            </form>
        </React.Fragment>
    );
}

LoginForm.propTypes = {
    offlineEnabled: PropTypes.bool.isRequired,
};
