import React from 'react';
import { Link, useLocation, useRouteMatch } from 'react-router-dom';
import classNames from 'classnames';
import { unescape } from 'html-escaper';
import { makeEntriesLink, ENTRIES_ROUTE_PATTERN } from '../helpers/uri';
import Collapse from '@kunukn/react-collapse';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { LoadingState } from '../requests/LoadingState';
import * as sourceRequests from '../requests/sources';

function handleTitleClick(setExpanded, [sourcesState, setSourcesState]) {
    if (!selfoss.db.online) {
        return;
    }

    setExpanded((expanded) => {
        if (!expanded && sourcesState === LoadingState.INITIAL) {
            sourceRequests.getStats().then((data) => {
                setSourcesState(LoadingState.SUCCESS);
                selfoss.sources.update(data);
            }).catch(function(error) {
                setSourcesState(LoadingState.FAILURE);
                selfoss.ui.showError(selfoss.ui._('error_loading_stats') + ' ' + error.message);
            });
        }

        return !expanded;
    });
}

export default function NavSources({ sourcesRepository, setNavExpanded }) {
    const [expanded, setExpanded] = React.useState(false);
    const [sourcesState, setSourcesState] = React.useState(sourcesRepository.state);
    const [sources, setSources] = React.useState(sourcesRepository.sources);

    React.useEffect(() => {
        const sourcesStateListener = (event) => {
            setSourcesState(event.state);
        };
        const sourcesListener = (event) => {
            setSources(event.sources);
        };

        // It might happen that filter changes between creating the component and setting up the event handlers.
        sourcesStateListener({ state: sourcesRepository.state });
        sourcesListener({ sources: sourcesRepository.sources });

        sourcesRepository.addEventListener('statechange', sourcesStateListener);
        sourcesRepository.addEventListener('change', sourcesListener);

        return () => {
            sourcesRepository.removeEventListener('statechange', sourcesStateListener);
            sourcesRepository.removeEventListener('change', sourcesListener);
        };
    }, [sourcesRepository]);

    // TODO: get rid of this
    React.useEffect(() => {
        selfoss.navSourcesExpanded.update(expanded);
    }, [expanded]);

    const reallyExpanded = expanded && sourcesState === LoadingState.SUCCESS;

    const location = useLocation();
    // useParams does not seem to work.
    const match = useRouteMatch(ENTRIES_ROUTE_PATTERN);
    const params = match !== null ? match.params : {};
    const currentSource = params.category?.startsWith('source-') ? parseInt(params.category.replace(/^source-/, ''), 10) : null;

    return (
        <React.Fragment>
            <h2><button type="button" id="nav-sources-title" className={classNames({'nav-section-toggle': true, 'nav-sources-collapsed': !reallyExpanded, 'nav-sources-expanded': reallyExpanded})} aria-expanded={reallyExpanded} onClick={() => handleTitleClick(setExpanded, [sourcesState, setSourcesState])}><FontAwesomeIcon icon={['fas', reallyExpanded ? 'caret-down' : 'caret-right']} size="lg" fixedWidth />  {selfoss.ui._('sources')}</button></h2>
            <Collapse isOpen={reallyExpanded} className="collapse-css-transition">
                <ul id="nav-sources" aria-labelledby="nav-sources-title">
                    {sources.map((source) =>
                        <li key={source.id}>
                            <Link to={makeEntriesLink(location, { category: `source-${source.id}`, id: null })} className={classNames({active: currentSource === source.id, unread: source.unread > 0})} onClick={() => setNavExpanded(false)}>
                                <span className="nav-source">{unescape(source.title)}</span>
                                <span className="unread">{source.unread > 0 ? source.unread : ''}</span>
                            </Link>
                        </li>
                    )}
                </ul>
            </Collapse>
        </React.Fragment>
    );
}
