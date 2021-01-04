import React from 'react';
import ReactDOM from 'react-dom';
import classNames from 'classnames';
import { unescape } from 'html-escaper';
import { filterTypeToString } from '../helpers/uri';
import Collapse from '@kunukn/react-collapse';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { LoadingState } from '../requests/LoadingState';
import * as sourceRequests from '../requests/sources';

function handleTitleClick(expanded, [sourcesState, setSourcesState], toggle) {
    if (!selfoss.db.online) {
        return;
    }

    selfoss.filter.update({ sourcesNav: !expanded });
    if (selfoss.filter.sourcesNav && sourcesState === LoadingState.INITIAL) {
        sourceRequests.getStats().then((data) => {
            setSourcesState(LoadingState.SUCCESS);
            selfoss.sources.update(data);
        }).catch(function(error) {
            setSourcesState(LoadingState.FAILURE);
            selfoss.ui.showError(selfoss.ui._('error_loading_stats') + ' ' + error.message);
        });
    } else {
        toggle();
    }
}

function handleClick(e, sourceId) {
    e.preventDefault();

    if (!selfoss.db.online) {
        return;
    }

    selfoss.events.setHash(filterTypeToString(selfoss.filter.type), `source-${sourceId}`);

    selfoss.ui.hideMobileNav();
}

export function NavSources({sourcesRepository, filter}) {
    const [expanded, setExpanded] = React.useState(false);
    const [currentSource, setCurrentSource] = React.useState(filter.source);
    const [sourcesState, setSourcesState] = React.useState(sourcesRepository.state);
    const [sources, setSources] = React.useState(sourcesRepository.sources);

    React.useEffect(() => {
        const filterListener = (event) => {
            setCurrentSource(event.filter.source);
        };
        const sourcesStateListener = (event) => {
            setSourcesState(event.state);
        };
        const sourcesListener = (event) => {
            setSources(event.sources);
        };

        // It might happen that filter changes between creating the component and setting up the event handlers.
        filterListener({ filter });
        sourcesStateListener({ state: sourcesRepository.state });
        sourcesListener({ sources: sourcesRepository.sources });

        filter.addEventListener('change', filterListener);
        sourcesRepository.addEventListener('statechange', sourcesStateListener);
        sourcesRepository.addEventListener('change', sourcesListener);

        return () => {
            filter.removeEventListener('change', filterListener);
            sourcesRepository.removeEventListener('statechange', sourcesStateListener);
            sourcesRepository.removeEventListener('change', sourcesListener);
        };
    }, [sourcesRepository, filter]);

    const reallyExpanded = expanded && sourcesState === LoadingState.SUCCESS;

    return (
        <React.Fragment>
            <h2><button type="button" id="nav-sources-title" className={classNames({'nav-section-toggle': true, 'nav-sources-collapsed': !reallyExpanded, 'nav-sources-expanded': reallyExpanded})} aria-expanded={reallyExpanded} onClick={() => handleTitleClick(reallyExpanded, [sourcesState, setSourcesState], () => setExpanded((expanded) => !expanded))}><FontAwesomeIcon icon={['fas', reallyExpanded ? 'caret-down' : 'caret-right']} size="lg" fixedWidth />  {selfoss.ui._('sources')}</button></h2>
            <Collapse isOpen={reallyExpanded} className="collapse-css-transition">
                <ul id="nav-sources" aria-labelledby="nav-sources-title">
                    {sources.map((source) =>
                        <li key={source.id}>
                            <a href="#" className={classNames({active: currentSource === source.id, unread: source.unread > 0})} onClick={(event) => handleClick(event, source.id)}>
                                <span className="nav-source">{unescape(source.title)}</span>
                                <span className="unread">{source.unread > 0 ? source.unread : ''}</span>
                            </a>
                        </li>
                    )}
                </ul>
            </Collapse>
        </React.Fragment>
    );
}

export function anchor(element, sources, filter) {
    ReactDOM.render(<NavSources sourcesRepository={sources} filter={filter} />, element);
}
