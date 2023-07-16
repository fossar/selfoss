import PropTypes from 'prop-types';
import React from 'react';
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
    setSources((sources) => [{ id: 'new-' + rand(), ...extraInitialData }, ...sources]);

    // Refresh the spout datea
    sourceRequests
        .getSpouts()
        .then(({ spouts }) => {
            // Update spout data.
            setSpouts(spouts);
        })
        .catch(() => {
            console.error('Unable to update spouts, falling back to previously fetched list.');
        });
}

// load sources
function loadSources({ abortController, location, setSpouts, setSources, setLoadingState }) {
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

        if (location.hash.startsWith('#source-')) {
            const source = document.querySelector(`.source[data-id="${location.hash.replace(/^#source-/, '')}"]`);

            if (!source) {
                return;
            }

            // needs to be delayed for some reason
            requestAnimationFrame(() => {
                source.scrollIntoView();
            });
        }
    }).catch((error) => {
        if (error.name === 'AbortError' || abortController.signal.aborted) {
            return;
        }

        selfoss.handleAjaxError(error, false).catch(function(error) {
            if (error instanceof HttpError && error.response.status === 403) {
                selfoss.history.push('/sign/in', {
                    error: selfoss.app._('error_session_expired'),
                });
                return;
            }

            selfoss.app.showError(selfoss.app._('error_loading') + ' ' + error.message);
        });

        setLoadingState(LoadingState.FAILURE);
    });
}


/**
 * Get dark OR bright color depending the color contrast.
 *
 * @param {string} color color (hex) value
 * @param {string} darkColor dark color value
 * @param {string} brightColor bright color value
 *
 * @return {string} dark OR bright color value
 *
 * @see https://24ways.org/2010/calculating-color-contrast/
 */
function getContrastYiq(hexcolor, darkColor = '#555', brightColor = '#eee') {
    const r = parseInt(hexcolor.substr(1 + 0, 2), 16);
    const g = parseInt(hexcolor.substr(1 + 2, 2), 16);
    const b = parseInt(hexcolor.substr(1 + 4, 2), 16);
    const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
    return (yiq >= 128) ? darkColor : brightColor;
}


export default function SourcesPage({ tags }) {
    const [spouts, setSpouts] = React.useState([]);
    const [sources, setSources] = React.useState([]);
    const tagInfo = useMemo(
        () => {
            let maxTagId = 1;
            let info = {};

            tags.forEach(({ tag, color }) => {
                if (typeof info[tag] === 'undefined') {
                    info[tag] = {
                        id: maxTagId++,
                        color,
                        foregroundColor: getContrastYiq(color.substr()),
                    };
                }
            });

            return info;
        },
        [tags]
    );

    const [loadingState, setLoadingState] = React.useState(LoadingState.INITIAL);

    const forceReload = useShouldReload();

    const history = useHistory();
    const location = useLocation();
    const isAdding = useRouteMatch('/manage/sources/add');

    React.useEffect(() => {
        const abortController = new AbortController();

        if (selfoss.app.state.tags.length === 0) {
            // Ensure tags are loaded.
            selfoss.reloadTags();
        }

        loadSources({ abortController, location, setSpouts, setSources, setLoadingState })
            .then(() => {
                if (isAdding) {
                    const params = new URLSearchParams(location.search);
                    handleAddSource({
                        setSources,
                        setSpouts,
                        extraInitialData: {
                            spout: 'spouts\\rss\\feed',
                            params: {
                                url: params.get('url') ?? '',
                            }
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

    const addOnClick = React.useCallback(
        (event) => handleAddSource({ event, setSources, setSpouts }),
        []
    );

    const _ = React.useContext(LocalizationContext);

    const [dirtySources, setDirtySources] = React.useState({});
    const isDirty = useMemo(
        () => Object.values(dirtySources).includes(true),
        [dirtySources]
    );

    if (loadingState === LoadingState.LOADING) {
        return (
            <SpinnerBig label={_('sources_loading')} />
        );
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

            <button
                className="source-add"
                onClick={addOnClick}
            >
                {_('source_add')}
            </button>
            <a className="source-export" href="opmlexport">
                {_('source_export')}
            </a>
            <Link className="source-opml" to="/opml">
                {_('source_opml')}
            </Link>
            {sources
                ? (
                    <ul>
                        {sources.map((source) => (
                            <Source
                                key={source.id}
                                dirty={dirtySources[source.id] ?? false}
                                {...{ source, setSources, spouts, setSpouts, setDirtySources, tagInfo }}
                            />
                        ))}
                    </ul>
                )
                : (
                    <p>
                        {_('no_sources')}
                    </p>
                )
            }
        </React.Fragment>
    );
}

SourcesPage.propTypes = {
    tags: PropTypes.array.isRequired,
};
