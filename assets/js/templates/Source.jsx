import React from 'react';
import { unescape } from 'html-escaper';
import classNames from 'classnames';
import pick from 'lodash.pick';
import SourceParam from './SourceParam';
import * as sourceRequests from '../requests/sources';

// cancel source editing
function handleCancel({ event, source, setSources, setEditedSource }) {
    event.preventDefault();

    const id = source.id;

    if (id.toString().startsWith('new-')) {
        const parent = $(event.target).parents('.source');
        parent.fadeOut('fast', () => {
            // Remove the source from this page’s model.
            setSources((sources) =>
                sources.filter((source) => source.id !== id)
            );
        });
    } else {
        // Hide the input form.
        setEditedSource(null);
    }
}

// save source
function handleSave({
    event,
    setSources,
    source,
    setEditedSource,
    setSourceActionLoading,
    setJustSavedTimeout,
    setSourceErrors
}) {
    event.preventDefault();

    // remove old errors
    setSourceErrors({});

    setSourceActionLoading(true);

    const newSource = source;
    const { id, tags, params, ...restSource } = source;

    // Build params for the API request.
    let values = Object.entries({ ...restSource, ...params });

    // Make tags into a list.
    const tagsList = tags
        ? tags
            .split(',')
            .map((tag) => tag.trim())
            .filter((tag) => tag !== '')
        : [];

    tagsList.forEach((tag) => values.push(['tags[]', tag]));

    sourceRequests
        .update(id, values)
        .then((response) => {
            if (!response.success) {
                setSourceErrors(response);
            } else {
                // Set justSavedTimeout state variable to a timeout.
                // The view will show “saved” text while the timeout runs.
                setJustSavedTimeout((oldTimeout) => {
                    // Only keep the most recent timeout.
                    if (oldTimeout !== null) {
                        clearTimeout(oldTimeout);
                    }

                    return window.setTimeout(() => {
                        setJustSavedTimeout(null);
                    }, 10000);
                });

                // Hide the input form.
                setEditedSource(null);

                // Update tags and sources for navigation.
                selfoss.tags.update(response.tags);
                selfoss.sources.update(response.sources);

                // Update sources in this page’s model.
                setSources((sources) =>
                    sources.map((source) => {
                        if (source.id === id) {
                            return {
                                ...newSource,
                                id: response.id,
                                tags: tagsList,
                                // Use fetched title.
                                title: response.title
                            };
                        } else {
                            return source;
                        }
                    })
                );
            }
        })
        .catch((error) => {
            selfoss.ui.showError(
                selfoss.ui._('error_edit_source') + ' ' + error.message
            );
        })
        .finally(() => {
            setSourceActionLoading(false);
        });
}

// delete source
function handleDelete({
    event,
    source,
    setSources,
    setSourceEditDeleteLoading
}) {
    event.preventDefault();

    const answer = confirm(selfoss.ui._('source_warn'));
    if (answer == false) {
        return;
    }

    // get id
    const id = source.id;

    // show loading
    setSourceEditDeleteLoading(true);

    // delete on server
    sourceRequests
        .remove(id)
        .then(() => {
            const parent = $(event.target).parents('.source');
            parent.fadeOut('fast', () => {
                // Remove the source from this page’s model.
                setSources((sources) =>
                    sources.filter((source) => source.id !== id)
                );
            });

            // Reload tags and remove source from navigation.
            selfoss.reloadTags();
            selfoss.sources.update(
                selfoss.sources.sources.filter((source) => source.id !== id)
            );
        })
        .catch((error) => {
            setSourceEditDeleteLoading(false);
            selfoss.ui.showError(
                selfoss.ui._('error_delete_source') + ' ' + error.message
            );
        });
}

// start editing
function handleEdit({ event, source, setEditedSource }) {
    event.preventDefault();

    const { id, title, tags, filter, spout, params } = source;

    setEditedSource({
        id,
        title: title ? unescape(title) : '',
        tags: tags ? tags.map(unescape).join(',') : '',
        filter,
        spout,
        params
    });
}

// select new source spout type
function handleSpoutChange({
    event,
    setSpouts,
    updateEditedSource,
    setSourceParamsLoading,
    setSourceParamsError
}) {
    const spoutClass = event.target.value;
    updateEditedSource({ spout: spoutClass });

    if (spoutClass.trim().length === 0) {
        return;
    }
    setSourceParamsLoading(true);
    sourceRequests
        .getSpoutParams(spoutClass)
        .then(({ spout }) => {
            setSourceParamsLoading(false);
            setSpouts((spouts) => ({ ...spouts, [spoutClass]: spout }));

            const defaults = Object.fromEntries(
                Object.entries(spout.params).map(([param, props]) => [
                    param,
                    props['default'] ?? ''
                ])
            );
            updateEditedSource((source) => {
                const oldCompatibleParams = pick(
                    source.params,
                    Object.keys(spout.params)
                );

                return {
                    params: { ...defaults, ...oldCompatibleParams }
                };
            });
        })
        .catch((error) => {
            setSourceParamsLoading(false);
            setSourceParamsError(error.message);
        });
}

// Taken from https://stackoverflow.com/a/15289883/160386
const MS_PER_DAY = 1000 * 60 * 60 * 24;

function daysAgo(date) {
    // Get number of days between now and when the last entry was seen
    // Note: The time of the two dates is set to midnight
    // to get the difference of the two dates in calendar days
    // instead of a day equaling any 24 hour period which makes it
    // impossible to distinguish today and yesterday.
    const now = new Date();
    const today = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate());
    const old = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());

    return Math.floor((today - old) / MS_PER_DAY);
}

function SourceEditForm({
    source,
    sourceError,
    setSources,
    spouts,
    setSpouts,
    setEditedSource,
    sourceActionLoading,
    setSourceActionLoading,
    sourceParamsLoading,
    setSourceParamsLoading,
    sourceParamsError,
    setSourceParamsError,
    setJustSavedTimeout,
    sourceErrors,
    setSourceErrors
}) {
    const sourceId = source.id;
    const updateEditedSource = (changes) => {
        if (typeof changes === 'function') {
            setEditedSource((source) => ({ ...source, ...changes(source) }));
        } else {
            setEditedSource((source) => ({ ...source, ...changes }));
        }
    };

    return (
        <ul className="source-edit-form">
            {/* title */}
            <li>
                <label htmlFor={`title-${sourceId}`}>
                    {selfoss.ui._('source_title')}
                </label>
                <input
                    id={`title-${sourceId}`}
                    type="text"
                    name="title"
                    accessKey="t"
                    value={source.title ?? ''}
                    placeholder={selfoss.ui._('source_autotitle_hint')}
                    onChange={(event) =>
                        updateEditedSource({ title: event.target.value })
                    }
                />
                {sourceErrors['title'] ? (
                    <span className="error">{sourceErrors['title']}</span>
                ) : null}
            </li>

            {/* tags */}
            <li>
                <label htmlFor={`tags-${sourceId}`}>
                    {selfoss.ui._('source_tags')}
                </label>
                <input
                    id={`tags-${sourceId}`}
                    type="text"
                    name="tags"
                    accessKey="g"
                    value={source.tags ?? ''}
                    onChange={(event) =>
                        updateEditedSource({ tags: event.target.value })
                    }
                />
                <span className="source-edit-form-help">
                    {' '}
                    {selfoss.ui._('source_comma')}
                </span>
                {sourceErrors['tags'] ? (
                    <span className="error">{sourceErrors['tags']}</span>
                ) : null}
            </li>

            {/* filter */}
            <li>
                <label htmlFor={`filter-${sourceId}`}>
                    {selfoss.ui._('source_filter')}
                </label>
                <input
                    id={`filter-${sourceId}`}
                    type="text"
                    name="filter"
                    accessKey="f"
                    value={source.filter ?? ''}
                    onChange={(event) =>
                        updateEditedSource({ filter: event.target.value })
                    }
                />
                {sourceErrors['filter'] ? (
                    <span className="error">{sourceErrors['filter']}</span>
                ) : null}
            </li>

            {/* type */}
            <li>
                <label htmlFor={`type-${sourceId}`}>
                    {selfoss.ui._('source_type')}
                </label>
                <select
                    id={`type-${sourceId}`}
                    className="source-spout"
                    name="spout"
                    accessKey="y"
                    onChange={(event) =>
                        handleSpoutChange({
                            event,
                            setSpouts,
                            updateEditedSource,
                            setSourceParamsLoading,
                            setSourceParamsError
                        })
                    }
                    value={source.spout}
                >
                    <option value="">{selfoss.ui._('source_select')}</option>
                    {Object.entries(spouts).map(([spouttype, spout]) => (
                        <option
                            key={spouttype}
                            title={spout.description}
                            value={spouttype}
                        >
                            {spout.name}
                        </option>
                    ))}
                </select>
                {sourceErrors['spout'] ? (
                    <span className="error">{sourceErrors['spout']}</span>
                ) : null}
            </li>

            {/* settings */}
            <li
                className={classNames({
                    'source-params': true,
                    loading: sourceParamsLoading
                })}
            >
                {sourceParamsError ??
                    (Object.keys(spouts).includes(source.spout) &&
                    Object.keys(spouts[source.spout].params).length > 0 ? (
                            <ul>
                                {Object.entries(spouts[source.spout].params).map(
                                    ([spoutParamName, spoutParam]) => (
                                        <SourceParam
                                            key={spoutParamName}
                                            params={source.params}
                                            {...{
                                                spoutParamName,
                                                spoutParam,
                                                sourceErrors,
                                                sourceId,
                                                setEditedSource
                                            }}
                                        />
                                    )
                                )}
                            </ul>
                        ) : null)}
            </li>

            {/* error messages */}
            {sourceError ? (
                <li className="source-error" aria-live="assertive">
                    {sourceError}
                </li>
            ) : null}

            {/* save/delete */}
            <li
                className={classNames({
                    'source-action': true,
                    loading: sourceActionLoading
                })}
            >
                <button
                    type="submit"
                    className="source-save"
                    accessKey="s"
                    onClick={(event) =>
                        handleSave({
                            event,
                            setSources,
                            source,
                            setEditedSource,
                            setSourceActionLoading,
                            setJustSavedTimeout,
                            setSourceErrors
                        })
                    }
                >
                    {selfoss.ui._('source_save')}
                </button>
                {' • '}
                <button
                    type="submit"
                    className="source-cancel"
                    accessKey="c"
                    onClick={(event) =>
                        handleCancel({
                            event,
                            source,
                            setSources,
                            setEditedSource
                        })
                    }
                >
                    {selfoss.ui._('source_cancel')}
                </button>
            </li>
        </ul>
    );
}

export default function Source({ source, setSources, spouts, setSpouts }) {
    const isNew = !source.title;
    let classes = {
        source: true,
        'source-new': isNew,
        error: source.error && source.error.length > 0
    };

    const [editedSource, setEditedSource] = React.useState(
        isNew ? { ...source } : null
    );
    const [sourceActionLoading, setSourceActionLoading] = React.useState(false);
    const [
        sourceEditDeleteLoading,
        setSourceEditDeleteLoading
    ] = React.useState(false);
    const [sourceParamsLoading, setSourceParamsLoading] = React.useState(false);
    const [justSavedTimeout, setJustSavedTimeout] = React.useState(null);
    const [sourceParamsError, setSourceParamsError] = React.useState(null);
    const [sourceErrors, setSourceErrors] = React.useState({});

    React.useEffect(() => {
        // Prevent timeout from trying to update state after unmount.
        let oldTimeout = justSavedTimeout;
        return () => {
            if (oldTimeout !== null) {
                clearTimeout(oldTimeout);
            }
        };
    }, [justSavedTimeout]);

    return (
        <form className={classNames(classes)}>
            <div className="source-icon">
                {source.icon && source.icon != '0' ? (
                    <img
                        src={`favicons/${source.icon}`}
                        aria-hidden="true"
                        alt=""
                    />
                ) : null}
            </div>
            <div className="source-title">
                {source.title
                    ? unescape(source.title)
                    : selfoss.ui._('source_new')}
            </div>{' '}
            <div
                className={classNames({
                    'source-edit-delete': true,
                    loading: sourceEditDeleteLoading
                })}
            >
                <button
                    type="button"
                    accessKey="e"
                    className={classNames({
                        'source-showparams': true,
                        saved: justSavedTimeout !== null
                    })}
                    onClick={(event) =>
                        handleEdit({ event, source, setEditedSource })
                    }
                >
                    {selfoss.ui._(
                        justSavedTimeout !== null ? 'source_saved' : 'source_edit'
                    )}
                </button>
                {' • '}
                <button
                    type="button"
                    accessKey="d"
                    className="source-delete"
                    onClick={(event) =>
                        handleDelete({
                            event,
                            source,
                            setSources,
                            setSourceEditDeleteLoading
                        })
                    }
                >
                    {selfoss.ui._('source_delete')}
                </button>
            </div>
            <div className="source-days">
                {source.lastentry
                    ? ` • ${selfoss.ui._(
                        'source_last_post'
                    )} ${selfoss.ui._('days', [
                        daysAgo(new Date(source.lastentry * 1000))
                    ])}`
                    : null}
            </div>
            {/* edit */}
            {editedSource ? (
                <SourceEditForm
                    {...{
                        setSources,
                        spouts,
                        setSpouts,
                        setEditedSource,
                        sourceActionLoading,
                        setSourceActionLoading,
                        sourceParamsLoading,
                        setSourceParamsLoading,
                        sourceParamsError,
                        setSourceParamsError,
                        setJustSavedTimeout,
                        sourceErrors,
                        setSourceErrors
                    }}
                    sourceError={source.error}
                    source={editedSource}
                />
            ) : null}
        </form>
    );
}
