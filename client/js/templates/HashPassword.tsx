import { TypedFormData } from '@k1eu/typed-formdata';
import React, {
    FormEvent,
    startTransition,
    useActionState,
    useCallback,
    useEffect,
} from 'react';
import { useNavigate } from 'react-router';
import { HttpError } from '../errors';
import { hashPassword } from '../requests/common';

type HashFormData = {
    password: string;
};

type State =
    | {
          hashedPassword?: string;
      }
    | {
          error: Error;
      };

type HashPasswordProps = {
    setTitle: (title: string | null) => void;
};

export default function HashPassword(
    props: HashPasswordProps,
): React.JSX.Element {
    const { setTitle } = props;

    const navigate = useNavigate();

    const [state, submitAction, isPending] = useActionState<
        State,
        TypedFormData<HashFormData>
    >(async (_previousState, formData) => {
        try {
            const password = formData.get('password').trim();
            const hashedPassword = await hashPassword(password);
            return { hashedPassword };
        } catch (error) {
            if (error instanceof HttpError && error.response.status === 403) {
                navigate('/sign/in', {
                    state: {
                        error: 'Generating a new password hash requires being logged in or not setting “password” in selfoss configuration.',
                        returnLocation: '/password',
                    },
                });

                return {};
            }

            return { error };
        }
    }, {});

    const submit = useCallback(
        (event: FormEvent<HTMLFormElement>) => {
            // Unlike `action` prop, `onSubmit` avoids clearing the form on submit.
            // https://github.com/facebook/react/issues/29034#issuecomment-2143595195
            event.preventDefault();
            const formData = new TypedFormData<HashFormData>(
                event.currentTarget,
            );
            startTransition(() => submitAction(formData));
        },
        [submitAction],
    );

    useEffect(() => {
        setTitle('selfoss password hash generator');

        return () => {
            setTitle(null);
        };
    }, [setTitle]);

    const message = isPending ? null : 'hashedPassword' in state ? (
        <p className="error">
            <label>
                Generated Password (insert this into config.ini):
                <input type="text" value={state.hashedPassword} readOnly />
            </label>
        </p>
    ) : 'error' in state ? (
        <p className="error">
            Unexpected happened.
            <details>
                <pre>${JSON.stringify(state.error)}</pre>
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
                        value={isPending ? 'Hashing password…' : 'Compute hash'}
                        accessKey="g"
                        disabled={isPending}
                    />
                </li>
            </ul>
        </form>
    );
}
