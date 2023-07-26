import React, { useCallback, useMemo, useContext, useEffect } from 'react';
import PropTypes from 'prop-types';
import { Link, useLocation } from 'react-router';
import { usePreviousImmediate } from 'rooks';
import classNames from 'classnames';
import { unescape } from 'html-escaper';
import {
    useForceReload,
    makeEntriesLinkLocation,
    useEntriesParams,
} from '../helpers/uri';
import { Collapse } from '@kunukn/react-collapse';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { LoadingState } from '../requests/LoadingState';
import * as sourceRequests from '../requests/sources';
import * as icons from '../icons';
import { LocalizationContext } from '../helpers/i18n';

function handleTitleClick({
    setExpanded,
    sourcesState,
    setSourcesState,
    setSources,
}) {
    if (!selfoss.isOnline()) {
        console.log('Cannot toggle, not online.');
        return;
    }

    setExpanded((expanded) => {
        if (!expanded && sourcesState === LoadingState.INITIAL) {
            sourceRequests
                .getStats()
                .then((data) => {
                    setSources(data);
                    setSourcesState(LoadingState.SUCCESS);
                })
                .catch((error) => {
                    setSourcesState(LoadingState.FAILURE);
                    selfoss.app.showError(
                        selfoss.app._('error_loading_stats') +
                            ' ' +
                            error.message,
                    );
                });
        }

        return !expanded;
    });
}

function Source({ source, active, collapseNav }) {
    const location = useLocation();
    const link = useMemo(
        () =>
            makeEntriesLinkLocation(location, {
                category: `source-${source.id}`,
                id: null,
            }),
        [source.id, location],
    );
    const forceReload = useForceReload();

    return (
        <li className={classNames({ read: source.unread === 0 })}>
            <Link
                to={link}
                className={classNames({ active, unread: source.unread > 0 })}
                onClick={collapseNav}
                state={forceReload}
            >
                <span className="nav-source">{unescape(source.title)}</span>
                <span className="unread">
                    {source.unread > 0 ? source.unread : ''}
                </span>
            </Link>
        </li>
    );
}

Source.propTypes = {
    source: PropTypes.object.isRequired,
    active: PropTypes.bool.isRequired,
    collapseNav: PropTypes.func.isRequired,
};

export default function NavSources({
    setNavExpanded,
    navSourcesExpanded,
    setNavSourcesExpanded,
    sourcesState,
    setSourcesState,
    sources,
    setSources,
}) {
    const reallyExpanded =
        navSourcesExpanded && sourcesState === LoadingState.SUCCESS;

    const params = useEntriesParams();
    const currentSource = params?.category?.startsWith('source-')
        ? parseInt(params.category.replace(/^source-/, ''), 10)
        : null;

    const toggleExpanded = useCallback(
        () =>
            handleTitleClick({
                setExpanded: setNavSourcesExpanded,
                sourcesState,
                setSourcesState,
                setSources,
            }),
        [setNavSourcesExpanded, sourcesState, setSourcesState, setSources],
    );

    const collapseNav = useCallback(
        () => setNavExpanded(false),
        [setNavExpanded],
    );

    const previousSourcesState = usePreviousImmediate(sourcesState);
    useEffect(() => {
        if (
            previousSourcesState === LoadingState.INITIAL &&
            sourcesState === LoadingState.SUCCESS
        ) {
            setNavSourcesExpanded(true);
        }
    }, [previousSourcesState, sourcesState, setNavSourcesExpanded]);

    const _ = useContext(LocalizationContext);

    return (
        <>
            <h2>
                <button
                    type="button"
                    id="nav-sources-title"
                    className={classNames({
                        'nav-section-toggle': true,
                        'nav-sources-collapsed': !reallyExpanded,
                        'nav-sources-expanded': reallyExpanded,
                    })}
                    aria-expanded={reallyExpanded}
                    onClick={toggleExpanded}
                >
                    <FontAwesomeIcon
                        icon={
                            navSourcesExpanded
                                ? icons.arrowExpanded
                                : icons.arrowCollapsed
                        }
                        size="lg"
                        fixedWidth
                    />{' '}
                    {_('sources')}
                </button>
            </h2>
            <Collapse
                isOpen={reallyExpanded}
                className="collapse-css-transition"
            >
                <ul id="nav-sources" aria-labelledby="nav-sources-title">
                    {sources.map((source) => (
                        <Source
                            key={source.id}
                            source={source}
                            active={currentSource === source.id}
                            collapseNav={collapseNav}
                        />
                    ))}
                </ul>
            </Collapse>
        </>
    );
}

NavSources.propTypes = {
    setNavExpanded: PropTypes.func.isRequired,
    navSourcesExpanded: PropTypes.bool.isRequired,
    setNavSourcesExpanded: PropTypes.func.isRequired,
    sourcesState: PropTypes.oneOf(Object.values(LoadingState)).isRequired,
    setSourcesState: PropTypes.func.isRequired,
    sources: PropTypes.arrayOf(PropTypes.object).isRequired,
    setSources: PropTypes.func.isRequired,
};
