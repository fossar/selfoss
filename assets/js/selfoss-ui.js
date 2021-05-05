import React from 'react';
import { HashRouter as Router } from 'react-router-dom';
import ReactDOM from 'react-dom';
import selfoss from './selfoss-base';
import App from './templates/App';

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


    setOffline: function() {
        selfoss.app.setOfflineState(true);
    },


    setOnline: function() {
        selfoss.app.setOfflineState(false);
    },


};
