import React, { useCallback, useMemo, useContext, useEffect } from 'react';
import { Link } from 'react-router';
import { usePreviousImmediate } from 'rooks';
import classNames from 'classnames';
import { unescape } from 'html-escaper';
import selfoss from '../selfoss-base';
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
import { NavSource } from '../requests/items';

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

type SourceProps = {
    source: NavSource;
    active: boolean;
    collapseNav: () => void;
};

function Source(props: SourceProps) {
    const { source, active, collapseNav } = props;

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

type NavSourcesProps = {
    setNavExpanded: React.Dispatch<React.SetStateAction<boolean>>;
    navSourcesExpanded: boolean;
    setNavSourcesExpanded: React.Dispatch<React.SetStateAction<boolean>>;
    sourcesState: LoadingState;
    setSourcesState: React.Dispatch<React.SetStateAction<LoadingState>>;
    sources: Array<NavSource>;
    setSources: React.Dispatch<React.SetStateAction<Array<NavSource>>>;
};

export default function NavSources(props: NavSourcesProps) {
    const {
        setNavExpanded,
        navSourcesExpanded,
        setNavSourcesExpanded,
        sourcesState,
        setSourcesState,
        sources,
        setSources,
    } = props;

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
