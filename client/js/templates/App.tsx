import React, {
    Dispatch,
    SetStateAction,
    useCallback,
    use,
    useEffect,
    useEffectEvent,
    useMemo,
    useState,
    MouseEvent,
} from 'react';
import {
    BrowserRouter as Router,
    Routes,
    Route,
    Link,
    Navigate,
    useNavigate,
} from 'react-router';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Collapse } from '@kunukn/react-collapse';
import classNames from 'classnames';
import selfoss from '../selfoss-base';
import HashPassword from './HashPassword';
import OpmlImport from './OpmlImport';
import LoginForm from './LoginForm';
import SourcesPage from './SourcesPage';
import EntriesPage, { StateHolder as EntriesPageStateful } from './EntriesPage';
import Navigation from './Navigation';
import SearchList from './SearchList';
import makeShortcuts from '../shortcuts';
import * as icons from '../icons';
import { useAllowedToRead, useAllowedToWrite } from '../helpers/authorizations';
import { useIsSmartphone, useListenableValue } from '../helpers/hooks';
import { i18nFormat, LocalizationContext } from '../helpers/i18n';
import { Configuration, ConfigurationContext } from '../model/Configuration';
import { LoadingState } from '../requests/LoadingState';
import * as sourceRequests from '../requests/sources';
import locales from '../locales';
import { useEntriesParams, useLocation } from '../helpers/uri';
import { NavSource, NavTag } from '../requests/items';

type MessageAction = {
    label: string;
    callback: (event: React.MouseEvent<HTMLButtonElement>) => void;
};

type GlobalMessage = {
    message: string;
    actions: Array<MessageAction>;
    isError?: boolean;
};

function handleNavToggle(args: {
    event: MouseEvent<HTMLButtonElement>;
    setNavExpanded: Dispatch<SetStateAction<boolean>>;
}): void {
    const { event, setNavExpanded } = args;
    event.preventDefault();

    // show hide navigation for mobile version
    setNavExpanded((expanded) => !expanded);
    window.scrollTo({ top: 0 });
}

function dismissMessage(event: React.MouseEvent<HTMLDivElement>): void {
    selfoss.app.setGlobalMessage(null);
    event.stopPropagation();
}

type MessageProps = {
    message: GlobalMessage | null;
};

/**
 * Global message bar for showing errors/information at the top of the page.
 * It watches globalMessage and updates/shows/hides itself as necessary
 * when the value changes.
 */
function Message(props: MessageProps): React.JSX.Element | null {
    const { message } = props;

    // Whenever message changes, dismiss it after 15 seconds.
    useEffect(() => {
        if (message !== null) {
            const dismissTimeout = window.setTimeout(() => {
                selfoss.app.setGlobalMessage(null);
            }, 15000);

            return () => {
                // Destory previous timeout.
                window.clearTimeout(dismissTimeout);
            };
        }
    }, [message]);

    return message !== null ? (
        <div
            id="message"
            className={classNames({ error: message.isError })}
            onClick={dismissMessage}
        >
            {message.message}
            {message.actions.map(({ label, callback }, index) => (
                <button key={index} type="button" onClick={callback}>
                    {label}
                </button>
            ))}
        </div>
    ) : null;
}

function NotFound(): React.JSX.Element {
    const location = useLocation();
    const _ = use(LocalizationContext);
    return <p>{_('error_invalid_subsection') + location.pathname}</p>;
}

type CheckAuthorizationProps = {
    isAllowed: boolean;
    returnLocation?: string;
    _: (translated: string, params?: { [index: string]: string }) => string;
    children: React.ReactNode;
};

function CheckAuthorization(props: CheckAuthorizationProps): React.ReactNode {
    const { isAllowed, returnLocation, _, children } = props;

    const navigate = useNavigate();

    const redirect = useEffectEvent(() => {
        navigate('/sign/in', {
            state: { returnLocation },
        });
    });

    useEffect(() => {
        if (!isAllowed) {
            redirect();
        }
    }, [isAllowed]);

    if (!isAllowed) {
        const [preLink, inLink, postLink] = _('error_unauthorized').split(
            /\{(?:link_begin|link_end)\}/,
        );

        return (
            <p>
                {preLink}
                <Link to="/sign/in">{inLink}</Link>
                {postLink}
            </p>
        );
    } else {
        return children;
    }
}

type EntriesFilterProps = {
    entriesRef: React.RefCallback<EntriesPageStateful>;
    setNavExpanded: Dispatch<SetStateAction<boolean>>;
    configuration: Configuration;
    navSourcesExpanded: boolean;
    unreadItemsCount: number;
    setGlobalUnreadCount: Dispatch<SetStateAction<number>>;
};

// Work around for regex patterns not being supported
// https://github.com/remix-run/react-router/issues/8254
function EntriesFilter(props: EntriesFilterProps): React.JSX.Element {
    const {
        entriesRef,
        setNavExpanded,
        configuration,
        navSourcesExpanded,
        unreadItemsCount,
        setGlobalUnreadCount,
    } = props;

    const params = useEntriesParams();

    if (params === null) {
        return <NotFound />;
    }

    return (
        <EntriesPage
            ref={entriesRef}
            setNavExpanded={setNavExpanded}
            configuration={configuration}
            navSourcesExpanded={navSourcesExpanded}
            unreadItemsCount={unreadItemsCount}
            setGlobalUnreadCount={setGlobalUnreadCount}
        />
    );
}

type PureAppProps = {
    navSourcesExpanded: boolean;
    setNavSourcesExpanded: Dispatch<SetStateAction<boolean>>;
    offlineState: boolean;
    allItemsCount: number;
    allItemsOfflineCount: number;
    unreadItemsCount: number;
    unreadItemsOfflineCount: number;
    starredItemsCount: number;
    starredItemsOfflineCount: number;
    globalMessage: GlobalMessage | null;
    sourcesState: LoadingState;
    setSourcesState: Dispatch<SetStateAction<LoadingState>>;
    sources: Array<NavSource>;
    setSources: Dispatch<SetStateAction<Array<NavSource>>>;
    tags: Array<NavTag>;
    reloadAll: () => Promise<void>;
};

function PureApp(props: PureAppProps): React.JSX.Element {
    const {
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
    } = props;

    const [navExpanded, setNavExpanded] = useState(false);
    const smartphone = useIsSmartphone();
    const offlineEnabled = useListenableValue(selfoss.db.enableOffline);
    const [entriesPage, setEntriesPage] = useState(null);
    const configuration = use(ConfigurationContext);

    useEffect(() => {
        // init shortcut handler
        const destroyShortcuts = makeShortcuts();

        return () => {
            destroyShortcuts();
        };
    }, []);

    // TODO: move stuff that depends on this to the App.
    const navigate = useNavigate();
    useEffect(() => {
        selfoss.navigate = navigate;
    }, [navigate]);

    // Prepare path of the homepage for redirecting from /
    const homePagePath = configuration.homepage.split('/');
    if (!homePagePath[1]) {
        homePagePath.push('all');
    }

    const menuButtonOnClick = useCallback(
        (event: MouseEvent<HTMLButtonElement>) =>
            handleNavToggle({ event, setNavExpanded }),
        [],
    );

    const entriesRef = useCallback((entriesPage: EntriesPageStateful) => {
        setEntriesPage(entriesPage);
        selfoss.entriesPage = entriesPage;

        return () => {
            setEntriesPage(null);
            selfoss.entriesPage = null;
        };
    }, []);

    const [title, setTitle] = useState(null);
    const [globalUnreadCount, setGlobalUnreadCount] = useState(null);
    const titleText = useMemo(
        () =>
            (title ?? configuration.htmlTitle) +
            ((globalUnreadCount ?? 0) > 0 ? ` (${globalUnreadCount})` : ''),
        [configuration, title, globalUnreadCount],
    );

    const _ = use(LocalizationContext);

    const isAllowedToRead = useAllowedToRead();
    const isAllowedToWrite = useAllowedToWrite();

    return (
        <React.StrictMode>
            <title>{titleText}</title>

            <Message message={globalMessage} />

            <Routes>
                <Route
                    path="/sign/in"
                    element={
                        /* menu open for smartphone */
                        <div id="loginform" role="main">
                            <LoginForm {...{ offlineEnabled }} />
                        </div>
                    }
                />

                <Route
                    path="/password"
                    element={
                        <CheckAuthorization
                            isAllowed={isAllowedToWrite}
                            returnLocation="/password"
                            _={_}
                        >
                            <div id="hashpasswordbody" role="main">
                                <HashPassword setTitle={setTitle} />
                            </div>
                        </CheckAuthorization>
                    }
                />

                <Route
                    path="/opml"
                    element={
                        <CheckAuthorization
                            isAllowed={isAllowedToWrite}
                            returnLocation="/opml"
                            _={_}
                        >
                            <main id="opmlbody">
                                <OpmlImport setTitle={setTitle} />
                            </main>
                        </CheckAuthorization>
                    }
                />

                <Route
                    path="*"
                    element={
                        <CheckAuthorization isAllowed={isAllowedToRead} _={_}>
                            <div id="nav-mobile" role="navigation">
                                <div id="nav-mobile-logo">
                                    <div
                                        id="nav-mobile-count"
                                        className={classNames({
                                            'unread-count': true,
                                            offline: offlineState,
                                            online: !offlineState,
                                            unread: unreadItemsCount > 0,
                                        })}
                                    >
                                        <span
                                            className={classNames({
                                                'offline-count': true,
                                                offline: offlineState,
                                                online: !offlineState,
                                                diff:
                                                    unreadItemsCount !==
                                                        unreadItemsOfflineCount &&
                                                    unreadItemsOfflineCount,
                                            })}
                                        >
                                            {unreadItemsOfflineCount > 0
                                                ? unreadItemsOfflineCount
                                                : ''}
                                        </span>
                                        <span className="count">
                                            {unreadItemsCount}
                                        </span>
                                    </div>
                                </div>
                                <button
                                    id="nav-mobile-settings"
                                    accessKey="t"
                                    aria-label={_('settingsbutton')}
                                    onClick={menuButtonOnClick}
                                >
                                    <FontAwesomeIcon
                                        icon={icons.menu}
                                        size="2x"
                                    />
                                </button>
                            </div>

                            {/* navigation */}
                            <Collapse
                                isOpen={!smartphone || navExpanded}
                                className="collapse-css-transition"
                            >
                                <div id="nav" role="navigation">
                                    <Navigation
                                        entriesPage={entriesPage}
                                        setNavExpanded={setNavExpanded}
                                        navSourcesExpanded={navSourcesExpanded}
                                        setNavSourcesExpanded={
                                            setNavSourcesExpanded
                                        }
                                        offlineState={offlineState}
                                        allItemsCount={allItemsCount}
                                        allItemsOfflineCount={
                                            allItemsOfflineCount
                                        }
                                        unreadItemsCount={unreadItemsCount}
                                        unreadItemsOfflineCount={
                                            unreadItemsOfflineCount
                                        }
                                        starredItemsCount={starredItemsCount}
                                        starredItemsOfflineCount={
                                            starredItemsOfflineCount
                                        }
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
                                <Routes>
                                    <Route
                                        path="/"
                                        element={
                                            <Navigate
                                                to={`/${homePagePath.join('/')}`}
                                                replace
                                            />
                                        }
                                    />
                                    <Route
                                        path="/:filter/:category/:id?"
                                        element={
                                            <EntriesFilter
                                                entriesRef={entriesRef}
                                                setNavExpanded={setNavExpanded}
                                                configuration={configuration}
                                                navSourcesExpanded={
                                                    navSourcesExpanded
                                                }
                                                unreadItemsCount={
                                                    unreadItemsCount
                                                }
                                                setGlobalUnreadCount={
                                                    setGlobalUnreadCount
                                                }
                                            />
                                        }
                                    />
                                    <Route
                                        path="/manage/sources/add?"
                                        element={<SourcesPage />}
                                    />
                                    <Route path="*" element={<NotFound />} />
                                </Routes>
                            </div>
                        </CheckAuthorization>
                    }
                />
            </Routes>
        </React.StrictMode>
    );
}

type AppProps = {
    configuration: Configuration;
};

type AppState = {
    /**
     * tag repository
     */
    tags: Array<NavTag>;
    tagsState: LoadingState;

    /**
     * source repository
     */
    sources: Array<NavSource>;
    sourcesState: LoadingState;

    /**
     * true when sources in the sidebar are expanded
     * and we should fetch info about them in API requests.
     */
    navSourcesExpanded: boolean;

    /**
     * whether off-line mode is enabled
     */
    offlineState: boolean;

    /**
     * number of unread items
     */
    unreadItemsCount: number;

    /**
     * number of unread items available offline
     */
    unreadItemsOfflineCount: number;

    /**
     * number of starred items
     */
    starredItemsCount: number;

    /**
     * number of starred items available offline
     */
    starredItemsOfflineCount: number;

    /**
     * number of all items
     */
    allItemsCount: number;

    /**
     * number of all items available offline
     */
    allItemsOfflineCount: number;

    /**
     * Global message popup.
     */
    globalMessage: GlobalMessage | null;
};

export class App extends React.Component<AppProps, AppState> {
    constructor(props: AppProps) {
        super(props);
        this.state = {
            tags: [],
            tagsState: LoadingState.INITIAL,
            sources: [],
            sourcesState: LoadingState.INITIAL,
            navSourcesExpanded: false,
            offlineState: false,
            unreadItemsCount: 0,
            unreadItemsOfflineCount: 0,
            starredItemsCount: 0,
            starredItemsOfflineCount: 0,
            allItemsCount: 0,
            allItemsOfflineCount: 0,
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
        this.setUnreadItemsOfflineCount =
            this.setUnreadItemsOfflineCount.bind(this);
        this.setStarredItemsCount = this.setStarredItemsCount.bind(this);
        this.setStarredItemsOfflineCount =
            this.setStarredItemsOfflineCount.bind(this);
        this.setAllItemsCount = this.setAllItemsCount.bind(this);
        this.setAllItemsOfflineCount = this.setAllItemsOfflineCount.bind(this);
        this.setGlobalMessage = this.setGlobalMessage.bind(this);
        this.reloadAll = this.reloadAll.bind(this);
    }

    setTags(tags: SetStateAction<Array<NavTag>>): void {
        if (typeof tags === 'function') {
            this.setState((state) => ({
                tags: tags(state.tags),
            }));
        } else {
            this.setState({ tags });
        }
    }

    setTagsState(tagsState: SetStateAction<LoadingState>): void {
        if (typeof tagsState === 'function') {
            this.setState((state) => ({
                tagsState: tagsState(state.tagsState),
            }));
        } else {
            this.setState({ tagsState });
        }
    }

    setSources(sources: SetStateAction<Array<NavSource>>): void {
        if (typeof sources === 'function') {
            this.setState((state) => ({
                sources: sources(state.sources),
            }));
        } else {
            this.setState({ sources });
        }
    }

    setSourcesState(sourcesState: SetStateAction<LoadingState>): void {
        if (typeof sourcesState === 'function') {
            this.setState((state) => ({
                sourcesState: sourcesState(state.sourcesState),
            }));
        } else {
            this.setState({ sourcesState });
        }
    }

    setOfflineState(offlineState: SetStateAction<boolean>): void {
        if (typeof offlineState === 'function') {
            this.setState((state) => ({
                offlineState: offlineState(state.offlineState),
            }));
        } else {
            this.setState({ offlineState });
        }
    }

    setNavSourcesExpanded(navSourcesExpanded: SetStateAction<boolean>): void {
        if (typeof navSourcesExpanded === 'function') {
            this.setState((state) => ({
                navSourcesExpanded: navSourcesExpanded(
                    state.navSourcesExpanded,
                ),
            }));
        } else {
            this.setState({ navSourcesExpanded });
        }
    }

    setUnreadItemsCount(unreadItemsCount: SetStateAction<number>): void {
        if (typeof unreadItemsCount === 'function') {
            this.setState((state) => ({
                unreadItemsCount: unreadItemsCount(state.unreadItemsCount),
            }));
        } else {
            this.setState({ unreadItemsCount });
        }
    }

    setUnreadItemsOfflineCount(
        unreadItemsOfflineCount: SetStateAction<number>,
    ): void {
        if (typeof unreadItemsOfflineCount === 'function') {
            this.setState((state) => ({
                unreadItemsOfflineCount: unreadItemsOfflineCount(
                    state.unreadItemsOfflineCount,
                ),
            }));
        } else {
            this.setState({ unreadItemsOfflineCount });
        }
    }

    setStarredItemsCount(starredItemsCount: SetStateAction<number>): void {
        if (typeof starredItemsCount === 'function') {
            this.setState((state) => ({
                starredItemsCount: starredItemsCount(state.starredItemsCount),
            }));
        } else {
            this.setState({ starredItemsCount });
        }
    }

    setStarredItemsOfflineCount(
        starredItemsOfflineCount: SetStateAction<number>,
    ): void {
        if (typeof starredItemsOfflineCount === 'function') {
            this.setState((state) => ({
                starredItemsOfflineCount: starredItemsOfflineCount(
                    state.starredItemsOfflineCount,
                ),
            }));
        } else {
            this.setState({ starredItemsOfflineCount });
        }
    }

    setAllItemsCount(allItemsCount: SetStateAction<number>): void {
        if (typeof allItemsCount === 'function') {
            this.setState((state) => ({
                allItemsCount: allItemsCount(state.allItemsCount),
            }));
        } else {
            this.setState({ allItemsCount });
        }
    }

    setAllItemsOfflineCount(
        allItemsOfflineCount: SetStateAction<number>,
    ): void {
        if (typeof allItemsOfflineCount === 'function') {
            this.setState((state) => ({
                allItemsOfflineCount: allItemsOfflineCount(
                    state.allItemsOfflineCount,
                ),
            }));
        } else {
            this.setState({ allItemsOfflineCount });
        }
    }

    setGlobalMessage(
        globalMessage: SetStateAction<GlobalMessage | null>,
    ): void {
        if (typeof globalMessage === 'function') {
            this.setState((state) => ({
                globalMessage: globalMessage(state.globalMessage),
            }));
        } else {
            this.setState({ globalMessage });
        }
    }

    /**
     * Triggers fetching news from all sources.
     */
    reloadAll(): Promise<void> {
        if (!selfoss.isOnline()) {
            return Promise.resolve();
        }

        return sourceRequests
            .refreshAll()
            .then(() => {
                // probe stats and prompt reload to the user
                selfoss.dbOnline.sync().then(() => {
                    if (this.state.unreadItemsCount > 0) {
                        this.showMessage(this._('sources_refreshed'), [
                            {
                                label: this._('reload_list'),
                                callback() {
                                    document
                                        .querySelector<HTMLAnchorElement>(
                                            '#nav-filter-unread',
                                        )
                                        .click();
                                },
                            },
                        ]);
                    }
                });
            })
            .catch((error) => {
                this.showError(
                    this._('error_refreshing_source') + ' ' + error.message,
                );
            });
    }

    /**
     * Obtain a localized message for given key, substituting placeholders for values, when given.
     */
    _(identifier: string, params?: { [index: string]: string }): string {
        const fallbackLanguage = 'en';

        let preferredLanguage = this.props.configuration.language;

        // locale auto-detection
        if (preferredLanguage === null) {
            if ('languages' in navigator) {
                preferredLanguage = navigator.languages.find((lang) =>
                    Object.keys(locales).includes(lang),
                );
            }
        }

        if (!Object.keys(locales).includes(preferredLanguage)) {
            preferredLanguage = fallbackLanguage;
        }

        let translated =
            locales[preferredLanguage][identifier] ||
            locales[fallbackLanguage][identifier] ||
            `#untranslated:${identifier}`;

        if (params) {
            translated = i18nFormat(translated, params);
        }

        return translated;
    }

    /**
     * Show error message in the message bar in the UI.
     */
    showError(message: string): void {
        this.showMessage(message, [], true);
    }

    /**
     * Show message in the message bar in the UI.
     */
    showMessage(
        message: string,
        actions: Array<MessageAction> = [],
        isError: boolean = false,
    ): void {
        this.setGlobalMessage({ message, actions, isError });
    }

    notifyNewVersion(cb: () => void): void {
        if (!cb) {
            cb = () => {
                window.location.reload();
            };
        }

        this.showMessage(this._('app_update'), [
            {
                label: this._('app_reload'),
                callback: cb,
            },
        ]);
    }

    refreshTagSourceUnread(
        tagCounts: { [index: string]: number },
        sourceCounts: { [index: number]: number },
        diff: boolean = true,
    ): void {
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
                    unread,
                };
            }),
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
                    unread,
                };
            }),
        );
    }

    refreshOfflineCounts(offlineCounts: {
        [index in 'unread' | 'starred' | 'newest']: number | 'keep';
    }): void {
        for (const [kind, newCount] of Object.entries(offlineCounts)) {
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

    render(): React.JSX.Element {
        return (
            <ConfigurationContext value={this.props.configuration}>
                <LocalizationContext value={this._}>
                    <PureApp
                        navSourcesExpanded={this.state.navSourcesExpanded}
                        setNavSourcesExpanded={this.setNavSourcesExpanded}
                        offlineState={this.state.offlineState}
                        allItemsCount={this.state.allItemsCount}
                        allItemsOfflineCount={this.state.allItemsOfflineCount}
                        unreadItemsCount={this.state.unreadItemsCount}
                        unreadItemsOfflineCount={
                            this.state.unreadItemsOfflineCount
                        }
                        starredItemsCount={this.state.starredItemsCount}
                        starredItemsOfflineCount={
                            this.state.starredItemsOfflineCount
                        }
                        globalMessage={this.state.globalMessage}
                        sourcesState={this.state.sourcesState}
                        setSourcesState={this.setSourcesState}
                        sources={this.state.sources}
                        setSources={this.setSources}
                        tags={this.state.tags}
                        reloadAll={this.reloadAll}
                    />
                </LocalizationContext>
            </ConfigurationContext>
        );
    }
}

/**
 * Creates the selfoss single-page application
 * with the required contexts.
 */
export function createApp(args: {
    basePath: string;
    appRef: React.Ref<App>;
    configuration: Configuration;
}): React.JSX.Element {
    const { basePath, appRef, configuration } = args;

    return (
        <Router basename={basePath}>
            <App ref={appRef} configuration={configuration} />
        </Router>
    );
}
