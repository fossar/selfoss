import 'regenerator-runtime/runtime';
import './jquery';
import 'spectrum-colorpicker';
import selfoss from './selfoss-base';
import './selfoss-shares';
import './selfoss-db-online';
import './selfoss-db-offline';
import './selfoss-db';
import './selfoss-ui';
import '@fancyapps/fancybox';

selfoss.init();

// make selfoss available in console for debugging
window.selfoss = selfoss;
