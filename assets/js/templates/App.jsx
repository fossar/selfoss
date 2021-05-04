import React from 'react';
import PropTypes from 'prop-types';
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
import * as icons from '../icons';
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

function PureApp({
    navSourcesExpanded,
    setNavSourcesExpanded,
    offlineState,
    allItemsCount,
    allItemsOfflineCount,
    unreadItemsCount,
    unreadItemsOfflineCount,
    starredItemsCount,
    starredItemsOfflineCount,
}) {
    const [navExpanded, setNavExpanded] = React.useState(false);
    const [smartphone, setSmartphone] = React.useState(false);
    const [loginFormError, setLoginFormError] = React.useState(selfoss.loginFormError.value);
    const [offlineEnabled, setOfflineEnabled] = React.useState(selfoss.db.enableOffline.value);
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

        const offlineEnabledListener = (event) => {
            setOfflineEnabled(event.value);
        };

        const smartphoneMediaQuery = window.matchMedia('(max-width: 641px)');

        // It might happen that values change between creating the component and setting up the event handlers.
        loginFormErrorListener({ value: selfoss.loginFormError.value });
        smartphoneListener({ matches: smartphoneMediaQuery.matches });
        offlineEnabledListener({ value: selfoss.db.enableOffline.value });

        selfoss.loginFormError.addEventListener('change', loginFormErrorListener);
        smartphoneMediaQuery.addEventListener('change', smartphoneListener);
        selfoss.db.enableOffline.addEventListener('change', offlineEnabledListener);

        return () => {
            destroyShortcuts();
            selfoss.loginFormError.removeEventListener('change', loginFormErrorListener);
            smartphoneMediaQuery.removeEventListener('change', smartphoneListener);
            selfoss.db.enableOffline.removeEventListener('change', offlineEnabledListener);
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

    const menuButtonOnClick = React.useCallback(
        (event) => handleNavToggle({ event, setNavExpanded }),
        []
    );

    const entriesRef = React.useCallback(
        (entriesPage) => {
            setEntriesPage(entriesPage);
            selfoss.entriesPage = entriesPage;
        },
        []
    );

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
                            onClick={menuButtonOnClick}
                        >
                            <FontAwesomeIcon icon={icons.menu} size="2x" />
                        </button>
                    </div>

                    {/* navigation */}
                    <Collapse isOpen={!smartphone || navExpanded} className="collapse-css-transition">
                        <div id="nav" role="navigation">
                            <Navigation
                                entriesPage={entriesPage}
                                setNavExpanded={setNavExpanded}
                                navSourcesExpanded={navSourcesExpanded}
                                setNavSourcesExpanded={setNavSourcesExpanded}
                                offlineState={offlineState}
                                allItemsCount={allItemsCount}
                                allItemsOfflineCount={allItemsOfflineCount}
                                unreadItemsCount={unreadItemsCount}
                                unreadItemsOfflineCount={unreadItemsOfflineCount}
                                starredItemsCount={starredItemsCount}
                                starredItemsOfflineCount={starredItemsOfflineCount}
                            />
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
                                        ref={entriesRef}
                                        setNavExpanded={setNavExpanded}
                                        navSourcesExpanded={navSourcesExpanded}
                                    />
                                )}
                            </Route>
                            <Route path="/sources">
                                <SourcesPage />
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

PureApp.propTypes = {
    navSourcesExpanded: PropTypes.bool.isRequired,
    setNavSourcesExpanded: PropTypes.func.isRequired,
    offlineState: PropTypes.bool.isRequired,
    allItemsCount: PropTypes.number.isRequired,
    allItemsOfflineCount: PropTypes.number.isRequired,
    unreadItemsCount: PropTypes.number.isRequired,
    unreadItemsOfflineCount: PropTypes.number.isRequired,
    starredItemsCount: PropTypes.number.isRequired,
    starredItemsOfflineCount: PropTypes.number.isRequired,
};

export default class App extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            /**
             * true when sources in the sidebar are expanded
             * and we should fetch info about them in API requests.
             */
            navSourcesExpanded: false,

            /**
             * whether off-line mode is enabled
             */
            offlineState: false,

            /**
             * number of unread items
             */
            unreadItemsCount: 0,

            /**
             * number of unread items available offline
             */
            unreadItemsOfflineCount: 0,

            /**
             * number of starred items
             */
            starredItemsCount: 0,

            /**
             * number of starred items available offline
             */
            starredItemsOfflineCount: 0,

            /**
             * number of all items
             */
            allItemsCount: 0,

            /**
             * number of all items available offline
             */
            allItemsOfflineCount: 0,
        };

        this.setOfflineState = this.setOfflineState.bind(this);
        this.setNavSourcesExpanded = this.setNavSourcesExpanded.bind(this);
        this.setUnreadItemsCount = this.setUnreadItemsCount.bind(this);
        this.setUnreadItemsOfflineCount = this.setUnreadItemsOfflineCount.bind(this);
        this.setStarredItemsCount = this.setStarredItemsCount.bind(this);
        this.setStarredItemsOfflineCount = this.setStarredItemsOfflineCount.bind(this);
        this.setAllItemsCount = this.setAllItemsCount.bind(this);
        this.setAllItemsOfflineCount = this.setAllItemsOfflineCount.bind(this);
    }

    setOfflineState(offlineState) {
        if (typeof offlineState === 'function') {
            this.setState({
                offlineState: offlineState(this.state.offlineState)
            });
        } else {
            this.setState({ offlineState });
        }
    }

    setNavSourcesExpanded(navSourcesExpanded) {
        if (typeof navSourcesExpanded === 'function') {
            this.setState({
                navSourcesExpanded: navSourcesExpanded(this.state.navSourcesExpanded)
            });
        } else {
            this.setState({ navSourcesExpanded });
        }
    }

    setUnreadItemsCount(unreadItemsCount) {
        if (typeof unreadItemsCount === 'function') {
            this.setState({
                unreadItemsCount: unreadItemsCount(this.state.unreadItemsCount)
            });
        } else {
            this.setState({ unreadItemsCount });
        }
    }

    setUnreadItemsOfflineCount(unreadItemsOfflineCount) {
        if (typeof unreadItemsOfflineCount === 'function') {
            this.setState({
                unreadItemsOfflineCount: unreadItemsOfflineCount(this.state.unreadItemsOfflineCount)
            });
        } else {
            this.setState({ unreadItemsOfflineCount });
        }
    }

    setStarredItemsCount(starredItemsCount) {
        if (typeof starredItemsCount === 'function') {
            this.setState({
                starredItemsCount: starredItemsCount(this.state.starredItemsCount)
            });
        } else {
            this.setState({ starredItemsCount });
        }
    }

    setStarredItemsOfflineCount(starredItemsOfflineCount) {
        if (typeof starredItemsOfflineCount === 'function') {
            this.setState({
                starredItemsOfflineCount: starredItemsOfflineCount(this.state.starredItemsOfflineCount)
            });
        } else {
            this.setState({ starredItemsOfflineCount });
        }
    }

    setAllItemsCount(allItemsCount) {
        if (typeof allItemsCount === 'function') {
            this.setState({
                allItemsCount: allItemsCount(this.state.allItemsCount)
            });
        } else {
            this.setState({ allItemsCount });
        }
    }

    setAllItemsOfflineCount(allItemsOfflineCount) {
        if (typeof allItemsOfflineCount === 'function') {
            this.setState({
                allItemsOfflineCount: allItemsOfflineCount(this.state.allItemsOfflineCount)
            });
        } else {
            this.setState({ allItemsOfflineCount });
        }
    }

    render() {
        return (
            <PureApp
                navSourcesExpanded={this.state.navSourcesExpanded}
                setNavSourcesExpanded={this.setNavSourcesExpanded}
                offlineState={this.state.offlineState}
                allItemsCount={this.state.allItemsCount}
                allItemsOfflineCount={this.state.allItemsOfflineCount}
                unreadItemsCount={this.state.unreadItemsCount}
                unreadItemsOfflineCount={this.state.unreadItemsOfflineCount}
                starredItemsCount={this.state.starredItemsCount}
                starredItemsOfflineCount={this.state.starredItemsOfflineCount}
            />
        );
    }
}
