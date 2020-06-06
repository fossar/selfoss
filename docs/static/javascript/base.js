$(document).ready(function() {
    $("#screenshots a[data-fancybox]").fancybox({
        toolbar: false,
        caption: function() {
            return $(this).attr('title');
        },
    });

    $('#header-donate').mouseenter(function() {
        $('#header-donate-tooltipp').fadeIn();
    });

    $('#header-donate').mouseleave(function() {
        $('#header-donate-tooltipp').fadeOut();
    });
});
