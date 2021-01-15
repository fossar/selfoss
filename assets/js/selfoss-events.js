import ReactDOM from 'react-dom';
import selfoss from './selfoss-base';
import * as SourcesPage from './templates/SourcesPage';
import * as EntriesPage from './templates/EntriesPage';
import { getAllSources } from './requests/sources';
import { filterTypeFromString } from './helpers/uri';
import { LoadingState } from './requests/LoadingState';

selfoss.events = {

    /* last hash before hash change */
    lasthash: '',

    path: null,
    lastpath: null,
    reloadSamePath: false,

    section: null,
    subsection: false,
    lastSubsection: null,

    entryId: null,

    /**
     * init events when page loads first time
     */
    init: function() {
        if (location.hash == '') {
            selfoss.events.initHash();
        }

        // scroll load more
        $(window).unbind('scroll').scroll(function() {
            if (!selfoss.config.autoStreamMore || selfoss.entriesPage === null) {
                return;
            }

            if (selfoss.entriesPage.state.hasMore
               && $('.stream-more').position().top < $(window).height() + $(window).scrollTop()
               && selfoss.entriesPage.state.loadingState !== LoadingState.LOADING) {
                document.querySelector('.stream-more').click();
            }
        });

        // hash change event
        window.onhashchange = selfoss.events.hashChange;

        // process current hash
        selfoss.events.processHash();
    },


    initHash: function() {
        var homePagePath = selfoss.config.homepage.split('/');
        if (!homePagePath[1]) {
            homePagePath.push('all');
        }
        selfoss.events.setHash(homePagePath[0], homePagePath[1]);
    },


    /**
     * handle History change
     */
    hashChange: function() {
        if (selfoss.events.processHashChange) {
            selfoss.events.processHash();
        }
    },

    /**
     * whether to process hash change events: when the hash is changed
     * programatically, the hash is set and this change event should not be
     * processed once more. In that case, the variable is set to false. The
     * default is to process hash change events that trigger for instance when
     * navigating using browser buttons (variable set to true).
     */
    processHashChange: true,

    processHash: function(hash = false) {
        var done = function() {
            selfoss.events.processHashChange = true;
        };

        if (hash) {
            selfoss.events.processHashChange = false;
            location.hash = hash;
        }

        // assume the hash is encoded
        hash = decodeURIComponent(location.href.split('#').splice(1).join('#'));

        if (!selfoss.events.reloadSamePath &&
            hash == selfoss.events.lasthash) {
            done();
            return;
        }

        // parse hash
        var hashPath = hash.split('/');

        selfoss.events.section = hashPath[0];

        if (hashPath.length > 1) {
            selfoss.events.subsection = hashPath[1];
        } else {
            selfoss.events.subsection = false;
        }
        selfoss.events.lastpath = selfoss.events.path;
        selfoss.events.path = selfoss.events.section
                              + '/' + selfoss.events.subsection;

        var entryId = null;
        if (hashPath.length > 2 && (entryId = parseInt(hashPath[2]))) {
            selfoss.events.entryId = entryId;
        } else {
            selfoss.events.entryId = null;
        }

        selfoss.events.lasthash = hash;

        // hash change indicates an entry open or close event (the path is
        // the same): do not reload list if list is the same and not
        // explicitely requested.
        if (!selfoss.events.reloadSamePath &&
             selfoss.events.lastpath == selfoss.events.path) {

            if (selfoss.isSmartphone()) {
                // if navigating using browser buttons and entry in hash,
                // open it.
                if (selfoss.events.entryId
                    && selfoss.events.processHashChange) {
                    selfoss.ui.entrySelect(selfoss.events.entryId);
                    selfoss.ui.entryExpand(selfoss.events.entryId);
                }

                // if navigating using browser buttons and entry opened,
                // close opened entry.
                if (!selfoss.events.entryId
                    && selfoss.events.processHashChange
                    && selfoss.ui.entryGetSelected() !== null) {
                    selfoss.ui.entrySelect(null);
                }
            } else {
                // if navigating using browser buttons and entry selected,
                // scroll to entry.
                if (selfoss.events.entryId
                    && selfoss.events.processHashChange) {
                    const entry = document.querySelector(`.entry[data-entry-id="${selfoss.events.entryId}"]`);
                    if (entry) {
                        entry.scrollIntoView();
                    }
                }
            }

            done();
            return;
        }

        if (hash !== 'sources' && selfoss.sourcesPage) {
            ReactDOM.unmountComponentAtNode(document.getElementById('content'));
            selfoss.sourcesPage = null;
        }

        if (!['newest', 'unread', 'starred'].includes(selfoss.events.section) && selfoss.entriesPage) {
            ReactDOM.unmountComponentAtNode(document.getElementById('content'));
            selfoss.entriesPage = null;
        }

        // load items
        if (['newest', 'unread', 'starred'].includes(selfoss.events.section)) {
            let filter = {
                type: filterTypeFromString(selfoss.events.section),
                tag: null,
                source: null
            };
            if (selfoss.events.subsection) {
                selfoss.events.lastSubsection = selfoss.events.subsection;
                if (selfoss.events.subsection.startsWith('tag-')) {
                    filter.tag = selfoss.events.subsection.substr(4);
                } else if (selfoss.events.subsection.startsWith('source-')) {
                    var sourceId = parseInt(selfoss.events.subsection.substr(7));
                    if (sourceId) {
                        filter.source = sourceId;
                        filter.sourcesNav = true;
                    }
                } else if (selfoss.events.subsection != 'all') {
                    selfoss.ui.showError(selfoss.ui._('error_invalid_subsection') + ' '
                                         + selfoss.events.subsection);
                    done();
                    return;
                }
            }

            selfoss.filter.update(filter);

            selfoss.events.reloadSamePath = false;
            selfoss.filterReset();

            if (!selfoss.entriesPage) {
                selfoss.entriesPage = EntriesPage.anchor(document.getElementById('content'));
            }

            selfoss.db.reloadList();
        } else if (hash == 'sources') { // load sources
            if (selfoss.events.subsection) {
                selfoss.ui.showError(selfoss.ui._('error_invalid_subsection') + ' '
                                     + selfoss.events.subsection);
                done();
                return;
            }

            if (selfoss.activeAjaxReq !== null) {
                selfoss.activeAjaxReq.controller.abort();
            }
            selfoss.activeAjaxReq = getAllSources();
            selfoss.activeAjaxReq.promise.then(({sources, spouts}) => {
                if (!selfoss.sourcesPage) {
                    selfoss.sourcesPage = SourcesPage.anchor(document.getElementById('content'));
                }
                selfoss.sourcesPage.setSpouts(spouts);
                selfoss.sourcesPage.setSources(sources);
            }).catch((error) => {
                if (error.name === 'AbortError') {
                    return;
                }

                selfoss.handleAjaxError(error, false).catch(function(error) {
                    selfoss.ui.showError(selfoss.ui._('error_loading') + ' ' + error.message);
                });
            });
        } else if (hash == 'login') {
            selfoss.ui.showLogin();
        } else {
            selfoss.ui.showError(selfoss.ui._('error_invalid_subsection') + ' ' + selfoss.events.section);
        }
        done();
    },


    setHash: function(section = 'same', subsection = 'same', entryId = false) {
        if (section === 'same') {
            section = selfoss.events.section;
        }
        var newHash = [section];

        if (subsection === 'same') {
            subsection = selfoss.events.lastSubsection;
        }
        if (subsection) {
            newHash.push(subsection.replace('%', '%25'));
        }

        if (entryId) {
            newHash.push(entryId);
        }
        selfoss.events.processHash('#' + newHash.join('/'));
    }
};
