import React from 'react';
import {
    Switch,
    Route,
    Redirect,
    useHistory,
    useLocation
} from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import Collapse from '@kunukn/react-collapse';
import classNames from 'classnames';
import LoginForm from './LoginForm';
import SourcesPage from './SourcesPage';
import EntriesPage from './EntriesPage';
import Navigation from './Navigation';
import SearchList from './SearchList';
import makeShortcuts from '../shortcuts';
import { ENTRIES_ROUTE_PATTERN } from '../helpers/uri';


function handleNavToggle({ event, setNavExpanded }) {
    event.preventDefault();

    // show hide navigation for mobile version
    setNavExpanded((expanded) => !expanded);
    window.scrollTo({ top: 0 });
}

function dismissMessage(event) {
    selfoss.globalMessage.update(null);
    event.stopPropagation();
}

/**
 * Global message bar for showing errors/information at the top of the page.
 * It listens to selfoss.globalMessage ValueNotifier and updates/shows/hides itself as necessary
 * when the value changes.
 */
function Message() {
    const [message, setMessage] = React.useState(null);

    // Whenever message changes, dismiss it after 15 seconds.
    React.useEffect(() => {
        if (message !== null) {
            const dismissTimeout = window.setTimeout(function() {
                selfoss.globalMessage.update(null);
            }, 15000);

            return () => {
                // Destory previous timeout.
                window.clearTimeout(dismissTimeout);
            };
        }
    }, [message]);

    React.useEffect(() => {
        const messageListener = (event) => {
            setMessage(event.value);
        };

        // It might happen that values change between creating the component and setting up the event handlers.
        messageListener({ value: selfoss.globalMessage.value });

        selfoss.globalMessage.addEventListener('change', messageListener);

        return () => {
            selfoss.globalMessage.removeEventListener('change', messageListener);
        };
    }, []);

    return (
        message !== null ?
            <div id="message" className={classNames({ error: message.isError })} onClick={dismissMessage}>
                {message.message}
                {message.actions.map(({ label, callback }, index) => (
                    <button key={index} type="button" onClick={callback}>
                        {label}
                    </button>
                ))}
            </div>
            : null
    );
}

function NotFound() {
    const location = useLocation();
    return (
        <p>
            {selfoss.ui._('error_invalid_subsection') + location.pathname}
        </p>
    );
}


export default function App() {
    const [navExpanded, setNavExpanded] = React.useState(false);
    const [smartphone, setSmartphone] = React.useState(false);
    const [loginFormError, setLoginFormError] = React.useState(selfoss.loginFormError.value);
    const [offlineState, setOfflineState] = React.useState(selfoss.offlineState.value);
    const [offlineEnabled, setOfflineEnabled] = React.useState(selfoss.db.enableOffline.value);
    const [unreadItemsCount, setUnreadItemsCount] = React.useState(selfoss.unreadItemsCount.value);
    const [unreadItemsOfflineCount, setUnreadItemsOfflineCount] = React.useState(selfoss.unreadItemsOfflineCount.value);
    const [entriesPage, setEntriesPage] = React.useState(null);

    React.useEffect(() => {
        // init shortcut handler
        const destroyShortcuts = makeShortcuts();

        const loginFormErrorListener = (event) => {
            setLoginFormError(event.value);
        };

        const smartphoneListener = (event) => {
            setSmartphone(event.matches);
        };

        const offlineStateListener = (event) => {
            setOfflineState(event.value);
        };

        const offlineEnabledListener = (event) => {
            setOfflineEnabled(event.value);
        };

        const unreadItemsCountListener = (event) => {
            setUnreadItemsCount(event.value);
        };

        const unreadOfflineCountListener = (event) => {
            setUnreadItemsOfflineCount(event.value);
        };

        const smartphoneMediaQuery = window.matchMedia('(max-width: 641px)');

        // It might happen that values change between creating the component and setting up the event handlers.
        loginFormErrorListener({ value: selfoss.loginFormError.value });
        smartphoneListener({ matches: smartphoneMediaQuery.matches });
        offlineStateListener({ value: selfoss.offlineState.value });
        offlineEnabledListener({ value: selfoss.db.enableOffline.value });
        unreadItemsCountListener({ value: selfoss.db.enableOffline.value });
        unreadOfflineCountListener({ value: selfoss.unreadItemsOfflineCount.value });

        selfoss.loginFormError.addEventListener('change', loginFormErrorListener);
        smartphoneMediaQuery.addEventListener('change', smartphoneListener);
        selfoss.offlineState.addEventListener('change', offlineStateListener);
        selfoss.db.enableOffline.addEventListener('change', offlineEnabledListener);
        selfoss.unreadItemsCount.addEventListener('change', unreadItemsCountListener);
        selfoss.unreadItemsOfflineCount.addEventListener('change', unreadOfflineCountListener);

        return () => {
            destroyShortcuts();
            selfoss.loginFormError.removeEventListener('change', loginFormErrorListener);
            smartphoneMediaQuery.removeEventListener('change', smartphoneListener);
            selfoss.offlineState.removeEventListener('change', offlineStateListener);
            selfoss.db.enableOffline.removeEventListener('change', offlineEnabledListener);
            selfoss.unreadItemsCount.removeEventListener('change', unreadItemsCountListener);
            selfoss.unreadItemsOfflineCount.removeEventListener('change', unreadOfflineCountListener);
        };
    }, []);

    // TODO: move stuff that depends on this to the App.
    const history = useHistory();
    React.useEffect(() => {
        selfoss.history = history;
    }, [history]);

    // Prepare path of the homepage for redirecting from /
    let homePagePath = selfoss.config.homepage.split('/');
    if (!homePagePath[1]) {
        homePagePath.push('all');
    }

    return (
        <React.Fragment>
            <Message />

            <Switch>
                <Route path="/login">
                    {/* menu open for smartphone */}
                    <div id="loginform" role="main">
                        <LoginForm
                            error={loginFormError}
                            setError={setLoginFormError}
                            {...{offlineEnabled, setOfflineEnabled}}
                        />
                    </div>
                </Route>

                <Route path="/">
                    <div id="nav-mobile" role="navigation">
                        <div id="nav-mobile-logo">
                            <div id="nav-mobile-count" className={classNames({'unread-count': true, offline: offlineState, online: !offlineState, unread: unreadItemsCount > 0})}>
                                <span className={classNames({'offline-count': true, offline: offlineState, online: !offlineState, diff: unreadItemsCount !== unreadItemsOfflineCount && unreadItemsOfflineCount})}>{unreadItemsOfflineCount > 0 ? unreadItemsOfflineCount : ''}</span>
                                <span className="count">{unreadItemsCount}</span>
                            </div>
                        </div>
                        <button
                            id="nav-mobile-settings"
                            accessKey="t"
                            aria-label={selfoss.ui._('settingsbutton')}
                            onClick={(event) => handleNavToggle({ event, setNavExpanded })}
                        >
                            <FontAwesomeIcon icon={['fas', 'cog']} size="2x" />
                        </button>
                    </div>

                    {/* navigation */}
                    <Collapse isOpen={!smartphone || navExpanded} className="collapse-css-transition">
                        <div id="nav" role="navigation">
                            <Navigation entriesPage={entriesPage} setNavExpanded={setNavExpanded} />
                        </div>
                    </Collapse>

                    <ul id="search-list">
                        <SearchList />
                    </ul>

                    {/* content */}
                    <div id="content" role="main">
                        <Switch>
                            <Route exact path="/">
                                <Redirect to={`/${homePagePath.join('/')}`} />
                            </Route>
                            <Route path={ENTRIES_ROUTE_PATTERN}>
                                {(routeProps) => (
                                    <EntriesPage
                                        {...routeProps}
                                        ref={(entriesPage) => {
                                            setEntriesPage(entriesPage);
                                            selfoss.entriesPage = entriesPage;
                                        }}
                                        setNavExpanded={setNavExpanded}
                                    />
                                )}
                            </Route>
                            <Route path="/sources">
                                <SourcesPage ref={(sourcesPage) => {
                                    selfoss.sourcesPage = sourcesPage;
                                }} />
                            </Route>
                            <Route path="*">
                                <NotFound />
                            </Route>
                        </Switch>
                    </div>
                </Route>
            </Switch>
        </React.Fragment>
    );
}
