import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import Collapse from '@kunukn/react-collapse';
import classNames from 'classnames';
import LoginForm from './LoginForm';
import Navigation from './Navigation';
import SearchList from './SearchList';
import makeShortcuts from '../shortcuts';

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


export default function App() {
    const [navExpanded, setNavExpanded] = React.useState(false);
    const [smartphone, setSmartphone] = React.useState(false);
    const [loginFormError, setLoginFormError] = React.useState(selfoss.loginFormError.value);
    const [offlineState, setOfflineState] = React.useState(selfoss.offlineState.value);
    const [offlineEnabled, setOfflineEnabled] = React.useState(selfoss.db.enableOffline.value);
    const [unreadItemsCount, setUnreadItemsCount] = React.useState(selfoss.unreadItemsCount.value);
    const [unreadItemsOfflineCount, setUnreadItemsOfflineCount] = React.useState(selfoss.unreadItemsOfflineCount.value);

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
    return (
        <React.Fragment>
            <Message />

            <div id="loginform" role="main">
                <LoginForm
                    error={loginFormError}
                    setError={setLoginFormError}
                    {...{offlineEnabled, setOfflineEnabled}}
                />
            </div>
            {/* menu open for smartphone */}
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
                    <Navigation />
                </div>
            </Collapse>

            <ul id="search-list">
                <SearchList />
            </ul>

            {/* content */}
            <div id="content" role="main">
            </div>
        </React.Fragment>
    );
}
