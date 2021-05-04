import React from 'react';
import { Link, useLocation, useRouteMatch } from 'react-router-dom';
import classNames from 'classnames';
import { FilterType } from '../Filter';
import { makeEntriesLink, ENTRIES_ROUTE_PATTERN } from '../helpers/uri';
import Collapse from '@kunukn/react-collapse';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';

export default function NavFilters({ setNavExpanded }) {
    const [expanded, setExpanded] = React.useState(true);
    const [offlineState, setOfflineState] = React.useState(selfoss.offlineState.value);
    const [allItemsCount, setallItemsCount] = React.useState(selfoss.allItemsCount.value);
    const [allItemsOfflineCount, setallItemsOfflineCount] = React.useState(selfoss.allItemsOfflineCount.value);
    const [unreadItemsCount, setUnreadItemsCount] = React.useState(selfoss.unreadItemsCount.value);
    const [unreadItemsOfflineCount, setUnreadItemsOfflineCount] = React.useState(selfoss.unreadItemsOfflineCount.value);
    const [starredItemsCount, setStarredItemsCount] = React.useState(selfoss.starredItemsCount.value);
    const [starredItemsOfflineCount, setStarredItemsOfflineCount] = React.useState(selfoss.starredItemsOfflineCount.value);

    React.useEffect(() => {
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
        offlineStateListener({ value: selfoss.offlineState.value });
        allCountListener({ value: selfoss.allItemsCount.value });
        allOfflineCountListener({ value: selfoss.allItemsOfflineCount.value });
        unreadCountListener({ value: selfoss.unreadItemsCount.value });
        unreadOfflineCountListener({ value: selfoss.unreadItemsOfflineCount.value });
        starredCountListener({ value: selfoss.starredItemsCount.value });
        starredOfflineCountListener({ value: selfoss.starredItemsOfflineCount.value });

        selfoss.offlineState.addEventListener('change', offlineStateListener);
        selfoss.allItemsCount.addEventListener('change', allCountListener);
        selfoss.allItemsOfflineCount.addEventListener('change', allOfflineCountListener);
        selfoss.unreadItemsCount.addEventListener('change', unreadCountListener);
        selfoss.unreadItemsOfflineCount.addEventListener('change', unreadOfflineCountListener);
        selfoss.starredItemsCount.addEventListener('change', starredCountListener);
        selfoss.starredItemsOfflineCount.addEventListener('change', starredOfflineCountListener);

        return () => {
            selfoss.offlineState.removeEventListener('change', offlineStateListener);
            selfoss.allItemsCount.removeEventListener('change', allCountListener);
            selfoss.allItemsOfflineCount.removeEventListener('change', allOfflineCountListener);
            selfoss.unreadItemsCount.removeEventListener('change', unreadCountListener);
            selfoss.unreadItemsOfflineCount.removeEventListener('change', unreadOfflineCountListener);
            selfoss.starredItemsCount.removeEventListener('change', starredCountListener);
            selfoss.starredItemsOfflineCount.removeEventListener('change', starredOfflineCountListener);
        };
    }, []);

    const location = useLocation();
    // useParams does not seem to work.
    const match = useRouteMatch(ENTRIES_ROUTE_PATTERN);
    const params = match !== null ? match.params : {};

    const toggleExpanded = React.useCallback(
        () => setExpanded((expanded) => !expanded),
        []
    );

    const collapseNav = React.useCallback(
        () => setNavExpanded(false),
        [setNavExpanded]
    );

    return (
        <div id="nav-filter-wrapper">
            <h2><button type="button" id="nav-filter-title" className={classNames({'nav-section-toggle': true, 'nav-filter-collapsed': !expanded, 'nav-filter-expanded': expanded})} aria-expanded={expanded} onClick={toggleExpanded}><FontAwesomeIcon icon={expanded ? icons.arrowExpanded : icons.arrowCollapsed} size="lg" fixedWidth /> {selfoss.ui._('filter')}</button></h2>
            <Collapse isOpen={expanded} className="collapse-css-transition">
                <ul id="nav-filter" aria-labelledby="nav-filter-title">
                    <li>
                        <Link id="nav-filter-newest" to={makeEntriesLink(location, { filter: FilterType.NEWEST })} className={classNames({'nav-filter-newest': true, active: params.filter === FilterType.NEWEST})} onClick={collapseNav}>
                            {selfoss.ui._('newest')}
                            <span className={classNames({'offline-count': true, offline: offlineState, online: !offlineState, diff: allItemsCount !== allItemsOfflineCount && allItemsOfflineCount})} title={selfoss.ui._('offline_count')}>{allItemsOfflineCount > 0 ? allItemsOfflineCount : ''}</span>
                            <span className="count" title={selfoss.ui._('online_count')}>{allItemsCount > 0 ? allItemsCount : ''}</span>
                        </Link>
                    </li>
                    <li>
                        <Link id="nav-filter-unread" to={makeEntriesLink(location, { filter: FilterType.UNREAD })} className={classNames({'nav-filter-unread': true, active: params.filter === FilterType.UNREAD})} onClick={collapseNav}>
                            {selfoss.ui._('unread')}
                            <span className={classNames({'unread-count': true, offline: offlineState, online: !offlineState, unread: unreadItemsCount > 0})}>
                                <span className={classNames({'offline-count': true, offline: offlineState, online: !offlineState, diff: unreadItemsCount !== unreadItemsOfflineCount && unreadItemsOfflineCount})} title={selfoss.ui._('offline_count')}>{unreadItemsOfflineCount > 0 ? unreadItemsOfflineCount : ''}</span>
                                <span className="count" title={selfoss.ui._('online_count')}>{unreadItemsCount > 0 ? unreadItemsCount : ''}</span>
                            </span>
                        </Link>
                    </li>
                    <li>
                        <Link id="nav-filter-starred" to={makeEntriesLink(location, { filter: FilterType.STARRED })} className={classNames({'nav-filter-starred': true, active: params.filter === FilterType.STARRED})} onClick={collapseNav}>
                            {selfoss.ui._('starred')}
                            <span className={classNames({'offline-count': true, offline: offlineState, online: !offlineState, diff: starredItemsCount !== starredItemsOfflineCount && starredItemsOfflineCount})} title={selfoss.ui._('offline_count')}>{starredItemsOfflineCount > 0 ? starredItemsOfflineCount : ''}</span>
                            <span className="count" title={selfoss.ui._('online_count')}>{starredItemsCount > 0 ? starredItemsCount : ''}</span>
                        </Link>
                    </li>
                </ul>
            </Collapse>
        </div>
    );
}
