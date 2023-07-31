import React, {
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
    forwardRef,
} from 'react';
import { Link, NavigateFunction, useLocation, useParams } from 'react-router';
import { useOnline } from 'rooks';
import { useStateWithDeps } from 'use-state-with-deps';
import selfoss from '../selfoss-base';
import Item from './Item';
import { FilterType } from '../Filter';
import * as itemsRequests from '../requests/items';
import * as sourceRequests from '../requests/sources';
import { LoadingState } from '../requests/LoadingState';
import { Spinner, SpinnerBig } from './Spinner';
import classNames from 'classnames';
import {
    useAllowedToUpdate,
    useAllowedToWrite,
} from '../helpers/authorizations';
import { autoScroll, Direction } from '../helpers/navigation';
import { LocalizationContext } from '../helpers/i18n';
import { useShouldReload } from '../helpers/hooks';
import { forceReload, makeEntriesLinkLocation } from '../helpers/uri';
import { ConfigurationContext } from '../model/Configuration';
import { HttpError } from '../errors';
import { useNavigate } from 'react-router';

function reloadList({
    fetchParams,
    abortController,
    navigate,
    append = false,
    waitForSync = true,
    configuration,
    entryId = null,
    setLoadingState,
}) {
    if (abortController.signal.aborted) {
        return Promise.resolve();
    }

    if (entryId && fetchParams.fromId === undefined) {
        fetchParams = {
            ...fetchParams,
            extraIds: [...fetchParams.extraIds, entryId],
        };
    }

    if (!append || fetchParams.type !== FilterType.NEWEST) {
        selfoss.dbOffline.olderEntriesOnline = false;
    }

    setLoadingState(LoadingState.LOADING);

    const reload = () => {
        if (abortController.signal.aborted) {
            return Promise.resolve();
        }

        let reloader = selfoss.dbOffline.getEntries;

        // tag, source and search filtering not supported offline (yet?)
        if (fetchParams.tag || fetchParams.source || fetchParams.search) {
            reloader = selfoss.dbOnline.getEntries;
        }

        const forceLoadOnline =
            selfoss.dbOffline.olderEntriesOnline ||
            selfoss.dbOffline.shouldLoadEntriesOnline;
        if (
            !selfoss.db.enableOffline.value ||
            (selfoss.isOnline() && forceLoadOnline)
        ) {
            reloader = selfoss.dbOnline.getEntries;
        }

        // Clean state when not just adding items.
        if (!append) {
            selfoss.entriesPage.setHasMore(false);
            selfoss.entriesPage.setExpandedEntries({});
            selfoss.entriesPage.setEntries([]);
            selfoss.entriesPage.setSelectedEntry(null);
        }

        setLoadingState(LoadingState.LOADING);
        return reloader(fetchParams, abortController)
            .then(({ entries, hasMore }) => {
                if (abortController.signal.aborted) {
                    return;
                }

                setLoadingState(LoadingState.SUCCESS);
                selfoss.entriesPage.setHasMore(hasMore);

                if (append) {
                    selfoss.entriesPage.appendEntries(entries);
                } else {
                    selfoss.entriesPage.setExpandedEntries({});
                    selfoss.entriesPage.setEntries(entries);

                    // open selected entry only if entry was requested (i.e. if not streaming
                    // more)
                    if (entryId && fetchParams.fromId === undefined) {
                        const entry = document.querySelector(
                            `.entry[data-entry-id="${entryId}"]`,
                        );

                        if (!entry) {
                            return;
                        }

                        selfoss.entriesPage.activateEntry(entryId);
                        // ensure scrolling to requested entry even if scrolling to article
                        // header is disabled
                        if (!configuration.scrollToArticleHeader) {
                            // needs to be delayed for some reason
                            requestAnimationFrame(() => {
                                entry.scrollIntoView();
                            });
                        }
                    } else {
                        window.scrollTo({ top: 0 });
                    }
                }
            })
            .catch((error) => {
                if (abortController.signal.aborted) {
                    return;
                }

                if (
                    error instanceof HttpError &&
                    error.response.status === 403
                ) {
                    navigate('/sign/in', {
                        state: {
                            error: selfoss.app._('error_session_expired'),
                        },
                    });
                    return;
                }

                setLoadingState(LoadingState.FAILURE);
                selfoss.app.showError(
                    selfoss.app._('error_loading') + ' ' + error.message,
                );
            });
    };

    if (waitForSync && selfoss.dbOnline.syncing.promise) {
        selfoss.db.userWaiting = true;
        return selfoss.dbOnline.syncing.promise.finally(reload);
    } else {
        return reload();
    }
}

/**
 * Try to open the selected article using the preferred method.
 * @param {number} selected
 */
function openSelectedArticle(selected) {
    const link = document.querySelector(
        `.entry[data-entry-id="${selected}"] .entry-datetime`,
    );
    if (selfoss.config.openInBackgroundTab) {
        // In Chromium, this will just cause the tab to open in the foreground.
        // Appears to be disallowed by the pop-under prevention:
        // https://crbug.com/431335
        // https://crbug.com/487919
        const event = new MouseEvent('click', {
            ctrlKey: true,
        });
        link.dispatchEvent(event);
    } else {
        // open item in new window
        link.click();
    }
}

// updates a source
function handleRefreshSource({
    event,
    source,
    setLoadingState,
    setNavExpanded,
    reload,
}) {
    event.preventDefault();

    // show loading
    setLoadingState(LoadingState.LOADING);

    return sourceRequests
        .refreshSingle(source)
        .then(() => {
            // hide nav on smartphone
            setNavExpanded(false);

            // Fetch the new items and reload the list.
            // Will also clear the loading status.
            reload();
        })
        .catch((error) => {
            setLoadingState(LoadingState.FAILURE);
            alert(
                selfoss.app._('error_refreshing_source') + ' ' + error.message,
            );
        });
}

type EntriesPageProps = {
    entries: Array<any>;
    hasMore: boolean;
    loadingState: LoadingState;
    setLoadingState: React.Dispatch<React.SetStateAction<LoadingState>>;
    selectedEntry: number | null;
    expandedEntries: { [index: string]: boolean };
    setNavExpanded: React.Dispatch<React.SetStateAction<boolean>>;
    navSourcesExpanded: boolean;
    reload: () => void;
    setGlobalUnreadCount: React.Dispatch<React.SetStateAction<number>>;
    unreadItemsCount: number;
};

export function EntriesPage(props: EntriesPageProps) {
    const {
        entries,
        hasMore,
        loadingState,
        setLoadingState,
        selectedEntry,
        expandedEntries,
        setNavExpanded,
        navSourcesExpanded,
        reload,
        setGlobalUnreadCount,
        unreadItemsCount,
    } = props;

    const allowedToUpdate = useAllowedToUpdate();
    const allowedToWrite = useAllowedToWrite();
    const configuration = useContext(ConfigurationContext);

    const location = useLocation();
    const navigate = useNavigate();
    const forceReload = useShouldReload();
    const searchText = useMemo(() => {
        const queryString = new URLSearchParams(location.search);

        return queryString.get('search') ?? '';
    }, [location.search]);

    const params = useParams();
    const currentTag = params.category?.startsWith('tag-')
        ? params.category.replace(/^tag-/, '')
        : null;
    const currentSource = params.category?.startsWith('source-')
        ? parseInt(params.category.replace(/^source-/, ''), 10)
        : null;

    // The offsets for pagination.
    // Clear them when URL changes, except for when only id changes since that happens when reading.
    const [fromDatetime, setFromDatetime] = useStateWithDeps(undefined, [
        params.filter,
        currentTag,
        currentSource,
        searchText,
    ]);
    const [fromId, setFromId] = useStateWithDeps(undefined, [
        params.filter,
        currentTag,
        currentSource,
        searchText,
    ]);

    // Cache the item id from initial URL so that we can fetch it
    // but do not re-fetch when the id in the URI changes later
    // since that happens when reading.
    const initialItemId = useMemo(() => {
        return parseInt(params.id, 10);
    }, [params.filter, currentTag, currentSource, searchText, forceReload]);
    // Same for the state of navigation being expanded.
    // It is only passed to the API request as a part of optimization scheme
    // so there is no need for it to trigger refresh of the entries.
    const initialNavSourcesExpanded = useMemo(() => {
        return navSourcesExpanded;
    }, [params.filter, currentTag, currentSource, searchText]);

    const [moreLoadingState, setMoreLoadingState] = useState(
        LoadingState.INITIAL,
    );

    // Perform the scheduled reload.
    useEffect(() => {
        const append = fromId !== undefined || fromDatetime !== undefined;
        const abortController = new AbortController();

        reloadList({
            // Object with parameters for GET /items and similar API calls
            // based on the current location.
            fetchParams: {
                type: params.filter,
                tag: currentTag,
                source: currentSource,
                extraIds: [],
                sourcesNav: initialNavSourcesExpanded,
                search: searchText,
                fromDatetime,
                fromId,
            },
            abortController,
            navigate,
            append,
            configuration,
            // We do not want to focus the entry on successive loads.
            entryId: append ? undefined : initialItemId,
            setLoadingState: append ? setMoreLoadingState : setLoadingState,
        }).then(() => {
            if (currentTag !== null && !selfoss.db.isValidTag(currentTag)) {
                selfoss.app.showError(
                    selfoss.app._('error_unknown_tag') + ' ' + currentTag,
                );
            }

            if (
                currentSource !== null &&
                !selfoss.db.isValidSource(currentSource)
            ) {
                selfoss.app.showError(
                    selfoss.app._('error_unknown_source') + ' ' + currentSource,
                );
            }
        });

        return () => {
            abortController.abort();
        };
    }, [
        // navigate is intentionally omitted
        // to prevent reloading when path is replaced
        configuration,
        params.filter,
        currentTag,
        currentSource,
        initialNavSourcesExpanded,
        searchText,
        fromDatetime,
        fromId,
        initialItemId,
        setLoadingState,
        forceReload,
    ]);

    useEffect(() => {
        // scroll load more
        const onScroll = () => {
            const streamMoreButton = document.querySelector('.stream-more');
            if (!streamMoreButton) {
                return;
            }

            const streamMoreButtonTop =
                window.scrollY + streamMoreButton.getBoundingClientRect().top;

            // When “More” button appears on the screen, click it.
            if (
                streamMoreButtonTop <
                document.body.clientHeight + window.scrollY
            ) {
                streamMoreButton.click();
            }
        };

        if (
            hasMore &&
            moreLoadingState !== LoadingState.LOADING &&
            configuration.autoStreamMore
        ) {
            window.addEventListener('scroll', onScroll);

            return () => {
                window.removeEventListener('scroll', onScroll);
            };
        }
    }, [configuration, hasMore, moreLoadingState]);

    useEffect(() => {
        // setup periodic server status sync
        const interval = window.setInterval(selfoss.db.sync, 60 * 1000);

        return () => {
            window.clearInterval(interval);
        };
    }, []);

    useEffect(() => {
        setGlobalUnreadCount(unreadItemsCount);

        return () => {
            setGlobalUnreadCount(null);
        };
    }, [setGlobalUnreadCount, unreadItemsCount]);

    const isOnline = useOnline();

    const refreshOnClick = useCallback(
        (event) =>
            handleRefreshSource({
                event,
                source: currentSource,
                setLoadingState,
                setNavExpanded,
                reload,
            }),
        [currentSource, setLoadingState, setNavExpanded, reload],
    );

    const moreOnClick = useCallback(
        (event) => {
            event.preventDefault();
            const lastEntry = entries[entries.length - 1];

            // Calculate offset.
            setFromDatetime(lastEntry ? lastEntry.datetime : undefined);
            setFromId(lastEntry ? lastEntry.id : undefined);
        },
        [entries, setFromDatetime, setFromId],
    );

    // Current time for calculating relative dates in items.
    const [currentTime, setCurrentTime] = useState(() => new Date());
    useEffect(() => {
        const tick = window.setInterval(() => {
            setCurrentTime(new Date());
        }, 60 * 1000);

        return () => {
            clearInterval(tick);
        };
    }, []);

    const _ = useContext(LocalizationContext);

    if (loadingState === LoadingState.LOADING) {
        return <SpinnerBig label={_('entries_loading')} />;
    }

    return (
        <>
            {currentSource !== null && allowedToUpdate && isOnline ? (
                <button
                    type="button"
                    className="refresh-source"
                    onClick={refreshOnClick}
                >
                    {_('source_refresh')}
                </button>
            ) : null}
            {currentSource !== null && allowedToWrite && isOnline ? (
                <Link
                    to={{
                        pathname: '/manage/sources',
                        hash: `#source-${currentSource}`,
                    }}
                    className="entries-go-to-settings"
                >
                    {_('source_go_to_settings')}
                </Link>
            ) : null}
            {entries.map((entry) => (
                <Item
                    key={entry.id}
                    item={entry}
                    currentTime={currentTime}
                    selected={selectedEntry == entry.id}
                    expanded={expandedEntries[entry.id] ?? false}
                    setNavExpanded={setNavExpanded}
                />
            ))}
            <div id="stream-buttons">
                {loadingState === LoadingState.SUCCESS &&
                entries.length === 0 ? (
                    <p aria-live="assertive" className="stream-empty">
                        {_('no_entries')}
                    </p>
                ) : null}
                {hasMore ? (
                    <button
                        className={classNames({
                            'stream-button': true,
                            'stream-more': true,
                        })}
                        accessKey="m"
                        aria-label={_('more')}
                        onClick={
                            moreLoadingState !== LoadingState.LOADING
                                ? moreOnClick
                                : null
                        }
                    >
                        {moreLoadingState !== LoadingState.LOADING ? (
                            <span>{_('more')}</span>
                        ) : (
                            <Spinner size="3x" label={_('entries_loading')} />
                        )}
                    </button>
                ) : null}
                {entries.length > 0 ? (
                    <button
                        className="stream-button mark-these-read"
                        aria-label={_('markread')}
                        onClick={selfoss.entriesPage.markVisibleRead}
                    >
                        <span>{_('markread')}</span>
                    </button>
                ) : null}
                {loadingState == LoadingState.FAILURE ? (
                    <button
                        className="stream-button stream-error"
                        aria-live="assertive"
                        aria-label={_('streamerror')}
                        onClick={reload}
                    >
                        {_('streamerror')}
                    </button>
                ) : null}
            </div>
        </>
    );
}

const initialState = {
    entries: [],
    hasMore: false,
    selectedEntry: null,
    expandedEntries: {},
    loadingState: LoadingState.INITIAL,
};

type StateHolderProps = {
    configuration: object;
    location: object;
    navigate: NavigateFunction;
    params: object;
    setNavExpanded: React.Dispatch<React.SetStateAction<boolean>>;
    navSourcesExpanded: boolean;
    setGlobalUnreadCount: React.Dispatch<React.SetStateAction<number>>;
    unreadItemsCount: number;
};

type StateHolderState = {
    entries: Array<object>;
    hasMore: boolean;
    /**
     * Currently selected entry.
     * The id in the location.hash should imply the selected entry.
     * It will also be used for keyboard navigation (for finding previous/next).
     */
    selectedEntry: number | null;
    expandedEntries: {
        [index: number]: boolean;
    };
    loadingState: LoadingState;
};

export class StateHolder extends React.Component<
    StateHolderProps,
    StateHolderState
> {
    constructor(props: StateHolderProps) {
        super(props);
        this.state = initialState;

        this.reload = this.reload.bind(this);
        this.setLoadingState = this.setLoadingState.bind(this);
        this.activateEntry = this.activateEntry.bind(this);
        this.deactivateEntry = this.deactivateEntry.bind(this);
        this.markVisibleRead = this.markVisibleRead.bind(this);
        this.markEntryRead = this.markEntryRead.bind(this);
        this.markEntryStarred = this.markEntryStarred.bind(this);
        this.nextPrev = this.nextPrev.bind(this);
        this.entryNav = this.entryNav.bind(this);
        this.jumpToNext = this.jumpToNext.bind(this);
        this.toggleSelectedStarred = this.toggleSelectedStarred.bind(this);
        this.toggleSelectedRead = this.toggleSelectedRead.bind(this);
        this.toggleSelectedExpanded = this.toggleSelectedExpanded.bind(this);
        this.openSelectedTarget = this.openSelectedTarget.bind(this);
        this.openSelectedTargetAndMarkRead =
            this.openSelectedTargetAndMarkRead.bind(this);
        this.throw = this.throw.bind(this);
    }

    setEntries(entries) {
        if (typeof entries === 'function') {
            this.setState((state) => ({
                entries: entries(state.entries),
            }));
        } else {
            this.setState({ entries });
        }
    }

    appendEntries(extraEntries) {
        this.setEntries((entries) => [...entries, ...extraEntries]);
    }

    /**
     * Make the given entry currently selected one.
     */
    setSelectedEntry(selectedEntry: React.SetStateAction<number>): void {
        if (typeof selectedEntry === 'function') {
            this.setState((state) => ({
                selectedEntry: selectedEntry(state.selectedEntry),
            }));
        } else {
            this.setState({ selectedEntry });
        }
    }

    /**
     * Get the currently selected entry.
     */
    getSelectedEntry(): number {
        return this.state.selectedEntry;
    }

    setExpandedEntries(
        expandedEntries: React.SetStateAction<{ [index: number]: boolean }>,
    ): void {
        if (typeof expandedEntries === 'function') {
            this.setState((state) => ({
                expandedEntries: expandedEntries(state.expandedEntries),
            }));
        } else {
            this.setState({ expandedEntries });
        }
    }

    setEntryExpanded(id: number, expand: React.SetStateAction<boolean>): void {
        if (typeof expand === 'function') {
            this.setExpandedEntries((oldEntries) => ({
                ...oldEntries,
                [id]: expand(oldEntries[id] ?? false),
            }));
        } else {
            this.setExpandedEntries((oldEntries) => ({
                ...oldEntries,
                [id]: expand,
            }));
        }
    }

    /**
     * Collapse all expanded entries.
     */
    collapseAllEntries(): void {
        this.setExpandedEntries({});
    }

    /**
     * Is entry with given id expanded?
     */
    isEntryExpanded(id: number): boolean {
        return this.state.expandedEntries[id] ?? false;
    }

    /**
     * Toggle expanded state of entry with given id.
     */
    toggleEntryExpanded(id: number): void {
        if (!id) {
            return;
        }

        this.setEntryExpanded(id, (expanded) => !(expanded ?? false));
    }

    /**
     * Activate entry as if it were clicked.
     * This will open it, focus it and based on the settings, mark it as read.
     */
    activateEntry(id: number): void {
        if (this.props.configuration.autoCollapse) {
            this.collapseAllEntries();
        }

        this.setSelectedEntry(id);

        // show/hide (with toolbar)
        this.setEntryExpanded(id, true);

        // automark as read
        const entry = this.state.entries.find((entry) => id === entry.id);
        const autoMarkAsRead =
            selfoss.isAllowedToWrite() &&
            this.props.configuration.autoMarkAsRead &&
            entry.unread == 1;
        if (autoMarkAsRead) {
            this.markEntryRead(id, true);
        }
    }

    /**
     * Deactivate entry, as if it were clicked.
     * This will close it and maybe something more.
     */
    deactivateEntry(id: number): void {
        this.setEntryExpanded(id, false);
    }

    starEntryInView(id: number, starred: boolean): void {
        this.setEntries((entries) =>
            entries.map((entry) => {
                if (entry.id === id) {
                    return {
                        ...entry,
                        starred,
                    };
                } else {
                    return entry;
                }
            }),
        );
    }

    markEntryInView(id: number, unread: boolean): void {
        this.setEntries((entries) =>
            entries.map((entry) => {
                if (entry.id === id) {
                    return {
                        ...entry,
                        unread,
                    };
                } else {
                    return entry;
                }
            }),
        );
    }

    refreshEntryStatuses(entryStatuses) {
        this.state.entries.forEach((entry) => {
            const { id } = entry;
            const newStatus = entryStatuses.find(
                (entryStatus) => entryStatus.id == id,
            );
            if (newStatus) {
                this.starEntryInView(id, newStatus.starred);
                this.markEntryInView(id, newStatus.unread);
            }
        });
    }

    setHasMore(hasMore: React.SetStateAction<boolean>): void {
        if (typeof hasMore === 'function') {
            this.setState((state) => ({
                hasMore: hasMore(state.hasMore),
            }));
        } else {
            this.setState({ hasMore });
        }
    }

    setLoadingState(loadingState: React.SetStateAction<LoadingState>): void {
        if (typeof loadingState === 'function') {
            this.setState((state) => ({
                loadingState: loadingState(state.loadingState),
            }));
        } else {
            this.setState({ loadingState });
        }
    }

    getActiveTag(): string | null {
        const category = this.props.params?.category;
        if (!category) {
            return null;
        }
        return category.startsWith('tag-')
            ? category.replace(/^tag-/, '')
            : null;
    }

    getActiveSource(): number | null {
        const category = this.props.params?.category;
        if (!category) {
            return null;
        }
        return category.startsWith('source-')
            ? parseInt(category.replace(/^source-/, ''), 10)
            : null;
    }

    getActiveFilter(): string | null {
        return this.props.params?.filter;
    }

    /**
     * Mark all visible items as read
     */
    markVisibleRead(): void {
        const ids = [];
        const tagUnreadDiff = {};
        const sourceUnreadDiff = {};

        let markedEntries = this.state.entries.map((entry) => {
            if (!entry.unread) {
                return entry;
            }

            ids.push(entry.id);

            Object.keys(entry.tags).forEach((tag) => {
                if (Object.keys(tagUnreadDiff).includes(tag)) {
                    tagUnreadDiff[tag] += -1;
                } else {
                    tagUnreadDiff[tag] = -1;
                }
            });

            const { source } = entry;
            if (Object.keys(sourceUnreadDiff).includes(source.toString())) {
                sourceUnreadDiff[source] += -1;
            } else {
                sourceUnreadDiff[source] = -1;
            }

            return {
                ...entry,
                unread: false,
            };
        });
        const oldEntries = this.state.entries;
        const hadMore = this.state.hasMore;

        // close opened entry and list
        this.setExpandedEntries({});
        this.props.setNavExpanded(false);

        if (this.props.params.filter === FilterType.UNREAD) {
            markedEntries = [];
        }

        this.setLoadingState(LoadingState.LOADING);
        this.setEntries(markedEntries);

        // update statistics in main menu
        const updateStats = (markRead) => {
            // update all unread counters
            const unreadstats = selfoss.app.state.unreadItemsCount;
            const diff = markRead ? -1 : 1;

            selfoss.refreshUnread(unreadstats + diff * ids.length);

            // update unread on tags and sources
            if (markRead) {
                selfoss.app.refreshTagSourceUnread(
                    tagUnreadDiff,
                    sourceUnreadDiff,
                );
            } else {
                // Diffs are negative by default.
                selfoss.app.refreshTagSourceUnread(
                    Object.fromEntries(
                        Object.entries(tagUnreadDiff).map(([tag, diff]) => [
                            tag,
                            -1 * diff,
                        ]),
                    ),
                    Object.fromEntries(
                        Object.entries(sourceUnreadDiff).map(
                            ([source, diff]) => [source, -1 * diff],
                        ),
                    ),
                );
            }
        };
        updateStats(true);

        if (selfoss.db.enableOffline.value) {
            selfoss.dbOffline.entriesMark(ids, false);
        }

        itemsRequests
            .markAll(ids)
            .then(() => {
                this.setLoadingState(LoadingState.SUCCESS);
            })
            .catch((error) => {
                selfoss
                    .handleAjaxError(error)
                    .then(() => {
                        const statuses = ids.map((id) => ({
                            entryId: id,
                            name: 'unread',
                            value: false,
                        }));
                        selfoss.dbOffline.enqueueStatuses(statuses);
                    })
                    .catch((error) => {
                        if (
                            error instanceof HttpError &&
                            error.response.status === 403
                        ) {
                            this.props.navigate('/sign/in', {
                                state: {
                                    error: selfoss.app._(
                                        'error_session_expired',
                                    ),
                                },
                            });
                            return;
                        }

                        this.setLoadingState(LoadingState.SUCCESS);
                        this.setEntries(oldEntries);
                        updateStats(false);
                        this.setHasMore(hadMore);
                        selfoss.app.showError(
                            selfoss.app._('error_mark_items') +
                                ' ' +
                                error.message,
                        );
                    });
            });
    }

    /**
     * Requests for an entry to be marked read/unread in the model.
     */
    markEntryRead(id: number, markRead: boolean | 'toggle'): void {
        // only loggedin users
        if (!selfoss.isAllowedToWrite()) {
            console.log('User not allowed to mark an entry (un)read.');
            return;
        }

        const entry = this.state.entries.find((entry) => id === entry.id);
        if (markRead === 'toggle') {
            markRead = entry.unread;
        }

        this.markEntryInView(id, !markRead);

        // update statistics in main menue and the currently active tag
        const updateStats = (markRead) => {
            // update all unread counters
            const unreadstats = selfoss.app.state.unreadItemsCount;
            const diff = markRead ? -1 : 1;

            selfoss.refreshUnread(unreadstats + diff);

            // update unread on tags and sources
            // Only a single instance of each tag per entry so we can just assign.
            const entryTags = Object.fromEntries(
                Object.keys(entry.tags).map((tag) => [tag, diff]),
            );
            selfoss.app.refreshTagSourceUnread(entryTags, {
                [entry.source]: diff,
            });
        };
        updateStats(markRead);

        if (selfoss.db.enableOffline.value) {
            selfoss.dbOffline.entryMark(id, !markRead);
        }

        itemsRequests
            .mark(id, !markRead)
            .then(() => {
                selfoss.db.setOnline();
            })
            .catch((error) => {
                selfoss
                    .handleAjaxError(error)
                    .then(() => {
                        selfoss.dbOffline.enqueueStatus(
                            id,
                            'unread',
                            !markRead,
                        );
                    })
                    .catch((error) => {
                        if (
                            error instanceof HttpError &&
                            error.response.status === 403
                        ) {
                            this.props.navigate('/sign/in', {
                                state: {
                                    error: selfoss.app._(
                                        'error_session_expired',
                                    ),
                                },
                            });
                            return;
                        }

                        // rollback ui changes
                        this.markEntryInView(id, markRead);
                        updateStats(!markRead);
                        selfoss.app.showError(
                            selfoss.app._('error_mark_item') +
                                ' ' +
                                error.message,
                        );
                    });
            });
    }

    /**
     * Requests for an entry to be marked (un)starred in the model.
     */
    markEntryStarred(id: number, markStarred: boolean | 'toggle'): void {
        // only loggedin users
        if (!selfoss.isAllowedToWrite()) {
            console.log('User not allowed to (un)star an entry.');
            return;
        }

        if (markStarred === 'toggle') {
            const entry = this.state.entries.find((entry) => id === entry.id);
            markStarred = !entry.starred;
        }

        this.starEntryInView(id, markStarred);

        // update statistics in main menu
        const updateStats = (markStarred) => {
            selfoss.app.setStarredItemsCount(
                (starred) => starred + (markStarred ? 1 : -1),
            );
        };
        updateStats(markStarred);

        if (selfoss.db.enableOffline.value) {
            selfoss.dbOffline.entryStar(id, markStarred);
        }

        itemsRequests
            .starr(id, markStarred)
            .then(() => {
                selfoss.db.setOnline();
            })
            .catch((error) => {
                selfoss
                    .handleAjaxError(error)
                    .then(() => {
                        selfoss.dbOffline.enqueueStatus(
                            id,
                            'starred',
                            markStarred,
                        );
                    })
                    .catch((error) => {
                        if (
                            error instanceof HttpError &&
                            error.response.status === 403
                        ) {
                            this.props.navigate('/sign/in', {
                                state: {
                                    error: selfoss.app._(
                                        'error_session_expired',
                                    ),
                                },
                            });
                            return;
                        }

                        // rollback ui changes
                        this.starEntryInView(id, !markStarred);
                        updateStats(!markStarred);
                        selfoss.app.showError(
                            selfoss.app._('error_star_item') +
                                ' ' +
                                error.message,
                        );
                    });
            });
    }

    reload(): void {
        /**
         * HACK: A counter that is increased every time reload action (r key) is triggered.
         */
        this.props.navigate(
            makeEntriesLinkLocation(this.props.location, { id: null }),
            {
                replace: true,
                state: forceReload(this.props.location),
            },
        );
    }

    /**
     * get next/prev item
     */
    nextPrev(direction: Direction, open: boolean = true): void {
        if (direction != Direction.NEXT && direction != Direction.PREV) {
            throw new Error('direction must be one of Direction.{PREV,NEXT}');
        }

        // when there are no entries
        if (this.state.entries.length == 0) {
            return;
        }

        // select current
        const old = this.getSelectedEntry();
        const oldIndex =
            old !== null
                ? this.state.entries.findIndex(({ id }) => id === old)
                : null;
        let current = null;

        // select next/prev entry and save it to "current"
        // if we would overflow, we stay on the old one
        if (direction == Direction.NEXT) {
            if (old === null) {
                current = this.state.entries[0].id;
            } else {
                const nextIndex = oldIndex + 1;
                if (nextIndex >= this.state.entries.length) {
                    current = old;

                    // attempt to load more
                    document.querySelector('.stream-more')?.click();
                } else {
                    current = this.state.entries[nextIndex].id;
                }
            }
        } else {
            if (old === null) {
                return;
            } else {
                if (oldIndex <= 0) {
                    current = old;
                } else {
                    current = this.state.entries[oldIndex - 1].id;
                }
            }
        }

        if (old !== current) {
            // remove active
            this.deactivateEntry(old);

            if (open) {
                this.activateEntry(current);
            } else {
                this.setSelectedEntry(current);
            }

            const currentElement = document.querySelector(
                `.entry[data-entry-id="${current}"]`,
            );

            // scroll to element
            autoScroll(currentElement);

            // focus the title link for better keyboard navigation
            currentElement.querySelector('.entry-title-link').focus();
        }
    }

    /**
     * entry navigation (next/prev) with keys
     */
    entryNav(direction: Direction): void {
        if (direction != Direction.NEXT && direction != Direction.PREV) {
            throw new Error('direction must be one of Direction.{PREV,NEXT}');
        }

        const open = this.isEntryExpanded(this.getSelectedEntry());
        this.nextPrev(direction, open);
    }

    jumpToNext(): void {
        const selected = this.getSelectedEntry();
        if (selected !== null && !this.isEntryExpanded(selected)) {
            this.activateEntry(selected);
        } else {
            this.nextPrev(Direction.NEXT, true);
        }
    }

    toggleSelectedStarred(): void {
        const selected = this.getSelectedEntry();

        if (selected !== null) {
            this.markEntryStarred(selected, 'toggle');
        }
    }

    toggleSelectedRead(): void {
        const selected = this.getSelectedEntry();

        if (selected !== null) {
            this.markEntryRead(selected, 'toggle');
        }
    }

    toggleSelectedExpanded(): void {
        this.toggleEntryExpanded(this.getSelectedEntry());
    }

    openSelectedTarget(): void {
        const selected = this.getSelectedEntry();

        if (selected !== null) {
            openSelectedArticle(selected);
        }
    }

    openSelectedTargetAndMarkRead(): void {
        const selected = this.getSelectedEntry();

        if (selected !== null) {
            this.markEntryRead(selected, true);
            openSelectedArticle(selected);
        }
    }

    throw(direction: Direction): void {
        const selected = this.getSelectedEntry();

        if (selected !== null) {
            this.markEntryRead(selected, true);
        }

        this.nextPrev(direction, true);
    }

    render() {
        return (
            <EntriesPage
                entries={this.state.entries}
                selectedEntry={this.state.selectedEntry}
                expandedEntries={this.state.expandedEntries}
                hasMore={this.state.hasMore}
                loadingState={this.state.loadingState}
                setLoadingState={this.setLoadingState}
                setNavExpanded={this.props.setNavExpanded}
                navSourcesExpanded={this.props.navSourcesExpanded}
                reload={this.reload}
                setGlobalUnreadCount={this.props.setGlobalUnreadCount}
                unreadItemsCount={this.props.unreadItemsCount}
            />
        );
    }
}

type StateHolderOuterProps = {
    configuration: Configuration;
    setNavExpanded: React.Dispatch<React.SetStateAction<boolean>>;
    navSourcesExpanded: boolean;
    setGlobalUnreadCount: React.Dispatch<React.SetStateAction<number>>;
    unreadItemsCount: number;
};

const StateHolderOuter = forwardRef(function StateHolderOuter(
    props: StateHolderOuterProps,
    ref,
) {
    const {
        configuration,
        setNavExpanded,
        navSourcesExpanded,
        setGlobalUnreadCount,
        unreadItemsCount,
    } = props;
    const location = useLocation();
    const navigate = useNavigate();
    const params = useParams();

    return (
        <StateHolder
            ref={ref}
            location={location}
            navigate={navigate}
            params={params}
            configuration={configuration}
            setNavExpanded={setNavExpanded}
            navSourcesExpanded={navSourcesExpanded}
            setGlobalUnreadCount={setGlobalUnreadCount}
            unreadItemsCount={unreadItemsCount}
        />
    );
});

export default StateHolderOuter;
