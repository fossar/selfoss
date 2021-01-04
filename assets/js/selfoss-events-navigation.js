import selfoss from './selfoss-base';
import * as sourceRequests from './requests/sources';

/**
 * initialize navigation events
 */
selfoss.events.navigation = function() {
    // hide/show filters
    $('#nav-filter-title').unbind('click').click(function() {
        $('#nav-filter').slideToggle('slow');
        $('#nav-filter-title').toggleClass('nav-filter-collapsed nav-filter-expanded');
        $('#nav-filter-title').find('svg').toggleClass('fa-caret-down fa-caret-right');
        $('#nav-filter-title').attr('aria-expanded', function(i, attr) {
            return attr == 'true' ? 'false' : 'true';
        });
    });

    // hide/show tags
    $('#nav-tags-title').unbind('click').click(function() {
        $('#nav-tags').slideToggle('slow');
        $('#nav-tags-title').toggleClass('nav-tags-collapsed nav-tags-expanded');
        $('#nav-tags-title').find('svg').toggleClass('fa-caret-down fa-caret-right');
        $('#nav-tags-title').attr('aria-expanded', function(i, attr) {
            return attr == 'true' ? 'false' : 'true';
        });
    });

    // hide/show sources
    $('#nav-sources-title').unbind('click').click(function() {
        if (!selfoss.db.online) {
            return;
        }

        var toggle = function() {
            $('#nav-sources').slideToggle('slow');
            $('#nav-sources-title').toggleClass('nav-sources-collapsed nav-sources-expanded');
            $('#nav-sources-title').find('svg').toggleClass('fa-caret-down fa-caret-right');
            $('#nav-sources-title').attr('aria-expanded', function(i, attr) {
                return attr == 'true' ? 'false' : 'true';
            });
        };

        selfoss.filter.update({ sourcesNav: $('#nav-sources-title').hasClass('nav-sources-collapsed') });
        if (selfoss.filter.sourcesNav && !selfoss.sourcesNavLoaded) {
            sourceRequests.getStats().then((data) => {
                selfoss.sources.update(data);
            }).catch(function(error) {
                selfoss.ui.showError(selfoss.ui._('error_loading_stats') + ' ' + error.message);
            });
        } else {
            toggle();
        }
    });

    // emulate clicking when using keyboard
    $('.entry-title-link').unbind('keypress').keypress(function(e) {
        if (e.keyCode === 13) { // ENTER key
            $(this).click();
        }
    });

    // show hide navigation for mobile version
    $('#nav-mobile-settings').unbind('click').click(function() {
        var nav = $('#nav');

        // show
        if (nav.is(':visible') == false) {
            nav.slideDown(400, function() {
                $(window).scrollTop(0);
            });

        // hide
        } else {
            nav.slideUp(400, function() {
                $(window).scrollTop(0);
            });
        }

    });

    // updates sources
    $('#nav-refresh').unbind('click').click(function() {
        if (!selfoss.db.online) {
            return;
        }

        $('#nav-refresh').find('svg').addClass('fa-spin');

        sourceRequests.refreshAll().then(() => {
            // hide nav on smartphone
            if (selfoss.isSmartphone()) {
                $('#nav-mobile-settings').click();
            }

            // probe stats and prompt reload to the user
            selfoss.dbOnline.sync().then(function() {
                if (selfoss.unreadItemsCount.value > 0) {
                    selfoss.ui.showMessage(selfoss.ui._('sources_refreshed'), [
                        {
                            label: selfoss.ui._('reload_list'),
                            callback() {
                                $('#nav-filter-unread').click();
                            }
                        }
                    ]);
                }
            });
        }).catch((error) => {
            selfoss.ui.showError(selfoss.ui._('error_refreshing_source') + ' ' + error.message);
        }).finally(() => {
            $('#nav-refresh').find('svg').removeClass('fa-spin');
        });
    });

    // login
    $('#nav-login').unbind('click').click(function() {
        selfoss.events.setHash('login', false);
    });

    // only loggedin users
    if ($('body').hasClass('loggedin') == true) {
        $('#nav-mark').unbind('click').click(selfoss.markVisibleRead);

        // show sources
        $('#nav-settings').unbind('click').click(function() {
            if (!selfoss.db.online) {
                return;
            }

            selfoss.events.setHash('sources', false);

            if (selfoss.isSmartphone()) {
                $('#nav-mobile-settings').click();
            }
        });


        // logout
        $('#nav-logout').unbind('click').click(function() {
            if (!selfoss.db.online) {
                return;
            }

            selfoss.db.clear();
            selfoss.logout();
        });
    }
};
