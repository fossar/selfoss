import React from 'react';
import PropTypes from 'prop-types';
import { Link, useLocation, useRouteMatch } from 'react-router-dom';
import classNames from 'classnames';
import { FilterType } from '../Filter';
import { makeEntriesLink, ENTRIES_ROUTE_PATTERN } from '../helpers/uri';
import Collapse from '@kunukn/react-collapse';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';

export default function NavFilters({
    setNavExpanded,
    offlineState,
    allItemsCount,
    allItemsOfflineCount,
    unreadItemsCount,
    unreadItemsOfflineCount,
    starredItemsCount,
    starredItemsOfflineCount,
}) {
    const [expanded, setExpanded] = React.useState(true);

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

NavFilters.propTypes = {
    setNavExpanded: PropTypes.func.isRequired,
    offlineState: PropTypes.bool.isRequired,
    allItemsCount: PropTypes.number.isRequired,
    allItemsOfflineCount: PropTypes.number.isRequired,
    unreadItemsCount: PropTypes.number.isRequired,
    unreadItemsOfflineCount: PropTypes.number.isRequired,
    starredItemsCount: PropTypes.number.isRequired,
    starredItemsOfflineCount: PropTypes.number.isRequired,
};
