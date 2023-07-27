import React, {
    useState,
} from 'react';
import PropTypes from 'prop-types';
import nullable from 'prop-types-nullable';
import {
    BrowserRouter as Router,
    Switch,
    Route,
    Link,
    Redirect,
    useHistory,
    useLocation,
} from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import Collapse from '@kunukn/react-collapse';
import classNames from 'classnames';
import HashPassword from './HashPassword';
import OpmlImport from './OpmlImport';
import LoginForm from './LoginForm';
import SourcesPage from './SourcesPage';
import EntriesPage from './EntriesPage';
import Navigation from './Navigation';
import SearchList from './SearchList';
import makeShortcuts from '../shortcuts';
import * as icons from '../icons';
import { useAllowedToRead, useAllowedToWrite } from '../helpers/authorizations';
import { ConfigurationContext } from '../helpers/configuration';
import { useIsSmartphone, useListenableValue } from '../helpers/hooks';
import { ENTRIES_ROUTE_PATTERN } from '../helpers/uri';
import { i18nFormat, LocalizationContext } from '../helpers/i18n';
import { LoadingState } from '../requests/LoadingState';
import * as sourceRequests from '../requests/sources';
import locales from '../locales';


function handleNavToggle({ event, setNavExpanded }) {
    event.preventDefault();

    // show hide navigation for mobile version
    setNavExpanded((expanded) => !expanded);
    window.scrollTo({ top: 0 });
}

function dismissMessage(event) {
    selfoss.app.setGlobalMessage(null);
    event.stopPropagation();
}

/**
 * Global message bar for showing errors/information at the top of the page.
 * It watches globalMessage and updates/shows/hides itself as necessary
 * when the value changes.
 */
function Message({ message }) {
    // Whenever message changes, dismiss it after 15 seconds.
    React.useEffect(() => {
        if (message !== null) {
            const dismissTimeout = window.setTimeout(function() {
                selfoss.app.setGlobalMessage(null);
            }, 15000);

            return () => {
                // Destory previous timeout.
                window.clearTimeout(dismissTimeout);
            };
        }
    }, [message]);

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

Message.propTypes = {
    message: nullable(PropTypes.object).isRequired,
};

function NotFound() {
    const location = useLocation();
    const _ = React.useContext(LocalizationContext);
    return (
        <p>
            {_('error_invalid_subsection') + location.pathname}
        </p>
    );
}

function CheckAuthorization({
    isAllowed,
    returnLocation,
    _,
    children,
}) {
    const history = useHistory();
    if (!isAllowed) {
        const [preLink, inLink, postLink] = _('error_unauthorized').split(/\{(?:link_begin|link_end)\}/);
        history.push('/sign/in', {
            returnLocation,
        });

        return (
            <p>
                {preLink}<Link to="/sign/in">{inLink}</Link>{postLink}
            </p>
        );
    } else {
        return children;
    }
}

CheckAuthorization.propTypes = {
    isAllowed: PropTypes.bool.isRequired,
    returnLocation: PropTypes.string,
    _: PropTypes.func.isRequired,
    children: PropTypes.any,
};

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
    globalMessage,
    sourcesState,
    setSourcesState,
    sources,
    setSources,
    tags,
    reloadAll,
}) {
    const [navExpanded, setNavExpanded] = React.useState(false);
    const smartphone = useIsSmartphone();
    const offlineEnabled = useListenableValue(selfoss.db.enableOffline);
    const [entriesPage, setEntriesPage] = React.useState(null);
    const configuration = React.useContext(ConfigurationContext);

    React.useEffect(() => {
        // init shortcut handler
        const destroyShortcuts = makeShortcuts();

        return () => {
            destroyShortcuts();
        };
    }, []);

    // TODO: move stuff that depends on this to the App.
    const history = useHistory();
    React.useEffect(() => {
        selfoss.history = history;
    }, [history]);

    // Prepare path of the homepage for redirecting from /
    let homePagePath = configuration.homepage.split('/');
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

    const [title, setTitle] = useState(null);
    const [globalUnreadCount, setGlobalUnreadCount] = useState(null);
    React.useEffect(() => {
        document.title = (title ?? configuration.htmlTitle) + ((globalUnreadCount ?? 0) > 0 ? ` (${globalUnreadCount})` : '');
    }, [configuration, title, globalUnreadCount]);

    const _ = React.useContext(LocalizationContext);

    const isAllowedToRead = useAllowedToRead();
    const isAllowedToWrite = useAllowedToWrite();

    return (
        <React.StrictMode>
            <Message message={globalMessage} />

            <Switch>
                <Route path="/sign/in">
                    {/* menu open for smartphone */}
                    <div id="loginform" role="main">
                        <LoginForm
                            {...{offlineEnabled}}
                        />
                    </div>
                </Route>

                <Route path="/password">
                    <CheckAuthorization
                        isAllowed={isAllowedToWrite}
                        returnLocation="/password"
                        _={_}
                    >
                        <div id="hashpasswordbody" role="main">
                            <HashPassword
                                setTitle={setTitle}
                            />
                        </div>
                    </CheckAuthorization>
                </Route>

                <Route path="/opml">
                    <CheckAuthorization
                        isAllowed={selfoss.isAllowedToWrite()}
                        returnLocation="/opml"
                        _={_}
                    >
                        <main id="opmlbody">
                            <OpmlImport
                                setTitle={setTitle}
                            />
                        </main>
                    </CheckAuthorization>
                </Route>

                <Route path="/">
                    <CheckAuthorization
                        isAllowed={isAllowedToRead}
                        _={_}
                    >
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
                                aria-label={_('settingsbutton')}
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
                                    sourcesState={sourcesState}
                                    setSourcesState={setSourcesState}
                                    sources={sources}
                                    setSources={setSources}
                                    tags={tags}
                                    reloadAll={reloadAll}
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
                                            configuration={configuration}
                                            navSourcesExpanded={navSourcesExpanded}
                                            unreadItemsCount={unreadItemsCount}
                                            setGlobalUnreadCount={setGlobalUnreadCount}
                                        />
                                    )}
                                </Route>
                                <Route path="/manage/sources">
                                    <SourcesPage
                                        tags={tags}
                                    />
                                </Route>
                                <Route path="*">
                                    <NotFound />
                                </Route>
                            </Switch>
                        </div>
                    </CheckAuthorization>
                </Route>
            </Switch>
        </React.StrictMode>
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
    globalMessage: nullable(PropTypes.object).isRequired,
    sourcesState: PropTypes.oneOf(Object.values(LoadingState)).isRequired,
    setSourcesState: PropTypes.func.isRequired,
    sources: PropTypes.arrayOf(PropTypes.object).isRequired,
    setSources: PropTypes.func.isRequired,
    tags: PropTypes.arrayOf(PropTypes.object).isRequired,
    reloadAll: PropTypes.func.isRequired,
};

export default class App extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            /**
             * tag repository
             */
            tags: [],
            tagsState: LoadingState.INITIAL,

            /**
             * source repository
             */
            sources: [],
            sourcesState: LoadingState.INITIAL,

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

            /**
             * Global message popup.
             * @var ?Object.{message: string, actions: Array.<Object.{label: string, callback: function>}, isError: bool}
             */
            globalMessage: null,
        };

        this._ = this._.bind(this);
        this.setTags = this.setTags.bind(this);
        this.setTagsState = this.setTagsState.bind(this);
        this.setSources = this.setSources.bind(this);
        this.setSourcesState = this.setSourcesState.bind(this);
        this.setOfflineState = this.setOfflineState.bind(this);
        this.setNavSourcesExpanded = this.setNavSourcesExpanded.bind(this);
        this.setUnreadItemsCount = this.setUnreadItemsCount.bind(this);
        this.setUnreadItemsOfflineCount = this.setUnreadItemsOfflineCount.bind(this);
        this.setStarredItemsCount = this.setStarredItemsCount.bind(this);
        this.setStarredItemsOfflineCount = this.setStarredItemsOfflineCount.bind(this);
        this.setAllItemsCount = this.setAllItemsCount.bind(this);
        this.setAllItemsOfflineCount = this.setAllItemsOfflineCount.bind(this);
        this.setGlobalMessage = this.setGlobalMessage.bind(this);
        this.reloadAll = this.reloadAll.bind(this);
    }

    setTags(tags) {
        if (typeof tags === 'function') {
            this.setState({
                tags: tags(this.state.tags)
            });
        } else {
            this.setState({ tags });
        }
    }

    setTagsState(tagsState) {
        if (typeof tagsState === 'function') {
            this.setState({
                tagsState: tagsState(this.state.tagsState)
            });
        } else {
            this.setState({ tagsState });
        }
    }

    setSources(sources) {
        if (typeof sources === 'function') {
            this.setState({
                sources: sources(this.state.sources)
            });
        } else {
            this.setState({ sources });
        }
    }

    setSourcesState(sourcesState) {
        if (typeof sourcesState === 'function') {
            this.setState({
                sourcesState: sourcesState(this.state.sourcesState)
            });
        } else {
            this.setState({ sourcesState });
        }
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

    setGlobalMessage(globalMessage) {
        if (typeof globalMessage === 'function') {
            this.setState({
                globalMessage: globalMessage(this.state.globalMessage)
            });
        } else {
            this.setState({ globalMessage });
        }
    }

    /**
     * Triggers fetching news from all sources.
     * @return Promise<undefined>
     */
    reloadAll() {
        if (!selfoss.isOnline()) {
            return Promise.resolve();
        }

        return sourceRequests.refreshAll().then(() => {
            // probe stats and prompt reload to the user
            selfoss.dbOnline.sync().then(() => {
                if (this.state.unreadItemsCount > 0) {
                    this.showMessage(this._('sources_refreshed'), [
                        {
                            label: this._('reload_list'),
                            callback() {
                                document.querySelector('#nav-filter-unread').click();
                            }
                        }
                    ]);
                }
            });
        }).catch((error) => {
            this.showError(this._('error_refreshing_source') + ' ' + error.message);
        });
    }

    /**
    * Obtain a localized message for given key, substituting placeholders for values, when given.
    * @param string key
    * @param ?array parameters
    * @return string
    */
    _(identifier, params) {
        const fallbackLanguage = 'en';
        const langKey = `lang_${identifier}`;

        let preferredLanguage = this.props.configuration.language;

        // locale auto-detection
        if (preferredLanguage === null) {
            if ('languages' in navigator) {
                preferredLanguage = navigator.languages.find(lang => Object.keys(locales).includes(lang));
            }
        }

        if (!Object.keys(locales).includes(preferredLanguage)) {
            preferredLanguage = fallbackLanguage;
        }

        let translated = locales[preferredLanguage][langKey] || locales[fallbackLanguage][langKey] || `#untranslated:${identifier}`;

        if (params) {
            translated = i18nFormat(translated, params);
        }

        return translated;
    }


    /**
     * Show error message in the message bar in the UI.
     *
     * @param {string} message
     * @return void
     */
    showError(message) {
        this.showMessage(message, [], true);
    }


    /**
     * Show message in the message bar in the UI.
     *
     * @param {string} message
     * @param {Array.<Object.{label: string, callback: function>} actions
     * @param {bool} isError
     * @return void
     */
    showMessage(message, actions = [], isError = false) {
        this.setGlobalMessage({ message, actions, isError });
    }


    notifyNewVersion(cb) {
        if (!cb) {
            cb = function() {
                window.location.reload();
            };
        }

        this.showMessage(this._('app_update'), [
            {
                label: this._('app_reload'),
                callback: cb
            }
        ]);
    }


    refreshTagSourceUnread(tagCounts, sourceCounts, diff = true) {
        this.setTags((tags) =>
            tags.map((tag) => {
                if (!(tag.tag in tagCounts)) {
                    return tag;
                }

                let unread;
                if (diff) {
                    unread = tag.unread + tagCounts[tag.tag];
                } else {
                    unread = tagCounts[tag.tag];
                }

                return {
                    ...tag,
                    unread
                };
            })
        );

        this.setSources((sources) =>
            sources.map((source) => {
                if (!(source.id in sourceCounts)) {
                    return source;
                }

                let unread;
                if (diff) {
                    unread = source.unread + sourceCounts[source.id];
                } else {
                    unread = sourceCounts[source.id];
                }

                return {
                    ...source,
                    unread
                };
            })
        );
    }


    refreshOfflineCounts(offlineCounts) {
        for (let [kind, newCount] of Object.entries(offlineCounts)) {
            if (newCount === 'keep') {
                continue;
            }

            if (kind === 'unread') {
                this.setUnreadItemsOfflineCount(newCount);
            } else if (kind === 'starred') {
                this.setStarredItemsOfflineCount(newCount);
            } else if (kind === 'newest') {
                this.setAllItemsOfflineCount(newCount);
            }
        }
    }


    render() {
        return (
            <ConfigurationContext.Provider value={this.props.configuration}>
                <LocalizationContext.Provider value={this._}>
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
                        globalMessage={this.state.globalMessage}
                        sourcesState={this.state.sourcesState}
                        setSourcesState={this.setSourcesState}
                        sources={this.state.sources}
                        setSources={this.setSources}
                        tags={this.state.tags}
                        reloadAll={this.reloadAll}
                    />
                </LocalizationContext.Provider>
            </ConfigurationContext.Provider>
        );
    }
}

App.propTypes = {
    configuration: PropTypes.object.isRequired,
};

/**
 * Creates the selfoss single-page application
 * with the required contexts.
 */
export function createApp({
    basePath,
    appRef,
    configuration,
}) {
    return (
        <Router basename={basePath}>
            <App
                ref={appRef}
                configuration={configuration}
            />
        </Router>
    );
}
