import selfoss from './selfoss-base';
import * as sourceRequests from './requests/sources';

/**
 * initialize navigation events
 */
selfoss.events.navigation = function() {
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
    if (selfoss.loggedin.value) {
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
