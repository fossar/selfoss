import React, {
    startTransition,
    useCallback,
    useContext,
    useActionState,
} from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { SpinnerBig } from './Spinner';
import { useLocation, useNavigate } from 'react-router';
import { HttpError, LoginError } from '../errors';
import { ConfigurationContext } from '../helpers/configuration';
import { LocalizationContext } from '../helpers/i18n';

async function handleLogIn({
    configuration,
    navigate,
    username,
    password,
    enableOffline,
    returnLocation,
}) {
    try {
        await selfoss.login({
            configuration,
            username,
            password,
            enableOffline,
        });
        navigate(returnLocation);
    } catch (err) {
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
    }
}

export default function LoginForm({ offlineEnabled }) {
    const configuration = useContext(ConfigurationContext);
    const navigate = useNavigate();
    const location = useLocation();
    const error = location?.state?.error;
    const returnLocation = location?.state?.returnLocation ?? '/';

    const [, submitAction, loading] = useActionState(
        async (_previousState, formData) => {
            const username = formData.get('username');
            const password = formData.get('password');
            const enableOffline = formData.get('enableoffline');
            await handleLogIn({
                configuration,
                navigate,
                username,
                password,
                enableOffline,
                returnLocation,
            });
            return null;
        },
        null,
    );

    const formOnSubmit = useCallback((event) => {
        // Unlike `action` prop, `onSubmit` avoids clearing the form on submit.
        // https://github.com/facebook/react/issues/29034#issuecomment-2143595195
        event.preventDefault();
        const formData = new FormData(event.target);
        startTransition(() => submitAction(formData));
    }, []);

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
                                defaultChecked={offlineEnabled}
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
