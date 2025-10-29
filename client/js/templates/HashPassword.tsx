import React, { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { useInput } from 'rooks';
import { LoadingState } from '../requests/LoadingState';
import { HttpError } from '../errors';
import { hashPassword } from '../requests/common';

type HashPasswordProps = {
    setTitle: (title: string | null) => void;
};

export default function HashPassword(props: HashPasswordProps) {
    const { setTitle } = props;

    const [state, setState] = useState(LoadingState.INITIAL);
    const [hashedPassword, setHashedPassword] = useState('');
    const [error, setError] = useState(null);
    const passwordEntry = useInput('');

    const navigate = useNavigate();

    const submit = useCallback(
        (event) => {
            event.preventDefault();

            setState(LoadingState.LOADING);
            hashPassword(passwordEntry.value.trim())
                .then((hashedPassword) => {
                    setHashedPassword(hashedPassword);
                    setState(LoadingState.SUCCESS);
                })
                .catch((error) => {
                    if (
                        error instanceof HttpError &&
                        error.response.status === 403
                    ) {
                        navigate('/sign/in', {
                            state: {
                                error: 'Generating a new password hash requires being logged in or not setting “password” in selfoss configuration.',
                                returnLocation: '/password',
                            },
                        });
                        return;
                    }
                    setError(error);
                    setState(LoadingState.FAILURE);
                });
        },
        [navigate, passwordEntry.value],
    );

    useEffect(() => {
        setTitle('selfoss password hash generator');

        return () => {
            setTitle(null);
        };
    }, [setTitle]);

    const message =
        state === LoadingState.SUCCESS ? (
            <p className="error">
                <label>
                    Generated Password (insert this into config.ini):
                    <input type="text" value={hashedPassword} readOnly />
                </label>
            </p>
        ) : state === LoadingState.FAILURE ? (
            <p className="error">
                Unexpected happened.
                <details>
                    <pre>${JSON.stringify(error)}</pre>
                </details>
            </p>
        ) : null;

    return (
        <form action="" method="post" onSubmit={submit}>
            <ul id="login">
                <li>
                    <h1>hash generator</h1>
                </li>
                <li>
                    <label htmlFor="password">Password:</label>
                    <input
                        type="password"
                        name="password"
                        autoComplete="new-password"
                        accessKey="p"
                        {...passwordEntry}
                    />
                </li>
                <li className="message-container" aria-live="assertive">
                    {message}
                </li>
                <li>
                    <label>&nbsp;</label>
                    <input
                        className="button"
                        type="submit"
                        value={
                            state === LoadingState.LOADING
                                ? 'Hashing password…'
                                : 'Compute hash'
                        }
                        accessKey="g"
                        disabled={state === LoadingState.LOADING}
                    />
                </li>
            </ul>
        </form>
    );
}
