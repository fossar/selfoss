import { TypedFormData } from '@k1eu/typed-formdata';
import React, {
    startTransition,
    useActionState,
    useCallback,
    useEffect,
} from 'react';
import { useOnline } from 'rooks';
import { Link, useNavigate } from 'react-router';
import { HttpError, UnexpectedStateError } from '../errors';
import { importOpml } from '../requests/common';

type OpmlImportFormData = {
    opml: File;
};

type State = React.JSX.Element | null;

type OpmlImportProps = {
    setTitle: (title: string | null) => void;
};

export default function OpmlImport(props: OpmlImportProps): React.JSX.Element {
    const { setTitle } = props;

    const navigate = useNavigate();

    const [message, submitAction, isPending] = useActionState<
        State,
        TypedFormData<OpmlImportFormData>
    >(async (_previousState, formData) => {
        const file = formData.get('opml');
        try {
            const { response, data } = await importOpml(file);
            const { messages } = data;

            if (response.status === 200) {
                return (
                    <p className="msg success">
                        <ul>
                            {messages.map((msg, i) => (
                                <li key={i}>{msg}</li>
                            ))}
                        </ul>
                        You might want to <a href="update">update now</a> or{' '}
                        <Link to="/">view your feeds</Link>.
                    </p>
                );
            } else if (response.status === 202) {
                return (
                    <p className="msg error">
                        The following feeds could not be imported:
                        <br />
                        <ul>
                            {messages.map((msg, i) => (
                                <li key={i}>{msg}</li>
                            ))}
                        </ul>
                    </p>
                );
            } else if (response.status === 400) {
                return (
                    <p className="msg error">
                        There was a problem importing your OPML file:
                        <br />
                        <ul>
                            {messages.map((msg, i) => (
                                <li key={i}>{msg}</li>
                            ))}
                        </ul>
                    </p>
                );
            } else {
                throw new UnexpectedStateError(
                    `OPML import handler received status ${response.status}. This should not happen.`,
                );
            }
        } catch (error) {
            if (error instanceof HttpError && error.response.status === 403) {
                navigate('/sign/in', {
                    state: {
                        error: 'Importing OPML file requires being logged in or not setting “password” in selfoss configuration.',
                        returnLocation: '/opml',
                    },
                });
                return null;
            } else {
                return (
                    <div className="msg error">
                        Unexpected error occurred.
                        <details>
                            <pre>{error.message}</pre>
                        </details>
                    </div>
                );
            }
        }
    }, null);

    const submit = useCallback(
        (event: React.FormEvent<HTMLFormElement>) => {
            // We cannot use `action` prop with `enctype`.
            event.preventDefault();
            const formData = new TypedFormData<OpmlImportFormData>(
                event.currentTarget,
            );
            startTransition(() => submitAction(formData));
        },
        [submitAction],
    );

    useEffect(() => {
        setTitle('selfoss OPML importer');

        return () => {
            setTitle(null);
        };
    }, [setTitle]);

    const isOnline = useOnline();

    return (
        <form
            action=""
            method="post"
            encType="multipart/form-data"
            onSubmit={submit}
        >
            <ul id="opml">
                <li>
                    <h1>Upload an OPML File</h1>
                </li>
                <li>
                    <p>
                        Coming from a different RSS Reader? Export your
                        subscriptions to an OPML file and upload it here. OPML
                        export is usually located somewhere in settings, consult
                        the reader’s manual.
                    </p>
                </li>
                <li className="message-container" aria-live="assertive">
                    {!isOnline && (
                        <li className="msg error">
                            OPML import requires an internet connection. Please
                            reconnect before proceeding.
                        </li>
                    )}
                    {!isPending && message}
                </li>
                <li className="center">
                    <label htmlFor="opml">Opml.xml:</label>
                    <input
                        type="file"
                        name="opml"
                        accessKey="f"
                        id="opml"
                        required={true}
                    />
                </li>
                <li className="button">
                    <label>&nbsp;</label>
                    <input
                        type="submit"
                        value={isPending ? 'Importing…' : 'Deliver my OPML!'}
                        accessKey="d"
                        disabled={!isOnline || isPending}
                    />
                </li>
            </ul>
        </form>
    );
}
