import React from 'react';
import ReactDOM from 'react-dom';
import classNames from 'classnames';
import Spinner from './Spinner';

function handleLogIn({
    event,
    setLoading,
    setError,
    username,
    password,
    setPassword,
    offlineEnabled
}) {
    event.preventDefault();

    setLoading(true);

    selfoss.login({ username, password, offlineEnabled }).then(() => {
        setPassword('');
    }).catch((error) => {
        setError(error.message);
    }).finally(() => {
        setLoading(false);
    });
}

export function LoginForm({
    error,
    setError,
    offlineEnabled,
    setOfflineEnabled
}) {
    const [username, setUsername] = React.useState('');
    const [password, setPassword] = React.useState('');
    const [loading, setLoading] = React.useState(false);

    return (
        <React.Fragment>
            {loading ? <Spinner /> : null}
            <form
                action=""
                className={classNames({ loading: loading })}
                method="post"
                onSubmit={(event) =>
                    handleLogIn({
                        event,
                        setLoading,
                        setError,
                        username,
                        password,
                        setPassword,
                        offlineEnabled
                    })
                }
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
                            onChange={(event) =>
                                setUsername(event.target.value)
                            }
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
                            onChange={(event) =>
                                setPassword(event.target.value)
                            }
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
                                onChange={(event) =>
                                    setOfflineEnabled(event.target.checked)
                                }
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

export class StateHolder extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            error: '',
            offlineEnabled: selfoss.db.enableOffline
        };
    }

    setError(error) {
        this.setState({ error });
    }

    setOfflineEnabled(offlineEnabled) {
        this.setState({ offlineEnabled });
    }

    render() {
        return (
            <LoginForm
                error={this.state.error}
                setError={this.setError.bind(this)}
                offlineEnabled={this.state.offlineEnabled}
                setOfflineEnabled={this.setOfflineEnabled.bind(this)}
            />
        );
    }
}

export function anchor(element) {
    return ReactDOM.render(<StateHolder />, element);
}
