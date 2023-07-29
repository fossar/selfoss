import React, { useCallback, useContext, useEffect, useState } from 'react';
import { useRef } from 'react';
import { Menu, MenuButton, MenuItem } from '@szhsin/react-menu';
import { useNavigate, useLocation } from 'react-router';
import { fadeOut } from '@siteparts/show-hide-effects';
import { makeEntriesLinkLocation } from '../helpers/uri';
import PropTypes from 'prop-types';
import nullable from 'prop-types-nullable';
import { unescape } from 'html-escaper';
import classNames from 'classnames';
import { pick } from 'lodash-es';
import SourceParam from './SourceParam';
import { Spinner } from './Spinner';
import * as sourceRequests from '../requests/sources';
import { LoadingState } from '../requests/LoadingState';
import { LocalizationContext } from '../helpers/i18n';

const FAST_DURATION_MS = 200;

// cancel source editing
function handleCancel({ source, sourceElem, setSources, setEditedSource }) {
    const id = source.id;

    if (id.toString().startsWith('new-')) {
        fadeOut(sourceElem.current, {
            duration: FAST_DURATION_MS,
            complete: () => {
                // Remove the source from this page’s model.
                setSources((sources) =>
                    sources.filter((source) => source.id !== id),
                );
            },
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
    setSourceErrors,
}) {
    event.preventDefault();

    // remove old errors
    setSourceErrors({});

    setSourceActionLoading(true);

    const newSource = source;
    const { id, tags, filter, params, ...restSource } = source;

    // Make tags into a list.
    const tagsList = tags
        ? tags
              .split(',')
              .map((tag) => tag.trim())
              .filter((tag) => tag !== '')
        : [];

    const values = {
        ...restSource,
        ...params,
        tags: tagsList,
        filter: filter || null,
    };

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
                                title: response.title,
                            };
                        } else {
                            return source;
                        }
                    }),
                );
            }
        })
        .catch((error) => {
            selfoss.app.showError(
                selfoss.app._('error_edit_source') + ' ' + error.message,
            );
        })
        .finally(() => {
            setSourceActionLoading(false);
        });
}

// delete source
function handleDelete({
    source,
    sourceElem,
    setSources,
    setSourceBeingDeleted,
    setDirty,
}) {
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
            fadeOut(sourceElem.current, {
                duration: FAST_DURATION_MS,
                complete: () => {
                    // Remove the source from this page’s model.
                    setSources((sources) =>
                        sources.filter((source) => source.id !== id),
                    );
                },
            });

            // Reload tags and remove source from navigation.
            selfoss.reloadTags();
            selfoss.app.setSources((sources) =>
                sources.filter((source) => source.id !== id),
            );
        })
        .catch((error) => {
            setSourceBeingDeleted(false);
            selfoss.app.showError(
                selfoss.app._('error_delete_source') + ' ' + error.message,
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
        params,
    });
}

// select new source spout type
function handleSpoutChange({
    event,
    setSpouts,
    updateEditedSource,
    setSourceParamsLoading,
    setSourceParamsError,
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
                    props.default ?? '',
                ]),
            );
            updateEditedSource((source) => {
                const oldCompatibleParams = pick(
                    source.params,
                    Object.keys(spout.params),
                );

                return {
                    params: { ...defaults, ...oldCompatibleParams },
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
    sourceElem,
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
    const updateEditedSource = useCallback(
        (changes) => {
            setDirty(true);
            if (typeof changes === 'function') {
                setEditedSource((source) => ({
                    ...source,
                    ...changes(source),
                }));
            } else {
                setEditedSource((source) => ({ ...source, ...changes }));
            }
        },
        [setEditedSource, setDirty],
    );

    const titleOnChange = useCallback(
        (event) => updateEditedSource({ title: event.target.value }),
        [updateEditedSource],
    );

    const tagsOnChange = useCallback(
        (event) => updateEditedSource({ tags: event.target.value }),
        [updateEditedSource],
    );

    const filterOnChange = useCallback(
        (event) => updateEditedSource({ filter: event.target.value }),
        [updateEditedSource],
    );

    const spoutOnChange = useCallback(
        (event) =>
            handleSpoutChange({
                event,
                setSpouts,
                updateEditedSource,
                setSourceParamsLoading,
                setSourceParamsError,
            }),
        [
            setSpouts,
            updateEditedSource,
            setSourceParamsLoading,
            setSourceParamsError,
        ],
    );

    const saveOnClick = useCallback(
        (event) => {
            setDirty(false);
            handleSave({
                event,
                setSources,
                source,
                setEditedSource,
                setSourceActionLoading,
                setJustSavedTimeout,
                setSourceErrors,
            });
        },
        [
            setSources,
            source,
            setEditedSource,
            setSourceActionLoading,
            setJustSavedTimeout,
            setSourceErrors,
            setDirty,
        ],
    );

    const cancelOnClick = useCallback(
        (event) => {
            event.preventDefault();

            if (dirty) {
                const answer = confirm(
                    selfoss.app._('source_warn_cancel_dirty'),
                );
                if (answer === false) {
                    return;
                }
            }

            setDirty(false);
            handleCancel({
                event,
                source,
                sourceElem,
                setSources,
                setEditedSource,
            });
        },
        [source, sourceElem, setSources, setEditedSource, dirty, setDirty],
    );

    const _ = useContext(LocalizationContext);

    const sourceParamsContent = sourceParamsLoading ? (
        <Spinner size="3x" label={_('source_params_loading')} />
    ) : (
        (sourceParamsError ??
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
                    ),
                )}
            </ul>
        ) : null))
    );

    return (
        <form>
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
                        autoFocus
                    />
                    {sourceErrors.title ? (
                        <span className="error">{sourceErrors.title}</span>
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
                    {sourceErrors.tags ? (
                        <span className="error">{sourceErrors.tags}</span>
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
                    {sourceErrors.filter ? (
                        <span className="error">{sourceErrors.filter}</span>
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
                    {sourceErrors.spout ? (
                        <span className="error">{sourceErrors.spout}</span>
                    ) : null}
                </li>

                {/* settings */}
                {sourceParamsContent ? (
                    <li className="source-params">{sourceParamsContent}</li>
                ) : null}

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

                        {sourceActionLoading && (
                            <>
                                {' '}
                                <Spinner label={_('source_saving')} />
                            </>
                        )}
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
        </form>
    );
}

SourceEditForm.propTypes = {
    source: PropTypes.object.isRequired,
    sourceElem: PropTypes.object.isRequired,
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

export default function Source({
    source,
    setSources,
    spouts,
    setSpouts,
    dirty,
    setDirtySources,
}) {
    const isNew = !source.title;
    const classes = {
        source: true,
        'source-new': isNew,
        error: source.error && source.error.length > 0,
    };

    const [editedSource, setEditedSource] = useState(
        isNew ? { ...source } : null,
    );
    const [sourceActionLoading, setSourceActionLoading] = useState(false);
    const [sourceBeingDeleted, setSourceBeingDeleted] = useState(false);
    const [sourceParamsLoading, setSourceParamsLoading] = useState(false);
    const [justSavedTimeout, setJustSavedTimeout] = useState(null);
    const [sourceParamsError, setSourceParamsError] = useState(null);
    const [sourceErrors, setSourceErrors] = useState({});

    useEffect(() => {
        // Prevent timeout from trying to update state after unmount.
        const oldTimeout = justSavedTimeout;
        return () => {
            if (oldTimeout !== null) {
                clearTimeout(oldTimeout);
            }
        };
    }, [justSavedTimeout]);

    const editOnClick = useCallback(
        (event) => handleEdit({ event, source, setEditedSource }),
        [source],
    );

    const setDirty = useCallback(
        (dirty) => {
            setDirtySources((dirtySources) => ({
                ...dirtySources,
                [source.id]: dirty,
            }));
        },
        [source.id, setDirtySources],
    );

    const navigate = useNavigate();
    const location = useLocation();

    const sourceElem = useRef(null);

    const extraMenuOnSelection = useCallback(
        ({ value }) => {
            if (value === 'delete') {
                handleDelete({
                    source,
                    sourceElem,
                    setSources,
                    setSourceBeingDeleted,
                    setDirty,
                });
            } else if (value === 'browse') {
                navigate(
                    makeEntriesLinkLocation(location, {
                        category: `source-${source.id}`,
                    }),
                );
            }
        },
        [source, sourceElem, setSources, setDirty, location, navigate],
    );

    const _ = useContext(LocalizationContext);

    return (
        <li
            className={classNames(classes)}
            data-id={source.id}
            id={`source-${source.id}`}
            ref={sourceElem}
        >
            <div className="source-icon">
                {source.icon && source.icon != '0' ? (
                    <img
                        src={`favicons/${source.icon}`}
                        aria-hidden="true"
                        alt=""
                    />
                ) : null}
            </div>
            <h2 className="source-title">
                {source.title ? unescape(source.title) : _('source_new')}
            </h2>{' '}
            <div className="source-edit-delete">
                {!editedSource && (
                    <>
                        <button
                            type="button"
                            accessKey="e"
                            className={classNames({
                                'source-showparams': true,
                                saved: justSavedTimeout !== null,
                            })}
                            onClick={editOnClick}
                            aria-expanded={!!editedSource}
                        >
                            {_(
                                justSavedTimeout !== null
                                    ? 'source_saved'
                                    : 'source_edit',
                            )}
                        </button>
                        {' • '}
                    </>
                )}
                <Menu
                    onItemClick={extraMenuOnSelection}
                    menuButton={
                        <MenuButton className="source-menu-button">
                            {_('source_menu')}
                            {sourceBeingDeleted && (
                                <>
                                    {' '}
                                    <Spinner label={_('source_deleting')} />
                                </>
                            )}
                        </MenuButton>
                    }
                    menuClassName="popup-menu"
                >
                    <MenuItem
                        accessKey="d"
                        className="popup-menu-item source-browse"
                        value="browse"
                    >
                        {_('source_browse')}
                    </MenuItem>
                    <MenuItem
                        accessKey="d"
                        className="popup-menu-item source-delete"
                        value="delete"
                    >
                        {_('source_delete')}
                    </MenuItem>
                </Menu>
            </div>
            <div className="source-days">
                {source.lastentry
                    ? ` • ${_('source_last_post')} ${_('days', [daysAgo(new Date(source.lastentry * 1000))])}`
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
                        sourceElem,
                    }}
                    sourceError={source.error}
                    source={editedSource}
                />
            ) : null}
        </li>
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
