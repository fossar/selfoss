import 'regenerator-runtime/runtime';
import './jquery';
import './lazy-image-loader';
import 'spectrum-colorpicker';
import 'jquery-hotkeys';
import selfoss from './selfoss-base';
import './selfoss-shares';
import './selfoss-db';
import './selfoss-ui';
import './selfoss-events';
import './selfoss-events-navigation';
import './selfoss-events-search';
import './selfoss-events-entries';
import './selfoss-events-entriestoolbar';
import './selfoss-events-sources';
import './selfoss-shortcuts';
import '@fancyapps/fancybox';

selfoss.init();

// make selfoss available in console for debugging
window.selfoss = selfoss;
