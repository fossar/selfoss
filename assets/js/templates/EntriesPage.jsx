import React from 'react';
import ReactDOM from 'react-dom';
import Item from './Item';
import * as sourceRequests from '../requests/sources';
import { LoadingState } from '../requests/LoadingState';
import Spinner from './Spinner';
import classNames from 'classnames';


// updates a source
function handleRefreshSource({ event, setLoadingState }) {
    event.preventDefault();

    // show loading
    setLoadingState(LoadingState.LOADING);

    sourceRequests.refreshSingle(selfoss.filter.source).then(() => {
        // hide nav on smartphone
        if (selfoss.isSmartphone()) {
            $('#nav-mobile-settings').click();
        }

        // refresh list
        // Will also clear the loading status.
        selfoss.db.reloadList();
    }).catch((error) => {
        alert(selfoss.ui._('error_refreshing_source') + ' ' + error.message);
    });
}

function loadMore({ event, entries }) {
    event.preventDefault();

    const lastEntry = entries[entries.length - 1];
    selfoss.events.setHash();
    selfoss.filter.update({
        extraIds: [],
        fromDatetime: new Date(lastEntry.datetime),
        fromId: lastEntry.id
    });

    selfoss.db.reloadList(true);
}

export function EntriesPage({ entries, hasMore, loadingState, setLoadingState, selectedEntry, expandedEntries }) {
    const firstPage = typeof selfoss.filter.fromId === 'undefined' && typeof selfoss.filter.fromDatetime === 'undefined';
    const allowedToUpdate = !selfoss.config.authEnabled || selfoss.config.allowPublicUpdate || selfoss.loggedin.value;

    // TODO: make this update when it changes
    const isOnline = selfoss.db.online;

    return (
        <React.Fragment>
            {loadingState === LoadingState.LOADING ? <Spinner /> : null}
            {selfoss.filter.source && allowedToUpdate && firstPage && isOnline ?
                <button
                    type="button"
                    className="refresh-source"
                    onClick={(event) => handleRefreshSource({ event, setLoadingState })}
                >
                    {selfoss.ui._('source_refresh')}
                </button>
                : null
            }
            {entries.map((entry) => (
                <Item
                    key={entry.id}
                    item={entry}
                    selected={selectedEntry == entry.id}
                    expanded={expandedEntries[entry.id] ?? false}
                />
            ))}
            <div id="stream-buttons">
                {entries.length === 0 ?
                    <p aria-live="assertive" className="stream-empty">{selfoss.ui._('no_entries')}</p>
                    : null}
                {hasMore ?
                    <button
                        className={classNames({'stream-button': true, 'stream-more': true})}
                        accessKey="m"
                        aria-label={selfoss.ui._('more')}
                        onClick={(event) => loadMore({ event, entries })}
                    >
                        <span>{selfoss.ui._('more')}</span>
                    </button>
                    : null}
                {entries.length > 0 ?
                    <button
                        className="stream-button mark-these-read"
                        aria-label={selfoss.ui._('markread')}
                        onClick={selfoss.markVisibleRead}
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
                        onClick={selfoss.db.reloadList}
                    >
                        {selfoss.ui._('streamerror')}
                    </button>
                    : null}
            </div>
        </React.Fragment>
    );
}

export class StateHolder extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            entries: [],
            hasMore: false,
            /**
             * Currently selected entry.
             * The id in the location.hash should imply the selected entry.
             * It will also be used for keyboard navigation (for finding previous/next).
             */
            selectedEntry: null,
            expandedEntries: {},
            loadingState: LoadingState.INITIAL
        };
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

    render() {
        return (
            <EntriesPage
                entries={this.state.entries}
                selectedEntry={this.state.selectedEntry}
                expandedEntries={this.state.expandedEntries}
                hasMore={this.state.hasMore}
                loadingState={this.state.loadingState}
                setLoadingState={this.setLoadingState.bind(this)}
            />
        );
    }
}

export function anchor(element) {
    return ReactDOM.render(<StateHolder />, element);
}
