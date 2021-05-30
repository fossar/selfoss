import React from 'react';
import Source from './Source';
import { SpinnerBig } from './Spinner';
import { LoadingState } from '../requests/LoadingState';
import * as sourceRequests from '../requests/sources';
import { getAllSources } from '../requests/sources';
import { LocalizationContext } from '../helpers/i18n';
import { HttpError } from '../errors';

function rand() {
    // https://www.php.net/manual/en/function.mt-getrandmax.php#117620
    return Math.floor(Math.random() * 2147483647);
}

function handleAddSource({ event, setSources, setSpouts }) {
    event.preventDefault();

    // add new source
    sourceRequests
        .getSpouts()
        .then(({ spouts }) => {
            // Update spout data.
            setSpouts(spouts);
            // Add new empty source.
            setSources((sources) => [{ id: 'new-' + rand() }, ...sources]);
        })
        .catch((error) => {
            selfoss.app.showError(
                selfoss.app._('error_add_source') + ' ' + error.message
            );
        });
}

// load sources
function loadSources({ abortController, setSpouts, setSources, setLoadingState }) {
    if (abortController.signal.aborted) {
        return Promise.resolve();
    }

    setLoadingState(LoadingState.LOADING);

    return getAllSources(abortController).then(({sources, spouts}) => {
        if (abortController.signal.aborted) {
            return;
        }

        setSpouts(spouts);
        setSources(sources);
        setLoadingState(LoadingState.SUCCESS);
    }).catch((error) => {
        if (error.name === 'AbortError' || abortController.signal.aborted) {
            return;
        }

        selfoss.handleAjaxError(error, false).catch(function(error) {
            if (error instanceof HttpError && error.response.status === 403) {
                selfoss.history.push('/login');
                // TODO: Use location state once we switch to BrowserRouter
                selfoss.app.setLoginFormError(selfoss.app._('error_session_expired'));
                return;
            }

            selfoss.app.showError(selfoss.app._('error_loading') + ' ' + error.message);
        });

        setLoadingState(LoadingState.FAILURE);
    });
}

export default function SourcesPage() {
    const [spouts, setSpouts] = React.useState([]);
    const [sources, setSources] = React.useState([]);

    const [loadingState, setLoadingState] = React.useState(LoadingState.INITIAL);

    React.useEffect(() => {
        const abortController = new AbortController();

        loadSources({ abortController, setSpouts, setSources, setLoadingState });

        return () => {
            abortController.abort();
        };
    }, []);

    const addOnClick = React.useCallback(
        (event) => handleAddSource({ event, setSources, setSpouts }),
        []
    );

    const _ = React.useContext(LocalizationContext);

    if (loadingState === LoadingState.LOADING) {
        return (
            <SpinnerBig />
        );
    }

    if (loadingState !== LoadingState.SUCCESS) {
        return null;
    }

    return (
        <React.Fragment>
            <button
                className="source-add"
                onClick={addOnClick}
            >
                {_('source_add')}
            </button>
            <a className="source-export" href="opmlexport">
                {_('source_export')}
            </a>
            <a className="source-opml" href="opml">
                {_('source_opml')}
            </a>
            {sources.map((source) => (
                <Source
                    key={source.id}
                    {...{ source, setSources, spouts, setSpouts }}
                />
            ))}
        </React.Fragment>
    );
}
