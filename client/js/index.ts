import 'regenerator-runtime/runtime';
import base from './selfoss-base';

base.init();

declare global {
    interface Window {
        selfoss: base;
    }
}

// make selfoss available in console for debugging
window.selfoss = base;
