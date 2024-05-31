import React, { useCallback, useContext, useState } from 'react';
import classNames from 'classnames';
import { useLocation } from '../helpers/uri';
import selfoss from '../selfoss-base';
import { SpinnerBig } from './Spinner';
import { NavigateFunction, useNavigate } from 'react-router';
import { Configuration } from '../model/Configuration';
import { HttpError, LoginError } from '../errors';
import { LocalizationContext } from '../helpers/i18n';
import { ConfigurationContext } from '../model/Configuration';

function handleLogIn({
    event,
    configuration,
    navigate,
    setLoading,
    username,
    password,
    enableOffline,
    returnLocation,
}: {
    event: React.FormEvent;
    configuration: Configuration;
    navigate: NavigateFunction;
    setLoading: React.Dispatch<React.SetStateAction<boolean>>;
    username: string;
    password: string;
    enableOffline: boolean;
    returnLocation: string;
}) {
    event.preventDefault();

    setLoading(true);

    selfoss
        .login({ configuration, username, password, enableOffline })
        .then(() => {
            navigate(returnLocation);
        })
        .catch((err: Error) => {
            const message =
                err instanceof LoginError
                    ? selfoss.app._('login_invalid_credentials')
                    : selfoss.app._('login_error_generic', {
                          errorMessage:
                              err instanceof HttpError
                                  ? `HTTP ${err.response.status} ${err.message}`
                                  : err.message,
                      });
            navigate('/sign/in', {
                replace: true,
                state: {
                    error: message,
                },
            });
        })
        .finally(() => {
            setLoading(false);
        });
}

type LoginFormProps = {
    offlineEnabled: boolean;
};

export default function LoginForm(props: LoginFormProps) {
    const { offlineEnabled } = props;

    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const [enableOffline, setEnableOffline] = useState(offlineEnabled);

    const configuration = useContext(ConfigurationContext);
    const navigate = useNavigate();
    const location = useLocation();
    const error = location?.state?.error;
    const returnLocation = location?.state?.returnLocation ?? '/';

    const formOnSubmit = useCallback(
        (event: React.FormEvent) =>
            handleLogIn({
                event,
                configuration,
                navigate,
                setLoading,
                username,
                password,
                enableOffline,
                returnLocation,
            }),
        [
            configuration,
            navigate,
            username,
            password,
            enableOffline,
            returnLocation,
        ],
    );

    const usernameOnChange = useCallback(
        (event: React.ChangeEvent<HTMLInputElement>) =>
            setUsername(event.target.value),
        [],
    );

    const passwordOnChange = useCallback(
        (event: React.ChangeEvent<HTMLInputElement>) =>
            setPassword(event.target.value),
        [],
    );

    const offlineOnChange = useCallback(
        (event: React.ChangeEvent<HTMLInputElement>) =>
            setEnableOffline(event.target.checked),
        [setEnableOffline],
    );

    const _ = useContext(LocalizationContext);

    return (
        <>
            {loading ? <SpinnerBig label={_('login_in_progress')} /> : null}
            <form
                action=""
                className={classNames({ loading })}
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
        </>
    );
}
