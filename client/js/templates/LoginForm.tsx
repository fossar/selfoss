import React, {
    startTransition,
    useCallback,
    useContext,
    useActionState,
} from 'react';
import classNames from 'classnames';
import { useLocation } from '../helpers/uri';
import selfoss from '../selfoss-base';
import { SpinnerBig } from './Spinner';
import { NavigateFunction, useNavigate } from 'react-router';
import { Configuration } from '../model/Configuration';
import { HttpError, LoginError } from '../errors';
import { LocalizationContext } from '../helpers/i18n';
import { ConfigurationContext } from '../model/Configuration';

async function handleLogIn({
    configuration,
    navigate,
    username,
    password,
    enableOffline,
    returnLocation,
}: {
    configuration: Configuration;
    navigate: NavigateFunction;
    username: string;
    password: string;
    enableOffline: boolean;
    returnLocation: string;
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

type LoginFormProps = {
    offlineEnabled: boolean;
};

export default function LoginForm(props: LoginFormProps) {
    const { offlineEnabled } = props;

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

    const formOnSubmit = useCallback(
        (event: React.FormEvent<HTMLFormElement>) => {
            // Unlike `action` prop, `onSubmit` avoids clearing the form on submit.
            // https://github.com/facebook/react/issues/29034#issuecomment-2143595195
            event.preventDefault();
            const formData = new FormData(event.target as HTMLFormElement);
            startTransition(() => submitAction(formData));
        },
        [],
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
        </>
    );
}
