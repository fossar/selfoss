import 'regenerator-runtime/runtime';
import './jquery';
import selfoss from './selfoss-base';
import './selfoss-db-online';
import './selfoss-db-offline';
import './selfoss-db';
import '@fancyapps/fancybox';

selfoss.init();

// make selfoss available in console for debugging
window.selfoss = selfoss;
