$(document).ready(function() {
    $("#screenshots a[data-fancybox]").fancybox({
        toolbar: false,
        caption: function() {
            return $(this).attr('title');
        },
    });

    $('.intro-donate').mouseenter(function() {
        $('.intro-donate-tooltip').fadeIn();
    });

    $('.intro-donate').mouseleave(function() {
        $('.intro-donate-tooltip').fadeOut();
    });
});
