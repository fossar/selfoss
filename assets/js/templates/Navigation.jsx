import React from 'react';
import classNames from 'classnames';
import NavFilters from './NavFilters';
import NavSources from './NavSources';
import NavSearch from './NavSearch';
import NavTags from './NavTags';
import NavToolBar from './NavToolBar';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';

export default function Navigation({ entriesPage, setNavExpanded }) {
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
            <button accessKey="a" id="nav-mark" onClick={entriesPage !== null ? entriesPage.markVisibleRead : null} disabled={entriesPage === null}>{selfoss.ui._('markread')}</button>

            <NavFilters setNavExpanded={setNavExpanded} />

            <div className="separator"><hr /></div>

            <div className={classNames({'nav-ts-wrapper': true, offline: offlineState, online: !offlineState})}>
                <NavTags tagsRepository={selfoss.tags} setNavExpanded={setNavExpanded} />
                <NavSources sourcesRepository={selfoss.sources} setNavExpanded={setNavExpanded} />
            </div>

            <div className={classNames({'nav-unavailable': true, offline: offlineState, online: !offlineState})}>
                <span className="fa-layers fa-2x">
                    <FontAwesomeIcon icon={icons.connection} />
                    <FontAwesomeIcon icon={icons.slash} />
                </span>
                <p>{selfoss.ui._('offline_navigation_unavailable')}</p>
            </div>

            <div className="separator"><hr /></div>

            <NavSearch setNavExpanded={setNavExpanded} />

            <NavToolBar setNavExpanded={setNavExpanded} />
        </React.Fragment>
    );
}
