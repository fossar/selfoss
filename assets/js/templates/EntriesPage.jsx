import React from 'react';
import { useRouteMatch, useLocation } from 'react-router-dom';
import Item from './Item';
import { FilterType } from '../Filter';
import * as itemsRequests from '../requests/items';
import * as sourceRequests from '../requests/sources';
import { LoadingState } from '../requests/LoadingState';
import { Spinner, SpinnerBig } from './Spinner';
import classNames from 'classnames';

function reloadList({ fetchParams, append = false, waitForSync = true, entryId = null, setLoadingState = selfoss.entriesPage.setLoadingState }) {
    if (entryId && fetchParams.fromId === undefined) {
        fetchParams = {
            ...fetchParams,
            extraIds: [...fetchParams.extraIds, entryId]
        };
    }

    if (!append || fetchParams.type !== FilterType.NEWEST) {
        selfoss.dbOffline.olderEntriesOnline = false;
    }

    setLoadingState(LoadingState.LOADING);

    var reload = () => {
        let reloader = selfoss.dbOffline.reloadList;

        // tag, source and search filtering not supported offline (yet?)
        if (fetchParams.tag || fetchParams.source || fetchParams.search) {
            reloader = selfoss.dbOnline.reloadList;
        }

        var forceLoadOnline = selfoss.dbOffline.olderEntriesOnline || selfoss.dbOffline.shouldLoadEntriesOnline;
        if (!selfoss.db.enableOffline.value || (selfoss.db.online && forceLoadOnline)) {
            reloader = selfoss.dbOnline.reloadList;
        }

        // Clean state when not just adding items.
        if (!append) {
            selfoss.entriesPage.setHasMore(false);
            selfoss.entriesPage.setExpandedEntries({});
            selfoss.entriesPage.setEntries([]);
            selfoss.entriesPage.setSelectedEntry(null);
        }

        setLoadingState(LoadingState.LOADING);
        reloader(fetchParams).then(({ entries, hasMore }) => {
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
                    var entry = document.querySelector(`.entry[data-entry-id="${entryId}"]`);

                    if (!entry) {
                        return;
                    }

                    selfoss.ui.entryActivate(entryId);
                    // ensure scrolling to requested entry even if scrolling to article
                    // header is disabled
                    if (!selfoss.config.scrollToArticleHeader) {
                        // needs to be delayed for some reason
                        requestAnimationFrame(() => {
                            entry.scrollIntoView();
                        });
                    }
                } else {
                    window.scrollTo({ top: 0 });
                }
            }

        }).catch((error) => {
            setLoadingState(LoadingState.FAILURE);
            selfoss.ui.showError(selfoss.ui._('error_loading') + ' ' + error.message);
        });
    };

    if (waitForSync && selfoss.dbOnline.syncing.promise) {
        selfoss.db.userWaiting = true;
        selfoss.dbOnline.syncing.promise.finally(reload);
    } else {
        reload();
    }
}

// updates a source
function handleRefreshSource({ event, fetchParams, setLoadingState, setNavExpanded }) {
    event.preventDefault();

    // show loading
    setLoadingState(LoadingState.LOADING);

    sourceRequests.refreshSingle(fetchParams.source).then(() => {
        // hide nav on smartphone
        setNavExpanded(false);

        // Fetch the new items and reload the list.
        // Will also clear the loading status.
        reloadList({ fetchParams });
    }).catch((error) => {
        alert(selfoss.ui._('error_refreshing_source') + ' ' + error.message);
    });
}

function loadMore({ event, fetchParams, entries, setMoreLoadingState }) {
    event.preventDefault();
    const lastEntry = entries[entries.length - 1];

    fetchParams = {
        ...fetchParams,
        // Calculate offset.
        fromDatetime: lastEntry ? lastEntry.datetime : undefined,
        fromId: lastEntry ? lastEntry.id : undefined
    };

    reloadList({
        fetchParams,
        append: true,
        setLoadingState: setMoreLoadingState
    });
}

export function EntriesPage({ entries, hasMore, loadingState, setLoadingState, selectedEntry, expandedEntries, setNavExpanded, shouldUpdateItems, setShouldUpdateItems }) {
    const allowedToUpdate = !selfoss.config.authEnabled || selfoss.config.allowPublicUpdate || selfoss.loggedin.value;

    const location = useLocation();
    const searchText = React.useMemo(() => {
        const queryString = new URLSearchParams(location.search);

        return queryString.get('search') ?? '';
    }, [location.search]);

    const [navSourcesExpanded, setNavSourcesExpanded] = React.useState(selfoss.navSourcesExpanded.value);

    const { params } = useRouteMatch();
    const currentTag = params.category?.startsWith('tag-') ? params.category.replace(/^tag-/, '') : null;
    const currentSource = params.category?.startsWith('source-') ? parseInt(params.category.replace(/^source-/, ''), 10) : null;

    // Object with parameters for GET /items and similar API calls
    // based on the current location.
    const fetchParams = React.useMemo(
        () => ({
            type: params.filter,
            tag: currentTag,
            source: currentSource,
            extraIds: [],
            sourcesNav: navSourcesExpanded,
            search: searchText
        }),
        [params.filter, currentTag, currentSource, navSourcesExpanded, searchText]
    );

    const [moreLoadingState, setMoreLoadingState] = React.useState(LoadingState.INITIAL);

    // Schedule fetching data and reloading the list when one of the critical parameters changes.
    // We ignore the change when only id changes since that happens when reading.
    React.useEffect(() => {
        setShouldUpdateItems(true);
    }, [fetchParams.type, fetchParams.tag, fetchParams.source, fetchParams.search]);

    // Perform the scheduled reload.
    React.useEffect(() => {
        if (!shouldUpdateItems) {
            return;
        }

        reloadList({
            fetchParams,
            // We do not want to focus the entry on successive loads.
            entryId: loadingState == LoadingState.INITIAL ? params.id : undefined
        });
        setShouldUpdateItems(false);

        return () => {
            if (selfoss.activeAjaxReq !== null) {
                selfoss.activeAjaxReq.controller.abort();
            }
        };
    }, [shouldUpdateItems]);

    React.useEffect(() => {
        const navSourcesExpandedListener = (event) => {
            setNavSourcesExpanded(event.value);
        };

        // It might happen that values change between creating the component and setting up the event handlers.
        navSourcesExpandedListener({ value: selfoss.navSourcesExpanded.value });

        selfoss.navSourcesExpanded.addEventListener('change', navSourcesExpandedListener);

        return () => {
            selfoss.navSourcesExpanded.removeEventListener('change', navSourcesExpandedListener);
        };
    }, []);

    React.useEffect(() => {
        // scroll load more
        function onScroll() {
            const streamMoreButton = document.querySelector('.stream-more');
            if (!streamMoreButton) {
                return;
            }

            const streamMoreButtonTop = window.scrollY + streamMoreButton.getBoundingClientRect().top;

            // When “More” button appears on the screen, click it.
            if (streamMoreButtonTop < document.body.clientHeight + window.scrollY) {
                streamMoreButton.click();
            }
        }

        if (hasMore && moreLoadingState !== LoadingState.LOADING && selfoss.config.autoStreamMore) {
            window.addEventListener('scroll', onScroll);

            return () => {
                window.removeEventListener('scroll', onScroll);
            };
        }
    }, [hasMore, moreLoadingState]);

    // TODO: make this update when it changes
    const isOnline = selfoss.db.online;

    const refreshOnClick = React.useCallback(
        (event) => handleRefreshSource({ event, fetchParams, setLoadingState, setNavExpanded }),
        [fetchParams, setLoadingState, setNavExpanded]
    );

    const moreOnClick = React.useCallback(
        (event) => loadMore({ event, fetchParams, entries, setMoreLoadingState }),
        [fetchParams, entries]
    );

    const errorOnClick = React.useCallback(
        () => reloadList({ fetchParams }),
        [fetchParams]
    );

    // Current time for calculating relative dates in items.
    const [currentTime, setCurrentTime] = React.useState(null);
    React.useEffect(() => {
        setCurrentTime(new Date());

        const tick = window.setInterval(() => {
            setCurrentTime(new Date());
        }, 60 * 1000);

        return () => {
            clearInterval(tick);
        };
    }, []);

    return (
        <React.Fragment>
            {loadingState === LoadingState.LOADING ? <SpinnerBig /> : null}
            {currentSource !== null && allowedToUpdate && isOnline ?
                <button
                    type="button"
                    className="refresh-source"
                    onClick={refreshOnClick}
                >
                    {selfoss.ui._('source_refresh')}
                </button>
                : null
            }
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
                {loadingState === LoadingState.SUCCESS && entries.length === 0 ?
                    <p aria-live="assertive" className="stream-empty">{selfoss.ui._('no_entries')}</p>
                    : null}
                {hasMore ?
                    <button
                        className={classNames({'stream-button': true, 'stream-more': true})}
                        accessKey="m"
                        aria-label={selfoss.ui._('more')}
                        onClick={moreLoadingState !== LoadingState.LOADING ? moreOnClick : null}
                    >
                        {moreLoadingState !== LoadingState.LOADING ? <span>{selfoss.ui._('more')}</span> : <Spinner size="3x" />}
                    </button>
                    : null}
                {entries.length > 0 ?
                    <button
                        className="stream-button mark-these-read"
                        aria-label={selfoss.ui._('markread')}
                        onClick={selfoss.entriesPage.markVisibleRead}
                    >
                        <span>{selfoss.ui._('markread')}</span>
                    </button>
                    : null
                }
                {loadingState == LoadingState.FAILURE ?
                    <button
                        className="stream-button stream-error"
                        aria-live="assertive"
                        aria-label={selfoss.ui._('streamerror')}
                        onClick={errorOnClick}
                    >
                        {selfoss.ui._('streamerror')}
                    </button>
                    : null}
            </div>
        </React.Fragment>
    );
}

const initialState = {
    entries: [],
    hasMore: false,
    /**
     * Currently selected entry.
     * The id in the location.hash should imply the selected entry.
     * It will also be used for keyboard navigation (for finding previous/next).
     */
    selectedEntry: null,
    expandedEntries: {},
    shouldUpdateItems: true,
    loadingState: LoadingState.INITIAL
};

export default class StateHolder extends React.Component {
    constructor(props) {
        super(props);
        this.state = initialState;

        this.setLoadingState = this.setLoadingState.bind(this);
        this.setShouldUpdateItems = this.setShouldUpdateItems.bind(this);
        this.markVisibleRead = this.markVisibleRead.bind(this);
    }

    setEntries(entries) {
        if (typeof entries === 'function') {
            this.setState({ entries: entries(this.state.entries) });
        } else {
            this.setState({ entries });
        }
    }

    appendEntries(extraEntries) {
        this.setEntries((entries) => [...entries, ...extraEntries]);
    }

    setSelectedEntry(selectedEntry) {
        if (typeof selectedEntry === 'function') {
            this.setState({ selectedEntry: selectedEntry(this.state.selectedEntry) });
        } else {
            this.setState({ selectedEntry });
        }
    }

    setExpandedEntries(expandedEntries) {
        if (typeof expandedEntries === 'function') {
            this.setState({
                expandedEntries: expandedEntries(this.state.expandedEntries)
            });
        } else {
            this.setState({ expandedEntries });
        }
    }

    setEntryExpanded(id, expand) {
        if (typeof expand === 'function') {
            this.setExpandedEntries((oldEntries) => ({
                ...oldEntries,
                [id]: expand(oldEntries[id] ?? false)
            }));
        } else {
            this.setExpandedEntries((oldEntries) => ({
                ...oldEntries,
                [id]: expand
            }));
        }
    }

    setHasMore(hasMore) {
        if (typeof hasMore === 'function') {
            this.setState({ hasMore: hasMore(this.state.hasMore) });
        } else {
            this.setState({ hasMore });
        }
    }

    setLoadingState(loadingState) {
        if (typeof loadingState === 'function') {
            this.setState({ loadingState: loadingState(this.state.loadingState) });
        } else {
            this.setState({ loadingState });
        }
    }

    setShouldUpdateItems(shouldUpdateItems) {
        this.setState({ shouldUpdateItems });
    }

    getActiveTag() {
        if (!this.props.match) {
            return null;
        }
        const { params } = this.props.match;
        return params.category?.startsWith('tag-') ? params.category.replace(/^tag-/, '') : null;
    }

    getActiveSource() {
        if (!this.props.match) {
            return null;
        }
        const { params } = this.props.match;
        return params.category?.startsWith('source-') ? parseInt(params.category.replace(/^source-/, ''), 10) : null;
    }

    getActiveFilter() {
        if (!this.props.match) {
            return null;
        }
        return this.props.match.params.filter;
    }

    /**
     * Mark all visible items as read
     */
    markVisibleRead() {
        let ids = [];
        let tagUnreadDiff = {};
        let sourceUnreadDiff = [];

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
            if (Object.keys(sourceUnreadDiff).includes(source)) {
                sourceUnreadDiff[source] += -1;
            } else {
                sourceUnreadDiff[source] = -1;
            }

            return {
                ...entry,
                unread: false
            };
        });
        const oldEntries = this.state.entries;
        const hadMore = this.state.hasMore;

        // close opened entry and list
        this.setExpandedEntries({});

        if (ids.length !== 0 && this.props.match.filter === FilterType.UNREAD) {
            markedEntries = markedEntries.filter(({ id }) => ids.includes(id));
        }

        this.setLoadingState(LoadingState.LOADING);
        this.setEntries(markedEntries);

        const unreadstats = selfoss.unreadItemsCount.value - ids.length;

        if (selfoss.db.enableOffline.value) {
            selfoss.refreshUnread(unreadstats);
            selfoss.dbOffline.entriesMark(ids, false);
        }

        itemsRequests.markAll(ids).then(() => {
            this.setLoadingState(LoadingState.SUCCESS);
        }).catch((error) => {
            selfoss.handleAjaxError(error).then(() => {
                let statuses = ids.map((id) => ({
                    entryId: id,
                    name: 'unread',
                    value: false
                }));
                selfoss.dbOffline.enqueueStatuses(statuses);
            }).catch((error) => {
                this.setLoadingState(LoadingState.SUCCESS);
                this.setEntries(oldEntries);
                this.setHasMore(hadMore);
                selfoss.ui.showError(selfoss.ui._('error_mark_items') + ' ' + error.message);
            });
        });
    }

    reloadList() {
        this.setState(initialState);
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
                shouldUpdateItems={this.state.shouldUpdateItems}
                setShouldUpdateItems={this.setShouldUpdateItems}
                setNavExpanded={this.props.setNavExpanded}
            />
        );
    }
}
