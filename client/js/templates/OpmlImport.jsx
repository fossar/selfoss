import React, { useCallback, useEffect, useRef, useState } from 'react';
import PropTypes from 'prop-types';
import { useOnline } from 'rooks';
import { Link, useHistory } from 'react-router-dom';
import { LoadingState } from '../requests/LoadingState';
import { HttpError, UnexpectedStateError } from '../errors';
import { importOpml } from '../requests/common';

export default function OpmlImport({ setTitle }) {
    const [state, setState] = useState(LoadingState.INITIAL);
    const [message, setMessage] = useState(null);
    const fileEntry = useRef();

    const history = useHistory();

    const submit = useCallback(
        (event) => {
            event.preventDefault();

            setState(LoadingState.LOADING);
            const file = fileEntry.current.files[0];
            importOpml(file)
                .then(({ response, data }) => {
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
                        setState(LoadingState.ERROR);
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
                        setState(LoadingState.ERROR);
                        setMessage(
                            <p className="msg error">
                                There was a problem importing your OPML file:
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
                })
                .catch((error) => {
                    if (
                        error instanceof HttpError &&
                        error.response.status === 403
                    ) {
                        history.push('/sign/in', {
                            error: 'Importing OPML file requires being logged in or not setting “password” in selfoss configuration.',
                            returnLocation: '/opml',
                        });
                        return;
                    } else {
                        setState(LoadingState.ERROR);
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
        [history],
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

OpmlImport.propTypes = {
    setTitle: PropTypes.func.isRequired,
};
