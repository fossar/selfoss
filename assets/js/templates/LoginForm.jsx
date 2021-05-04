import React from 'react';
import classNames from 'classnames';
import { SpinnerBig } from './Spinner';
import { useHistory } from 'react-router-dom';

function handleLogIn({
    event,
    history,
    setLoading,
    setError,
    username,
    password,
    offlineEnabled
}) {
    event.preventDefault();

    setLoading(true);

    selfoss.login({ username, password, offlineEnabled }).then(() => {
        history.push('/');
    }).catch((error) => {
        setError(error.message);
    }).finally(() => {
        setLoading(false);
    });
}

export default function LoginForm({
    error,
    setError,
    offlineEnabled,
    setOfflineEnabled
}) {
    const [username, setUsername] = React.useState('');
    const [password, setPassword] = React.useState('');
    const [loading, setLoading] = React.useState(false);

    const history = useHistory();

    const formOnSubmit = React.useCallback(
        (event) =>
            handleLogIn({
                event,
                history,
                setLoading,
                setError,
                username,
                password,
                offlineEnabled
            }),
        [history, setError, username, password, offlineEnabled]
    );

    const usernameOnChange = React.useCallback(
        (event) => setUsername(event.target.value),
        []
    );

    const passwordOnChange = React.useCallback(
        (event) => setPassword(event.target.value),
        []
    );

    const offlineOnChange = React.useCallback(
        (event) => setOfflineEnabled(event.target.checked),
        [setOfflineEnabled]
    );

    return (
        <React.Fragment>
            {loading ? <SpinnerBig /> : null}
            <form
                action=""
                className={classNames({ loading: loading })}
                method="post"
                onSubmit={formOnSubmit}
            >
                <ul id="login">
                    <li>
                        <h1>{selfoss.config.htmlTitle} login</h1>
                    </li>
                    <li>
                        <label htmlFor="username">
                            {selfoss.ui._('login_username')}
                        </label>{' '}
                        <input
                            type="text"
                            name="username"
                            id="username"
                            accessKey="u"
                            autoComplete="username"
                            onChange={usernameOnChange}
                            value={username}
                            required
                        />
                    </li>
                    <li>
                        <label htmlFor="password">
                            {selfoss.ui._('login_password')}
                        </label>{' '}
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
                            {selfoss.ui._('login_offline')}
                        </label>{' '}
                        <label>
                            <input
                                type="checkbox"
                                name="enableoffline"
                                id="enableoffline"
                                accessKey="o"
                                onChange={offlineOnChange}
                                checked={offlineEnabled}
                            />{' '}
                            <span className="badge-experimental">
                                {selfoss.ui._('experimental')}
                            </span>
                        </label>
                    </li>
                    <li className="error" aria-live="assertive">
                        {error}
                    </li>
                    <li className="button">
                        <label>{' '}</label>
                        <input
                            type="submit"
                            accessKey="l"
                            value={selfoss.ui._('login')}
                        />
                    </li>
                </ul>
            </form>
        </React.Fragment>
    );
}
