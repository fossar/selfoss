import jQuery from 'jquery';
import mousewheel from 'jquery-mousewheel';
import scrollbar from 'malihu-custom-scrollbar-plugin';
window.$ = window.jQuery = jQuery; // workaround for https://github.com/parcel-bundler/parcel/issues/333

// register plug-ins
mousewheel(jQuery); // required by scrollbar
scrollbar(jQuery);
