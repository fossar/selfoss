import React, { Dispatch, SetStateAction, use } from 'react';
import classNames from 'classnames';
import { StateHolder as EntriesPage } from './EntriesPage';
import NavFilters from './NavFilters';
import NavSources from './NavSources';
import NavSearch from './NavSearch';
import NavTags from './NavTags';
import NavToolBar from './NavToolBar';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import * as icons from '../icons';
import { LoadingState } from '../requests/LoadingState';
import { useAllowedToWrite } from '../helpers/authorizations';
import { LocalizationContext } from '../helpers/i18n';
import { NavSource, NavTag } from '../requests/items';

type NavigationProps = {
    entriesPage: EntriesPage | null;
    setNavExpanded: Dispatch<SetStateAction<boolean>>;
    navSourcesExpanded: boolean;
    setNavSourcesExpanded: Dispatch<SetStateAction<boolean>>;
    offlineState: boolean;
    allItemsCount: number;
    allItemsOfflineCount: number;
    unreadItemsCount: number;
    unreadItemsOfflineCount: number;
    starredItemsCount: number;
    starredItemsOfflineCount: number;
    sourcesState: LoadingState;
    setSourcesState: Dispatch<SetStateAction<LoadingState>>;
    sources: Array<NavSource>;
    setSources: Dispatch<SetStateAction<Array<NavSource>>>;
    tags: Array<NavTag>;
    reloadAll: () => Promise<void>;
    showError: (message: string) => void;
};

export default function Navigation(props: NavigationProps): React.JSX.Element {
    const {
        entriesPage,
        setNavExpanded,
        navSourcesExpanded,
        setNavSourcesExpanded,
        offlineState,
        allItemsCount,
        allItemsOfflineCount,
        unreadItemsCount,
        unreadItemsOfflineCount,
        starredItemsCount,
        starredItemsOfflineCount,
        sourcesState,
        setSourcesState,
        sources,
        setSources,
        tags,
        reloadAll,
        showError,
    } = props;

    const _ = use(LocalizationContext);

    const canWrite = useAllowedToWrite();

    return (
        <>
            <div id="nav-logo"></div>
            {canWrite && (
                <button
                    accessKey="a"
                    id="nav-mark"
                    onClick={
                        entriesPage !== null
                            ? entriesPage.markVisibleRead
                            : null
                    }
                    disabled={entriesPage === null}
                >
                    {_('markread')}
                </button>
            )}

            <NavFilters
                setNavExpanded={setNavExpanded}
                offlineState={offlineState}
                allItemsCount={allItemsCount}
                allItemsOfflineCount={allItemsOfflineCount}
                unreadItemsCount={unreadItemsCount}
                unreadItemsOfflineCount={unreadItemsOfflineCount}
                starredItemsCount={starredItemsCount}
                starredItemsOfflineCount={starredItemsOfflineCount}
            />

            <div className="separator">
                <hr />
            </div>

            <div
                className={classNames({
                    'nav-ts-wrapper': true,
                    offline: offlineState,
                    online: !offlineState,
                })}
            >
                <NavTags
                    tags={tags}
                    setNavExpanded={setNavExpanded}
                    showError={showError}
                />
                <NavSources
                    setNavExpanded={setNavExpanded}
                    navSourcesExpanded={navSourcesExpanded}
                    setNavSourcesExpanded={setNavSourcesExpanded}
                    sourcesState={sourcesState}
                    setSourcesState={setSourcesState}
                    sources={sources}
                    setSources={setSources}
                    showError={showError}
                />
            </div>

            <div
                className={classNames({
                    'nav-unavailable': true,
                    offline: offlineState,
                    online: !offlineState,
                })}
            >
                <span className="fa-layers fa-2x">
                    <FontAwesomeIcon icon={icons.connection} />
                    <FontAwesomeIcon icon={icons.slash} />
                </span>
                <p>{_('offline_navigation_unavailable')}</p>
            </div>

            <div className="separator">
                <hr />
            </div>

            <NavSearch
                setNavExpanded={setNavExpanded}
                offlineState={offlineState}
            />

            <NavToolBar reloadAll={reloadAll} setNavExpanded={setNavExpanded} />
        </>
    );
}
