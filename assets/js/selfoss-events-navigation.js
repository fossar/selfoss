import selfoss from './selfoss-base';

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
};
