import React from 'react';
import { HashRouter as Router } from 'react-router-dom';
import ReactDOM from 'react-dom';
import locales from './locales';
import selfoss from './selfoss-base';
import App from './templates/App';
import { LoadingState } from './requests/LoadingState';

/**
 * Creates the selfoss single-page application
 * with the required contexts.
 */
function createApp(appRef) {
    return (
        <Router hashType="noslash">
            <App ref={appRef} />
        </Router>
    );
}

/**
 * ui change functions
 */
selfoss.ui = {
    /**
     * Create basic DOM structure of the page.
     */
    init: function() {
        document.getElementById('js-loading-message')?.remove();

        const mainUi = document.createElement('div');
        document.body.appendChild(mainUi);
        mainUi.classList.add('app-toplevel');

        ReactDOM.render(
            createApp((app) => {
                selfoss.app = app;
            }),
            mainUi
        );

        // Cannot add these to the append above, since jQuery automatically cache-busts links, which would prevent them from loading off-line.
        if (selfoss.config.userCss !== null) {
            let link = document.createElement('link');
            link.setAttribute('rel', 'stylesheet');
            link.setAttribute('href', `user.css?v=${selfoss.config.userCss}`);
            document.head.appendChild(link);
        }
        if (selfoss.config.userJs !== null) {
            let script = document.createElement('script');
            script.setAttribute('src', `user.js?v=${selfoss.config.userJs}`);
            document.body.appendChild(script);
        }

        function loggedinChanged(event) {
            document.body.classList.toggle('loggedin', event.value);
        }
        // It might happen that the value changes before event handler is attached.
        loggedinChanged({ value: selfoss.loggedin.value });
        selfoss.loggedin.addEventListener('change', loggedinChanged);
    },

    showLogin: function(error = '') {
        selfoss.history.push('/login');
        // TODO: Use location state once we switch to BrowserRouter
        selfoss.app.setLoginFormError(error);
        document.querySelector('#username').focus();
    },


    setOffline: function() {
        selfoss.app.setOfflineState(true);
    },


    setOnline: function() {
        selfoss.app.setOfflineState(false);
    },


    /*
     * This is a naive and partial implementation for parsing the
     * local-aware formatted strings from the Fat-Free Framework.
     * The full spec is at https://fatfreeframework.com/3.6/base#format and is
     * not fully implemented.
     */
    i18nFormat: function(translated, params) {
        var formatted = '';

        var curChar = undefined;
        var buffer = '';

        var state = 'out';
        var placeholder = undefined;
        var plural = undefined;
        var pluralKeyword = undefined;
        var pluralValue = undefined;

        for (var i = 0, len = translated.length; i < len; i++) {
            curChar = translated.charAt(i);
            switch (curChar) {
            case '{':
                if (placeholder) {
                    if (state == 'plural') {
                        pluralKeyword = buffer.trim();
                        if (['zero', 'one', 'other'].includes(pluralKeyword)) {
                            buffer = '';
                        } else {
                            pluralKeyword = undefined;
                        }
                    }
                } else {
                    formatted = formatted + buffer;
                    buffer = '';
                    placeholder = {};
                    state = 'index';
                }
                break;
            case '}':
            case ',':
                if (placeholder) {
                    if (state == 'index') {
                        placeholder.index = parseInt(buffer.trim());
                        placeholder.value = params[placeholder.index];
                        buffer = '';
                    } else if (state == 'type') {
                        placeholder.type = buffer.trim();
                        buffer = '';
                        if (placeholder.type == 'plural') {
                            plural = {};
                            state = 'plural';
                        }
                    }
                    if (curChar == '}') {
                        if (state == 'plural' && pluralKeyword) {
                            plural[pluralKeyword] = buffer;
                            buffer = '';
                            pluralKeyword = undefined;
                        } else if (plural) {
                            if ('zero' in plural
                                    && placeholder.value === 0) {
                                pluralValue = plural.zero;
                            } else if ('one' in plural
                                            && placeholder.value == 1) {
                                pluralValue = plural.one;
                            } else {
                                pluralValue = plural.other;
                            }
                            formatted = formatted + pluralValue.replace('#', placeholder.value);
                            plural = undefined;
                            placeholder = undefined;
                            state = 'out';
                        } else {
                            formatted = formatted + placeholder.value;
                            placeholder = undefined;
                            state = 'out';
                        }
                    } else if (curChar == ',' && state == 'index') {
                        state = 'type';
                    }
                }
                break;
            default:
                buffer = buffer + curChar;
                break;
            }
        }

        if (state != 'out') {
            return 'Error formatting \'' + translated + '\', bug report?';
        }

        formatted = formatted + buffer;

        return formatted;
    },


    /**
    * Obtain a localized message for given key, substituting placeholders for values, when given.
    * @param string key
    * @param ?array parameters
    * @return string
    */
    _: function(identifier, params) {
        const fallbackLanguage = 'en';
        const langKey = `lang_${identifier}`;

        let preferredLanguage = selfoss.config.language;

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
            translated = selfoss.ui.i18nFormat(translated, params);
        }

        return translated;
    },


    /**
     * Show error message in the message bar in the UI.
     *
     * @param {string} message
     * @return void
     */
    showError: function(message) {
        selfoss.ui.showMessage(message, [], true);
    },


    /**
     * Show message in the message bar in the UI.
     *
     * @param {string} message
     * @param {Array.<Object.{label: string, callback: function>} actions
     * @param {bool} isError
     * @return void
     */
    showMessage: function(message, actions = [], isError = false) {
        selfoss.app.setGlobalMessage({ message, actions, isError });
    },


    notifyNewVersion: function(cb) {
        if (!cb) {
            cb = function() {
                window.location.reload();
            };
        }

        selfoss.ui.showMessage(selfoss.ui._('app_update'), [
            {
                label: selfoss.ui._('app_reload'),
                callback: cb
            }
        ]);
    },


    refreshTagSourceUnread: function(tagCounts, sourceCounts, diff = true) {
        selfoss.app.setTags((tags) =>
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

        selfoss.app.setSources((sources) =>
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
    },


    refreshOfflineCounts: function(offlineCounts) {
        for (let [kind, newCount] of Object.entries(offlineCounts)) {
            if (newCount === 'keep') {
                continue;
            }

            if (kind === 'unread') {
                selfoss.app.setUnreadItemsOfflineCount(newCount);
            } else if (kind === 'starred') {
                selfoss.app.setStarredItemsOfflineCount(newCount);
            } else if (kind === 'newest') {
                selfoss.app.setAllItemsOfflineCount(newCount);
            }
        }
    }


};
