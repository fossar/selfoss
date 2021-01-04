import React from 'react';
import ReactDOM from 'react-dom';
import classNames from 'classnames';
import { FilterType } from '../Filter';
import { filterTypeToString } from '../helpers/uri';

function handleClick(e, filterType) {
    e.preventDefault();

    selfoss.filter.update({ type: filterType });

    selfoss.events.reloadSamePath = true;
    if (selfoss.events.lastSubsection == null) {
        selfoss.events.lastSubsection = 'all';
    }
    selfoss.events.setHash(filterTypeToString(selfoss.filter.type), 'same');

    selfoss.ui.hideMobileNav();
}

export function NavFilters({filter}) {
    const [currentType, setCurrenttype] = React.useState(filter.type);
    const [offlineState, setOfflineState] = React.useState(selfoss.offlineState.value);
    const [allItemsCount, setallItemsCount] = React.useState(selfoss.allItemsCount.value);
    const [allItemsOfflineCount, setallItemsOfflineCount] = React.useState(selfoss.allItemsOfflineCount.value);
    const [unreadItemsCount, setUnreadItemsCount] = React.useState(selfoss.unreadItemsCount.value);
    const [unreadItemsOfflineCount, setUnreadItemsOfflineCount] = React.useState(selfoss.unreadItemsOfflineCount.value);
    const [starredItemsCount, setStarredItemsCount] = React.useState(selfoss.starredItemsCount.value);
    const [starredItemsOfflineCount, setStarredItemsOfflineCount] = React.useState(selfoss.starredItemsOfflineCount.value);

    React.useEffect(() => {
        const filterListener = (event) => {
            setCurrenttype(event.filter.type);
        };

        const offlineStateListener = (event) => {
            setOfflineState(event.value);
        };

        const allCountListener = (event) => {
            setallItemsCount(event.value);
        };

        const allOfflineCountListener = (event) => {
            setallItemsOfflineCount(event.value);
        };

        const unreadCountListener = (event) => {
            setUnreadItemsCount(event.value);
        };

        const unreadOfflineCountListener = (event) => {
            setUnreadItemsOfflineCount(event.value);
        };

        const starredCountListener = (event) => {
            setStarredItemsCount(event.value);
        };

        const starredOfflineCountListener = (event) => {
            setStarredItemsOfflineCount(event.value);
        };

        // It might happen that filter changes between creating the component and setting up the event handlers.
        filterListener({ filter });
        offlineStateListener({ value: selfoss.offlineState.value });
        allCountListener({ value: selfoss.allItemsCount.value });
        allOfflineCountListener({ value: selfoss.allItemsOfflineCount.value });
        unreadCountListener({ value: selfoss.unreadItemsCount.value });
        unreadOfflineCountListener({ value: selfoss.unreadItemsOfflineCount.value });
        starredCountListener({ value: selfoss.starredItemsCount.value });
        starredOfflineCountListener({ value: selfoss.starredItemsOfflineCount.value });

        filter.addEventListener('change', filterListener);
        selfoss.offlineState.addEventListener('change', offlineStateListener);
        selfoss.allItemsCount.addEventListener('change', allCountListener);
        selfoss.allItemsOfflineCount.addEventListener('change', allOfflineCountListener);
        selfoss.unreadItemsCount.addEventListener('change', unreadCountListener);
        selfoss.unreadItemsOfflineCount.addEventListener('change', unreadOfflineCountListener);
        selfoss.starredItemsCount.addEventListener('change', starredCountListener);
        selfoss.starredItemsOfflineCount.addEventListener('change', starredOfflineCountListener);

        return () => {
            filter.removeEventListener('change', filterListener);
            selfoss.offlineState.removeEventListener('change', offlineStateListener);
            selfoss.allItemsCount.removeEventListener('change', allCountListener);
            selfoss.allItemsOfflineCount.removeEventListener('change', allOfflineCountListener);
            selfoss.unreadItemsCount.removeEventListener('change', unreadCountListener);
            selfoss.unreadItemsOfflineCount.removeEventListener('change', unreadOfflineCountListener);
            selfoss.starredItemsCount.removeEventListener('change', starredCountListener);
            selfoss.starredItemsOfflineCount.removeEventListener('change', starredOfflineCountListener);
        };
    }, [filter]);

    return (
        <React.Fragment>
            <h2><button type="button" id="nav-filter-title" className="nav-section-toggle nav-filter-expanded" aria-expanded="true"><i className="fas fa-caret-down fa-lg fa-fw"></i> {selfoss.ui._('filter')}</button></h2>
            <ul id="nav-filter" aria-labelledby="nav-filter-title">
                <li>
                    <a id="nav-filter-newest" href="#" className={classNames({'nav-filter-newest': true, active: currentType === FilterType.NEWEST})} onClick={(event) => handleClick(event, FilterType.NEWEST)}>
                        {selfoss.ui._('newest')}
                        <span className={classNames({'offline-count': true, offline: offlineState, online: !offlineState, diff: allItemsCount !== allItemsOfflineCount && allItemsOfflineCount})} title={selfoss.ui._('offline_count')}>{allItemsOfflineCount > 0 ? allItemsOfflineCount : ''}</span>
                        <span className="count" title={selfoss.ui._('online_count')}>{allItemsCount > 0 ? allItemsCount : ''}</span>
                    </a>
                </li>
                <li>
                    <a id="nav-filter-unread" href="#" className={classNames({'nav-filter-unread': true, active: currentType === FilterType.UNREAD})} onClick={(event) => handleClick(event, FilterType.UNREAD)}>
                        {selfoss.ui._('unread')}
                        <span className={classNames({'unread-count': true, offline: offlineState, online: !offlineState, unread: unreadItemsCount > 0})}>
                            <span className={classNames({'offline-count': true, offline: offlineState, online: !offlineState, diff: unreadItemsCount !== unreadItemsOfflineCount && unreadItemsOfflineCount})} title={selfoss.ui._('offline_count')}>{unreadItemsOfflineCount > 0 ? unreadItemsOfflineCount : ''}</span>
                            <span className="count" title={selfoss.ui._('online_count')}>{unreadItemsCount > 0 ? unreadItemsCount : ''}</span>
                        </span>
                    </a>
                </li>
                <li>
                    <a id="nav-filter-starred" href="#" className={classNames({'nav-filter-starred': true, active: currentType === FilterType.STARRED})} onClick={(event) => handleClick(event, FilterType.STARRED)}>
                        {selfoss.ui._('starred')}
                        <span className={classNames({'offline-count': true, offline: offlineState, online: !offlineState, diff: starredItemsCount !== starredItemsOfflineCount && starredItemsOfflineCount})} title={selfoss.ui._('offline_count')}>{starredItemsOfflineCount > 0 ? starredItemsOfflineCount : ''}</span>
                        <span className="count" title={selfoss.ui._('online_count')}>{starredItemsCount > 0 ? starredItemsCount : ''}</span>
                    </a>
                </li>
            </ul>
        </React.Fragment>
    );
}

export function anchor(element, filter) {
    ReactDOM.render(<NavFilters filter={filter} />, element);
}
