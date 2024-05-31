import React, { useCallback, useContext, useEffect, useState } from 'react';
import { useMemo } from 'react';
import { Prompt } from 'react-router';
import { Link, useHistory, useLocation, useRouteMatch } from 'react-router-dom';
import Source from './Source';
import { SpinnerBig } from './Spinner';
import { LoadingState } from '../requests/LoadingState';
import * as sourceRequests from '../requests/sources';
import { getAllSources } from '../requests/sources';
import { useShouldReload } from '../helpers/hooks';
import { LocalizationContext } from '../helpers/i18n';
import { HttpError } from '../errors';

function rand() {
    // https://www.php.net/manual/en/function.mt-getrandmax.php#117620
    return Math.floor(Math.random() * 2147483647);
}

function handleAddSource({
    event = null,
    setSources,
    setSpouts,
    extraInitialData = {},
}) {
    if (event) {
        event.preventDefault();
    }

    // Add new empty source.
    setSources((sources) => [
        { id: 'new-' + rand(), ...extraInitialData },
        ...sources,
    ]);

    // Refresh the spout datea
    sourceRequests
        .getSpouts()
        .then(({ spouts }) => {
            // Update spout data.
            setSpouts(spouts);
        })
        .catch(() => {
            console.error(
                'Unable to update spouts, falling back to previously fetched list.',
            );
        });
}

// load sources
function loadSources({
    abortController,
    location,
    setSpouts,
    setSources,
    setLoadingState,
}) {
    if (abortController.signal.aborted) {
        return Promise.resolve();
    }

    setLoadingState(LoadingState.LOADING);

    return getAllSources(abortController)
        .then(({ sources, spouts }) => {
            if (abortController.signal.aborted) {
                return;
            }

            setSpouts(spouts);
            setSources(sources);
            setLoadingState(LoadingState.SUCCESS);

            if (location.hash.startsWith('#source-')) {
                const source = document.querySelector(
                    `.source[data-id="${location.hash.replace(/^#source-/, '')}"]`,
                );

                if (!source) {
                    return;
                }

                // needs to be delayed for some reason
                requestAnimationFrame(() => {
                    source.scrollIntoView();
                });
            }
        })
        .catch((error) => {
            if (error.name === 'AbortError' || abortController.signal.aborted) {
                return;
            }

            selfoss.handleAjaxError(error, false).catch((error) => {
                if (
                    error instanceof HttpError &&
                    error.response.status === 403
                ) {
                    selfoss.history.push('/sign/in', {
                        error: selfoss.app._('error_session_expired'),
                    });
                    return;
                }

                selfoss.app.showError(
                    selfoss.app._('error_loading') + ' ' + error.message,
                );
            });

            setLoadingState(LoadingState.FAILURE);
        });
}

export default function SourcesPage() {
    const [spouts, setSpouts] = useState([]);
    const [sources, setSources] = useState([]);

    const [loadingState, setLoadingState] = useState(LoadingState.INITIAL);

    const forceReload = useShouldReload();

    const history = useHistory();
    const location = useLocation();
    const isAdding = useRouteMatch('/manage/sources/add');

    useEffect(() => {
        const abortController = new AbortController();

        loadSources({
            abortController,
            location,
            setSpouts,
            setSources,
            setLoadingState,
        }).then(() => {
            if (isAdding) {
                const params = new URLSearchParams(location.search);
                handleAddSource({
                    setSources,
                    setSpouts,
                    extraInitialData: {
                        spout: 'spouts\\rss\\feed',
                        params: {
                            url: params.get('url') ?? '',
                        },
                    },
                });

                // Clear the value from the state so it does not bug us forever.
                history.replace('/manage/sources');
            }
        });

        return () => {
            abortController.abort();
        };
    }, [
        forceReload,
        // location.search and history are intentionally omitted
        // to prevent reloading when the presets are cleaned from the URL.
    ]);

    const addOnClick = useCallback(
        (event) => handleAddSource({ event, setSources, setSpouts }),
        [],
    );

    const _ = useContext(LocalizationContext);

    const [dirtySources, setDirtySources] = useState({});
    const isDirty = useMemo(
        () => Object.values(dirtySources).includes(true),
        [dirtySources],
    );

    if (loadingState === LoadingState.LOADING) {
        return <SpinnerBig label={_('sources_loading')} />;
    }

    if (loadingState !== LoadingState.SUCCESS) {
        return null;
    }

    return (
        <React.Fragment>
            <Prompt
                when={isDirty}
                message={_('sources_leaving_unsaved_prompt')}
            />

            <button className="source-add" onClick={addOnClick}>
                {_('source_add')}
            </button>
            <a className="source-export" href="opmlexport">
                {_('source_export')}
            </a>
            <Link className="source-opml" to="/opml">
                {_('source_opml')}
            </Link>
            {sources ? (
                <ul>
                    {sources.map((source) => (
                        <Source
                            key={source.id}
                            dirty={dirtySources[source.id] ?? false}
                            {...{
                                source,
                                setSources,
                                spouts,
                                setSpouts,
                                setDirtySources,
                            }}
                        />
                    ))}
                </ul>
            ) : (
                <p>{_('no_sources')}</p>
            )}
        </React.Fragment>
    );
}
