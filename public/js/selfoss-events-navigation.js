/**
 * initialize navigation events
 */
selfoss.events.navigation = function() {

    // init colorpicker
    $(".color").spectrum({
        showPaletteOnly: true,
        color: 'blanchedalmond',
        palette: [
            ['#ffccc9', '#ffce93', '#fffc9e', '#ffffc7', '#9aff99', '#96fffb', '#cdffff' , '#cbcefb', '#fffe65', '#cfcfcf', '#fd6864', '#fe996b','#fcff2f', '#67fd9a', '#38fff8', '#68fdff', '#9698ed', '#c0c0c0', '#fe0000', '#f8a102', '#ffcc67', '#f8ff00', '#34ff34', '#68cbd0', '#34cdf9', '#6665cd', '#9b9b9b', '#cb0000', '#f56b00', '#ffcb2f', '#ffc702', '#32cb00', '#00d2cb', '#3166ff', '#6434fc', '#656565', '#9a0000', '#ce6301', '#cd9934', '#999903', '#009901', '#329a9d', '#3531ff', '#6200c9', '#343434', '#680100', '#963400', '#986536', '#646809', '#036400', '#34696d', '#00009b', '#303498', '#000000', '#330001', '#643403', '#663234', '#343300', '#013300', '#003532', '#010066', '#340096']
        ],
        change: function(color) {
            $(this).css('backgroundColor', color.toHexString());
            
            $.ajax({
                url: $('base').attr('href') + 'tags/color',
                type: 'POST',
                data: {
                    tag: $(this).parent().find('.tag').html(),
                    color: color.toHexString()
                },
                success: function() {
                    selfoss.reloadList();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    selfoss.showError('Can not save new color: '+errorThrown);
                }
            });
            
        }
    });
    
    // filter
    $('#nav-filter > li').unbind('click').click(function () {
        if($(this).hasClass('nav-filter-newest'))
            selfoss.filter.type='newest';
        else if($(this).hasClass('nav-filter-unread'))
            selfoss.filter.type='unread';
        else if($(this).hasClass('nav-filter-starred'))
            selfoss.filter.type='starred';
        
        $('#nav-filter > li').removeClass('active');
        $(this).addClass('active');
        
        selfoss.filter.offset = 0;
        selfoss.reloadList();
        
        if(selfoss.isSmartphone())
            $('#nav-mobile-settings').click();
    });
    
    // tag
    $('#nav-tags > li').unbind('click').click(function () {
        $('#nav-tags > li').removeClass('active');
        $('#nav-sources > li').removeClass('active');
        $(this).addClass('active');
        
        selfoss.filter.source = '';
        selfoss.filter.tag = '';
        if($(this).hasClass('nav-tags-all')==false)
            selfoss.filter.tag = $(this).find('span').html();
            
        selfoss.filter.offset = 0;
        selfoss.reloadList();
        
        if(selfoss.isSmartphone())
            $('#nav-mobile-settings').click();
    });
    
    // hide/show tags
    $('#nav-tags-title').unbind('click').click(function () {
        $('#nav-tags').slideToggle("slow");
        $('#nav-tags-title').toggleClass("nav-tags-collapsed nav-tags-expanded");
    });
    
    // source
    $('#nav-sources > li').unbind('click').click(function () {
        $('#nav-tags > li').removeClass('active');
        $('#nav-sources > li').removeClass('active');
        $(this).addClass('active');
        
        selfoss.filter.tag = '';
        selfoss.filter.source = $(this).attr('id').substr(6);
            
        selfoss.filter.offset = 0;
        selfoss.reloadList();
        
        if(selfoss.isSmartphone())
            $('#nav-mobile-settings').click();
    });
    
    // hide/show sources
    $('#nav-sources-title').unbind('click').click(function () {
        $('#nav-sources').slideToggle("slow");
        $('#nav-sources-title').toggleClass("nav-sources-collapsed nav-sources-expanded");
    });
    
    // show hide navigation for mobile version
    $('#nav-mobile-settings').unbind('click').click(function () {
        var nav = $('#nav');
        
        // show
        if(nav.is(':visible')==false) {
            nav.slideDown(400, function() {
                location.hash = "nav";
                $(window).scrollTop(0);
            });
            
        // hide
        } else {
            nav.slideUp(400, function() {
                if(location.hash=="#nav") {
                    location.hash = "";
                }
                $(window).scrollTop(0);
            });
        }
        
    });

    // updates sources
    $('#nav-refresh').unbind('click').click(function () {
        // show loading
        var content = $('#content');
        var articleList = content.html();
        $('#content').addClass('loading').html("");
          
        $.ajax({
            url: $('base').attr('href') + 'update',
            type: 'GET',
            dataType: 'text',
            data: {},
            success: function(response) {
                // hide nav on smartphone
                if(selfoss.isSmartphone())
                    $('#nav-mobile-settings').click();
                    
                // refresh list
                 selfoss.reloadList();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                content.html(articleList);
                $('#content').removeClass('loading');
                alert('Can not refresh sources: ' + errorThrown);
            }
        });
    });
    
    // login
    $('#nav-login').unbind('click').click(function () {
        window.location.href = $('base').attr('href')+"?login=1";
    });
    
    // only loggedin users
    if($('body').hasClass('loggedin')==true) {
        // mark as read
        $('#nav-mark').unbind('click').click(function () {
            var ids = new Array();
            $('.entry.unread').each(function(index, item) {
                ids.push( $(item).attr('id').substr(5) );
            });

            if(ids.length === 0){
                return;
            }
            
            // show loading
            var content = $('#content');
            var articleList = content.html();
            $('#content').addClass('loading').html("");
            
            $.ajax({
                url: $('base').attr('href') + 'mark',
                type: 'POST',
                dataType: 'json',
                data: {
                    ids: ids
                },
                success: function(response) {
                    $('.entry').removeClass('unread');
                    
                    // update unread stats
                    var unreadstats = parseInt($('.nav-filter-unread span').html()) - ids.length;
                    $('.nav-filter-unread span').html(unreadstats);
                    $('.nav-filter-unread span').removeClass('unread');
                    if(unreadstats>0)
                        $('.nav-filter-unread span').addClass('unread');
                    
                    // hide nav on smartphone
                    if(selfoss.isSmartphone())
                        $('#nav-mobile-settings').click();
                    
                    // refresh list
                    selfoss.reloadList();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    content.html(articleList);
                    $('#content').removeClass('loading');
                    selfoss.showError('Can not mark all visible item:'+errorThrown);
                }
            });
        });
        
        // show sources
        $('#nav-settings').unbind('click').click(function () {
            location.hash = "sources";
            
            if(selfoss.isSmartphone())
                $('#nav-mobile-settings').click();
        });
        
        
        // logout
        $('#nav-logout').unbind('click').click(function () {
            window.location.href = $('base').attr('href')+"?logout=1";
        });
    }
};
