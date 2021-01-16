import React from 'react';
import classNames from 'classnames';
import NavFilters from './NavFilters';
import NavSources from './NavSources';
import NavSearch from './NavSearch';
import NavTags from './NavTags';
import NavToolBar from './NavToolBar';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

export default function Navigation({ entriesPage }) {
    const [offlineState, setOfflineState] = React.useState(selfoss.offlineState.value);

    React.useEffect(() => {
        const offlineStateListener = (event) => {
            setOfflineState(event.value);
        };

        // It might happen that value changes between creating the component and setting up the event handlers.
        offlineStateListener({ value: selfoss.offlineState.value });

        selfoss.offlineState.addEventListener('change', offlineStateListener);

        return () => {
            selfoss.offlineState.removeEventListener('change', offlineStateListener);
        };
    }, []);

    return (
        <React.Fragment>
            <div id="nav-logo"></div>
            <button accessKey="a" id="nav-mark" onClick={entriesPage !== null ? () => entriesPage.markVisibleRead() : null} disabled={entriesPage === null}>{selfoss.ui._('markread')}</button>

            <NavFilters />

            <div className="separator"><hr /></div>

            <div className={classNames({'nav-ts-wrapper': true, offline: offlineState, online: !offlineState})}>
                <NavTags tagsRepository={selfoss.tags} />
                <NavSources sourcesRepository={selfoss.sources} />
            </div>

            <div className={classNames({'nav-unavailable': true, offline: offlineState, online: !offlineState})}>
                <span className="fa-layers fa-2x">
                    <FontAwesomeIcon icon={['fas', 'wifi']} />
                    <FontAwesomeIcon icon={['fas', 'slash']} />
                </span>
                <p>{selfoss.ui._('offline_navigation_unavailable')}</p>
            </div>

            <div className="separator"><hr /></div>

            <NavSearch />

            <NavToolBar />
        </React.Fragment>
    );
}
