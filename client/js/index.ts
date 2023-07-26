import 'regenerator-runtime/runtime';
import selfoss from './selfoss-base';
import './selfoss-db-online';
import './selfoss-db-offline';
import './selfoss-db';

selfoss.init();

declare global {
    interface Window {
        selfoss: base;
    }
}

// make selfoss available in console for debugging
window.selfoss = selfoss;
