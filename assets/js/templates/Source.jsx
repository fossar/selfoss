import React from 'react';
import PropTypes from 'prop-types';
import nullable from 'prop-types-nullable';
import { unescape } from 'html-escaper';
import classNames from 'classnames';
import pick from 'lodash.pick';
import SourceParam from './SourceParam';
import { Spinner } from './Spinner';
import * as sourceRequests from '../requests/sources';
import { LoadingState } from '../requests/LoadingState';
import { LocalizationContext } from '../helpers/i18n';

// cancel source editing
function handleCancel({ event, source, setSources, setEditedSource }) {
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
                selfoss.app.setTags(response.tags);
                selfoss.app.setTagsState(LoadingState.SUCCESS);
                selfoss.app.setSources(response.sources);
                selfoss.app.setSourcesState(LoadingState.SUCCESS);

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
            selfoss.app.showError(
                selfoss.app._('error_edit_source') + ' ' + error.message
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
    setSourceBeingDeleted,
    setDirty,
}) {
    event.preventDefault();

    const answer = confirm(selfoss.app._('source_warn'));
    if (answer == false) {
        return;
    }

    // get id
    const id = source.id;

    setDirty(false);

    // show loading
    setSourceBeingDeleted(true);

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
            selfoss.app.setSources((sources) =>
                sources.filter((source) => source.id !== id)
            );
        })
        .catch((error) => {
            setSourceBeingDeleted(false);
            selfoss.app.showError(
                selfoss.app._('error_delete_source') + ' ' + error.message
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
    setSourceErrors,
    dirty,
    setDirty,
}) {
    const sourceId = source.id;
    const updateEditedSource = React.useCallback(
        (changes) => {
            setDirty(true);
            if (typeof changes === 'function') {
                setEditedSource((source) => ({ ...source, ...changes(source) }));
            } else {
                setEditedSource((source) => ({ ...source, ...changes }));
            }
        },
        [setEditedSource, setDirty]
    );

    const titleOnChange = React.useCallback(
        (event) => updateEditedSource({ title: event.target.value }),
        [updateEditedSource]
    );

    const tagsOnChange = React.useCallback(
        (event) => updateEditedSource({ tags: event.target.value }),
        [updateEditedSource]
    );

    const filterOnChange = React.useCallback(
        (event) => updateEditedSource({ filter: event.target.value }),
        [updateEditedSource]
    );

    const spoutOnChange = React.useCallback(
        (event) =>
            handleSpoutChange({
                event,
                setSpouts,
                updateEditedSource,
                setSourceParamsLoading,
                setSourceParamsError
            }),
        [setSpouts, updateEditedSource, setSourceParamsLoading, setSourceParamsError]
    );

    const saveOnClick = React.useCallback(
        (event) => {
            setDirty(false);
            handleSave({
                event,
                setSources,
                source,
                setEditedSource,
                setSourceActionLoading,
                setJustSavedTimeout,
                setSourceErrors
            });
        },
        [setSources, source, setEditedSource, setSourceActionLoading, setJustSavedTimeout, setSourceErrors, setDirty]
    );

    const cancelOnClick = React.useCallback(
        (event) => {
            event.preventDefault();

            if (dirty) {
                const answer = confirm(selfoss.app._('source_warn_cancel_dirty'));
                if (answer === false) {
                    return;
                }
            }

            setDirty(false);
            handleCancel({
                event,
                source,
                setSources,
                setEditedSource
            });
        },
        [source, setSources, setEditedSource, dirty, setDirty]
    );

    const _ = React.useContext(LocalizationContext);

    return (
        <ul className="source-edit-form">
            {/* title */}
            <li>
                <label htmlFor={`title-${sourceId}`}>
                    {_('source_title')}
                </label>
                <input
                    id={`title-${sourceId}`}
                    type="text"
                    name="title"
                    accessKey="t"
                    value={source.title ?? ''}
                    placeholder={_('source_autotitle_hint')}
                    onChange={titleOnChange}
                />
                {sourceErrors['title'] ? (
                    <span className="error">{sourceErrors['title']}</span>
                ) : null}
            </li>

            {/* tags */}
            <li>
                <label htmlFor={`tags-${sourceId}`}>
                    {_('source_tags')}
                </label>
                <input
                    id={`tags-${sourceId}`}
                    type="text"
                    name="tags"
                    accessKey="g"
                    value={source.tags ?? ''}
                    onChange={tagsOnChange}
                />
                <span className="source-edit-form-help">
                    {' '}
                    {_('source_comma')}
                </span>
                {sourceErrors['tags'] ? (
                    <span className="error">{sourceErrors['tags']}</span>
                ) : null}
            </li>

            {/* filter */}
            <li>
                <label htmlFor={`filter-${sourceId}`}>
                    {_('source_filter')}
                </label>
                <input
                    id={`filter-${sourceId}`}
                    type="text"
                    name="filter"
                    accessKey="f"
                    value={source.filter ?? ''}
                    onChange={filterOnChange}
                />
                {sourceErrors['filter'] ? (
                    <span className="error">{sourceErrors['filter']}</span>
                ) : null}
            </li>

            {/* type */}
            <li>
                <label htmlFor={`type-${sourceId}`}>
                    {_('source_type')}
                </label>
                <select
                    id={`type-${sourceId}`}
                    className="source-spout"
                    name="spout"
                    accessKey="y"
                    onChange={spoutOnChange}
                    value={source.spout}
                >
                    <option value="">{_('source_select')}</option>
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
            <li className="source-params">
                {sourceParamsLoading &&
                    <Spinner size="3x" />
                }

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
                                                setEditedSource,
                                                setDirty,
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
            <li className="source-action">
                <button
                    type="submit"
                    className="source-save"
                    accessKey="s"
                    onClick={saveOnClick}
                >
                    {_('source_save')}

                    {sourceActionLoading &&
                        <React.Fragment>
                            {' '}
                            <Spinner />
                        </React.Fragment>
                    }
                </button>
                {' • '}
                <button
                    type="submit"
                    className="source-cancel"
                    accessKey="c"
                    onClick={cancelOnClick}
                >
                    {_('source_cancel')}
                </button>
            </li>
        </ul>
    );
}

SourceEditForm.propTypes = {
    source: PropTypes.object.isRequired,
    sourceError: PropTypes.string,
    setSources: PropTypes.func.isRequired,
    spouts: PropTypes.object.isRequired,
    setSpouts: PropTypes.func.isRequired,
    setEditedSource: PropTypes.func.isRequired,
    sourceActionLoading: PropTypes.bool.isRequired,
    setSourceActionLoading: PropTypes.func.isRequired,
    sourceParamsLoading: PropTypes.bool.isRequired,
    setSourceParamsLoading: PropTypes.func.isRequired,
    sourceParamsError: nullable(PropTypes.string).isRequired,
    setSourceParamsError: PropTypes.func.isRequired,
    setJustSavedTimeout: PropTypes.func.isRequired,
    sourceErrors: PropTypes.objectOf(PropTypes.string).isRequired,
    setSourceErrors: PropTypes.func.isRequired,
    dirty: PropTypes.bool.isRequired,
    setDirty: PropTypes.func.isRequired,
};

export default function Source({ source, setSources, spouts, setSpouts, dirty, setDirtySources }) {
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
    const [sourceBeingDeleted, setSourceBeingDeleted] = React.useState(false);
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

    const editOnClick = React.useCallback(
        (event) => handleEdit({ event, source, setEditedSource }),
        [source]
    );

    const setDirty = React.useCallback(
        (dirty) => {
            setDirtySources((dirtySources) => ({
                ...dirtySources,
                [source.id]: dirty,
            }));
        },
        [source.id, setDirtySources]
    );

    const deleteOnClick = React.useCallback(
        (event) =>
            handleDelete({
                event,
                source,
                setSources,
                setSourceBeingDeleted,
                setDirty,
            }),
        [source, setSources, setDirty]
    );

    const _ = React.useContext(LocalizationContext);

    return (
        <form className={classNames(classes)} data-id={source.id}>
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
                    : _('source_new')}
            </div>{' '}
            <div className="source-edit-delete">

                {!editedSource &&
                    <React.Fragment>
                        <button
                            type="button"
                            accessKey="e"
                            className={classNames({
                                'source-showparams': true,
                                saved: justSavedTimeout !== null
                            })}
                            onClick={editOnClick}
                        >
                            {_(
                                justSavedTimeout !== null ? 'source_saved' : 'source_edit'
                            )}
                        </button>
                        {' • '}
                    </React.Fragment>
                }
                <button
                    type="button"
                    accessKey="d"
                    className="source-delete"
                    onClick={deleteOnClick}
                >
                    {_('source_delete')}

                    {sourceBeingDeleted &&
                        <React.Fragment>
                            {' '}
                            <Spinner />
                        </React.Fragment>
                    }
                </button>
            </div>
            <div className="source-days">
                {source.lastentry
                    ? ` • ${_(
                        'source_last_post'
                    )} ${_('days', [
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
                        setSourceErrors,
                        dirty,
                        setDirty,
                    }}
                    sourceError={source.error}
                    source={editedSource}
                />
            ) : null}
        </form>
    );
}

Source.propTypes = {
    source: PropTypes.object.isRequired,
    setSources: PropTypes.func.isRequired,
    spouts: PropTypes.object.isRequired,
    setSpouts: PropTypes.func.isRequired,
    dirty: PropTypes.bool.isRequired,
    setDirtySources: PropTypes.func.isRequired,
};
