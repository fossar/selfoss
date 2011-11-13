var selfoss = {

    init: function() {
        $(document).ready(function() {
			$("#screenshots a[rel=screenshots]").fancybox();
            
            $('#header-navigation li').click(function() {
                $.scrollTo('#'+$(this).attr('class'), { 'duration': 1000 });
            });
            
            $('#header-donate').mouseenter(function() {
                $('#header-donate-tooltipp').fadeIn();
            });
            
            $('#header-donate').mouseleave(function() {
                $('#header-donate-tooltipp').fadeOut();
            });
        });
    }
    
}