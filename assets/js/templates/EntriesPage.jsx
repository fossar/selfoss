import React from 'react';
import PropTypes from 'prop-types';
import { useRouteMatch, useLocation } from 'react-router-dom';
import nullable from 'prop-types-nullable';
import Item from './Item';
import { FilterType } from '../Filter';
import * as itemsRequests from '../requests/items';
import * as sourceRequests from '../requests/sources';
import { LoadingState } from '../requests/LoadingState';
import { Spinner, SpinnerBig } from './Spinner';
import classNames from 'classnames';
import { LocalizationContext } from '../helpers/i18n';

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
        return reloader(fetchParams).then(({ entries, hasMore }) => {
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

                    selfoss.entriesPage.activateEntry(entryId);
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
            selfoss.app.showError(selfoss.app._('error_loading') + ' ' + error.message);
        });
    };

    if (waitForSync && selfoss.dbOnline.syncing.promise) {
        selfoss.db.userWaiting = true;
        return selfoss.dbOnline.syncing.promise.finally(reload);
    } else {
        return reload();
    }
}

// updates a source
function handleRefreshSource({ event, fetchParams, setLoadingState, setNavExpanded, reload }) {
    event.preventDefault();

    // show loading
    setLoadingState(LoadingState.LOADING);

    return sourceRequests.refreshSingle(fetchParams.source).then(() => {
        // hide nav on smartphone
        setNavExpanded(false);

        // Fetch the new items and reload the list.
        // Will also clear the loading status.
        reload();
    }).catch((error) => {
        alert(selfoss.app._('error_refreshing_source') + ' ' + error.message);
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

export function EntriesPage({ entries, hasMore, loadingState, setLoadingState, selectedEntry, expandedEntries, setNavExpanded, shouldUpdateItems, setShouldUpdateItems, navSourcesExpanded, reload }) {
    const allowedToUpdate = !selfoss.config.authEnabled || selfoss.config.allowPublicUpdate || selfoss.loggedin.value;

    const location = useLocation();
    const searchText = React.useMemo(() => {
        const queryString = new URLSearchParams(location.search);

        return queryString.get('search') ?? '';
    }, [location.search]);

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
        }).then(() => {
            if (fetchParams.tag !== null && !selfoss.db.isValidTag(fetchParams.tag)) {
                selfoss.app.showError(selfoss.app._('error_unknown_tag') + ' ' + fetchParams.tag);
            }

            if (fetchParams.source !== null && !selfoss.db.isValidSource(fetchParams.source)) {
                selfoss.app.showError(selfoss.app._('error_unknown_source') + ' ' + fetchParams.source);
            }
        });
        setShouldUpdateItems(false);

        return () => {
            if (selfoss.activeAjaxReq !== null) {
                selfoss.activeAjaxReq.controller.abort();
            }
        };
    }, [shouldUpdateItems]);

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
        (event) => handleRefreshSource({ event, fetchParams, setLoadingState, setNavExpanded, reload }),
        [fetchParams, setLoadingState, setNavExpanded, reload]
    );

    const moreOnClick = React.useCallback(
        (event) => loadMore({ event, fetchParams, entries, setMoreLoadingState }),
        [fetchParams, entries]
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

    const _ = React.useContext(LocalizationContext);

    if (loadingState === LoadingState.LOADING) {
        return (
            <SpinnerBig />
        );
    }

    return (
        <React.Fragment>
            {currentSource !== null && allowedToUpdate && isOnline ?
                <button
                    type="button"
                    className="refresh-source"
                    onClick={refreshOnClick}
                >
                    {_('source_refresh')}
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
                    <p aria-live="assertive" className="stream-empty">{_('no_entries')}</p>
                    : null}
                {hasMore ?
                    <button
                        className={classNames({'stream-button': true, 'stream-more': true})}
                        accessKey="m"
                        aria-label={_('more')}
                        onClick={moreLoadingState !== LoadingState.LOADING ? moreOnClick : null}
                    >
                        {moreLoadingState !== LoadingState.LOADING ? <span>{_('more')}</span> : <Spinner size="3x" />}
                    </button>
                    : null}
                {entries.length > 0 ?
                    <button
                        className="stream-button mark-these-read"
                        aria-label={_('markread')}
                        onClick={selfoss.entriesPage.markVisibleRead}
                    >
                        <span>{_('markread')}</span>
                    </button>
                    : null
                }
                {loadingState == LoadingState.FAILURE ?
                    <button
                        className="stream-button stream-error"
                        aria-live="assertive"
                        aria-label={_('streamerror')}
                        onClick={reload}
                    >
                        {_('streamerror')}
                    </button>
                    : null}
            </div>
        </React.Fragment>
    );
}

EntriesPage.propTypes = {
    entries: PropTypes.array.isRequired,
    hasMore: PropTypes.bool.isRequired,
    loadingState: PropTypes.oneOf(Object.values(LoadingState)).isRequired,
    setLoadingState: PropTypes.func.isRequired,
    selectedEntry: nullable(PropTypes.number).isRequired,
    expandedEntries: PropTypes.objectOf(PropTypes.bool).isRequired,
    setNavExpanded: PropTypes.func.isRequired,
    shouldUpdateItems: PropTypes.bool.isRequired,
    setShouldUpdateItems: PropTypes.func.isRequired,
    navSourcesExpanded: PropTypes.bool.isRequired,
    reload: PropTypes.func.isRequired,
};

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

        this.reload = this.reload.bind(this);
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

    /**
     * Make the given entry currently selected one.
     * @param {number|function(number): number} id of entry to select, or a function that transforms a previous id into a new one
     */
    setSelectedEntry(selectedEntry) {
        if (typeof selectedEntry === 'function') {
            this.setState({ selectedEntry: selectedEntry(this.state.selectedEntry) });
        } else {
            this.setState({ selectedEntry });
        }
    }

    /**
     * Get the currently selected entry.
     * @return {number}
     */
    getSelectedEntry() {
        return this.state.selectedEntry;
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


    /**
     * Collapse all expanded entries.
     */
    collapseAllEntries() {
        this.setExpandedEntries({});
    }


    /**
     * Is given entry expanded?
     * @param {number} id of entry to check
     * @return {bool} whether it is expanded
     */
    isEntryExpanded(entry) {
        return this.state.expandedEntries[entry] ?? false;
    }


    /**
     * Toggle expanded state of given entry.
     * @param {number} id of entry to toggle
     */
    toggleEntryExpanded(entry) {
        if (!entry) {
            return;
        }

        this.setEntryExpanded(entry, (expanded) => !(expanded ?? false));
    }


    /**
     * Activate entry as if it were clicked.
     * This will open it, focus it and based on the settings, mark it as read.
     * @param {number} id of entry
     */
    activateEntry(id) {
        const entry = document.querySelector(`.entry[data-entry-id="${id}"]`);

        if (!this.isEntryExpanded(id)) {
            entry.querySelector('.entry-title > .entry-title-link').click();
        }
    }


    /**
     * Deactivate entry, as if it were clicked.
     * This will close it and maybe something more.
     * @param {number} id of entry
     */
    deactivateEntry(id) {
        const entry = document.querySelector(`.entry[data-entry-id="${id}"]`);

        if (this.isEntryExpanded(id)) {
            entry.querySelector('.entry-title > .entry-title-link').click();
        }
    }


    starEntry(id, starred) {
        this.setEntries((entries) =>
            entries.map((entry) => {
                if (entry.id === id) {
                    return {
                        ...entry,
                        starred
                    };
                } else {
                    return entry;
                }
            })
        );
    }


    markEntry(id, unread) {
        this.setEntries((entries) =>
            entries.map((entry) => {
                if (entry.id === id) {
                    return {
                        ...entry,
                        unread
                    };
                } else {
                    return entry;
                }
            })
        );
    }


    refreshEntryStatuses(entryStatuses) {
        this.state.entries.forEach((entry) => {
            const { id } = entry;
            var newStatus = false;
            entryStatuses.some(function(entryStatus) {
                if (entryStatus.id == id) {
                    newStatus = entryStatus;
                }
                return newStatus;
            });
            if (newStatus) {
                this.starEntry(id, newStatus.starred);
                this.markEntry(id, newStatus.unread);
            }
        });
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

        const unreadstats = selfoss.app.state.unreadItemsCount - ids.length;

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
                selfoss.app.showError(selfoss.app._('error_mark_items') + ' ' + error.message);
            });
        });
    }

    reload() {
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
                navSourcesExpanded={this.props.navSourcesExpanded}
                reload={this.reload}
            />
        );
    }
}

StateHolder.propTypes = {
    match: PropTypes.object.isRequired,
    setNavExpanded: PropTypes.func.isRequired,
    navSourcesExpanded: PropTypes.bool.isRequired,
};
