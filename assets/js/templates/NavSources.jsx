import React from 'react';
import ReactDOM from 'react-dom';
import classNames from 'classnames';
import { unescape } from 'html-escaper';
import { filterTypeToString } from '../helpers/uri';

function handleClick(e, sourceId) {
    e.preventDefault();

    if (!selfoss.db.online) {
        return;
    }

    selfoss.events.setHash(filterTypeToString(selfoss.filter.type), `source-${sourceId}`);

    selfoss.ui.hideMobileNav();
}

export function NavSources({sourcesRepository, filter}) {
    const [currentSource, setCurrentSource] = React.useState(filter.source);
    const [sources, setSources] = React.useState(sourcesRepository.sources);

    React.useEffect(() => {
        const filterListener = (event) => {
            setCurrentSource(event.filter.source);
        };
        const sourcesListener = (event) => {
            setSources(event.sources);
        };

        // It might happen that filter changes between creating the component and setting up the event handlers.
        filterListener({ filter });

        filter.addEventListener('change', filterListener);
        sourcesRepository.addEventListener('change', sourcesListener);

        return () => {
            filter.removeEventListener('change', filterListener);
            sourcesRepository.removeEventListener('change', sourcesListener);
        };
    }, [sourcesRepository, filter]);

    return sources.map((source) =>
        <li key={source.id}>
            <a href="#" className={classNames({active: currentSource === source.id, unread: source.unread > 0})} onClick={(event) => handleClick(event, source.id)}>
                <span className="nav-source">{unescape(source.title)}</span>
                <span className="unread">{source.unread > 0 ? source.unread : ''}</span>
            </a>
        </li>
    );
}

export function anchor(element, sources, filter) {
    ReactDOM.render(<NavSources sourcesRepository={sources} filter={filter} />, element);
}
