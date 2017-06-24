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

    SCROLLBAR_UNLOADED: 0,
    SCROLLBAR_LOADING: 1,
    SCROLLBAR_LOADED: 2,

    scrollBarState: 0, /* SCROLLBAR_UNLOADED */

    /**
     * init events when page loads first time
     */
    init: function() {
        selfoss.events.navigation();

        // re-init on media query change
        if ((typeof window.matchMedia) != 'undefined') {
            var mq = window.matchMedia('(min-width: 641px) and (max-width: 1024px)');
            if ((typeof mq.addListener) != 'undefined') {
                mq.addListener(selfoss.events.entries);
            }
        }

        // window resize
        $(window).bind('resize', selfoss.events.resize);
        selfoss.events.resize();

        if (location.hash == '') {
            selfoss.events.initHash();
        }

        // hash change event
        window.onhashchange = selfoss.events.hashChange;

        // process current hash
        selfoss.events.processHash();
    },


    initHash: function() {
        var homePagePath = $('#config').data('homepage').split('/');
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

    processHash: function(hash) {
        hash = (typeof hash != 'undefined') ? hash : false;

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
                    $('#entry' + selfoss.events.entryId).click();
                }

                // if navigating using browser buttons and entry opened,
                // close opened entry.
                if (!selfoss.events.entryId
                    && selfoss.events.processHashChange
                    && $('#fullscreen-entry').is(':visible')) {
                    $('.entry.fullscreen').click();
                }
            } else {
                // if navigating using browser buttons and entry selected,
                // scroll to entry.
                if (selfoss.events.entryId
                    && selfoss.events.processHashChange) {
                    var entry = $('#entry' + selfoss.events.entryId);
                    if (entry) {
                        entry.get(0).scrollIntoView();
                    }
                }
            }

            done();
            return;
        }

        // load items
        if ($.inArray(selfoss.events.section,
            ['newest', 'unread', 'starred']) > -1) {
            selfoss.filter.type = selfoss.events.section;
            selfoss.filter.tag = '';
            selfoss.filter.source = '';
            if (selfoss.events.subsection) {
                selfoss.events.lastSubsection = selfoss.events.subsection;
                if (selfoss.events.subsection.substr(0, 4) == 'tag-') {
                    selfoss.filter.tag = selfoss.events.subsection.substr(4);
                } else if (selfoss.events.subsection.substr(0, 7) == 'source-') {
                    var sourceId = parseInt(selfoss.events.subsection.substr(7));
                    if (sourceId) {
                        selfoss.filter.source = sourceId;
                        selfoss.filter.sourcesNav = true;
                    }
                } else if (selfoss.events.subsection != 'all') {
                    selfoss.ui.showError('Invalid subsection: '
                                         + selfoss.events.subsection);
                    done();
                    return;
                }
            }

            selfoss.events.reloadSamePath = false;
            selfoss.filterReset();

            $('#nav-filter > li').removeClass('active');
            $('#nav-filter-' + selfoss.events.section).addClass('active');
            selfoss.dbOnline.reloadList();
        } else if (hash == 'sources') { // load sources
            if (selfoss.events.subsection) {
                selfoss.ui.showError('Invalid subsection: '
                                     + selfoss.events.subsection);
                done();
                return;
            }

            if (selfoss.activeAjaxReq !== null) {
                selfoss.activeAjaxReq.abort();
            }

            selfoss.ui.refreshStreamButtons();
            $('#content').addClass('loading').html('');
            selfoss.activeAjaxReq = $.ajax({
                url: $('base').attr('href') + 'sources',
                type: 'GET',
                success: function(data) {
                    $('#content').html(data);
                    selfoss.events.sources();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (textStatus == 'abort') {
                        return;
                    } else if (selfoss.hasSession() && errorThrown === 'Forbidden') {
                        selfoss.ui.showError('Your session has expired');
                        selfoss.logout();
                    } else if (errorThrown) {
                        selfoss.ui.showError('Load list error: ' +
                                             textStatus + ' ' + errorThrown);
                    }
                },
                complete: function() {
                    $('#content').removeClass('loading');
                }
            });
        } else if (hash == 'login') {
            selfoss.ui.showLogin();
        } else {
            selfoss.ui.showError('Invalid section: ' + selfoss.events.section);
        }
        done();
    },


    setHash: function(section, subsection, entryId) {
        section = (typeof section !== 'undefined') ? section : 'same';
        subsection = (typeof subsection !== 'undefined') ? subsection : 'same';
        entryId = (typeof entryId !== 'undefined') ? entryId : false;

        if (section == 'same') {
            section = selfoss.events.section;
        }
        var newHash = [section];

        if (subsection == 'same') {
            subsection = selfoss.events.lastSubsection;
        }
        if (subsection) {
            newHash.push(subsection.replace('%', '%25'));
        }

        if (entryId) {
            newHash.push(entryId);
        }
        selfoss.events.processHash('#' + newHash.join('/'));
    },


    /**
     * set automatically the height of the tags and set scrollbar for div scrolling
     */
    resize: function() {
        // only set height if smartphone is false
        if (selfoss.isSmartphone() == false) {
            var start = $('#nav-tags-wrapper').position().top;
            var windowHeight = $(window).height();
            $('#nav-tags-wrapper').height(windowHeight - start - 100);
            $('#nav').show();
            if (selfoss.events.scrollBarState === selfoss.events.SCROLLBAR_UNLOADED) {
                selfoss.events.scrollBarState = selfoss.events.SCROLLBAR_LOADING;
                $('#nav-tags-wrapper').mCustomScrollbar({
                    advanced: {
                        updateOnContentResize: true
                    }
                });
                selfoss.events.scrollBarState = selfoss.events.SCROLLBAR_LOADED;
            }
        } else {
            $('#nav-tags-wrapper').height('auto');
            if (selfoss.events.scrollBarState === selfoss.events.SCROLLBAR_LOADED) {
                selfoss.events.scrollBarState = selfoss.events.SCROLLBAR_LOADING;
                $('#nav-tags-wrapper').mCustomScrollbar('destroy');
                selfoss.events.scrollBarState = selfoss.events.SCROLLBAR_UNLOADED;
            }
        }
    }
};
