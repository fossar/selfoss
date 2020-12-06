import selfoss from './selfoss-base';
import * as ajax from './helpers/ajax';

/**
 * initialize navigation events
 */
selfoss.events.navigation = function() {

    // init colorpicker
    $('.color').spectrum({
        showPaletteOnly: true,
        color: 'blanchedalmond',
        palette: [
            ['#ffccc9', '#ffce93', '#fffc9e', '#ffffc7', '#9aff99', '#96fffb', '#cdffff', '#cbcefb', '#fffe65', '#cfcfcf', '#fd6864', '#fe996b', '#fcff2f', '#67fd9a', '#38fff8', '#68fdff', '#9698ed', '#c0c0c0', '#fe0000', '#f8a102', '#ffcc67', '#f8ff00', '#34ff34', '#68cbd0', '#34cdf9', '#6665cd', '#9b9b9b', '#cb0000', '#f56b00', '#ffcb2f', '#ffc702', '#32cb00', '#00d2cb', '#3166ff', '#6434fc', '#656565', '#9a0000', '#ce6301', '#cd9934', '#999903', '#009901', '#329a9d', '#3531ff', '#6200c9', '#343434', '#680100', '#963400', '#986536', '#646809', '#036400', '#34696d', '#00009b', '#303498', '#000000', '#330001', '#643403', '#663234', '#343300', '#013300', '#003532', '#010066', '#340096']
        ],
        change: function(color) {
            $(this).css('backgroundColor', color.toHexString());

            ajax.post('tags/color', {
                body: ajax.makeSearchParams({
                    tag: $(this).parent().find('.tag').html(),
                    color: color.toHexString()
                })
            }).promise.then(() => {
                selfoss.ui.beforeReloadList();
                selfoss.dbOnline.reloadList();
                selfoss.ui.afterReloadList();
            }).catch((error) => {
                selfoss.ui.showError(selfoss.ui._('error_saving_color') + ' ' + error.message);
            });

        }
    });

    // filter
    $('#nav-filter > li > a').unbind('click').click(function(e) {
        e.preventDefault();

        if ($(this).hasClass('nav-filter-newest')) {
            selfoss.filter.type = 'newest';
        } else if ($(this).hasClass('nav-filter-unread')) {
            selfoss.filter.type = 'unread';
        } else if ($(this).hasClass('nav-filter-starred')) {
            selfoss.filter.type = 'starred';
        }

        selfoss.events.reloadSamePath = true;
        if (selfoss.events.lastSubsection == null) {
            selfoss.events.lastSubsection = 'all';
        }
        selfoss.events.setHash(selfoss.filter.type, 'same');

        $('#nav-filter > li > a').removeClass('active');
        $(this).addClass('active');

        selfoss.ui.hideMobileNav();
    });

    // hide/show filters
    $('#nav-filter-title').unbind('click').click(function() {
        $('#nav-filter').slideToggle('slow');
        $('#nav-filter-title').toggleClass('nav-filter-collapsed nav-filter-expanded');
        $('#nav-filter-title').find('svg').toggleClass('fa-caret-down fa-caret-right');
        $('#nav-filter-title').attr('aria-expanded', function(i, attr) {
            return attr == 'true' ? 'false' : 'true';
        });
    });

    // tag
    $('#nav-tags > li > a').unbind('click').click(function(e) {
        e.preventDefault();

        if (!selfoss.db.online) {
            return;
        }

        $('#nav-tags > li > a').removeClass('active');
        $('#nav-sources > li > a').removeClass('active');
        $(this).addClass('active');

        if ($(this).hasClass('nav-tags-all') == false) {
            selfoss.events.setHash(selfoss.filter.type,
                'tag-' + $(this).find('span').html());
        } else {
            selfoss.events.setHash(selfoss.filter.type, 'all');
        }

        selfoss.ui.hideMobileNav();
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

    // source
    $('#nav-sources > li > a').unbind('click').click(function(e) {
        e.preventDefault();

        if (!selfoss.db.online) {
            return;
        }

        $('#nav-tags > li > a').removeClass('active');
        $('#nav-sources > li > a').removeClass('active');
        $(this).addClass('active');

        selfoss.events.setHash(selfoss.filter.type,
            'source-' + $(this).attr('data-source-id'));

        selfoss.ui.hideMobileNav();
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

        selfoss.filter.sourcesNav = $('#nav-sources-title').hasClass('nav-sources-collapsed');
        if (selfoss.filter.sourcesNav && !selfoss.sourcesNavLoaded) {
            ajax.get('sources/stats').promise.then(response => response.json()).then((data) => {
                selfoss.refreshSources(data);
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

        ajax.get('update', {
            timeout: 0
        }).promise.then(() => {
            // hide nav on smartphone
            if (selfoss.isSmartphone()) {
                $('#nav-mobile-settings').click();
            }

            // probe stats and prompt reload to the user
            selfoss.dbOnline.sync().then(function() {
                if ($('.unread-count').hasClass('unread')) {
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
