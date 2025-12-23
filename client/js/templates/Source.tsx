import React, {
    ChangeEvent,
    Dispatch,
    MouseEvent,
    RefObject,
    SetStateAction,
    useCallback,
    useContext,
    useEffect,
    useState,
} from 'react';
import { useRef } from 'react';
import { Menu, MenuButton, MenuItem } from '@szhsin/react-menu';
import { useNavigate, useLocation } from 'react-router';
import { fadeOut } from '@siteparts/show-hide-effects';
import { makeEntriesLinkLocation } from '../helpers/uri';
import { unescape } from 'html-escaper';
import classNames from 'classnames';
import { pick } from 'lodash-es';
import selfoss from '../selfoss-base';
import SourceParam from './SourceParam';
import { Spinner } from './Spinner';
import * as sourceRequests from '../requests/sources';
import { LoadingState } from '../requests/LoadingState';
import { LocalizationContext, Translate } from '../helpers/i18n';

const FAST_DURATION_MS = 200;

// cancel source editing
function handleCancel(args: {
    event?: MouseEvent<HTMLButtonElement>;
    source: EditedSource;
    sourceElem: RefObject<HTMLLIElement>;
    setSources: Dispatch<SetStateAction<Array<Source>>>;
    setEditedSource: Dispatch<SetStateAction<EditedSource>>;
}): void {
    const { source, sourceElem, setSources, setEditedSource } = args;
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
function handleSave(args: {
    event: MouseEvent<HTMLButtonElement>;
    setSources: Dispatch<SetStateAction<Array<Source>>>;
    source: EditedSource;
    setEditedSource: Dispatch<SetStateAction<EditedSource>>;
    setSourceActionLoading: Dispatch<SetStateAction<boolean>>;
    setJustSavedTimeout: Dispatch<SetStateAction<number>>;
    setSourceErrors: Dispatch<SetStateAction<{ [index: string]: string }>>;
    isNew: boolean;
    setNewIds: Dispatch<SetStateAction<Set<number>>>;
    _: Translate;
}): void {
    const {
        event,
        setSources,
        source,
        setEditedSource,
        setSourceActionLoading,
        setJustSavedTimeout,
        setSourceErrors,
        isNew,
        setNewIds,
        _,
    } = args;
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

    const idString = isNew ? `new-${id}` : `${id}`;

    sourceRequests
        .update(idString, values)
        .then((response) => {
            if (response.success === false) {
                setSourceErrors(response.errors);
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

                setNewIds((newIds) => {
                    const s = new Set(newIds);
                    s.delete(id);
                    return s;
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
                                icon: null,
                                lastentry: null,
                                error: null,
                            };
                        } else {
                            return source;
                        }
                    }),
                );
            }
        })
        .catch((error) => {
            selfoss.app.showError(_('error_edit_source') + ' ' + error.message);
        })
        .finally(() => {
            setSourceActionLoading(false);
        });
}

// delete source
function handleDelete(args: {
    source: Source;
    sourceElem: RefObject<HTMLLIElement>;
    setSources: Dispatch<SetStateAction<Array<Source>>>;
    setSourceBeingDeleted: Dispatch<SetStateAction<boolean>>;
    setDirty: Dispatch<SetStateAction<boolean>>;
    _: Translate;
}): void {
    const {
        source,
        sourceElem,
        setSources,
        setSourceBeingDeleted,
        setDirty,
        _,
    } = args;
    const answer = confirm(_('source_warn'));
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
                _('error_delete_source') + ' ' + error.message,
            );
        });
}

// start editing
function handleEdit(args: {
    event: MouseEvent<HTMLButtonElement>;
    source: Source;
    setEditedSource: Dispatch<SetStateAction<EditedSource>>;
}): void {
    const { event, source, setEditedSource } = args;
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
function handleSpoutChange(args: {
    event: ChangeEvent<HTMLSelectElement>;
    setSpouts: Dispatch<SetStateAction<{ [key: string]: Spout }>>;
    updateEditedSource: Dispatch<SetStateAction<Partial<EditedSource>>>;
    setSourceParamsLoading: Dispatch<SetStateAction<boolean>>;
    setSourceParamsError: Dispatch<SetStateAction<string | null>>;
}): void {
    const {
        event,
        setSpouts,
        updateEditedSource,
        setSourceParamsLoading,
        setSourceParamsError,
    } = args;
    const spoutClass = event.currentTarget.value;
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

function daysAgo(date: Date): number {
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

export type SpoutParam = {
    title: string;
    default: string;
} & (
    | {
          type: 'text' | 'url' | 'password' | 'checkbox';
      }
    | {
          type: 'select';
          values: { [s: string]: string };
      }
);

export type Spout = {
    name: string;
    description: string;
    params: { [name: string]: SpoutParam };
};

export type Source = {
    id: number;
    title: string;
    spout: string;
    tags: string[];
    filter: string;
    params: { [name: string]: string };
    icon: string;
    lastentry: number;
    error: string;
};

// Similar to `Source` but fields reflect the values of input elements.
export type EditedSource = {
    id: number;
    title: string;
    spout: string;
    tags: string;
    filter: string;
    params: { [name: string]: string };
};

type SourceEditFormProps = {
    source: EditedSource;
    sourceElem: RefObject<HTMLLIElement>;
    sourceError?: string;
    setSources: Dispatch<SetStateAction<Array<Source>>>;
    spouts: { [className: string]: Spout };
    setSpouts: Dispatch<SetStateAction<{ [key: string]: Spout }>>;
    setEditedSource: Dispatch<SetStateAction<EditedSource>>;
    sourceActionLoading: boolean;
    setSourceActionLoading: Dispatch<SetStateAction<boolean>>;
    sourceParamsLoading: boolean;
    setSourceParamsLoading: Dispatch<SetStateAction<boolean>>;
    sourceParamsError: string | null;
    setSourceParamsError: Dispatch<SetStateAction<string | null>>;
    setJustSavedTimeout: Dispatch<SetStateAction<number>>;
    sourceErrors: { [index: string]: string };
    setSourceErrors: Dispatch<SetStateAction<{ [index: string]: string }>>;
    dirty: boolean;
    setDirty: Dispatch<SetStateAction<boolean>>;
    isNew: boolean;
    setNewIds: Dispatch<SetStateAction<Set<number>>>;
};

function SourceEditForm(props: SourceEditFormProps): React.JSX.Element {
    const {
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
        isNew,
        setNewIds,
    } = props;

    const _ = useContext(LocalizationContext);

    const sourceId = source.id;
    const updateEditedSource = useCallback(
        (changes: SetStateAction<Partial<EditedSource>>) => {
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
        (event: ChangeEvent<HTMLInputElement>) =>
            updateEditedSource({ title: event.currentTarget.value }),
        [updateEditedSource],
    );

    const tagsOnChange = useCallback(
        (event: ChangeEvent<HTMLInputElement>) =>
            updateEditedSource({ tags: event.currentTarget.value }),
        [updateEditedSource],
    );

    const filterOnChange = useCallback(
        (event: ChangeEvent<HTMLInputElement>) =>
            updateEditedSource({ filter: event.currentTarget.value }),
        [updateEditedSource],
    );

    const spoutOnChange = useCallback(
        (event: ChangeEvent<HTMLSelectElement>) =>
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
        (event: MouseEvent<HTMLButtonElement>) => {
            setDirty(false);
            handleSave({
                event,
                setSources,
                source,
                setEditedSource,
                setSourceActionLoading,
                setJustSavedTimeout,
                setSourceErrors,
                isNew,
                setNewIds,
                _,
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
            isNew,
            setNewIds,
            _,
        ],
    );

    const cancelOnClick = useCallback(
        (event: MouseEvent<HTMLButtonElement>) => {
            event.preventDefault();

            if (dirty) {
                const answer = confirm(_('source_warn_cancel_dirty'));
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
        [source, sourceElem, setSources, setEditedSource, dirty, setDirty, _],
    );

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

type SourceProps = {
    source: Source;
    isNew: boolean;
    setSources: Dispatch<SetStateAction<Array<Source>>>;
    spouts: { [className: string]: Spout };
    setSpouts: Dispatch<SetStateAction<{ [key: string]: Spout }>>;
    dirty: boolean;
    setDirtySources: Dispatch<SetStateAction<{ [id: number]: boolean }>>;
    setNewIds: Dispatch<SetStateAction<Set<number>>>;
};

export default function Source(props: SourceProps): React.JSX.Element {
    const {
        source,
        isNew,
        setSources,
        spouts,
        setSpouts,
        dirty,
        setDirtySources,
        setNewIds,
    } = props;

    const _ = useContext(LocalizationContext);

    const classes = {
        source: true,
        'source-new': isNew,
        error: source.error && source.error.length > 0,
    };

    const [editedSource, setEditedSource] = useState(
        isNew
            ? {
                  id: source.id,
                  title: source.title,
                  spout: source.spout,
                  tags: source.tags.join(', '),
                  filter: source.filter,
                  params: source.params,
              }
            : null,
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
        (event: MouseEvent<HTMLButtonElement>) =>
            handleEdit({ event, source, setEditedSource }),
        [source, setEditedSource],
    );

    const setDirty = useCallback(
        (dirty: boolean) => {
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
        ({ value }: { value?: string }) => {
            if (value === 'delete') {
                handleDelete({
                    source,
                    sourceElem,
                    setSources,
                    setSourceBeingDeleted,
                    setDirty,
                    _,
                });
            } else if (value === 'browse') {
                navigate(
                    makeEntriesLinkLocation(location, {
                        category: `source-${source.id}`,
                    }),
                );
            }
        },
        [source, sourceElem, setSources, setDirty, location, navigate, _],
    );

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
                    ? ` • ${_('source_last_post')} ${_('days', [
                          daysAgo(new Date(source.lastentry * 1000)),
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
                        isNew,
                        setNewIds,
                        sourceElem,
                    }}
                    sourceError={source.error}
                    source={editedSource}
                />
            ) : null}
        </li>
    );
}
