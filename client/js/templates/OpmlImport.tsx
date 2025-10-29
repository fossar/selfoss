import React, { useCallback, useEffect, useRef, useState } from 'react';
import { useOnline } from 'rooks';
import { Link, useNavigate } from 'react-router';
import { LoadingState } from '../requests/LoadingState';
import { HttpError, UnexpectedStateError } from '../errors';
import { importOpml, OpmlImportData } from '../requests/common';

type OpmlImportProps = {
    setTitle: (title: string | null) => void;
};

export default function OpmlImport(props: OpmlImportProps): React.JSX.Element {
    const { setTitle } = props;

    const [state, setState] = useState<LoadingState>(LoadingState.INITIAL);
    const [message, setMessage] = useState<React.JSX.Element | null>(null);
    const fileEntry = useRef<HTMLInputElement>(null);

    const navigate = useNavigate();

    const submit = useCallback(
        (event: React.FormEvent<HTMLFormElement>) => {
            event.preventDefault();

            setState(LoadingState.LOADING);
            const file = fileEntry.current.files[0];
            importOpml(file)
                .then(
                    ({
                        response,
                        data,
                    }: {
                        response: Response;
                        data: OpmlImportData;
                    }) => {
                        const { messages } = data;

                        if (response.status === 200) {
                            setState(LoadingState.SUCCESS);
                            setMessage(
                                <p className="msg success">
                                    <ul>
                                        {messages.map((msg, i) => (
                                            <li key={i}>{msg}</li>
                                        ))}
                                    </ul>
                                    You might want to{' '}
                                    <a href="update">update now</a> or{' '}
                                    <Link to="/">view your feeds</Link>.
                                </p>,
                            );
                        } else if (response.status === 202) {
                            setState(LoadingState.FAILURE);
                            setMessage(
                                <p className="msg error">
                                    The following feeds could not be imported:
                                    <br />
                                    <ul>
                                        {messages.map((msg, i) => (
                                            <li key={i}>{msg}</li>
                                        ))}
                                    </ul>
                                </p>,
                            );
                        } else if (response.status === 400) {
                            setState(LoadingState.FAILURE);
                            setMessage(
                                <p className="msg error">
                                    There was a problem importing your OPML
                                    file:
                                    <br />
                                    <ul>
                                        {messages.map((msg, i) => (
                                            <li key={i}>{msg}</li>
                                        ))}
                                    </ul>
                                </p>,
                            );
                        } else {
                            throw new UnexpectedStateError(
                                `OPML import handler received status ${response.status}. This should not happen.`,
                            );
                        }
                    },
                )
                .catch((error) => {
                    if (
                        error instanceof HttpError &&
                        error.response.status === 403
                    ) {
                        navigate('/sign/in', {
                            state: {
                                error: 'Importing OPML file requires being logged in or not setting “password” in selfoss configuration.',
                                returnLocation: '/opml',
                            },
                        });
                        return;
                    } else {
                        setState(LoadingState.FAILURE);
                        setMessage(
                            <div className="msg error">
                                Unexpected error occurred.
                                <details>
                                    <pre>{error.message}</pre>
                                </details>
                            </div>,
                        );
                    }
                });
        },
        [navigate],
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
                    {message}
                </li>
                <li className="center">
                    <label htmlFor="opml">Opml.xml:</label>
                    <input
                        type="file"
                        accessKey="f"
                        ref={fileEntry}
                        id="opml"
                        required={true}
                    />
                </li>
                <li className="button">
                    <label>&nbsp;</label>
                    <input
                        type="submit"
                        value={
                            state === LoadingState.LOADING
                                ? 'Importing…'
                                : 'Deliver my OPML!'
                        }
                        accessKey="d"
                        disabled={!isOnline || state === LoadingState.LOADING}
                    />
                </li>
            </ul>
        </form>
    );
}
