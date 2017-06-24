$(document).ready(function() {
    $("#screenshots a[data-fancybox]").fancybox({
        toolbar: false,
        caption: function() {
            return $(this).attr('title');
        },
    });

    $('#header-navigation li:not(.forum)').click(function() {
        var target = $('#'+$(this).attr('class'));
        if(target.length==0)
            return;
        $('html,body').animate({
            scrollTop: target.offset().top
        }, 1000);
    });
    
    $('#header-donate').mouseenter(function() {
        $('#header-donate-tooltipp').fadeIn();
    });
    
    $('#header-donate').mouseleave(function() {
        $('#header-donate-tooltipp').fadeOut();
    });
});
